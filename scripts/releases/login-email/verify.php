<?php
declare(strict_types=1);

// Verify the production-compatible patch against isolated in-memory data.
$config = json_decode(file_get_contents(__DIR__.'/release-config.json'), true, 512, JSON_THROW_ON_ERROR);
$root = $config['root'];
$mode = $argv[1] ?? 'stage';
$source = $mode === 'stage' ? __DIR__.'/patch' : $root;
$manifest = json_decode(file_get_contents(__DIR__.'/patch/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
foreach ($manifest as $file => $hashes) {
    if (hash_file('sha256', "$source/$file") !== $hashes['after']) throw new RuntimeException("Unexpected source: $file");
}
$loader = require "$root/vendor/autoload.php";
foreach ([
    App\Models\User::class => 'app/Models/User.php',
    App\Models\Concerns\ReleasesDeletedEmail::class => 'app/Models/Concerns/ReleasesDeletedEmail.php',
    App\Rules\AvailableAccountEmail::class => 'app/Rules/AvailableAccountEmail.php',
    App\Http\Controllers\Auth\LoginController::class => 'app/Http/Controllers/Auth/LoginController.php',
    App\Http\Controllers\Admin\UserController::class => 'app/Http/Controllers/Admin/UserController.php',
    App\Http\Controllers\Admin\ParentController::class => 'app/Http/Controllers/Admin/ParentController.php',
] as $class => $file) $loader->addClassMap([$class => "$source/$file"]);
$app = require "$root/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array', 'cache.default' => 'array',
    'database.default' => 'release_check', 'database.connections.release_check' => [
        'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
    ]]);
