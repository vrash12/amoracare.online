<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\AdoptionCase;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ParentDashboardController extends Controller
{
    public function index(): View
    {
        /** @var User $parentUser */
        $parentUser = Auth::user();

        abort_unless(
            $parentUser && $parentUser->hasRole('prospective_parent'),
            403,
            'Only prospective adoptive parents can access this portal.'
        );

        $parentUser->loadMissing([
            'role',
            'matchingProfile',
        ]);

        $adoptionCase = AdoptionCase::with([
                'documents.uploader',
                'documents.verifier',
                'notes.creator',
                'assignedSocialWorker',
            ])
            ->where('prospective_parent_id', $parentUser->id)
            ->latest('created_at')
            ->first();

        $requiredDocuments = collect();
        $submittedDocumentsCount = 0;
        $verifiedDocumentsCount = 0;
        $requiredDocumentsCount = 0;
        $documentProgressPercent = 0;
        $parentUpdates = collect();

        if ($adoptionCase) {
            $requiredDocuments = $adoptionCase->documents
                ->where('requirement_scope', 'parent')
                ->sortBy('document_name')
                ->values();

            $requiredDocumentsCount = $requiredDocuments->count();

            $submittedDocumentsCount = $requiredDocuments
                ->whereIn('status', [
                    'submitted',
                    'under_review',
                    'verified',
                ])
                ->count();

            $verifiedDocumentsCount = $requiredDocuments
                ->where('status', 'verified')
                ->count();

            $documentProgressPercent = $requiredDocumentsCount > 0
                ? (int) round(
                    ($verifiedDocumentsCount / $requiredDocumentsCount) * 100
                )
                : 0;

            $parentUpdates = $adoptionCase->notes
                ->where('visibility', 'parent_update')
                ->sortByDesc('created_at')
                ->take(5)
                ->values();
        }

        return view('parent.dashboard', [
            'parentUser' => $parentUser,
            'adoptionCase' => $adoptionCase,
            'requiredDocuments' => $requiredDocuments,
            'submittedDocumentsCount' => $submittedDocumentsCount,
            'verifiedDocumentsCount' => $verifiedDocumentsCount,
            'requiredDocumentsCount' => $requiredDocumentsCount,
            'documentProgressPercent' => $documentProgressPercent,
            'parentUpdates' => $parentUpdates,
        ]);
    }
}