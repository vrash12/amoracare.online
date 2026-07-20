<?php

namespace App\Services\AdoptionMatching;

use App\Models\Child;
use App\Models\User;
use Illuminate\Support\Collection;

class GaleShapleyMatcher
{
    /**
     * Child-proposing Gale-Shapley matching.
     *
     * @param Collection<int, Child> $children
     * @param Collection<int, User> $parents
     * @return array{
     *     matches: array<int, array>,
     *     unmatched_children: array<int>,
     *     unmatched_parents: array<int>
     * }
     */
    public function match(Collection $children, Collection $parents): array
    {
        $childPreferences = [];
        $parentRankings = [];
        $scores = [];

        foreach ($children as $child) {
            $rankedParents = $parents
                ->map(function (User $parent) use ($child, &$scores) {
                    $childScore = $this->scoreParentForChild($child, $parent);
                    $parentScore = $this->scoreChildForParent($child, $parent);

                    $scores[$child->id][$parent->id] = [
                        'child_score' => $childScore,
                        'parent_score' => $parentScore,
                        'explanation' => $this->buildExplanation($child, $parent, $childScore, $parentScore),
                    ];

                    return [
                        'parent_id' => $parent->id,
                        'score' => $childScore,
                    ];
                })
                ->filter(fn (array $row) => $row['score'] > -500)
                ->sortByDesc('score')
                ->values();

            $childPreferences[$child->id] = $rankedParents
                ->pluck('parent_id')
                ->values()
                ->all();
        }

        foreach ($parents as $parent) {
            $rankedChildren = $children
                ->map(function (Child $child) use ($parent) {
                    return [
                        'child_id' => $child->id,
                        'score' => $this->scoreChildForParent($child, $parent),
                    ];
                })
                ->filter(fn (array $row) => $row['score'] > -500)
                ->sortByDesc('score')
                ->values();

            $parentRankings[$parent->id] = [];

            foreach ($rankedChildren as $rank => $row) {
                $parentRankings[$parent->id][$row['child_id']] = $rank;
            }
        }

        $freeChildren = collect(array_keys($childPreferences))->values();
        $nextProposalIndex = array_fill_keys(array_keys($childPreferences), 0);

        // parent_id => child_id
        $parentMatches = [];

        while ($freeChildren->isNotEmpty()) {
            $childId = $freeChildren->shift();

            $preferences = $childPreferences[$childId] ?? [];
            $proposalIndex = $nextProposalIndex[$childId] ?? 0;

            if ($proposalIndex >= count($preferences)) {
                continue;
            }

            $parentId = $preferences[$proposalIndex];
            $nextProposalIndex[$childId] = $proposalIndex + 1;

            if (!isset($parentMatches[$parentId])) {
                $parentMatches[$parentId] = $childId;
                continue;
            }

            $currentChildId = $parentMatches[$parentId];

            $newRank = $parentRankings[$parentId][$childId] ?? PHP_INT_MAX;
            $currentRank = $parentRankings[$parentId][$currentChildId] ?? PHP_INT_MAX;

            if ($newRank < $currentRank) {
                $parentMatches[$parentId] = $childId;

                if (($nextProposalIndex[$currentChildId] ?? 0) < count($childPreferences[$currentChildId] ?? [])) {
                    $freeChildren->push($currentChildId);
                }
            } else {
                if (($nextProposalIndex[$childId] ?? 0) < count($preferences)) {
                    $freeChildren->push($childId);
                }
            }
        }

        $matches = [];
        $matchedChildIds = [];
        $matchedParentIds = [];

        foreach ($parentMatches as $parentId => $childId) {
            $matchedChildIds[] = $childId;
            $matchedParentIds[] = $parentId;

            $rankForChild = array_search($parentId, $childPreferences[$childId] ?? [], true);
            $rankForParent = $parentRankings[$parentId][$childId] ?? null;

            $matches[] = [
                'child_id' => $childId,
                'prospective_parent_id' => $parentId,
                'child_score' => $scores[$childId][$parentId]['child_score'] ?? 0,
                'parent_score' => $scores[$childId][$parentId]['parent_score'] ?? 0,
                'rank_for_child' => $rankForChild === false ? null : $rankForChild + 1,
                'rank_for_parent' => $rankForParent === null ? null : $rankForParent + 1,
                'explanation' => $scores[$childId][$parentId]['explanation'] ?? [],
            ];
        }

        return [
            'matches' => $matches,
            'unmatched_children' => array_values(array_diff($children->pluck('id')->all(), $matchedChildIds)),
            'unmatched_parents' => array_values(array_diff($parents->pluck('id')->all(), $matchedParentIds)),
        ];
    }

