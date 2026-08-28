<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserAccountStatusService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserAccountStatusServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-28 10:00:00');
        config(['accounts.inactivity_days' => 60]);

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone_number')->nullable();
            $table->string('status')->default(User::STATUS_PENDING);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_only_deactivates_active_accounts_dormant_for_sixty_days(): void
    {
        $dormant = $this->createUser([
            'created_at' => now()->subDays(120),
            'updated_at' => now()->subDays(120),
            'activated_at' => now()->subDays(90),
            'last_login_at' => now()->subDays(60),
        ]);

        $recent = $this->createUser([
            'created_at' => now()->subDays(120),
            'updated_at' => now()->subDays(120),
            'activated_at' => now()->subDays(90),
            'last_login_at' => now()->subDays(10),
        ]);

        $pending = $this->createUser([
            'status' => User::STATUS_PENDING,
            'created_at' => now()->subDays(120),
            'updated_at' => now()->subDays(120),
            'activated_at' => null,
            'last_login_at' => null,
        ]);

        $count = app(UserAccountStatusService::class)->deactivateDormantUsers();

        $this->assertSame(1, $count);
        $this->assertSame(User::STATUS_INACTIVE, $dormant->fresh()->status);
        $this->assertSame(User::STATUS_ACTIVE, $recent->fresh()->status);
        $this->assertSame(User::STATUS_PENDING, $pending->fresh()->status);
    }

    public function test_an_active_account_that_never_logged_in_uses_its_activation_date(): void
    {
        $oldActivation = $this->createUser([
            'created_at' => now()->subDays(90),
            'updated_at' => now()->subDays(90),
            'activated_at' => now()->subDays(61),
            'last_login_at' => null,
        ]);

        $recentActivation = $this->createUser([
            'created_at' => now()->subDays(90),
            'updated_at' => now()->subDays(90),
            'activated_at' => now()->subDays(10),
            'last_login_at' => null,
        ]);

        app(UserAccountStatusService::class)->deactivateDormantUsers();

        $this->assertSame(User::STATUS_INACTIVE, $oldActivation->fresh()->status);
        $this->assertSame(User::STATUS_ACTIVE, $recentActivation->fresh()->status);
    }

    public function test_reactivation_restarts_the_inactivity_timer(): void
    {
        $user = $this->createUser([
            'status' => User::STATUS_INACTIVE,
            'created_at' => now()->subDays(180),
            'updated_at' => now()->subDays(90),
            'activated_at' => now()->subDays(120),
            'last_login_at' => now()->subDays(100),
        ]);

        $user->update(['status' => User::STATUS_ACTIVE]);

        $this->assertTrue($user->activated_at->equalTo(now()));
        $this->assertFalse(app(UserAccountStatusService::class)->deactivateIfDormant($user));
        $this->assertSame(User::STATUS_ACTIVE, $user->fresh()->status);
    }

    private function createUser(array $attributes = []): User
    {
        $user = new User;

        $user->forceFill(array_merge([
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'status' => User::STATUS_ACTIVE,
            'created_at' => now(),
            'updated_at' => now(),
            'activated_at' => now(),
            'last_login_at' => null,
        ], $attributes));

        $user->saveQuietly();

        return $user->fresh();
    }
}
