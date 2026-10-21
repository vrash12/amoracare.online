<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\AdoptionCase;
use App\Models\AdoptionCaseDocument;
use App\Services\AdoptionDocumentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        if (! $document->canBeUploadedByParent()) {
            return $this->uploadNotAllowed();
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
        $path = null;

        try {
            $uploaded = DB::transaction(function () use ($document, $file, &$path): bool {
                $lockedDocument = AdoptionCaseDocument::query()
                    ->lockForUpdate()
                    ->findOrFail($document->id);

                $this->authorizeParentDocument($lockedDocument);

                // Recheck after locking so simultaneous uploads cannot replace the first submission.
                if (! $lockedDocument->canBeUploadedByParent()) {
                    return false;
                }

                $path = $file->store('adoption/parent-documents', 'local');

                $lockedDocument->update([
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

                return true;
            });
        } catch (\Throwable $exception) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }

        if (! $uploaded) {
            return $this->uploadNotAllowed();
        }

        return redirect()
            ->route('parent.documents.index')
            ->with('success', 'Document uploaded successfully. Please wait for staff verification.');
    }

    public function download(
        AdoptionCaseDocument $document,
        AdoptionDocumentStorageService $documentStorage
    ): Response {
        $this->authorizeParentDocument($document);

        if (! $documentStorage->exists($document->file_path)) {
            return redirect()
                ->route('parent.documents.index')
                ->with('error', 'The uploaded file is missing or no longer available. Please contact authorized AmoraCare staff for assistance.');
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

    private function uploadNotAllowed(): RedirectResponse
    {
        return redirect()
            ->route('parent.documents.index')
            ->with('error', 'Only pending documents without a submitted file can be uploaded to an open case. Submitted documents cannot be replaced. Please contact authorized AmoraCare staff if a correction is needed.');
    }
}
