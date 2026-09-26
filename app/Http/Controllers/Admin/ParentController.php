<?php
// app/Http/Controllers/Admin/ParentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdoptionMatchingResult;
use App\Models\ParentMatchingProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ParentController extends Controller
{
    private function closedCaseStatuses(): array
    {
        return ['finalized', 'closed', 'cancelled'];
    }

    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $homeStudyVerified = $request->input('home_study_verified');
        $openToSpecialNeeds = $request->input('open_to_special_needs');

        $parentsQuery = User::query()
            ->with(['role', 'matchingProfile'])
            ->withCount([
                'adoptionCasesAsProspectiveParent as total_cases_count',
                'adoptionCasesAsProspectiveParent as active_cases_count' => function ($query) {
                    $query->whereNotIn('status', $this->closedCaseStatuses());
                },
            ])
            ->whereHas('role', function ($query) {
                $query->where('slug', 'prospective_parent');
            });

        if ($search) {
            $parentsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $parentsQuery->where('status', $status);
        }

        if ($homeStudyVerified !== null && $homeStudyVerified !== '') {
            $parentsQuery->whereHas('matchingProfile', function ($query) use ($homeStudyVerified) {
                $query->where('home_study_verified', (bool) $homeStudyVerified);
            });
        }

        if ($openToSpecialNeeds !== null && $openToSpecialNeeds !== '') {
            $parentsQuery->whereHas('matchingProfile', function ($query) use ($openToSpecialNeeds) {
                $query->where('open_to_special_needs', (bool) $openToSpecialNeeds);
            });
        }

        $parents = $parentsQuery
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $totalParentsCount = User::whereHas('role', function ($query) {
                $query->where('slug', 'prospective_parent');
            })
            ->count();

        $activeParentsCount = User::whereHas('role', function ($query) {
                $query->where('slug', 'prospective_parent');
            })
            ->where('status', 'active')
            ->count();

        $withProfileCount = User::whereHas('role', function ($query) {
                $query->where('slug', 'prospective_parent');
            })
            ->whereHas('matchingProfile')
            ->count();

        $homeStudyVerifiedCount = User::whereHas('role', function ($query) {
                $query->where('slug', 'prospective_parent');
            })
            ->whereHas('matchingProfile', function ($query) {
                $query->where('home_study_verified', true);
            })
            ->count();

        $statuses = [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'pending' => 'Pending',
        ];

        return view('admin.parents.index', compact(
            'parents',
            'search',
            'status',
            'homeStudyVerified',
            'openToSpecialNeeds',
            'totalParentsCount',
            'activeParentsCount',
            'withProfileCount',
            'homeStudyVerifiedCount',
            'statuses'
        ));
    }

    public function create(): View
    {
        $parent = new User([
            'status' => 'active',
        ]);

        $profile = new ParentMatchingProfile([
            'preferred_child_sex' => 'any',
            'min_child_age' => 0,
            'max_child_age' => 10,
            'open_to_special_needs' => false,
            'home_study_verified' => false,
            'financial_capacity_score' => 80,
            'housing_score' => 80,
            'parenting_capacity_score' => 80,
        ]);

        $statuses = [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'pending' => 'Pending',
        ];

        return view('admin.parents.create', compact('parent', 'profile', 'statuses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateParent($request);

        DB::transaction(function () use ($validated, $request) {
            $role = Role::firstOrCreate(
                ['slug' => 'prospective_parent'],
                [
                    'name' => 'Prospective Parent',
                    'description' => 'Parent applicant for adoption matching and application tracking.',
                ]
            );

            $parent = new User([
                'role_id' => $role->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'] ?? null,
                'status' => $validated['status'],
                'password' => Hash::make($validated['password']),
            ]);
            if (Schema::hasColumn('users', 'must_change_password')) {
                $parent->forceFill(['must_change_password' => true]);
            }
            $parent->save();

            ParentMatchingProfile::create($this->profilePayload($validated, $request, $parent->id));
        });

        return redirect()
            ->route('admin.parents.index')
            ->with('success', 'Parent profile created successfully.');
    }

    public function show(User $parent): View
    {
        $this->ensureProspectiveParent($parent);

        $parent->load(['role', 'matchingProfile']);

        $adoptionCases = $parent->adoptionCasesAsProspectiveParent()
            ->with(['child', 'assignedSocialWorker', 'documents'])
            ->latest()
            ->get();

        $matchingResults = AdoptionMatchingResult::with(['run', 'child'])
            ->where('prospective_parent_id', $parent->id)
            ->latest()
            ->take(10)
            ->get();

        $activeCasesCount = $adoptionCases
            ->whereNotIn('status', $this->closedCaseStatuses())
            ->count();

        return view('admin.parents.show', compact(
            'parent',
            'adoptionCases',
            'matchingResults',
            'activeCasesCount'
        ));
    }

    public function edit(User $parent): View
    {
        $this->ensureProspectiveParent($parent);

        $parent->load(['matchingProfile']);

        $profile = $parent->matchingProfile ?? new ParentMatchingProfile([
            'preferred_child_sex' => 'any',
            'min_child_age' => 0,
            'max_child_age' => 10,
            'open_to_special_needs' => false,
            'home_study_verified' => false,
            'financial_capacity_score' => 80,
            'housing_score' => 80,
            'parenting_capacity_score' => 80,
        ]);

        $statuses = [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'pending' => 'Pending',
        ];

        return view('admin.parents.edit', compact('parent', 'profile', 'statuses'));
    }

    public function update(Request $request, User $parent): RedirectResponse
    {
        $this->ensureProspectiveParent($parent);

        $validated = $this->validateParent($request, $parent);

        DB::transaction(function () use ($validated, $request, $parent) {
            $parentData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'] ?? null,
                'status' => $validated['status'],
            ];

            if (!empty($validated['password'])) {
                $parentData['password'] = Hash::make($validated['password']);
                if (Schema::hasColumn('users', 'must_change_password')) {
                    $parent->forceFill(['must_change_password' => true]);
                }
                $parent->forceFill(['remember_token' => null]);
            }

            $parent->update($parentData);

            ParentMatchingProfile::updateOrCreate(
                ['user_id' => $parent->id],
                $this->profilePayload($validated, $request, $parent->id)
            );
        });

        return redirect()
            ->route('admin.parents.show', $parent)
            ->with('success', 'Parent profile updated successfully.');
    }

    public function destroy(User $parent): RedirectResponse
    {
        $this->ensureProspectiveParent($parent);

        $activeCasesCount = $parent->adoptionCasesAsProspectiveParent()
            ->whereNotIn('status', $this->closedCaseStatuses())
            ->count();

        if ($activeCasesCount > 0) {
            return back()->with('error', 'This parent cannot be deleted because they still have an active adoption case.');
        }

        $parent->delete();

        return redirect()
            ->route('admin.parents.index')
            ->with('success', 'Parent account deleted successfully.');
    }

    private function validateParent(Request $request, ?User $parent = null): array
    {
        $isUpdate = $parent !== null;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($parent?->id),
            ],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['active', 'inactive', 'pending'])],

            'password' => [
                $isUpdate ? 'nullable' : 'required',
                'string',
                'min:8',
                'max:255',
                'confirmed',
            ],

            'preferred_child_sex' => ['required', Rule::in(['any', 'male', 'female'])],
            'min_child_age' => ['nullable', 'integer', 'min:0', 'max:18'],
            'max_child_age' => ['nullable', 'integer', 'min:0', 'max:18', 'gte:min_child_age'],
            'open_to_special_needs' => ['nullable', 'boolean'],
            'home_study_verified' => ['nullable', 'boolean'],
            'financial_capacity_score' => ['required', 'integer', 'min:0', 'max:100'],
            'housing_score' => ['required', 'integer', 'min:0', 'max:100'],
            'parenting_capacity_score' => ['required', 'integer', 'min:0', 'max:100'],
            'matching_notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function profilePayload(array $validated, Request $request, int $userId): array
    {
        return [
            'user_id' => $userId,
            'preferred_child_sex' => $validated['preferred_child_sex'],
            'min_child_age' => $validated['min_child_age'],
            'max_child_age' => $validated['max_child_age'],
            'open_to_special_needs' => $request->boolean('open_to_special_needs'),
            'home_study_verified' => $request->boolean('home_study_verified'),
            'financial_capacity_score' => $validated['financial_capacity_score'],
            'housing_score' => $validated['housing_score'],
            'parenting_capacity_score' => $validated['parenting_capacity_score'],
            'matching_notes' => $validated['matching_notes'] ?? null,
        ];
    }

    private function ensureProspectiveParent(User $parent): void
    {
        if (!$parent->hasRole('prospective_parent')) {
            abort(404);
        }
    }
}
