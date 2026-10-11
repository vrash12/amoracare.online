"""Build the account-workflow patch for Hostinger's legacy web-guard install.

Keeps the deployed homepage, FAQ, login, Composer dependencies, and environment.
Only two additive user migrations are included. Run them by explicit --path;
the imported production schema has no baseline migration history.
"""
import hashlib
import io
import json
import re
import subprocess
import sys
import tarfile
from pathlib import Path

repo = Path(__file__).resolve().parents[1]
output = Path(sys.argv[1]).resolve()
output.mkdir(parents=True, exist_ok=True)
patch = output / "patch"
patch.mkdir(exist_ok=True)

files = """app/Http/Controllers/Admin/AdoptionCaseController.php
app/Http/Controllers/Admin/UserController.php
app/Http/Controllers/Auth/AccountController.php
app/Http/Controllers/Parent/ParentDocumentController.php
app/Http/Controllers/Reviewer/AuthorizedCaseController.php
app/Http/Controllers/Reviewer/ReviewerDashboardController.php
app/Http/Middleware/RoleMiddleware.php
app/Models/User.php
app/Support/PersonName.php
config/auth.php
config/legal.php
database/migrations/2026_09_26_000000_add_account_setup_to_users_table.php
database/migrations/2026_09_26_000001_add_name_parts_to_users_table.php
public/css/dashboard.css
public/css/legal.css
public/js/child-search.js
resources/views/account/security.blade.php
resources/views/account/terms.blade.php
resources/views/admin/adoption_cases/partials/form.blade.php
resources/views/admin/matching/index.blade.php
resources/views/admin/matching/show.blade.php
resources/views/admin/users/_name-fields.blade.php
resources/views/admin/users/create.blade.php
resources/views/admin/users/edit.blade.php
resources/views/components/document-status-icon.blade.php
resources/views/layouts/dashboard.blade.php
resources/views/layouts/legal.blade.php
resources/views/legal/privacy.blade.php
resources/views/legal/terms.blade.php
resources/views/pagination/amoracare.blade.php
resources/views/parent/application/index.blade.php
resources/views/parent/dashboard.blade.php
resources/views/parent/documents/index.blade.php
resources/views/partials/legal-links.blade.php
resources/views/partials/terms-content.blade.php
resources/views/reviewer/dashboard.blade.php
routes/web.php""".splitlines()

def baseline(path):
    return subprocess.check_output(["git", "show", f"09e4f87:{path}"], cwd=repo).decode()

def write(path, content):
    target = patch / path
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(content, encoding="utf-8", newline="\n")

for path in files:
    write(path, (repo / path).read_text(encoding="utf-8"))

# Keep the older user listing and its existing account-status policy.
path = "app/Http/Controllers/Admin/UserController.php"
content = (patch / path).read_text(encoding="utf-8")
old_index = baseline(path).split("    public function index(", 1)[1].split("    public function create(", 1)[0]
content = re.sub(r"    public function index\([\s\S]*?(?=    public function create\()", "    public function index(" + old_index, content, count=1)
content = content.replace("use App\\Services\\UserAccountStatusService;\n", "")
write(path, content)

# The production release only needs name and account-setup model additions.
path = "app/Models/User.php"
content = (patch / path).read_text(encoding="utf-8")
content = content.replace("use Carbon\\CarbonInterface;\n", "")
content = content.replace("        'activated_at',\n", "").replace("        'activated_at' => 'datetime',\n", "")
start = content.index("        static::creating(")
end = content.index("    protected function name(): Attribute", start)
content = content[:start] + "    }\n\n" + content[end:]
start = content.index("    public function inactivityReferenceAt(")
end = content.index("    public function role():", start)
content = content[:start] + content[end:]
start = content.index("    public function emailVerificationCode():")
content = content[:start] + "}\n"
write(path, content)

# Retain legacy login eligibility; add password-session and terms enforcement.
path = "app/Http/Middleware/RoleMiddleware.php"
content = (patch / path).read_text(encoding="utf-8").replace("use App\\Services\\UserAccountStatusService;\n", "")
content = re.sub(r"    public function __construct\([\s\S]*?\) \{\}\n\n", "", content, count=1)
content = content.replace("        $this->accountStatusService->deactivateIfDormant($user);\n\n", "")
start = content.index("        if (! $user->email_verified_at)")
end = content.index("        if (! in_array", start)
content = content[:start] + content[end:]
write(path, content)

# Add account routes to the actual legacy route layout, with its web guard.
content = baseline("routes/web.php")
content = content.replace("use App\\Http\\Controllers\\Auth\\LoginController;", "use App\\Http\\Controllers\\Auth\\LoginController;\nuse App\\Http\\Controllers\\Auth\\AccountController;")
new_routes = """
Route::view('/terms-and-conditions', 'legal.terms')->name('legal.terms');
Route::view('/privacy-notice', 'legal.privacy')->name('legal.privacy');

foreach (['admin' => 'admin', 'parent' => 'prospective_parent', 'reviewer' => 'external_reviewer'] as $prefix => $role) {
    Route::middleware(['auth:web', "role:{$role}"])
        ->prefix($prefix.'/account')->name($prefix.'.account.')->group(function () {
            Route::get('/security', [AccountController::class, 'security'])->name('security');
            Route::put('/password', [AccountController::class, 'updatePassword'])->middleware('throttle:6,1')->name('password');
            Route::get('/terms', [AccountController::class, 'terms'])->name('terms');
            Route::post('/terms', [AccountController::class, 'acceptTerms'])->middleware('throttle:10,1')->name('terms.accept');
        });
}
"""
content = content.replace("})->name('home');", "})->name('home');\n" + new_routes, 1)
write("routes/web.php", content)

