<?php

namespace Tests\Feature;

use App\Models\AdoptionCase;
use App\Models\ExternalReviewerCaseAccess;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdoptionCaseDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['external_reviewer_case_accesses', 'adoption_cases', 'users', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('status')->default(User::STATUS_ACTIVE);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('adoption_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_code')->unique();
            $table->unsignedBigInteger('child_id')->nullable();
            $table->unsignedBigInteger('prospective_parent_id')->nullable();
            $table->unsignedBigInteger('assigned_social_worker_id')->nullable();
            $table->string('case_type')->default('domestic_adoption');
            $table->string('status')->default('draft');
            $table->string('priority')->default('normal');
            $table->date('opened_at')->nullable();
            $table->date('target_completion_date')->nullable();
            $table->date('closed_at')->nullable();
            $table->text('summary')->nullable();
            $table->text('confidential_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('external_reviewer_case_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id');
            $table->foreignId('adoption_case_id');
            $table->string('access_status')->default('active');
            $table->boolean('can_view_summary')->default(true);
            $table->boolean('can_view_document_status')->default(true);
            $table->boolean('can_submit_notes')->default(true);
            $table->boolean('can_make_decision')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach (['external_reviewer_case_accesses', 'adoption_cases', 'users', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_deleting_a_case_soft_deletes_it_and_expires_reviewer_access(): void
    {
        $adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin']);
        $reviewerRole = Role::create(['name' => 'External Reviewer', 'slug' => 'external_reviewer']);

        $admin = User::create([
            'role_id' => $adminRole->id,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'SecurePass123',
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
            'activated_at' => now(),
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $reviewer = User::create([
            'role_id' => $reviewerRole->id,
            'name' => 'Reviewer User',
            'email' => 'reviewer@example.com',
            'password' => 'SecurePass123',
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
            'activated_at' => now(),
        ]);
        $reviewer->forceFill(['email_verified_at' => now()])->save();
        $adoptionCase = AdoptionCase::create([
            'case_code' => 'AC-2026-00001',
            'case_type' => 'domestic_adoption',
            'status' => 'document_collection',
            'priority' => 'normal',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $access = ExternalReviewerCaseAccess::create([
            'reviewer_id' => $reviewer->id,
            'adoption_case_id' => $adoptionCase->id,
            'access_status' => 'active',
            'expires_at' => null,
        ]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.adoption-cases.destroy', $adoptionCase))
            ->assertRedirect(route('admin.adoption-cases.index'))
            ->assertSessionHas('success', 'Adoption case deleted successfully.');

        $this->assertSoftDeleted('adoption_cases', ['id' => $adoptionCase->id]);
        $this->assertNotNull($access->fresh()->expires_at);
        $this->assertSame('active', $access->fresh()->access_status);
    }
}
