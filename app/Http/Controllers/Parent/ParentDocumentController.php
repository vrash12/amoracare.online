<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\AdoptionCase;
use App\Models\AdoptionCaseDocument;
use App\Services\AdoptionDocumentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ParentDocumentController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $adoptionCase = AdoptionCase::with([
            'documents' => function ($query) {
                $query->where('requirement_scope', 'parent')
                    ->orderBy('document_name');
            },
        ])
            ->where('prospective_parent_id', $user->id)
            ->latest()
            ->first();

        $documents = collect();

        $requiredDocumentsCount = 0;
        $submittedDocumentsCount = 0;
        $verifiedDocumentsCount = 0;
        $documentProgressPercent = 0;

        if ($adoptionCase) {
            $documents = $adoptionCase->documents;

            $requiredDocumentsCount = $documents->count();

            $submittedDocumentsCount = $documents
                ->whereIn('status', ['submitted', 'under_review', 'verified'])
                ->count();

            $verifiedDocumentsCount = $documents
                ->where('status', 'verified')
                ->count();

            $documentProgressPercent = $requiredDocumentsCount > 0
                ? round(($verifiedDocumentsCount / $requiredDocumentsCount) * 100)
                : 0;
        }

        return view('parent.documents.index', compact(
            'user',
            'adoptionCase',
            'documents',
            'requiredDocumentsCount',
            'submittedDocumentsCount',
            'verifiedDocumentsCount',
            'documentProgressPercent'
        ));
    }

    public function upload(Request $request, AdoptionCaseDocument $document): RedirectResponse
    {
        $this->authorizeParentDocument($document);

        if ($this->documentReplacementIsLocked($document)) {
            return redirect()
                ->route('parent.documents.index')
                ->with('error', 'Documents can no longer be replaced because this case has already been approved or closed.');
        }

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,doc,docx',
                'max:5120',
            ],
        ]);

        $file = $validated['file'];
        $oldPath = $document->file_path;
        $isReplacement = filled($oldPath);
        $path = $file->store('adoption/parent-documents', 'local');

        try {
            $document->update([
                'status' => 'submitted',
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'remarks' => null,
                'uploaded_by' => Auth::id(),
                'verified_by' => null,
                'verified_at' => null,
                'updated_by' => Auth::id(),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('local')->delete($oldPath);
        }

        return redirect()
            ->route('parent.documents.index')
            ->with(
                'success',
                $isReplacement
                    ? 'Document replaced successfully and returned to Submitted status for a new review.'
                    : 'Document uploaded successfully. Please wait for staff verification.'
            );
    }

    public function download(
        AdoptionCaseDocument $document,
        AdoptionDocumentStorageService $documentStorage
    ): Response {
        $this->authorizeParentDocument($document);

        if (! $documentStorage->exists($document->file_path)) {
            return redirect()
                ->route('parent.documents.index')
                ->with('error', 'The uploaded file is missing or no longer available. Please upload the document again.');
        }

        return $documentStorage->download(
            $document->file_path,
            $document->original_filename ?? 'document'
        );
    }

    private function authorizeParentDocument(AdoptionCaseDocument $document): void
    {
        $user = Auth::user();

        $document->loadMissing('adoptionCase');

        if (
            ! $document->adoptionCase ||
            $document->adoptionCase->prospective_parent_id !== $user->id ||
            $document->requirement_scope !== 'parent'
        ) {
            abort(403, 'You are not allowed to access this document.');
        }
    }

    private function documentReplacementIsLocked(AdoptionCaseDocument $document): bool
    {
        $adoptionCase = $document->adoptionCase;

        return $document->status === 'not_required'
            || $adoptionCase?->racco_review_status === 'approved'
            || in_array($adoptionCase?->status, ['finalized', 'closed', 'cancelled'], true);
    }
}
