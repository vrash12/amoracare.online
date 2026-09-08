<?php

namespace Tests\Feature;

use App\Models\ParentMatchingProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProspectiveParentApplicationTest extends TestCase
{
    private ?string $sentCode = null;

    protected function setUp(): void
    {
        parent::setUp();

        config([
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
            $this->sentCode = $matches[1] ?? null;

            return Http::response(['messageId' => 'test-message-id'], 201);
        });

        Schema::dropIfExists('email_verification_codes');
        Schema::dropIfExists('parent_matching_profiles');
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
            $table->timestamp('activated_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('parent_matching_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->string('preferred_child_sex');
            $table->unsignedTinyInteger('min_child_age');
            $table->unsignedTinyInteger('max_child_age');
            $table->boolean('open_to_special_needs')->default(false);
            $table->boolean('home_study_verified')->default(false);
            $table->unsignedTinyInteger('financial_capacity_score')->default(0);
            $table->unsignedTinyInteger('housing_score')->default(0);
            $table->unsignedTinyInteger('parenting_capacity_score')->default(0);
            $table->text('matching_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('email_verification_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('email_verification_codes');
        Schema::dropIfExists('parent_matching_profiles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');

        parent::tearDown();
    }

    public function test_public_application_form_is_available(): void
    {
        $this->get(route('parent.application.create'))
            ->assertOk()
            ->assertSee('Prospective Adoptive Parent')
            ->assertSee('Application Form');
    }

    public function test_application_requires_email_otp_before_pending_submission_is_completed(): void
    {
        $response = $this->post(route('parent.application.store'), [
            'name' => '  maRIA   santos  ',
            'email' => 'MARIA.SANTOS@example.com',
            'phone_number' => '09171234567',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'preferred_child_sex' => 'female',
            'min_child_age' => 2,
            'max_child_age' => 8,
            'open_to_special_needs' => '1',
            'consent' => '1',
            'terms_accepted' => '1',
        ]);

        $response->assertRedirect(route('email.verification.notice'))
            ->assertSessionHas('email_verification_purpose', 'registration')
            ->assertSessionHas('pending_parent_registration');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('parent_matching_profiles', 0);
        $this->assertGuest();
        $this->assertNotNull($this->sentCode);

        $this->get(route('email.verification.notice'))
            ->assertOk()
            ->assertSee('Sign-up verification')
            ->assertSee('Verify sign-up and submit');

        $this->post(route('email.verification.verify'), [
            'code' => $this->sentCode,
        ])->assertRedirect(route('parent.application.submitted'))
            ->assertSessionHas('application_email', 'maria.santos@example.com');

        $parent = User::with(['role', 'matchingProfile'])->firstOrFail();

        $this->assertGuest();
        $this->assertSame('Maria Santos', $parent->name);
        $this->assertSame('maria.santos@example.com', $parent->email);
        $this->assertSame(User::STATUS_PENDING, $parent->status);
        $this->assertNotNull($parent->email_verified_at);
        $this->assertSame('prospective_parent', $parent->role->slug);
        $this->assertTrue(Hash::check('SecurePass123', $parent->password));

        $this->assertInstanceOf(ParentMatchingProfile::class, $parent->matchingProfile);
        $this->assertSame('female', $parent->matchingProfile->preferred_child_sex);
        $this->assertTrue($parent->matchingProfile->open_to_special_needs);
        $this->assertFalse($parent->matchingProfile->home_study_verified);
        $this->assertSame(0, $parent->matchingProfile->financial_capacity_score);
        $this->assertSame(0, $parent->matchingProfile->housing_score);
        $this->assertSame(0, $parent->matchingProfile->parenting_capacity_score);
        $this->assertDatabaseMissing('email_verification_codes', ['user_id' => $parent->id]);
    }

    public function test_incorrect_registration_otp_does_not_create_an_account(): void
    {
        $this->post(route('parent.application.store'), [
            'name' => 'Maria Santos',
            'email' => 'maria.santos@example.com',
            'phone_number' => '09171234567',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'preferred_child_sex' => 'any',
            'min_child_age' => 0,
            'max_child_age' => 10,
            'consent' => '1',
            'terms_accepted' => '1',
        ])->assertRedirect(route('email.verification.notice'));

        $wrongCode = $this->sentCode === '000000' ? '999999' : '000000';

        $this->post(route('email.verification.verify'), ['code' => $wrongCode])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('parent_matching_profiles', 0);
    }

    public function test_terms_must_be_accepted_before_an_otp_is_sent(): void
    {
        $this->sentCode = null;

        $this->from(route('parent.application.create'))
            ->post(route('parent.application.store'), [
                'name' => 'Maria Santos',
                'email' => 'maria.santos@example.com',
                'phone_number' => '09171234567',
                'password' => 'SecurePass123',
                'password_confirmation' => 'SecurePass123',
                'preferred_child_sex' => 'any',
                'min_child_age' => 0,
                'max_child_age' => 10,
                'consent' => '1',
            ])
            ->assertRedirect(route('parent.application.create'))
            ->assertSessionHasErrors('terms_accepted');

        $this->assertNull($this->sentCode);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $role = Role::create([
            'name' => 'Prospective Adoptive Parent',
            'slug' => 'prospective_parent',
        ]);

        User::create([
            'role_id' => $role->id,
            'name' => 'Existing Parent',
            'email' => 'parent@example.com',
            'phone_number' => '09170000000',
            'password' => 'ExistingPass123',
            'status' => User::STATUS_PENDING,
        ]);

        $this->from(route('parent.application.create'))
            ->post(route('parent.application.store'), [
                'name' => 'Another Parent',
                'email' => 'parent@example.com',
                'phone_number' => '09171111111',
                'password' => 'SecurePass123',
                'password_confirmation' => 'SecurePass123',
                'preferred_child_sex' => 'any',
                'min_child_age' => 0,
                'max_child_age' => 10,
                'consent' => '1',
                'terms_accepted' => '1',
            ])
            ->assertRedirect(route('parent.application.create'))
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::count());
    }

    public function test_admin_can_view_and_download_the_application_qr_code(): void
    {
        $role = Role::create([
            'name' => 'Administrator',
            'slug' => 'admin',
        ]);

        $admin = User::create([
            'role_id' => $role->id,
            'name' => 'System Administrator',
            'email' => 'admin@example.com',
            'phone_number' => '09170000000',
            'password' => 'AdminPass123',
            'status' => User::STATUS_ACTIVE,
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($admin)
            ->get(route('admin.parents.application-qr'))
            ->assertOk()
            ->assertSee(route('parent.application.create'));

        $this->actingAs($admin)
            ->get(route('admin.parents.application-qr.image'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertSee('<svg', false);

        $this->actingAs($admin)
            ->get(route('admin.parents.application-qr.download'))
            ->assertOk()
            ->assertDownload('amoracare-parent-application-qr.svg');
    }
}
