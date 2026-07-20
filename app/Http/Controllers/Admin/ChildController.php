<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdoptionMatchingResult;
use App\Models\Child;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChildController extends Controller
{
    private function authorizeAdmin(): void
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            abort(403, 'Only administrators can access child profile management.');
        }
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $search = $request->input('search');
        $caseStatus = $request->input('case_status');
        $eligibilityStatus = $request->input('adoption_eligibility_status');

        $children = Child::with(['creator', 'updater'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('child_code', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('nickname', 'like', "%{$search}%");
                });
            })
            ->when($caseStatus, function ($query) use ($caseStatus) {
                $query->where('case_status', $caseStatus);
            })
            ->when($eligibilityStatus, function ($query) use ($eligibilityStatus) {
                $query->where('adoption_eligibility_status', $eligibilityStatus);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $childIds = $children->getCollection()->pluck('id');

        $latestMatchingResults = AdoptionMatchingResult::with([
                'run',
                'prospectiveParent',
            ])
            ->whereIn('child_id', $childIds)
            ->latest()
            ->get()
            ->unique('child_id')
            ->keyBy('child_id');

        $caseStatuses = Child::CASE_STATUSES;
        $eligibilityStatuses = Child::ELIGIBILITY_STATUSES;

        return view('admin.children.index', compact(
            'children',
            'search',
            'caseStatus',
            'eligibilityStatus',
            'caseStatuses',
            'eligibilityStatuses',
            'latestMatchingResults'
        ));
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        $caseStatuses = Child::CASE_STATUSES;
        $eligibilityStatuses = Child::ELIGIBILITY_STATUSES;

        return view('admin.children.create', compact('caseStatuses', 'eligibilityStatuses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $this->validateChild($request);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('children/photos', 'public');
        }

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        Child::create($validated);

        return redirect()
            ->route('admin.children.index')
            ->with('success', 'Child profile created successfully.');
    }

    public function show(Child $child): View
    {
        $this->authorizeAdmin();

        $child->load(['creator', 'updater']);

        return view('admin.children.show', compact('child'));
    }

    public function edit(Child $child): View
    {
        $this->authorizeAdmin();

        $caseStatuses = Child::CASE_STATUSES;
        $eligibilityStatuses = Child::ELIGIBILITY_STATUSES;

        return view('admin.children.edit', compact('child', 'caseStatuses', 'eligibilityStatuses'));
    }

    public function update(Request $request, Child $child): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $this->validateChild($request, $child);

        if ($request->boolean('remove_photo') && $child->photo_path) {
            Storage::disk('public')->delete($child->photo_path);
            $validated['photo_path'] = null;
        }

        if ($request->hasFile('photo')) {
            if ($child->photo_path) {
                Storage::disk('public')->delete($child->photo_path);
            }

            $validated['photo_path'] = $request->file('photo')->store('children/photos', 'public');
        }

        $validated['updated_by'] = Auth::id();

        $child->update($validated);

        return redirect()
            ->route('admin.children.index')
            ->with('success', 'Child profile updated successfully.');
    }

    public function destroy(Child $child): RedirectResponse
    {
        $this->authorizeAdmin();

        $child->update([
            'updated_by' => Auth::id(),
        ]);

        $child->delete();

        return redirect()
            ->route('admin.children.index')
            ->with('success', 'Child profile deleted successfully.');
    }

    private function validateChild(Request $request, ?Child $child = null): array
    {
        return $request->validate([
            'child_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('children', 'child_code')->ignore($child?->id),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'sex' => ['required', Rule::in(['male', 'female'])],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'current_location' => ['nullable', 'string', 'max:255'],
            'admission_date' => ['nullable', 'date', 'before_or_equal:today'],
            'admission_reason' => ['nullable', 'string', 'max:255'],
            'case_status' => ['required', Rule::in(array_keys(Child::CASE_STATUSES))],
            'adoption_eligibility_status' => ['required', Rule::in(array_keys(Child::ELIGIBILITY_STATUSES))],
            'health_status' => ['nullable', 'string'],
            'educational_level' => ['nullable', 'string', 'max:150'],
            'school_name' => ['nullable', 'string', 'max:150'],
            'is_special_needs' => ['nullable', 'boolean'],
            'background_summary' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);
    }
}