content = baseline("config/auth.php")
content = content.replace("return [", "return [\n    'role_dashboards' => [\n        'admin' => 'admin.dashboard',\n        'prospective_parent' => 'parent.dashboard',\n        'external_reviewer' => 'reviewer.dashboard',\n    ],\n", 1)
write("config/auth.php", content)

# Avoid displaying claims for separate features absent in this installation.
for path in ["resources/views/admin/users/create.blade.php", "resources/views/admin/users/edit.blade.php"]:
    content = (patch / path).read_text(encoding="utf-8")
    content = re.sub(r'\s*<small style="color:#667085;">\s*(?:Active accounts are automatically|Reactivating an account restarts)[\s\S]*?</small>', "", content)
    content = content.replace('<small style="color:#667085;">Changing this address requires the user to verify the new email at the next sign-in.</small>', '')
    write(path, content)
path = "resources/views/layouts/dashboard.blade.php"
content = (patch / path).read_text(encoding="utf-8")
content = re.sub(r'\s*<a href="\{\{ route\(\'login\'\) \}\}" class="sidebar-link portal-login-link">[\s\S]*?</a>', "", content)
write(path, content)
path = "resources/views/legal/privacy.blade.php"
write(path, (patch / path).read_text(encoding="utf-8").replace("email verification, ", ""))
path = "resources/views/partials/terms-content.blade.php"
content = (patch / path).read_text(encoding="utf-8")
content = content.replace("You must complete the required email verification before registration or login is completed.", "You must complete the required account setup and accept the current Terms and Conditions before accessing your portal.")
content = content.replace("your password and one-time verification codes", "your password")
write(path, content)

manifest = {p: hashlib.sha256((patch / p).read_bytes()).hexdigest() for p in files}
(output / "manifest.json").write_text(json.dumps(manifest, indent=2) + "\n")
with tarfile.open(output / "account-release.tar.gz", "w:gz") as archive:
    for path in files:
        archive.add(patch / path, arcname=path)

# Disposable compatibility fixture; never includes the local or production .env.
fixture = output / "compatibility-app"
fixture.mkdir(exist_ok=True)
base_archive = subprocess.check_output(["git", "archive", "09e4f87"], cwd=repo)
with tarfile.open(fileobj=io.BytesIO(base_archive)) as archive:
    archive.extractall(fixture, filter="data")
for path in files:
    target = fixture / path
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_bytes((patch / path).read_bytes())
for path in ["phpunit.xml", "tests/TestCase.php", "tests/Feature/AccountSetupTest.php", "tests/Feature/AdoptionCaseDeletionTest.php", "tests/Feature/LegalPagesTest.php"]:
    target = fixture / path
    target.parent.mkdir(parents=True, exist_ok=True)
    content = (repo / path).read_text(encoding="utf-8")
    if path.endswith("AccountSetupTest.php"):
        content = content.replace('"auth:{$guard},web"', "'auth:web'")
        content = content.replace("$this->actingAs($user, $guard)", "$this->actingAs($user, 'web')")
        content = content.replace("$this->assertAuthenticatedAs($user, $guard)", "$this->assertAuthenticatedAs($user, 'web')")
        content = content.replace("$this->actingAs($this->user('admin', ['must_change_password' => true]), 'admin')", "$this->actingAs($this->user('admin', ['must_change_password' => true]), 'web')")
        start = content.index("    public function test_a_stale_password_session_")
        end = content.index("    public function test_admin_created_and_reset", start)
        content = content[:start] + """    public function test_a_stale_password_session_is_signed_out(): void
    {
        $parent = $this->user('prospective_parent', $this->acceptedTerms());
        $this->actingAs($parent, 'web');
        $this->withSession([
            'account_password_hash.web.'.$parent->id => Hash::make('OldPass123!'),
        ])->get(route('parent.account.security'))->assertRedirect(route('login'));
        $this->assertGuest('web');
    }

""" + content[end:]
    if path.endswith(("AccountSetupTest.php", "AdoptionCaseDeletionTest.php")):
        for guard in ["admin", "prospective_parent", "external_reviewer"]:
            content = content.replace(", '" + guard + "')", ", 'web')")
    target.write_text(content, encoding="utf-8", newline="\n")
for path in ["storage/framework/views", "storage/framework/cache", "storage/framework/sessions", "storage/logs", "bootstrap/cache"]:
    (fixture / path).mkdir(parents=True, exist_ok=True)
(fixture / ".env").write_text("APP_ENV=testing\nAPP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=\nAPP_DEBUG=false\nAPP_URL=http://localhost\n")
print(json.dumps({"patch_files":len(files), "archive":str(output / "account-release.tar.gz"), "fixture":str(fixture)}, indent=2))
