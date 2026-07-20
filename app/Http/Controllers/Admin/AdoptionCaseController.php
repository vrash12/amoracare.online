<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdoptionCase;
use App\Models\AdoptionCaseDocument;
use App\Models\AdoptionCaseNote;
use App\Models\Child;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdoptionCaseController extends Controller
{
    private function authorizeAdmin(): void
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            abort(403, 'Only administrators can access adoption case management.');
        }
    }

    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $search = $request->input('search');
        $status = $request->input('status');
        $priority = $request->input('priority');

        $adoptionCases = AdoptionCase::with([
                'child',
                'prospectiveParent',
                'assignedSocialWorker',
                'documents',
                'creator',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('case_code', 'like', "%{$search}%")
                        ->orWhereHas('child', function ($childQuery) use ($search) {
                            $childQuery->where('child_code', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('prospectiveParent', function ($parentQuery) use ($search) {
                            $parentQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($priority, function ($query) use ($priority) {
                $query->where('priority', $priority);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $statuses = AdoptionCase::STATUSES;
        $priorities = AdoptionCase::PRIORITIES;

        return view('admin.adoption_cases.index', compact(
            'adoptionCases',
            'search',
            'status',
            'priority',
            'statuses',
            'priorities'
        ));
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        $children = Child::orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $prospectiveParents = User::whereHas('role', function ($query) {
                $query->where('slug', 'prospective_parent');
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $staffUsers = User::whereHas('role', function ($query) {
                $query->where('slug', 'admin');
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $caseTypes = AdoptionCase::CASE_TYPES;
        $statuses = AdoptionCase::STATUSES;
        $priorities = AdoptionCase::PRIORITIES;

        return view('admin.adoption_cases.create', compact(
            'children',
            'prospectiveParents',
            'staffUsers',
            'caseTypes',
            'statuses',
            'priorities'
        ));
    }


public function store(Request $request): RedirectResponse
{
    $this->authorizeAdmin();

    $validated = $this->validateAdoptionCase($request);

    DB::transaction(function () use ($validated) {
        $validated['case_code'] = $this->generateCaseCode();
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $adoptionCase = AdoptionCase::create($validated);

        foreach (AdoptionCase::DEFAULT_DOCUMENTS as $document) {
            $adoptionCase->documents()->create([
                'document_name' => $document['document_name'],
                'document_type' => $document['document_type'],
                'requirement_scope' => $document['requirement_scope'],
                'status' => 'pending',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        }

        $adoptionCase->notes()->create([
            'note_type' => 'status_update',
            'visibility' => 'internal',
            'title' => 'Case Created',
            'body' => 'Adoption case was created and initial document checklist was generated.',
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    });

    return redirect()
        ->route('admin.adoption-cases.index')
        ->with('success', 'Adoption case created successfully.');
}


    public function show(AdoptionCase $adoptionCase): View
    {
        $this->authorizeAdmin();

        $adoptionCase->load([
            'child',
            'prospectiveParent',
            'assignedSocialWorker',
            'documents.verifier',
            'documents.uploader',
            'notes.creator',
            'creator',
            'updater',
        ]);

        $documentStatuses = AdoptionCaseDocument::STATUSES;
        $noteTypes = AdoptionCaseNote::NOTE_TYPES;
        $noteVisibilities = AdoptionCaseNote::VISIBILITIES;

        return view('admin.adoption_cases.show', compact(
            'adoptionCase',
            'documentStatuses',
            'noteTypes',
            'noteVisibilities'
        ));
    }

    public function edit(AdoptionCase $adoptionCase): View
    {
        $this->authorizeAdmin();

        $children = Child::orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $prospectiveParents = User::whereHas('role', function ($query) {
                $query->where('slug', 'prospective_parent');
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $staffUsers = User::whereHas('role', function ($query) {
                $query->where('slug', 'admin');
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $caseTypes = AdoptionCase::CASE_TYPES;
        $statuses = AdoptionCase::STATUSES;
        $priorities = AdoptionCase::PRIORITIES;

        return view('admin.adoption_cases.edit', compact(
            'adoptionCase',
            'children',
            'prospectiveParents',
            'staffUsers',
            'caseTypes',
            'statuses',
            'priorities'
        ));
    }

    public function update(Request $request, AdoptionCase $adoptionCase): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $this->validateAdoptionCase($request, $adoptionCase);
        $validated['updated_by'] = Auth::id();

        $oldStatus = $adoptionCase->status;

        DB::transaction(function () use ($adoptionCase, $validated, $oldStatus) {
            $adoptionCase->update($validated);

            if ($oldStatus !== $validated['status']) {
                $adoptionCase->notes()->create([
                    'note_type' => 'status_update',
                    'visibility' => 'internal',
                    'title' => 'Case Status Updated',
                    'body' => 'Case status changed from ' .
                        (AdoptionCase::STATUSES[$oldStatus] ?? $oldStatus) .
                        ' to ' .
                        (AdoptionCase::STATUSES[$validated['status']] ?? $validated['status']) .
                        '.',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }
        });

        return redirect()
            ->route('admin.adoption-cases.show', $adoptionCase)
            ->with('success', 'Adoption case updated successfully.');
    }

    public function destroy(AdoptionCase $adoptionCase): RedirectResponse
    {
        $this->authorizeAdmin();

        $adoptionCase->update([
            'updated_by' => Auth::id(),
        ]);

        $adoptionCase->delete();

        return redirect()
            ->route('admin.adoption-cases.index')
            ->with('success', 'Adoption case deleted successfully.');
    }

    public function storeNote(Request $request, AdoptionCase $adoptionCase): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'note_type' => ['required', Rule::in(array_keys(AdoptionCaseNote::NOTE_TYPES))],
            'visibility' => ['required', Rule::in(array_keys(AdoptionCaseNote::VISIBILITIES))],
            'title' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string'],
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        $adoptionCase->notes()->create($validated);

        return redirect()
            ->route('admin.adoption-cases.show', $adoptionCase)
            ->with('success', 'Case note added successfully.');
    }

    public function updateDocument(
        Request $request,
        AdoptionCase $adoptionCase,
        AdoptionCaseDocument $document
    ): RedirectResponse {
        $this->authorizeAdmin();

        if ($document->adoption_case_id !== $adoptionCase->id) {
            abort(404);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(AdoptionCaseDocument::STATUSES))],
            'expiry_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
            'file' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,doc,docx',
                'max:5120',
            ],
        ]);

        DB::transaction(function () use ($request, $validated, $document) {
            if ($request->hasFile('file')) {
                if ($document->file_path) {
                    Storage::disk('local')->delete($document->file_path);
                }

                $file = $request->file('file');

                $validated['file_path'] = $file->store('adoption/documents', 'local');
                $validated['original_filename'] = $file->getClientOriginalName();
                $validated['mime_type'] = $file->getMimeType();
                $validated['file_size'] = $file->getSize();
                $validated['uploaded_by'] = Auth::id();

                if ($validated['status'] === 'pending') {
                    $validated['status'] = 'submitted';
                }
            }

            if ($validated['status'] === 'verified') {
                $validated['verified_by'] = Auth::id();
                $validated['verified_at'] = now();
            } else {
                $validated['verified_by'] = null;
                $validated['verified_at'] = null;
            }

            $validated['updated_by'] = Auth::id();

            $document->update($validated);
        });

        return redirect()
            ->route('admin.adoption-cases.show', $adoptionCase)
            ->with('success', 'Document checklist updated successfully.');
    }

    private function validateAdoptionCase(Request $request, ?AdoptionCase $adoptionCase = null): array
    {
        return $request->validate([
        
            'child_id' => ['required', 'exists:children,id'],
            'prospective_parent_id' => ['nullable', 'exists:users,id'],
            'assigned_social_worker_id' => ['nullable', 'exists:users,id'],
            'case_type' => ['required', Rule::in(array_keys(AdoptionCase::CASE_TYPES))],
            'status' => ['required', Rule::in(array_keys(AdoptionCase::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(AdoptionCase::PRIORITIES))],
            'opened_at' => ['nullable', 'date'],
            'target_completion_date' => ['nullable', 'date', 'after_or_equal:opened_at'],
            'closed_at' => ['nullable', 'date', 'after_or_equal:opened_at'],
            'summary' => ['nullable', 'string'],
            'confidential_notes' => ['nullable', 'string'],
        ]);
    }
private function generateCaseCode(): string
{
    $year = now()->format('Y');

    $latestCode = AdoptionCase::withTrashed()
        ->where('case_code', 'like', "AC-{$year}-%")
        ->orderByRaw("CAST(SUBSTRING(case_code, -5) AS UNSIGNED) DESC")
        ->value('case_code');

    $nextNumber = 1;

    if ($latestCode) {
        $lastNumber = (int) substr($latestCode, -5);
        $nextNumber = $lastNumber + 1;
    }

    do {
        $caseCode = 'AC-' . $year . '-' . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
        $exists = AdoptionCase::withTrashed()
            ->where('case_code', $caseCode)
            ->exists();

        $nextNumber++;
    } while ($exists);

    return $caseCode;
}

}