    private function scoreParentForChild(Child $child, User $parent): int
    {
        $profile = $parent->matchingProfile;

        if (!$profile) {
            return -1000;
        }

        $score = 0;

        if ($profile->home_study_verified) {
            $score += 30;
        }

        $score += (int) round($profile->financial_capacity_score * 0.25);
        $score += (int) round($profile->housing_score * 0.25);
        $score += (int) round($profile->parenting_capacity_score * 0.30);

        if ($child->is_special_needs && $profile->open_to_special_needs) {
            $score += 20;
        }

        if ($child->is_special_needs && !$profile->open_to_special_needs) {
            $score -= 300;
        }

        $activeCasesCount = $parent->adoptionCasesAsProspectiveParent
            ->whereNotIn('status', ['finalized', 'closed', 'cancelled'])
            ->count();

        if ($activeCasesCount > 0) {
            $score -= 200;
        }

        return $score;
    }

    private function scoreChildForParent(Child $child, User $parent): int
    {
        $profile = $parent->matchingProfile;

        if (!$profile) {
            return -1000;
        }

        $score = 0;

        if ($child->adoption_eligibility_status === 'eligible') {
            $score += 40;
        } else {
            $score -= 500;
        }

        if ($child->case_status === 'available_for_adoption') {
            $score += 40;
        } else {
            $score -= 500;
        }

        if ($profile->preferred_child_sex === 'any' || $profile->preferred_child_sex === $child->sex) {
            $score += 15;
        } else {
            $score -= 50;
        }

        $age = $this->calculateAge($child);

        if ($age !== null) {
            if ($profile->min_child_age !== null && $age < $profile->min_child_age) {
                $score -= 25;
            }

            if ($profile->max_child_age !== null && $age > $profile->max_child_age) {
                $score -= 25;
            }

            if (
                ($profile->min_child_age === null || $age >= $profile->min_child_age) &&
                ($profile->max_child_age === null || $age <= $profile->max_child_age)
            ) {
                $score += 20;
            }
        }

        if ($child->is_special_needs && $profile->open_to_special_needs) {
            $score += 15;
        }

        if ($child->is_special_needs && !$profile->open_to_special_needs) {
            $score -= 300;
        }

        return $score;
    }

    private function calculateAge(Child $child): ?int
    {
        if (!$child->date_of_birth) {
            return null;
        }

        return $child->date_of_birth->age;
    }

private function buildExplanation(Child $child, User $parent, int $childScore, int $parentScore): array
{
    $profile = $parent->matchingProfile;
    $childAge = $this->calculateAge($child);

    $reasons = [];

    if ($child->adoption_eligibility_status === 'eligible') {
        $reasons[] = 'The child is marked as eligible for adoption.';
    }

    if ($child->case_status === 'available_for_adoption') {
        $reasons[] = 'The child is currently available for adoption.';
    }

    if ($profile?->home_study_verified) {
        $reasons[] = 'The prospective parent has a verified home study.';
    }

    if ($profile && ($profile->preferred_child_sex === 'any' || $profile->preferred_child_sex === $child->sex)) {
        $reasons[] = 'The child matches the parent’s preferred child sex.';
    }

    if ($profile && $childAge !== null) {
        $ageFits =
            ($profile->min_child_age === null || $childAge >= $profile->min_child_age) &&
            ($profile->max_child_age === null || $childAge <= $profile->max_child_age);

        if ($ageFits) {
            $reasons[] = 'The child’s age is within the parent’s preferred age range.';
        }
    }

    if ($child->is_special_needs && $profile?->open_to_special_needs) {
        $reasons[] = 'The parent is open to caring for a child with special needs.';
    }

    if (!$child->is_special_needs) {
        $reasons[] = 'The child is not marked as having special needs in the current record.';
    }

    return [
        'child_score' => $childScore,
        'parent_score' => $parentScore,

        'rule_based_reasons' => $reasons,

        'child_status' => $child->case_status,
        'child_eligibility' => $child->adoption_eligibility_status,
        'child_sex' => $child->sex,
        'child_age' => $childAge,
        'child_special_needs' => (bool) $child->is_special_needs,

        'parent_preferred_child_sex' => $profile?->preferred_child_sex,
        'parent_min_child_age' => $profile?->min_child_age,
        'parent_max_child_age' => $profile?->max_child_age,
        'parent_open_to_special_needs' => (bool) $profile?->open_to_special_needs,
        'home_study_verified' => (bool) $profile?->home_study_verified,

        'financial_capacity_score' => $profile?->financial_capacity_score,
        'housing_score' => $profile?->housing_score,
        'parenting_capacity_score' => $profile?->parenting_capacity_score,

        'ai_summary' => null,
        'review_reminder' => 'This is only a system-generated recommendation. Final review and approval must still be done by authorized staff, social worker, and RACCO when applicable.',
    ];
}
}