<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-28 10:00:00');

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

        Schema::dropIfExists('email_verification_codes');
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
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_names_are_automatically_capitalized_and_trimmed(): void
    {
        $user = new User(['name' => '  jUAN   de la CRUZ  ']);

        $this->assertSame('Juan De La Cruz', $user->name);
    }

    public function test_brevo_sends_a_hashed_six_digit_code_with_resend_protection(): void
    {
        $sentCode = null;

        Http::fake(function (ClientRequest $request) use (&$sentCode) {
            preg_match('/letter-spacing:10px;">\s*(\d{6})\s*</', (string) $request['htmlContent'], $matches);
            $sentCode = $matches[1] ?? null;

            return Http::response(['messageId' => 'test-message-id'], 201);
        });

        $user = $this->createUser();
        $service = app(EmailVerificationService::class);

        $this->assertTrue($service->sendCode($user));
        $this->assertFalse($service->sendCode($user));

        $record = $user->emailVerificationCode()->firstOrFail();

        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $sentCode);
        $this->assertTrue(Hash::check($sentCode, $record->code_hash));
        $this->assertTrue($record->expires_at->equalTo(now()->addMinutes(10)));

        Http::assertSentCount(1);
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://api.brevo.com/v3/smtp/email'
            && $request['to'][0]['email'] === $user->email
            && $request->hasHeader('api-key', 'test-api-key'));
    }

    public function test_user_can_complete_login_with_the_emailed_otp(): void
    {
        $sentCode = null;

        Http::fake(function (ClientRequest $request) use (&$sentCode) {
            preg_match('/letter-spacing:10px;">\s*(\d{6})\s*</', (string) $request['htmlContent'], $matches);
            $sentCode = $matches[1] ?? null;

            return Http::response(['messageId' => 'test-message-id'], 201);
        });

        $roleId = Schema::getConnection()->table('roles')->insertGetId([
            'name' => 'Prospective Parent',
            'slug' => 'prospective_parent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = $this->createUser(['role_id' => $roleId]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('email.verification.notice'))
            ->assertSessionHas('email_verification_user_id', $user->id)
            ->assertSessionHas('email_verification_purpose', 'login');

        $this->assertGuest();
        $this->assertNotNull($sentCode);

        $this->get(route('email.verification.notice'))
            ->assertOk()
            ->assertSee('Enter your one-time password')
            ->assertSee('expires in 10 minutes');

        $this->post(route('email.verification.verify'), [
            'code' => $sentCode,
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseMissing('email_verification_codes', ['user_id' => $user->id]);
    }

    public function test_already_verified_user_still_requires_a_new_otp_for_every_login(): void
    {
        $sentCode = null;

        Http::fake(function (ClientRequest $request) use (&$sentCode) {
            preg_match('/letter-spacing:10px;">\s*(\d{6})\s*</', (string) $request['htmlContent'], $matches);
            $sentCode = $matches[1] ?? null;

            return Http::response(['messageId' => 'test-message-id'], 201);
        });

        $roleId = Schema::getConnection()->table('roles')->insertGetId([
            'name' => 'Prospective Parent',
            'slug' => 'prospective_parent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = $this->createUser(['role_id' => $roleId]);
        $user->forceFill(['email_verified_at' => now()->subDay()])->save();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('email.verification.notice'))
            ->assertSessionHas('email_verification_purpose', 'login');

        $this->assertGuest();
        $this->assertNotNull($sentCode);

        $this->post(route('email.verification.verify'), [
            'code' => $sentCode,
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->email_verified_at->equalTo(now()));
    }

    public function test_verification_code_expires_and_limits_incorrect_attempts(): void
    {
        $sentCode = null;

        Http::fake(function (ClientRequest $request) use (&$sentCode) {
            preg_match('/letter-spacing:10px;">\s*(\d{6})\s*</', (string) $request['htmlContent'], $matches);
            $sentCode = $matches[1] ?? null;

            return Http::response(['messageId' => 'test-message-id'], 201);
        });

        $service = app(EmailVerificationService::class);
        $user = $this->createUser();
        $service->sendCode($user);

        $wrongCode = $sentCode === '000000' ? '999999' : '000000';
        $result = [];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $result = $service->verifyCode($user, $wrongCode);
        }

        $this->assertFalse($result['verified']);
        $this->assertSame('Too many incorrect attempts. Request a new code.', $result['message']);
        $this->assertDatabaseMissing('email_verification_codes', ['user_id' => $user->id]);

        Carbon::setTestNow(now()->addMinute());
        $service->sendCode($user);
        $user->emailVerificationCode()->update(['expires_at' => now()->subSecond()]);

        $expired = $service->verifyCode($user, (string) $sentCode);

        $this->assertFalse($expired['verified']);
        $this->assertSame('The verification code has expired. Request a new code.', $expired['message']);
        $this->assertDatabaseMissing('email_verification_codes', ['user_id' => $user->id]);
    }

    private function createUser(array $attributes = []): User
    {
        return User::query()->create(array_merge([
            'role_id' => null,
            'name' => 'test applicant',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => null,
        ], $attributes));
    }
}
