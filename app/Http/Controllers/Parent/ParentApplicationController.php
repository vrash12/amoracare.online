<?php
// app/Http/Controllers/Parent/ParentApplicationController.php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\AdoptionCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ParentApplicationController extends Controller
{
    public function index(): View
    {
        $user = Auth::user()->load([
            'role',
            'matchingProfile',
        ]);

        $parentMatchingProfile = $user->matchingProfile;

        $adoptionCase = AdoptionCase::with([
                'prospectiveParent.matchingProfile',
                'assignedSocialWorker',
                'documents' => function ($query) {
                    $query->orderBy('requirement_scope')
                        ->orderBy('document_name');
                },
                'notes.creator',
            ])
            ->where('prospective_parent_id', $user->id)
            ->latest()
            ->first();

        $parentDocuments = collect();
        $parentUpdates = collect();

        $requiredDocumentsCount = 0;
        $submittedDocumentsCount = 0;
        $verifiedDocumentsCount = 0;
        $documentProgressPercent = 0;

        $applicationTimeline = collect();

        if ($adoptionCase) {
            $parentDocuments = $adoptionCase->documents
                ->where('requirement_scope', 'parent')
                ->values();

            $requiredDocumentsCount = $parentDocuments->count();

            $submittedDocumentsCount = $parentDocuments
                ->whereIn('status', ['submitted', 'under_review', 'verified'])
                ->count();

            $verifiedDocumentsCount = $parentDocuments
                ->where('status', 'verified')
                ->count();

            $documentProgressPercent = $requiredDocumentsCount > 0
                ? round(($verifiedDocumentsCount / $requiredDocumentsCount) * 100)
                : 0;

            $parentUpdates = $adoptionCase->notes
                ->where('visibility', 'parent_update')
                ->sortByDesc('created_at')
                ->values();

            $applicationTimeline = $this->buildApplicationTimeline($adoptionCase);
        }

        return view('parent.application.index', compact(
            'user',
            'parentMatchingProfile',
            'adoptionCase',
            'parentDocuments',
            'parentUpdates',
            'requiredDocumentsCount',
            'submittedDocumentsCount',
            'verifiedDocumentsCount',
            'documentProgressPercent',
            'applicationTimeline'
        ));
    }

    private function buildApplicationTimeline(AdoptionCase $adoptionCase)
    {
        $statuses = array_keys(AdoptionCase::STATUSES);
        $currentIndex = array_search($adoptionCase->status, $statuses, true);

        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        return collect(AdoptionCase::STATUSES)->map(function ($label, $status) use ($statuses, $currentIndex) {
            $statusIndex = array_search($status, $statuses, true);

            if ($statusIndex < $currentIndex) {
                $state = 'completed';
            } elseif ($statusIndex === $currentIndex) {
                $state = 'current';
            } else {
                $state = 'upcoming';
            }

            return [
                'status' => $status,
                'label' => $label,
                'state' => $state,
            ];
        })->values();
    }
}