$db = Illuminate\Support\Facades\DB::connection();
$schema = $db->getSchemaBuilder();
Illuminate\Support\Facades\Schema::swap($schema);
$assertions = 0;
$check = function (bool $ok, string $message) use (&$assertions): void {
    if (!$ok) throw new RuntimeException($message);
    $assertions++;
};
$check($db->getDriverName() === 'sqlite' && $db->getConfig('database') === ':memory:', 'Must use isolated SQLite');
foreach ([App\Models\User::class, App\Models\Role::class] as $class) {
    $check((new $class)->getConnectionName() === null, 'Unexpected fixed model connection');
}
Illuminate\Support\Facades\Http::preventStrayRequests();
Illuminate\Support\Facades\Event::listen(Illuminate\Mail\Events\MessageSending::class, function () { throw new RuntimeException('Unexpected email during password login'); });
Illuminate\Support\Facades\Event::listen(Illuminate\Notifications\Events\NotificationSending::class, function () { throw new RuntimeException('Unexpected notification during password login'); });
$schema->create('roles', function ($t) { $t->id(); $t->string('slug'); $t->string('name'); $t->timestamps(); });
$schema->create('users', function ($t) {
    $t->id(); $t->unsignedBigInteger('role_id'); $t->string('name'); $t->string('email')->unique();
    $t->string('password'); $t->string('status')->default('active'); $t->string('phone_number')->nullable();
    $t->timestamp('email_verified_at')->nullable(); $t->timestamp('last_login_at')->nullable();
    $t->boolean('must_change_password')->default(false); $t->string('terms_accepted_version')->nullable();
    $t->timestamp('terms_accepted_at')->nullable(); $t->rememberToken(); $t->timestamps(); $t->softDeletes();
});
$router = app('router');
$router->setRoutes(new Illuminate\Routing\RouteCollection);
require "$source/routes/web.php";
$router->getRoutes()->refreshNameLookups();
$check(in_array('throttle:5,1', $router->getRoutes()->getByName('login.store')->gatherMiddleware()), 'Login throttle missing');
session()->start();
$request = function (array $data): Illuminate\Http\Request {
    $request = Illuminate\Http\Request::create('https://amoracare.online/login', 'POST', $data);
    $request->setLaravelSession(session()->driver());
    return $request;
};
$user = function (string $email, string $slug = 'prospective_parent'): App\Models\User {
    $role = App\Models\Role::firstOrCreate(['slug' => $slug], ['name' => $slug]);
    return App\Models\User::create(['role_id' => $role->id, 'name' => 'Release Test', 'email' => $email,
        'password' => 'IsolatedTest123!', 'status' => 'active']);
};
$legacy = $user('legacy@example.test');
$db->table('users')->where('id', $legacy->id)->update(['deleted_at' => now()]);
$migration = require "$source/{$config['migration']}";
$migration->up(); $migration->up();
$check(App\Models\User::withTrashed()->find($legacy->id)->deleted_email === 'legacy@example.test', 'Legacy email was not released');
$available = fn (string $email) => validator(['email' => $email], ['email' => new App\Rules\AvailableAccountEmail])->passes();
$check($available('legacy@example.test'), 'Legacy email cannot be reused');
$admin = $user('admin@example.test', 'admin');
$parent = $user('parent@example.test');
$reviewer = $user('reviewer@example.test', 'external_reviewer');
$login = new App\Http\Controllers\Auth\LoginController;
foreach ([$admin, $parent, $reviewer] as $account) {
    Illuminate\Support\Facades\Auth::forgetGuards();
    $response = $login->login($request(['email' => ' '.strtoupper($account->email).' ', 'password' => 'IsolatedTest123!']));
    $check(Illuminate\Support\Facades\Auth::id() === $account->id, 'Password login failed');
    $check($response->getTargetUrl() === route('dashboard'), 'Login unexpectedly requested OTP');
    $check($account->fresh()->email_verified_at === null, 'Login falsely verified email');
    $check($account->fresh()->last_login_at !== null, 'Login not recorded');
}
foreach (['pending', 'inactive'] as $status) {
    $parent->update(['status' => $status]);
    Illuminate\Support\Facades\Auth::forgetGuards(); session()->flush();
    try { $login->login($request(['email' => $parent->email, 'password' => 'IsolatedTest123!'])); throw new RuntimeException('Blocked account logged in'); }
    catch (Illuminate\Validation\ValidationException $e) { $check(isset($e->errors()['email']), 'Missing status error'); }
    $check(!$available(strtoupper($parent->email)), 'Existing email accepted');
}
$parent->update(['status' => 'active']);
Illuminate\Support\Facades\Auth::login($admin);
$controller = new App\Http\Controllers\Admin\UserController;
$controller->destroy($parent);
$check(App\Models\User::find($parent->id) === null, 'Deleted account visible');
$archived = App\Models\User::withTrashed()->findOrFail($parent->id);
$check($archived->deleted_email === 'parent@example.test' && $archived->remember_token === null, 'Deleted history missing');
$payload = ['role_id' => $parent->role_id, 'first_name' => 'New', 'last_name' => 'Parent', 'email' => ' PARENT@EXAMPLE.TEST ',
    'password' => 'Replacement123!', 'password_confirmation' => 'Replacement123!', 'status' => 'active'];
$controller->store($request($payload));
$replacement = App\Models\User::where('email', 'parent@example.test')->firstOrFail();
$check($replacement->id !== $parent->id && $replacement->must_change_password, 'New account did not receive fresh identity/setup');
try { $controller->store($request($payload)); throw new RuntimeException('Duplicate accepted'); }
catch (Illuminate\Validation\ValidationException $e) { $check(isset($e->errors()['email']), 'Missing duplicate error'); }
try { $archived->restore(); throw new RuntimeException('Restore stole reused email'); }
catch (Illuminate\Validation\ValidationException $e) { $check(isset($e->errors()['email']), 'Missing restore error'); }
$validateParent = new ReflectionMethod(App\Http\Controllers\Admin\ParentController::class, 'validateParent');
try { $validateParent->invoke(new App\Http\Controllers\Admin\ParentController, $request(['email' => ' PARENT@EXAMPLE.TEST '])); throw new RuntimeException('Invalid parent accepted'); }
catch (Illuminate\Validation\ValidationException $e) { $check(isset($e->errors()['email']), 'Parent form duplicate not rejected'); }
$check(!str_contains(file_get_contents("$source/resources/views/admin/users/create.blade.php"), 'OTP at every sign-in'), 'Old OTP text remains');
echo json_encode(['result' => 'passed', 'mode' => $mode, 'assertions' => $assertions, 'database' => 'isolated SQLite memory'], JSON_PRETTY_PRINT).PHP_EOL;
