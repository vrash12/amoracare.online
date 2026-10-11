<?php

namespace Tests\Feature;

use App\Models\AdoptionCase;
use App\Models\AdoptionCaseDocument;
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

        foreach (['external_reviewer_case_accesses', 'adoption_case_documents', 'adoption_case_notes', 'adoption_cases', 'users', 'roles'] as $table) {
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
            $table->unsignedBigInteger('authorized_by')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('adoption_case_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adoption_case_id');
            $table->string('document_name');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('adoption_case_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adoption_case_id');
            $table->string('note_type');
            $table->string('visibility');
            $table->string('title');
            $table->text('body');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        foreach (['external_reviewer_case_accesses', 'adoption_case_documents', 'adoption_case_notes', 'adoption_cases', 'users', 'roles'] as $table) {
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
        $document = AdoptionCaseDocument::create([
            'adoption_case_id' => $adoptionCase->id,
            'document_name' => 'Submitted requirement',
            'status' => 'submitted',
        ]);
        $remainingCase = AdoptionCase::create([
            'case_code' => 'AC-2026-00002',
            'status' => 'document_collection',
            'created_by' => $admin->id,
        ]);
        ExternalReviewerCaseAccess::create([
            'reviewer_id' => $reviewer->id,
            'adoption_case_id' => $remainingCase->id,
        ]);
        foreach ([$adoptionCase, $remainingCase] as $case) {
            $case->notes()->create([
                'note_type' => 'external_review',
                'visibility' => 'reviewer_summary',
                'title' => 'Recorded review',
                'body' => 'Original review note.',
                'created_by' => $reviewer->id,
            ]);
        }

        $this->actingAs($reviewer, 'external_reviewer')
            ->get(route('reviewer.cases.index'))
            ->assertOk()->assertSee($adoptionCase->case_code)->assertSee($remainingCase->case_code);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.adoption-cases.destroy', $adoptionCase))
            ->assertRedirect(route('admin.adoption-cases.index'))
            ->assertSessionHas('success', 'Adoption case deleted successfully.');

        $this->assertSoftDeleted('adoption_cases', ['id' => $adoptionCase->id]);
        $this->assertNotNull($access->fresh()->expires_at);
        $this->assertSame('active', $access->fresh()->access_status);

        $this->actingAs($reviewer, 'external_reviewer')
            ->get(route('reviewer.cases.index'))
            ->assertOk()->assertDontSee($adoptionCase->case_code)->assertSee($remainingCase->case_code)
            ->assertViewHas('caseAccesses', fn ($cases) => $cases->total() === 1);
        $this->get(route('reviewer.dashboard'))->assertOk()
            ->assertViewHas('authorizedCases', 1)
            ->assertViewHas('pendingReviews', 1)
            ->assertViewHas('submittedNotes', 1)
            ->assertViewHas('documentChecks', 1)
            ->assertSee('href="'.route('reviewer.account.security').'"', false)
            ->assertSee('href="'.route('reviewer.account.terms').'"', false);

        // A tab opened before deletion must not crash or accept stale actions.
        $this->get(route('reviewer.cases.show', $access))
            ->assertRedirect(route('reviewer.cases.index'))
            ->assertSessionHas('error', 'This adoption case is no longer available. It has been removed from your authorized cases.');
        $this->get(route('reviewer.cases.index'))->assertOk()->assertSee('no longer available');
        $this->getJson(route('reviewer.cases.show', $access))->assertNotFound()
            ->assertJsonPath('redirect', route('reviewer.cases.index'));
        foreach (['notes.store', 'approve', 'request-changes'] as $action) {
            $this->post(route('reviewer.cases.'.$action, $access), ['body' => 'Stale note', 'remarks' => 'Stale review'])
                ->assertRedirect(route('reviewer.cases.index'));
        }
        $this->get(route('reviewer.cases.documents.download', [$access, $document]))
            ->assertRedirect(route('reviewer.cases.index'));
        foreach (['accept', 'reject'] as $action) {
            $this->post(route('reviewer.cases.documents.'.$action, [$access, $document]), ['remarks' => 'Stale review'])
                ->assertRedirect(route('reviewer.cases.index'));
        }
        $this->assertDatabaseCount('adoption_case_notes', 2);
        $this->assertSame('submitted', $document->fresh()->status);
        $this->assertSame('document_collection', $adoptionCase->fresh()->status);
        $this->assertDatabaseCount('external_reviewer_case_accesses', 2);
    }

    public function test_orphaned_access_does_not_show_or_crash_the_reviewer_portal(): void
    {
        $role = Role::create(['name' => 'External Reviewer', 'slug' => 'external_reviewer']);
        $reviewer = User::create([
            'role_id' => $role->id,
            'name' => 'Reviewer',
            'email' => 'reviewer@example.com',
            'password' => 'SecurePass123',
            'status' => User::STATUS_ACTIVE,
        ]);
        $reviewer->forceFill(['email_verified_at' => now()])->save();
        $case = AdoptionCase::create(['case_code' => 'AC-REMOVED']);
        $access = ExternalReviewerCaseAccess::create([
            'reviewer_id' => $reviewer->id,
            'adoption_case_id' => $case->id,
        ]);
        $case->forceDelete();

        $this->actingAs($reviewer, 'external_reviewer')
            ->get(route('reviewer.cases.index'))->assertOk()
            ->assertViewHas('caseAccesses', fn ($cases) => $cases->total() === 0)
            ->assertSee('No authorized cases are currently assigned to you.');
        $this->get(route('reviewer.dashboard'))->assertOk()->assertViewHas('authorizedCases', 0);
        $this->get(route('reviewer.cases.show', $access))->assertRedirect(route('reviewer.cases.index'));
    }
}
