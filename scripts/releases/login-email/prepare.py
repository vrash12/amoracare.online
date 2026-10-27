"""Build a narrow patch against the captured single-guard production release.

Usage: python prepare.py <release-directory> <remote-release-directory> <source-commit>
Original live files must be saved with their relative paths under original/.
Never includes environment files, vendor files, or unrelated local features.
"""
from pathlib import Path
import hashlib
import json
import shutil
import sys

release = Path(sys.argv[1]).resolve()
repo = Path(__file__).resolve().parents[3]
manifest = {}


def replace(text, old, new, count=1):
    if text.count(old) != count:
        raise RuntimeError(f"Unexpected source for replacement: {old[:80]}")
    return text.replace(old, new)


def save(path, text):
    original = release / 'original' / path
    target = release / 'patch' / path
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(text, encoding='utf-8', newline='\n')
    manifest[path] = {
        'before': hashlib.sha256(original.read_bytes()).hexdigest() if original.exists() else None,
        'after': hashlib.sha256(target.read_bytes()).hexdigest(),
    }


def read(path):
    return (release / 'original' / path).read_text(encoding='utf-8')


migration = 'database/migrations/2026_10_28_000001_release_deleted_user_emails.php'
for path in [migration, 'app/Models/Concerns/ReleasesDeletedEmail.php', 'app/Rules/AvailableAccountEmail.php']:
    save(path, (repo / path).read_text(encoding='utf-8'))

path = 'app/Models/User.php'
text = replace(read(path), 'use App\\Support\\PersonName;', 'use App\\Support\\PersonName;\nuse App\\Models\\Concerns\\ReleasesDeletedEmail;')
text = replace(text, 'use Notifiable, SoftDeletes;', 'use Notifiable, SoftDeletes, ReleasesDeletedEmail {\n        ReleasesDeletedEmail::runSoftDelete insteadof SoftDeletes;\n    }')
text = replace(text, "        'remember_token',", "        'remember_token',\n        'deleted_email',")
save(path, text)

for name in ['UserController', 'ParentController']:
    path = f'app/Http/Controllers/Admin/{name}.php'
    text = replace(read(path), 'use App\\Models\\User;', 'use App\\Models\\User;\nuse App\\Rules\\AvailableAccountEmail;')
    normalizer = "$request->merge(['email' => strtolower(trim((string) $request->input('email')))]);"
    if name == 'UserController':
        text = replace(text, '$nameParts = $this->validateName($request);', normalizer+'\n        $nameParts = $this->validateName($request);', 2)
        text = replace(text, "'unique:users,email'", 'new AvailableAccountEmail')
        text = replace(text, "Rule::unique('users', 'email')->ignore($user->id)", 'new AvailableAccountEmail($user->id)')
    else:
        text = replace(text, '$isUpdate = $parent !== null;', normalizer+'\n        $isUpdate = $parent !== null;')
        text = replace(text, "Rule::unique('users', 'email')->ignore($parent?->id)", 'new AvailableAccountEmail($parent?->id)')
    save(path, text)

path = 'app/Http/Controllers/Auth/LoginController.php'
text = replace(read(path), '$validated = $request->validate([', "$request->merge(['email' => strtolower(trim((string) $request->input('email')))]);\n        $validated = $request->validate([")
text = replace(text, "if (!$user->role)", "if (!$user->role || !in_array($user->role->slug, ['admin', 'prospective_parent', 'external_reviewer'], true))")
text = replace(text, '$request->session()->regenerate();', "$request->session()->regenerate();\n        $request->session()->put('account_password_hash.web.'.$user->getKey(), $user->password);")
save(path, text)

path = 'routes/web.php'
text = replace(read(path), "Route::post('/login', [LoginController::class, 'login'])\n        ->name('login.store');", "Route::post('/login', [LoginController::class, 'login'])\n        ->middleware('throttle:5,1')\n        ->name('login.store');")
save(path, text)

path = 'resources/views/admin/users/create.blade.php'
text = replace(read(path), 'The user will receive a new six-digit email OTP at every sign-in.', "The user signs in with this email address and password. A deleted account's email can be reused.")
save(path, text)

path = 'resources/views/admin/parents/_form.blade.php'
text = replace(read(path), "            @error('email')", "            <p class=\"muted\">Sign in with this email address and password. A deleted account's email can be reused.</p>\n            @error('email')")
save(path, text)

(release / 'patch/manifest.json').write_text(json.dumps(manifest, indent=2), encoding='utf-8')
(release / 'release-config.json').write_text(json.dumps({
    'root': '/home/u330835917/domains/amoracare.online',
    'release': sys.argv[2], 'source_commit': sys.argv[3], 'migration': migration,
}, indent=2), encoding='utf-8')
for name in ['verify.php', 'snapshot.php', 'deploy.php']:
    shutil.copyfile(Path(__file__).parent / name, release / name)
print(json.dumps({'files': len(manifest), 'release': sys.argv[2]}))
