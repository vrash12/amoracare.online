<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\AdoptionCase;
use App\Models\AdoptionCaseDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        if ($document->status === 'verified') {
            return redirect()
                ->route('parent.documents.index')
                ->with('error', 'This document has already been verified and can no longer be replaced.');
        }

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,doc,docx',
                'max:5120',
            ],
        ]);

        if ($document->file_path) {
            Storage::disk('local')->delete($document->file_path);
        }

        $file = $validated['file'];

        $path = $file->store('adoption/parent-documents', 'local');

        $document->update([
            'status' => 'submitted',
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => Auth::id(),
            'verified_by' => null,
            'verified_at' => null,
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('parent.documents.index')
            ->with('success', 'Document uploaded successfully. Please wait for staff verification.');
    }

    public function download(AdoptionCaseDocument $document): StreamedResponse
    {
        $this->authorizeParentDocument($document);

        if (!$document->file_path || !Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_filename ?? 'document'
        );
    }

    private function authorizeParentDocument(AdoptionCaseDocument $document): void
    {
        $user = Auth::user();

        $document->loadMissing('adoptionCase');

        if (
            !$document->adoptionCase ||
            $document->adoptionCase->prospective_parent_id !== $user->id ||
            $document->requirement_scope !== 'parent'
        ) {
            abort(403, 'You are not allowed to access this document.');
        }
    }
}