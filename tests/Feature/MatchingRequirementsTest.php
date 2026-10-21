<?php

namespace Tests\Feature;

use App\Models\AdoptionMatchingResult;
use App\Models\AdoptionMatchingRun;
use App\Models\AdoptionCase;
use App\Models\Child;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\ParentMatchingProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class MatchingRequirementsTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
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
        (require database_path('migrations/2026_09_26_000000_add_account_setup_to_users_table.php'))->up();
        (require database_path('migrations/2026_09_26_000001_add_name_parts_to_users_table.php'))->up();
        Schema::create('parent_matching_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->string('preferred_child_sex')->default('any');
            $table->integer('min_child_age')->nullable();
            $table->integer('max_child_age')->nullable();
            $table->boolean('open_to_special_needs')->default(false);
            $table->boolean('home_study_verified')->default(false);
            $table->integer('financial_capacity_score')->default(80);
            $table->integer('housing_score')->default(80);
            $table->integer('parenting_capacity_score')->default(80);
            $table->text('matching_notes')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_21_000000_add_parent_matching_readiness_checklist.php'))->up();
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->string('child_code')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('nickname')->nullable();
            $table->string('sex')->default('male');
            $table->boolean('is_special_needs')->default(false);
            $table->json('special_needs_categories')->nullable();
            $table->string('adoption_eligibility_status')->default('eligible');
            $table->string('case_status')->default('available_for_adoption');
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('adoption_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->nullable();
            $table->foreignId('prospective_parent_id');
            $table->string('case_type')->default('domestic_adoption');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('adoption_matching_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_code');
            $table->string('status');
            $table->foreignId('generated_by');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
        Schema::create('adoption_matching_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adoption_matching_run_id')->nullable();
            $table->foreignId('child_id');
            $table->foreignId('prospective_parent_id');
            $table->string('status');
            $table->decimal('child_score', 8, 2)->nullable();
            $table->decimal('parent_score', 8, 2)->nullable();
            $table->integer('rank_for_child')->nullable();
            $table->integer('rank_for_parent')->nullable();
            $table->text('explanation')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_21_000002_add_soft_deletes_to_adoption_matching_results.php'))->up();
        Schema::create('adoption_case_documents', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('donors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('donor_type')->default('individual');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')->nullable();
            $table->string('donation_code')->nullable();
            $table->string('donation_type')->default('cash');
            $table->date('donation_date');
            $table->string('purpose')->default('general_support');
            $table->decimal('cash_amount', 12, 2)->default(100);
            $table->string('status')->default('recorded');
            $table->foreignId('encoded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('donation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_id');
            $table->decimal('estimated_total_value', 12, 2)->default(0);
            $table->timestamps();
        });
        $this->admin = $this->user('admin', 'admin@example.invalid');
        $this->actingAs($this->admin, 'admin');
    }

    public function test_admin_creation_and_promotion_are_blocked_but_parent_and_reviewer_creation_work(): void
    {
        $this->get(route('admin.users.create'))->assertOk()
            ->assertDontSee('<option value="'.$this->admin->role_id.'"', false);
        $this->post(route('admin.users.store'), $this->accountInput($this->admin->role_id))
            ->assertSessionHasErrors('role_id');
        $this->assertSame(1, User::count());

        foreach (['prospective_parent', 'external_reviewer'] as $slug) {
            $role = Role::firstOrCreate(['slug' => $slug], ['name' => $slug]);
            $this->post(route('admin.users.store'), $this->accountInput($role->id, $slug.'@example.invalid'))
                ->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));
        }
        $parent = User::where('email', 'prospective_parent@example.invalid')->firstOrFail();
        $this->get(route('admin.users.edit', $parent))->assertOk()
            ->assertDontSee('<option value="'.$this->admin->role_id.'"', false);
        $this->put(route('admin.users.update', $parent), $this->accountInput($this->admin->role_id, $parent->email))
            ->assertSessionHasErrors('role_id');
        $this->assertFalse($parent->fresh()->isAdmin());
        $this->assertSame(1, User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->count());
    }

    public function test_existing_admin_can_be_edited_without_losing_the_only_admin_role(): void
    {
        $this->get(route('admin.users.edit', $this->admin))->assertOk()->assertSee('administrator role is retained');
        $input = $this->accountInput($this->admin->role_id, $this->admin->email);
        unset($input['password'], $input['password_confirmation']);
        $this->put(route('admin.users.update', $this->admin), $input)
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));
        $parentRole = Role::create(['name' => 'Parent', 'slug' => 'prospective_parent']);
        $this->put(route('admin.users.update', $this->admin), [...$input, 'role_id' => $parentRole->id])
            ->assertSessionHasErrors('role_id');
        $this->assertTrue($this->admin->fresh()->isAdmin());
    }

    public function test_parent_checklist_persists_and_unchecked_items_are_cleared(): void
    {
        $parent = $this->user('prospective_parent', 'parent@example.invalid');
        ParentMatchingProfile::create(['user_id' => $parent->id]);
        $this->get(route('admin.parents.edit', $parent))->assertOk()->assertSee('Parent Readiness Checklist');
        $input = [
            'name' => 'Parent Test', 'email' => $parent->email, 'status' => 'active',
            'preferred_child_sex' => 'any', 'min_child_age' => 0, 'max_child_age' => 10,
            'financial_capacity_score' => 80, 'housing_score' => 80, 'parenting_capacity_score' => 80,
            'physically_capable' => '1', 'financially_capable' => '1', 'willing_to_support_child' => '1',
        ];
        $this->put(route('admin.parents.update', $parent), $input)->assertSessionHasNoErrors();
        $this->assertTrue($parent->fresh()->matchingProfile->hasCompletedReadinessChecklist());
        $this->put(route('admin.parents.update', $parent), [...$input, 'financially_capable' => '0'])->assertSessionHasNoErrors();
        $this->assertFalse($parent->fresh()->matchingProfile->hasCompletedReadinessChecklist());
        $this->put(route('admin.parents.update', $parent), [...$input, 'physically_capable' => 'not-a-boolean'])
            ->assertSessionHasErrors('physically_capable');
    }

    public function test_matching_readiness_count_and_run_exclude_incomplete_checklists(): void
    {
        $parent = $this->user('prospective_parent', 'parent@example.invalid');
        $profile = ParentMatchingProfile::create(['user_id' => $parent->id]);
        $this->assertFalse($profile->fresh()->hasCompletedReadinessChecklist());
        Child::create(['child_code' => 'TEST-CHILD', 'sex' => 'male', 'adoption_eligibility_status' => 'eligible', 'case_status' => 'available_for_adoption']);
        $this->get(route('admin.matching.index'))->assertOk()->assertViewHas('activeParentsWithProfilesCount', 0);
        $this->post(route('admin.matching.run'))->assertSessionHas('error', 'No active prospective parents have all three readiness requirements confirmed and no active case.');
        $profile->update(array_fill_keys(array_keys(ParentMatchingProfile::READINESS_REQUIREMENTS), true));
        $this->get(route('admin.matching.index'))->assertOk()->assertViewHas('activeParentsWithProfilesCount', 1);
        $profile->update(['willing_to_support_child' => false]);
        $this->assertSame(0, ParentMatchingProfile::readyForMatching()->count());
    }

    public function test_old_recommendation_cannot_bypass_current_checklist(): void
    {
        $parent = $this->user('prospective_parent', 'parent@example.invalid');
        ParentMatchingProfile::create(['user_id' => $parent->id]);
        $result = AdoptionMatchingResult::create(['child_id' => 1, 'prospective_parent_id' => $parent->id, 'status' => 'recommended']);
        $this->post(route('admin.matching.create-case', $result))
            ->assertSessionHas('error', 'Confirm all three parent readiness requirements before creating an adoption case from this recommendation.');
        $this->assertDatabaseCount('adoption_cases', 0);
        $this->assertSame('recommended', $result->fresh()->status);
    }

    public function test_old_recommendation_cannot_bypass_the_current_special_needs_preference(): void
    {
        $parent = $this->user('prospective_parent', 'parent@example.invalid');
        ParentMatchingProfile::create([
            'user_id' => $parent->id,
            'open_to_special_needs' => false,
            ...array_fill_keys(array_keys(ParentMatchingProfile::READINESS_REQUIREMENTS), true),
        ]);
        $child = Child::create([...$this->childInput(), 'is_special_needs' => true]);
        $result = AdoptionMatchingResult::create([
            'child_id' => $child->id, 'prospective_parent_id' => $parent->id, 'status' => 'recommended',
        ]);

        $this->post(route('admin.matching.create-case', $result))
            ->assertSessionHas('error', 'This recommendation no longer matches the parent’s special-needs preference. Update the profiles and run matching again.');

        $this->assertDatabaseCount('adoption_cases', 0);
        $this->assertSame('recommended', $result->fresh()->status);
    }

    public function test_child_form_has_exclusive_options_and_preserves_the_saved_selection(): void
    {
        $response = $this->get(route('admin.children.create'))->assertOk()
            ->assertSee('No special needs')->assertSee('Child has special needs');
        $form = new \DOMDocument;
        @$form->loadHTML($response->getContent());
        $xpath = new \DOMXPath($form);
        $this->assertSame(2, $xpath->query('//input[@type="radio" and @name="is_special_needs" and @required]')->length);
        $this->assertSame(0, $xpath->query('//input[@name="is_special_needs" and @checked]')->length);

        foreach ([false, true] as $specialNeeds) {
            $child = Child::create([
                ...$this->childInput('CH-'.(int) $specialNeeds),
                'is_special_needs' => $specialNeeds,
                'special_needs_categories' => $specialNeeds ? ['sensory_impairment'] : [],
            ]);
            $response = $this->get(route('admin.children.edit', $child))->assertOk();
            @$form->loadHTML($response->getContent());
            $xpath = new \DOMXPath($form);
            $selected = $xpath->query('//input[@name="is_special_needs" and @checked]');
            $this->assertSame(1, $selected->length);
            $this->assertSame((string) (int) $specialNeeds, $selected->item(0)->getAttribute('value'));
            $categories = $xpath->query('//fieldset[@data-special-needs-categories]')->item(0);
            $this->assertSame(! $specialNeeds, $categories->hasAttribute('hidden'));
            $this->assertSame(! $specialNeeds, $categories->hasAttribute('disabled'));
        }
    }

    public function test_child_special_needs_status_and_categories_persist_and_clear_on_update(): void
    {
        $input = [...$this->childInput(), 'is_special_needs' => '1', 'special_needs_categories' => ['sensory_impairment']];
        $this->post(route('admin.children.store'), $input)
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.children.index'));
        $child = Child::firstOrFail();
        $this->assertTrue($child->is_special_needs);
        $this->assertSame(['sensory_impairment'], $child->special_needs_categories);

        // Ignore stale category submissions when the administrator explicitly selects No.
        $this->put(route('admin.children.update', $child), [...$input, 'is_special_needs' => '0'])
            ->assertSessionHasNoErrors();
        $this->assertFalse($child->fresh()->is_special_needs);
        $this->assertSame([], $child->fresh()->special_needs_categories);

        unset($input['special_needs_categories']);
        $this->put(route('admin.children.update', $child), $input)->assertSessionHasNoErrors();
        $this->assertTrue($child->fresh()->is_special_needs);
        $this->assertSame([], $child->fresh()->special_needs_categories);

        $this->post(route('admin.children.store'), [...$this->childInput('CH-NONE'), 'is_special_needs' => '0'])
            ->assertSessionHasNoErrors();
        $this->assertFalse(Child::where('child_code', 'CH-NONE')->firstOrFail()->is_special_needs);
    }

    public function test_child_status_must_be_explicit_and_special_needs_categories_must_be_valid(): void
    {
        $this->post(route('admin.children.store'), $this->childInput())->assertSessionHasErrors('is_special_needs');
        $this->post(route('admin.children.store'), [...$this->childInput(), 'is_special_needs' => 'invalid'])
            ->assertSessionHasErrors('is_special_needs');
        $this->post(route('admin.children.store'), [
            ...$this->childInput(), 'is_special_needs' => '1', 'special_needs_categories' => ['invalid'],
        ])->assertSessionHasErrors('special_needs_categories.0');
        $this->assertDatabaseCount('children', 0);
    }

    public function test_child_report_uses_compact_pagination_and_keeps_filters(): void
    {
        $children = new LengthAwarePaginator([], 30, 10, 2, ['path' => route('admin.reports.children')]);
        $children->appends(['case_status' => 'available_for_adoption', 'from' => '2026-10-01', 'to' => '2026-10-21']);
        $view = $this->view('admin.reports.children', [
            'children' => $children, 'from' => now()->startOfMonth(), 'to' => now(),
            'caseStatuses' => Child::CASE_STATUSES, 'eligibilityStatuses' => Child::ELIGIBILITY_STATUSES,
            'caseStatus' => 'available_for_adoption', 'eligibilityStatus' => null, 'errors' => new ViewErrorBag,
        ]);
        $view->assertSee('ac-pagination-controls')->assertSee('rel="prev"', false)->assertSee('rel="next"', false);
        $view->assertSee('from=2026-10-01')->assertSee('page=3')->assertDontSee('<svg', false);
    }

    public function test_child_totals_cover_all_pages_and_are_independent_of_filters(): void
    {
        $children = collect(range(1, 14))->map(fn ($id) => Child::create($this->childInput('CH-STAT-'.$id)));
        $children[12]->update(['case_status' => 'inactive', 'adoption_eligibility_status' => 'pending_documents']);
        $children[13]->delete();
        AdoptionCase::create(['child_id' => $children[0]->id, 'prospective_parent_id' => $this->admin->id, 'status' => 'matching_review']);
        AdoptionCase::create(['child_id' => $children[1]->id, 'prospective_parent_id' => $this->admin->id, 'status' => 'finalized']);
        foreach ([2, 2, 10, 13] as $index) {
            AdoptionMatchingResult::create(['child_id' => $children[$index]->id, 'prospective_parent_id' => $this->admin->id, 'status' => 'recommended']);
        }
        AdoptionMatchingResult::create(['child_id' => $children[11]->id, 'prospective_parent_id' => $this->admin->id, 'status' => 'recommended'])->delete();

        $expected = ['total' => 13, 'ready_for_matching' => 11, 'with_recommendation' => 2, 'needs_attention' => 1];
        foreach ([[], ['page' => 2], ['search' => 'CH-STAT-14'], ['case_status' => 'inactive']] as $query) {
            $this->get(route('admin.children.index', $query))->assertOk()
                ->assertViewHas('childSummary', $expected)
                ->assertSee('Total child profiles')->assertDontSee('Visible on page');
        }
    }

    public function test_donor_report_counts_distinct_donors_with_live_donations_in_the_selected_dates(): void
    {
        Donor::create(['name' => 'Orphan profile']);
        $active = Donor::create(['name' => 'Active donor']);
        Donation::create(['donor_id' => $active->id, 'donation_date' => '2026-10-01', 'status' => 'recorded']);
        Donation::create(['donor_id' => $active->id, 'donation_date' => '2026-10-31', 'status' => 'verified']);
        $outside = Donor::create(['name' => 'Previous donor']);
        Donation::create(['donor_id' => $outside->id, 'donation_date' => '2026-09-30', 'status' => 'verified']);
        $deletedOnly = Donor::create(['name' => 'Deleted donation']);
        Donation::create(['donor_id' => $deletedOnly->id, 'donation_date' => '2026-10-12', 'status' => 'verified'])->delete();
        $cancelled = Donor::create(['name' => 'Cancelled donation']);
        Donation::create(['donor_id' => $cancelled->id, 'donation_date' => '2026-10-12', 'status' => 'cancelled']);
        $deletedDonor = Donor::create(['name' => 'Deleted donor']);
        Donation::create(['donor_id' => $deletedDonor->id, 'donation_date' => '2026-10-12', 'status' => 'verified']);
        $deletedDonor->delete();
        $other = Donor::create(['name' => 'Second active donor']);
        Donation::create(['donor_id' => $other->id, 'donation_date' => '2026-10-31', 'status' => 'recorded']);

        $response = $this->get(route('admin.reports.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()
            ->assertSee('Donors in date range')->assertDontSee('Registered donors');
        $this->assertSame(2, $response->viewData('summary')['total_donors']);
        $this->assertSame(5, $response->viewData('summary')['total_donations']);
        $this->get(route('admin.reports.index', ['from' => '2026-09-01', 'to' => '2026-09-30']))
            ->assertOk()->assertViewHas('summary', fn ($summary) => $summary['total_donors'] === 1);
        $this->get(route('admin.reports.index', ['from' => '2026-11-01', 'to' => '2026-11-30']))
            ->assertOk()->assertViewHas('summary', fn ($summary) => $summary['total_donors'] === 0);
    }

    public function test_admin_can_delete_a_recommendation_without_deleting_the_run_child_or_parent(): void
    {
        $run = AdoptionMatchingRun::create(['run_code' => 'MR-DELETE', 'status' => 'completed', 'generated_by' => $this->admin->id]);
        $child = Child::create($this->childInput());
        $result = AdoptionMatchingResult::create(['adoption_matching_run_id' => $run->id, 'child_id' => $child->id, 'prospective_parent_id' => $this->admin->id, 'status' => 'recommended']);

        $this->get(route('admin.matching.show', $run))->assertOk()->assertSee('Delete Recommendation');
        $this->delete(route('admin.matching.results.destroy', $result))->assertRedirect(route('admin.matching.show', $run))->assertSessionHas('success');
        $this->assertSoftDeleted($result);
        $this->assertSame(0, $run->results()->count());
        $this->assertModelExists($run);
        $this->assertModelExists($child);
        $this->assertModelExists($this->admin);
        $this->get(route('admin.matching.show', $run))->assertOk()->assertSee('No matches generated');
        $this->delete(route('admin.matching.results.destroy', $result))->assertNotFound();
        $this->assertDatabaseCount('adoption_cases', 0);
    }

    public function test_converted_recommendations_and_their_cases_are_protected_from_deletion(): void
    {
        $run = AdoptionMatchingRun::create(['run_code' => 'MR-CONVERTED', 'status' => 'completed', 'generated_by' => $this->admin->id]);
        $child = Child::create($this->childInput());
        $case = AdoptionCase::create(['child_id' => $child->id, 'prospective_parent_id' => $this->admin->id, 'status' => 'matching_review']);
        $result = AdoptionMatchingResult::create(['adoption_matching_run_id' => $run->id, 'child_id' => $child->id, 'prospective_parent_id' => $this->admin->id, 'status' => 'converted_to_case']);
        $this->get(route('admin.matching.show', $run))->assertOk()->assertDontSee('Delete Recommendation');
        $this->delete(route('admin.matching.results.destroy', $result))->assertSessionHas('error');
        $this->assertFalse($result->fresh()->trashed());
        $this->assertModelExists($case);
    }

    public function test_non_admin_cannot_delete_a_matching_recommendation(): void
    {
        $result = AdoptionMatchingResult::create(['child_id' => 1, 'prospective_parent_id' => $this->admin->id, 'status' => 'recommended']);
        $parent = $this->user('prospective_parent', 'delete-parent@example.invalid');
        auth('admin')->logout();
        $this->actingAs($parent, 'web')->delete(route('admin.matching.results.destroy', $result))->assertForbidden();
        $this->assertFalse($result->fresh()->trashed());
    }

    private function accountInput(int $roleId, string $email = 'new@example.invalid'): array
    {
        return ['role_id' => $roleId, 'first_name' => 'Test', 'last_name' => 'Account', 'email' => $email,
            'status' => 'active', 'password' => 'TemporaryPass123!', 'password_confirmation' => 'TemporaryPass123!'];
    }

    private function childInput(string $code = 'CH-SPECIAL-TEST'): array
    {
        return [
            'child_code' => $code, 'first_name' => 'Test', 'last_name' => 'Child', 'sex' => 'female',
            'adoption_eligibility_status' => 'eligible', 'case_status' => 'available_for_adoption',
        ];
    }

    private function user(string $roleSlug, string $email): User
    {
        $role = Role::firstOrCreate(['slug' => $roleSlug], ['name' => $roleSlug]);
        $user = User::create(['role_id' => $role->id, 'name' => 'Test Account', 'email' => $email,
            'password' => 'TemporaryPass123!', 'status' => 'active']);
        $user->forceFill(['email_verified_at' => now(), 'terms_accepted_at' => now(),
            'terms_accepted_version' => config('legal.terms_version')])->saveQuietly();

        return $user;
    }
}
