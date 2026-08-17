<?php

namespace App\Services;

use App\Models\AdoptionCase;
use App\Models\ExternalReviewerCaseAccess;
use App\Models\User;

class ExternalReviewerAccessService
{
    public function authorizeCaseForActiveReviewers(AdoptionCase $adoptionCase, ?int $authorizedBy = null): void
    {
        User::query()
            ->whereHas('role', fn ($query) => $query->where('slug', 'external_reviewer'))
            ->where('status', 'active')
            ->pluck('id')
            ->each(fn (int $reviewerId) => $this->grantAccess(
                $reviewerId,
                $adoptionCase->id,
                $authorizedBy
            ));
    }

    public function ensureReviewerCanAccessExistingCases(User $reviewer): void
    {
        if (!$reviewer->isExternalReviewer() || $reviewer->status !== 'active') {
            return;
        }

        AdoptionCase::query()
            ->select(['id', 'created_by'])
            ->whereDoesntHave('reviewerAccesses', function ($query) use ($reviewer) {
                $query->where('reviewer_id', $reviewer->id);
            })
            ->chunkById(100, function ($cases) use ($reviewer) {
                foreach ($cases as $adoptionCase) {
                    $this->grantAccess(
                        $reviewer->id,
                        $adoptionCase->id,
                        $adoptionCase->created_by
                    );
                }
            });
    }

    private function grantAccess(int $reviewerId, int $adoptionCaseId, ?int $authorizedBy): void
    {
        ExternalReviewerCaseAccess::firstOrCreate(
            [
                'reviewer_id' => $reviewerId,
                'adoption_case_id' => $adoptionCaseId,
            ],
            [
                'access_status' => 'active',
                'can_view_summary' => true,
                'can_view_document_status' => true,
                'can_submit_notes' => true,
                'can_make_decision' => true,
                'authorized_by' => $authorizedBy,
                'authorized_at' => now(),
                'remarks' => 'Automatically authorized for external review.',
            ]
        );
    }
}
