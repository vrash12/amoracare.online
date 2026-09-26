<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use App\Models\AdoptionCaseDocument;
use App\Models\ExternalReviewerCaseAccess;
use App\Services\ExternalReviewerAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AuthorizedCaseController extends Controller
{
    public function index(ExternalReviewerAccessService $reviewerAccessService): View
    {
        $user = Auth::user();
        $reviewerAccessService->ensureReviewerCanAccessExistingCases($user);

        $caseAccesses = ExternalReviewerCaseAccess::with([
                'adoptionCase.child',
                'adoptionCase.prospectiveParent',
                'adoptionCase.assignedSocialWorker',
                'adoptionCase.documents',
                'authorizer',
            ])
            ->where('reviewer_id', $user->id)
            ->where('access_status', 'active')
            ->whereHas('adoptionCase')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->latest()
            ->paginate(10);

        return view('reviewer.cases.index', compact('caseAccesses'));
    }

    public function show(ExternalReviewerCaseAccess $access): View
    {
        $this->authorizeAccess($access);

        $access->load([
            'adoptionCase.child',
            'adoptionCase.prospectiveParent',
            'adoptionCase.assignedSocialWorker',
            'adoptionCase.documents.uploader',
            'adoptionCase.documents.verifier',
            'adoptionCase.notes.creator',
            'authorizer',
        ]);

        $adoptionCase = $access->adoptionCase;

        $reviewerNotes = $adoptionCase->notes
            ->where('visibility', 'reviewer_summary')
            ->sortByDesc('created_at')
            ->values();

        $documents = $adoptionCase->documents
            ->sortBy('document_name')
            ->values();

        $documentGroups = [
            'parent' => [
                'label' => 'Parent Applicant Requirements',
                'description' => 'Documents submitted or required from the prospective adoptive parent.',
                'documents' => $documents->where('requirement_scope', 'parent')->values(),
            ],
            'child' => [
                'label' => 'Child Legal Availability Requirements',
                'description' => 'Documents supporting the child record and legal availability for adoption.',
                'documents' => $documents->where('requirement_scope', 'child')->values(),
            ],
            'case' => [
                'label' => 'Case, Petition, Placement, and Finalization Requirements',
                'description' => 'Documents related to petition, placement, supervision, and finalization.',
                'documents' => $documents->where('requirement_scope', 'case')->values(),
            ],
        ];

        $totalDocuments = $documents->count();
        $verifiedDocuments = $documents->where('status', 'verified')->count();
        $pendingDocuments = $documents->where('status', 'pending')->count();
        $submittedDocuments = $documents->where('status', 'submitted')->count();
        $underReviewDocuments = $documents->where('status', 'under_review')->count();
        $rejectedDocuments = $documents->where('status', 'rejected')->count();
        $expiredDocuments = $documents->where('status', 'expired')->count();

        $documentProgressPercent = $totalDocuments > 0
            ? round(($verifiedDocuments / $totalDocuments) * 100)
            : 0;

        $documentSummary = [
            'total' => $totalDocuments,
            'verified' => $verifiedDocuments,
            'pending' => $pendingDocuments,
            'submitted' => $submittedDocuments,
            'under_review' => $underReviewDocuments,
            'rejected' => $rejectedDocuments,
            'expired' => $expiredDocuments,
            'progress_percent' => $documentProgressPercent,
        ];

        return view('reviewer.cases.show', compact(
            'access',
            'adoptionCase',
            'reviewerNotes',
            'documentGroups',
            'documentSummary'
        ));
    }

    public function storeNote(Request $request, ExternalReviewerCaseAccess $access): RedirectResponse
    {
        $this->authorizeAccess($access);

        if (!$access->can_submit_notes) {
            abort(403, 'You are not authorized to submit notes for this case.');
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $access->adoptionCase->notes()->create([
            'note_type' => 'external_review',
            'visibility' => 'reviewer_summary',
            'title' => $validated['title'] ?? 'External Reviewer Note',
            'body' => $validated['body'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('reviewer.cases.show', $access)
            ->with('success', 'Review note submitted successfully.');
    }

    public function downloadDocument(ExternalReviewerCaseAccess $access, AdoptionCaseDocument $document)
    {
        $this->authorizeDocumentAccess($access, $document);

        if (!$access->can_view_document_status) {
            abort(403, 'You are not authorized to view documents for this case.');
        }

        if (!$this->documentFileExists($document)) {
            return redirect()
                ->route('reviewer.cases.show', $access)
                ->with('error', 'The submitted file is missing or no longer available.');
        }

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_filename ?? $document->document_name
        );
    }

    public function acceptDocument(Request $request, ExternalReviewerCaseAccess $access, AdoptionCaseDocument $document): RedirectResponse
    {
        $this->authorizeDocumentAccess($access, $document);

        if (!$access->can_make_decision) {
            abort(403, 'You are not authorized to accept submitted documents.');
        }

        if (!$this->documentFileExists($document)) {
            return redirect()
                ->route('reviewer.cases.show', $access)
                ->with('error', 'This document cannot be accepted because the submitted file is missing or no longer available.');
        }

        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($access, $document, $validated) {
            $document->update([
                'status' => 'verified',
                'remarks' => $validated['remarks'] ?? 'Submitted file accepted by external reviewer.',
                'verified_by' => Auth::id(),
                'verified_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $access->adoptionCase->notes()->create([
                'note_type' => 'document_review',
                'visibility' => 'reviewer_summary',
                'title' => 'Document Accepted by Reviewer',
                'body' => 'The submitted file for "' . $document->document_name . '" was accepted.'
                    . (!empty($validated['remarks']) ? "\n\nRemarks: " . $validated['remarks'] : ''),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $access->adoptionCase->notes()->create([
                'note_type' => 'status_update',
                'visibility' => 'parent_update',
                'title' => 'Document Accepted',
                'body' => 'Your submitted document "' . $document->document_name . '" has been accepted.',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        });

        return redirect()
            ->route('reviewer.cases.show', $access)
            ->with('success', 'Submitted file accepted successfully.');
    }

    public function rejectDocument(Request $request, ExternalReviewerCaseAccess $access, AdoptionCaseDocument $document): RedirectResponse
    {
        $this->authorizeDocumentAccess($access, $document);

        if (!$access->can_make_decision) {
            abort(403, 'You are not authorized to reject submitted documents.');
        }

        if (!$this->documentFileExists($document)) {
            return redirect()
                ->route('reviewer.cases.show', $access)
                ->with('error', 'This document cannot be rejected because the submitted file is missing or no longer available.');
        }

        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($access, $document, $validated) {
            $document->update([
                'status' => 'rejected',
                'remarks' => $validated['remarks'],
                'verified_by' => null,
                'verified_at' => null,
                'updated_by' => Auth::id(),
            ]);

            $access->adoptionCase->notes()->create([
                'note_type' => 'document_review',
                'visibility' => 'reviewer_summary',
                'title' => 'Document Rejected by Reviewer',
                'body' => 'The submitted file for "' . $document->document_name . '" was rejected.'
                    . "\n\nRemarks: " . $validated['remarks'],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $access->adoptionCase->notes()->create([
                'note_type' => 'status_update',
                'visibility' => 'parent_update',
                'title' => 'Document Needs Revision',
                'body' => 'Your submitted document "' . $document->document_name . '" needs revision. Remarks: ' . $validated['remarks'],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        });

        return redirect()
            ->route('reviewer.cases.show', $access)
            ->with('success', 'Submitted file rejected and returned for revision.');
    }

    public function approve(Request $request, ExternalReviewerCaseAccess $access): RedirectResponse
    {
        $this->authorizeAccess($access);

        if (!$access->can_make_decision) {
            abort(403, 'You are not authorized to approve this case.');
        }

        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($access, $validated) {
            $adoptionCase = $access->adoptionCase;

            $adoptionCase->update([
                'status' => 'matching_review',
                'racco_review_status' => 'approved',
                'racco_decided_by' => Auth::id(),
                'racco_decided_at' => now(),
                'racco_decision_remarks' => $validated['remarks'] ?? null,
            ]);

            $adoptionCase->notes()->create([
                'note_type' => 'external_review',
                'visibility' => 'reviewer_summary',
                'title' => 'RACCO Approved the Case',
                'body' => $validated['remarks'] ?? 'The case has been approved by RACCO and may proceed to the next stage.',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $adoptionCase->notes()->create([
                'note_type' => 'status_update',
                'visibility' => 'parent_update',
                'title' => 'Assessment Completed',
                'body' => 'Your application has completed the assessment stage and will proceed to the next review stage.',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        });

        return redirect()
            ->route('reviewer.cases.show', $access)
            ->with('success', 'Case approved successfully.');
    }

    public function requestChanges(Request $request, ExternalReviewerCaseAccess $access): RedirectResponse
    {
        $this->authorizeAccess($access);

        if (!$access->can_make_decision) {
            abort(403, 'You are not authorized to request changes for this case.');
        }

        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($access, $validated) {
            $adoptionCase = $access->adoptionCase;

            $adoptionCase->update([
                'status' => 'document_collection',
                'racco_review_status' => 'changes_requested',
                'racco_decided_by' => Auth::id(),
                'racco_decided_at' => now(),
                'racco_decision_remarks' => $validated['remarks'],
            ]);

            $adoptionCase->notes()->create([
                'note_type' => 'external_review',
                'visibility' => 'reviewer_summary',
                'title' => 'RACCO Requested Changes',
                'body' => $validated['remarks'],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $adoptionCase->notes()->create([
                'note_type' => 'status_update',
                'visibility' => 'parent_update',
                'title' => 'Additional Requirements Needed',
                'body' => 'Your application needs additional review or document updates. Please wait for instructions from AmoraCare staff.',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        });

        return redirect()
            ->route('reviewer.cases.show', $access)
            ->with('success', 'Change request submitted successfully.');
    }

    private function authorizeAccess(ExternalReviewerCaseAccess $access): void
    {
        if ($access->reviewer_id !== Auth::id()) {
            abort(403, 'You are not authorized to view this case.');
        }

        // Historical access rows can outlive a soft-deleted case.
        abort_unless($access->adoptionCase()->exists(), 404, 'This adoption case is no longer available.');

        if ($access->access_status !== 'active') {
            abort(403, 'This case access is no longer active.');
        }

        if ($access->expires_at && $access->expires_at->isPast()) {
            abort(403, 'This case access has expired.');
        }
    }

    private function authorizeDocumentAccess(ExternalReviewerCaseAccess $access, AdoptionCaseDocument $document): void
    {
        $this->authorizeAccess($access);

        if ((int) $document->adoption_case_id !== (int) $access->adoption_case_id) {
            abort(403, 'This document does not belong to your authorized case.');
        }
    }

    private function documentFileExists(AdoptionCaseDocument $document): bool
    {
        return (bool) $document->file_path
            && Storage::disk('local')->exists($document->file_path);
    }
}
