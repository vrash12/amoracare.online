<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MultiRoleSessionTest extends TestCase
{
    /** @var array<string, string> */
    private array $sentCodes = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'accounts.inactivity_days' => 60,
            'accounts.email_verification.driver' => 'brevo',
            'accounts.email_verification.expires_minutes' => 10,
            'accounts.email_verification.resend_cooldown_seconds' => 60,
            'accounts.email_verification.max_attempts' => 5,
            'services.brevo.api_url' => 'https://api.brevo.com/v3/smtp/email',
            'services.brevo.api_key' => 'test-api-key',
            'services.brevo.sender_email' => 'verified-sender@example.com',
            'services.brevo.sender_name' => 'AmoraCare',
        ]);

        Http::fake(function (ClientRequest $request) {
            preg_match('/letter-spacing:10px;">\s*(\d{6})\s*</', (string) $request['htmlContent'], $matches);
            $email = (string) $request['to'][0]['email'];
            $this->sentCodes[$email] = $matches[1] ?? '';

            return Http::response(['messageId' => 'test-message-id'], 201);
        });

        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone_number')->nullable();
            $table->string('status')->default(User::STATUS_PENDING);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');

        parent::tearDown();
    }

    public function test_admin_parent_and_reviewer_can_remain_signed_in_on_one_browser(): void
    {
        $admin = $this->createUser('admin', 'admin@example.com');
        $parent = $this->createUser('prospective_parent', 'parent@example.com');
        $reviewer = $this->createUser('external_reviewer', 'reviewer@example.com');

        $this->completeOtpLogin($admin, 'admin', 'admin.dashboard');
        $this->completeOtpLogin($parent, 'prospective_parent', 'parent.dashboard');
        $this->completeOtpLogin($reviewer, 'external_reviewer', 'reviewer.dashboard');

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertAuthenticatedAs($parent, 'prospective_parent');
        $this->assertAuthenticatedAs($reviewer, 'external_reviewer');

        $this->post(route('logout'), ['guard' => 'prospective_parent'])
            ->assertRedirect(route('login'));

        $this->assertGuest('prospective_parent');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertAuthenticatedAs($reviewer, 'external_reviewer');
    }

    private function completeOtpLogin(User $user, string $guard, string $dashboardRoute): void
    {
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('email.verification.notice'));

        $this->assertNotEmpty($this->sentCodes[$user->email] ?? null);

        $this->post(route('email.verification.verify'), [
            'code' => $this->sentCodes[$user->email],
        ])->assertRedirect(route($dashboardRoute));

        $this->assertAuthenticatedAs($user, $guard);
    }

    private function createUser(string $roleSlug, string $email): User
    {
        $roleId = Schema::getConnection()->table('roles')->insertGetId([
            'name' => ucwords(str_replace('_', ' ', $roleSlug)),
            'slug' => $roleSlug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->create([
            'role_id' => $roleId,
            'name' => $roleSlug.' user',
            'email' => $email,
            'password' => 'password',
            'status' => User::STATUS_ACTIVE,
        ]);

        $user->forceFill(['email_verified_at' => now()])->saveQuietly();

        return $user;
    }
}
