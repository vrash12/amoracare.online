<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Rules\AvailableAccountEmail;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AccountEmailReuseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
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
            $table->string('password');
            $table->string('status')->default('active');
            $table->string('phone_number')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        $this->migration()->up();
        Http::fake();
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_28_000001_release_deleted_user_emails.php');
    }

    private function user(string $email, string $role = 'prospective_parent', string $status = 'active'): User
    {
        $role = Role::firstOrCreate(['slug' => $role], ['name' => $role]);

        return User::create(['role_id' => $role->id, 'name' => 'Test User', 'email' => $email,
            'password' => 'SecretPass123!', 'status' => $status]);
    }

    private function payload(User $user, string $email): array
    {
        return ['role_id' => $user->role_id, 'first_name' => 'New', 'last_name' => 'Parent',
            'email' => $email, 'password' => 'NewSecretPass123!', 'password_confirmation' => 'NewSecretPass123!',
            'status' => 'active'];
    }

    public function test_delete_then_recreate_reuses_email_with_a_new_id_and_preserves_history(): void
    {
        $admin = $this->user('admin@example.com', 'admin');
        $old = $this->user('Parent@Example.com');
        $old->forceFill(['remember_token' => 'old-token'])->saveQuietly();
        $this->actingAs($admin, 'admin')->delete(route('admin.users.destroy', $old))->assertRedirect();
        $archived = User::withTrashed()->findOrFail($old->id);
        $this->assertTrue($archived->trashed());
        $this->assertSame('parent@example.com', $archived->deleted_email);
        $this->assertStringEndsWith('@deleted.invalid', $archived->email);
        $this->assertNull($archived->remember_token);
        $this->post(route('admin.users.store'), $this->payload($old, ' PARENT@EXAMPLE.COM '))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));
        $new = User::where('email', 'parent@example.com')->firstOrFail();
        $this->assertNotSame($old->id, $new->id);
        $this->assertNull($new->email_verified_at);
        $this->assertSame(3, User::withTrashed()->count());
        $this->post(route('admin.users.store'), $this->payload($old, 'parent@example.com'))
            ->assertSessionHasErrors('email');
        $this->expectException(ValidationException::class);
        $archived->restore();
    }

    public function test_existing_accounts_including_inactive_and_pending_block_duplicates_and_edits(): void
    {
        $admin = $this->user('admin@example.com', 'admin');
        $editable = $this->user('editable@example.com');
        $this->actingAs($admin, 'admin');
        foreach (['active', 'inactive', 'pending'] as $status) {
            $existing = $this->user($status.'@example.com', 'prospective_parent', $status);
            $payload = $this->payload($existing, ' '.strtoupper($existing->email).' ');
            $this->post(route('admin.users.store'), $payload)->assertSessionHasErrors('email');
            $this->put(route('admin.users.update', $editable), $payload)->assertSessionHasErrors('email');
        }
        $this->assertSame('editable@example.com', $editable->fresh()->email);
        $this->put(route('admin.users.update', $editable), $this->payload($editable, 'EDITABLE@EXAMPLE.COM'))
            ->assertSessionHasNoErrors();
    }

    public function test_migration_releases_previously_deleted_emails_and_normalizes_existing_accounts(): void
    {
        $old = $this->user('legacy@example.com');
        DB::table('users')->where('id', $old->id)->update(['deleted_at' => now()]);
        $active = $this->user('active@example.com');
        DB::table('users')->where('id', $active->id)->update(['email' => ' ACTIVE@EXAMPLE.COM ']);
        $this->migration()->up();
        $this->migration()->up();
        $this->assertSame('legacy@example.com', User::withTrashed()->find($old->id)->deleted_email);
        $this->assertSame('active@example.com', $active->fresh()->email);
        $this->assertTrue(Validator::make(['email' => 'legacy@example.com'], ['email' => new AvailableAccountEmail])->passes());
        $this->assertFalse(Validator::make(['email' => ' ACTIVE@example.com '], ['email' => new AvailableAccountEmail])->passes());
        $new = $this->user('legacy@example.com');
        $this->assertNotSame($old->id, $new->id);
        $this->expectException(\RuntimeException::class);
        $this->migration()->down();
    }

    public function test_restore_without_email_reuse_restores_the_original_email(): void
    {
        $user = $this->user('restore@example.com');
        $user->delete();
        $user->restore();
        $this->assertSame('restore@example.com', $user->fresh()->email);
        $this->assertNull($user->fresh()->deleted_email);
    }

    public function test_invalid_password_pending_inactive_and_deleted_users_cannot_sign_in(): void
    {
        $user = $this->user('login@example.com');
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        foreach (['pending', 'inactive'] as $status) {
            $user->update(['status' => $status]);
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'SecretPass123!'])->assertSessionHasErrors('email');
        }
        $user->update(['status' => 'active']);
        $user->delete();
        $this->post(route('login.store'), ['email' => 'login@example.com', 'password' => 'SecretPass123!'])->assertSessionHasErrors('email');
        $this->assertGuest('prospective_parent');
        Http::assertNothingSent();
    }
}
