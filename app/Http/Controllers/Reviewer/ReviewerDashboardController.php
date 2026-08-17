<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use App\Models\ExternalReviewerCaseAccess;
use App\Services\ExternalReviewerAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReviewerDashboardController extends Controller
{
    public function index(ExternalReviewerAccessService $reviewerAccessService): View
    {
        $user = Auth::user();
        $reviewerAccessService->ensureReviewerCanAccessExistingCases($user);

        $baseQuery = ExternalReviewerCaseAccess::query()
            ->where('reviewer_id', $user->id)
            ->where('access_status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });

        $authorizedCases = (clone $baseQuery)->count();

        $pendingReviews = (clone $baseQuery)
            ->whereHas('adoptionCase', function ($query) {
                $query->whereNotIn('status', ['finalized', 'closed', 'cancelled']);
            })
            ->count();

        $submittedNotes = \App\Models\AdoptionCaseNote::query()
            ->where('created_by', $user->id)
            ->where('note_type', 'external_review')
            ->count();

        $documentChecks = (clone $baseQuery)
            ->where('can_view_document_status', true)
            ->count();

        return view('reviewer.dashboard', compact(
            'authorizedCases',
            'pendingReviews',
            'submittedNotes',
            'documentChecks'
        ));
    }
}
