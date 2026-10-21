<?php

namespace App\Support;

use App\Models\AdoptionCase;
use Illuminate\Support\Collection;

class AdoptionTimeline
{
    public static function forStatus(?string $status): Collection
    {
        // Closing or cancelling a case is an outcome, not an adoption milestone.
        if (in_array($status, ['closed', 'cancelled'], true)) {
            return collect([[
                'status' => $status,
                'label' => AdoptionCase::STATUSES[$status],
                'state' => 'stopped',
            ]]);
        }

        $stages = collect(AdoptionCase::STATUSES)->except(['closed', 'cancelled']);
        $currentIndex = array_search($status, $stages->keys()->all(), true);

        if ($currentIndex === false) {
            return collect();
        }

        return $stages->map(function (string $label, string $stage) use ($stages, $status, $currentIndex): array {
            $stageIndex = array_search($stage, $stages->keys()->all(), true);

            return [
                'status' => $stage,
                'label' => $label,
                'state' => $status === 'finalized' || $stageIndex < $currentIndex
                    ? 'completed'
                    : ($stageIndex === $currentIndex ? 'current' : 'upcoming'),
            ];
        })->values();
    }

    public static function progress(Collection $timeline): ?int
    {
        if ($timeline->isEmpty() || $timeline->contains('state', 'stopped')) {
            return null;
        }

        if ($timeline->every(fn (array $step): bool => $step['state'] === 'completed')) {
            return 100;
        }

        return (int) round(
            $timeline->where('state', 'completed')->count() / max(1, $timeline->count() - 1) * 100
        );
    }
}
