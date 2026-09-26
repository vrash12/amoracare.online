<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccountSetupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['legal.terms_version' => 'test-terms-v1']);

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone_number')->nullable();
            $table->string('password');
            $table->string('status')->default(User::STATUS_ACTIVE);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        (require database_path('migrations/2026_09_26_000000_add_account_setup_to_users_table.php'))->up();
        (require database_path('migrations/2026_09_26_000001_add_name_parts_to_users_table.php'))->up();

        // Exercise the real access middleware without unrelated dashboard tables.
        foreach (self::portals() as [$guard, $prefix]) {
            Route::middleware(['web', "auth:{$guard},web", "role:{$guard}"])
                ->get("/_test/{$prefix}/protected", fn () => response('Allowed'))
                ->name("{$prefix}.test.protected");
        }
        Route::getRoutes()->refreshNameLookups();
    }

    public static function portals(): array
    {
        return [
            'administrator' => ['admin', 'admin'],
            'parent' => ['prospective_parent', 'parent'],
            'reviewer' => ['external_reviewer', 'reviewer'],
        ];
    }

    #[DataProvider('portals')]
    public function test_temporary_password_then_terms_are_required_before_portal_access(string $guard, string $prefix): void
    {
        $user = $this->user($guard, ['must_change_password' => true]);
        $this->actingAs($user, $guard);
        $this->get(route("{$prefix}.test.protected"))
            ->assertRedirect(route("{$prefix}.account.security"));
        $this->get(route("{$prefix}.account.security"))
            ->assertOk()
            ->assertSee('temporary password')
            ->assertSee('Change password')
            ->assertSee('href="'.route("{$prefix}.account.security").'"', false)
            ->assertSee('Mobile portal navigation');

        $this->put(route("{$prefix}.account.password"), $this->passwordInput())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route("{$prefix}.account.terms"));
        $this->assertTrue(Hash::check('PersonalPass456!', $user->fresh()->password));
        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertNotNull($user->fresh()->password_changed_at);
        $this->get(route("{$prefix}.account.terms"))
            ->assertOk()
            ->assertSee('account-terms-box', false);
        $this->assertAuthenticatedAs($user, $guard);
        $this->get(route("{$prefix}.test.protected"))
            ->assertRedirect(route("{$prefix}.account.terms"));
        $this->getJson(route("{$prefix}.test.protected"))
            ->assertForbidden()
            ->assertJsonPath('redirect', route("{$prefix}.account.terms"));

        $this->post(route("{$prefix}.account.terms.accept"), [
            'terms_accepted' => '1',
            'terms_version' => config('legal.terms_version'),
        ])->assertSessionHasNoErrors()->assertRedirect(route("{$prefix}.dashboard"));
        $this->assertTrue($user->fresh()->hasAcceptedCurrentTerms());
        $this->get(route("{$prefix}.test.protected"))->assertOk()->assertSee('Allowed');
    }

    public function test_invalid_current_password_confirmation_and_password_reuse_are_rejected(): void
    {
        $user = $this->user('prospective_parent', ['must_change_password' => true]);
        $this->actingAs($user, 'prospective_parent');
        $this->put(route('parent.account.password'), $this->passwordInput(['current_password' => 'wrong']))
            ->assertSessionHasErrors('current_password');
        $this->put(route('parent.account.password'), $this->passwordInput(['password_confirmation' => 'mismatch']))
            ->assertSessionHasErrors('password');
        $this->put(route('parent.account.password'), $this->passwordInput([
            'password' => 'TemporaryPass123!',
            'password_confirmation' => 'TemporaryPass123!',
        ]))->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check('TemporaryPass123!', $user->fresh()->password));
    }

    public function test_missing_acceptance_and_outdated_terms_are_rejected(): void
    {
        $user = $this->user('prospective_parent');
        $this->actingAs($user, 'prospective_parent');
        $this->post(route('parent.account.terms.accept'), [
            'terms_version' => config('legal.terms_version'),
        ])->assertSessionHasErrors('terms_accepted');
        $this->post(route('parent.account.terms.accept'), [
            'terms_accepted' => '1',
            'terms_version' => 'outdated',
        ])->assertSessionHasErrors('terms_version');

        $this->assertNull($user->fresh()->terms_accepted_at);
        $this->get(route('parent.test.protected'))->assertRedirect(route('parent.account.terms'));
    }

    public function test_accepting_terms_cannot_bypass_a_temporary_password(): void
    {
        $user = $this->user('prospective_parent', ['must_change_password' => true]);
        $this->actingAs($user, 'prospective_parent');
        $this->post(route('parent.account.terms.accept'), [
            'terms_accepted' => '1',
            'terms_version' => config('legal.terms_version'),
        ])->assertRedirect(route('parent.account.security'));
        $this->get(route('parent.test.protected'))->assertRedirect(route('parent.account.security'));
    }

    public function test_password_change_updates_only_the_current_user_and_keeps_other_role_sessions(): void
    {
        $admin = $this->user('admin', $this->acceptedTerms());
        $parent = $this->user('prospective_parent', $this->acceptedTerms());
        $reviewer = $this->user('external_reviewer', $this->acceptedTerms());
        $this->actingAs($admin, 'admin')
            ->actingAs($reviewer, 'external_reviewer')
            ->actingAs($parent, 'prospective_parent');
        $this->put(route('parent.account.password'), $this->passwordInput(['user_id' => $admin->id]))
            ->assertRedirect(route('parent.account.security'));
        $this->get(route('parent.account.security'))->assertOk();
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertAuthenticatedAs($parent, 'prospective_parent');
        $this->assertAuthenticatedAs($reviewer, 'external_reviewer');
        $this->assertTrue(Hash::check('TemporaryPass123!', $admin->fresh()->password));
        $this->assertTrue(Hash::check('TemporaryPass123!', $reviewer->fresh()->password));
        $this->get(route('admin.test.protected'))->assertOk();
        $this->get(route('reviewer.test.protected'))->assertOk();
    }

    public function test_a_stale_password_session_is_signed_out_without_ending_another_portal(): void
    {
        $parent = $this->user('prospective_parent', $this->acceptedTerms());
        $admin = $this->user('admin', $this->acceptedTerms());
        $this->actingAs($admin, 'admin')->actingAs($parent, 'prospective_parent');
        $this->withSession([
            'account_password_hash.prospective_parent.'.$parent->id => Hash::make('OldPass123!'),
        ])->get(route('parent.account.security'))->assertRedirect(route('login'));
        $this->assertGuest('prospective_parent');
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_admin_created_and_reset_passwords_are_temporary(): void
    {
        $admin = $this->user('admin', $this->acceptedTerms());
        $parentRole = Role::create(['name' => 'Parent', 'slug' => 'prospective_parent']);
        $payload = [
            'role_id' => $parentRole->id,
            'first_name' => 'New',
            'middle_name' => 'Test',
            'last_name' => 'Parent',
            'name_extension' => '',
            'email' => 'new-parent@example.com',
            'status' => User::STATUS_ACTIVE,
            'password' => 'TemporaryPass123!',
            'password_confirmation' => 'TemporaryPass123!',
        ];
        $this->actingAs($admin, 'admin')->post(route('admin.users.store'), $payload)
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));
        $parent = User::where('email', $payload['email'])->firstOrFail();
        $this->assertTrue($parent->must_change_password);
        $this->assertFalse($parent->hasAcceptedCurrentTerms());
        $this->assertNull($parent->email_verified_at);
        $this->assertSame('New', $parent->first_name);
        $this->assertSame('Test', $parent->middle_name);
        $this->assertSame('Parent', $parent->last_name);
        $this->assertSame('New Test Parent', $parent->name);

        $parent->forceFill(['must_change_password' => false, 'remember_token' => 'old-token'])->save();
        $this->put(route('admin.users.update', $parent), $payload)
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));
        $this->assertTrue($parent->fresh()->must_change_password);
        $this->assertNull($parent->fresh()->remember_token);
    }

    private function user(string $guard, array $attributes = []): User
    {
        $role = Role::firstOrCreate(['slug' => $guard], ['name' => $guard]);
        $user = User::create([
            'role_id' => $role->id,
            'name' => 'Test '.$guard,
            'email' => $guard.'@example.com',
            'password' => 'TemporaryPass123!',
            'status' => User::STATUS_ACTIVE,
        ]);
        $user->forceFill(['email_verified_at' => now(), ...$attributes])->saveQuietly();

        return $user;
    }

    private function acceptedTerms(): array
    {
        return ['terms_accepted_version' => config('legal.terms_version'), 'terms_accepted_at' => now()];
    }

    private function passwordInput(array $overrides = []): array
    {
        return [
            'current_password' => 'TemporaryPass123!',
            'password' => 'PersonalPass456!',
            'password_confirmation' => 'PersonalPass456!',
            ...$overrides,
        ];
    }
}
