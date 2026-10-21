<?php

namespace Tests\Unit;

use App\Models\Child;
use App\Models\ParentMatchingProfile;
use App\Models\User;
use App\Services\AdoptionMatching\GaleShapleyMatcher;
use Tests\TestCase;

class ParentReadinessTest extends TestCase
{
    public function test_all_three_items_are_required_in_every_combination(): void
    {
        for ($mask = 0; $mask < 8; $mask++) {
            $profile = new ParentMatchingProfile;
            foreach (array_keys(ParentMatchingProfile::READINESS_REQUIREMENTS) as $index => $field) {
                $profile->{$field} = (bool) ($mask & (1 << $index));
            }
            $this->assertSame($mask === 7, $profile->hasCompletedReadinessChecklist());
        }
        $this->assertFalse((new ParentMatchingProfile)->hasCompletedReadinessChecklist());
    }

    public function test_matcher_excludes_unconfirmed_parents_even_when_passed_directly(): void
    {
        $child = new Child(['sex' => 'male', 'adoption_eligibility_status' => 'eligible', 'case_status' => 'available_for_adoption']);
        $child->id = 101;
        $parents = collect();
        for ($mask = 0; $mask < 8; $mask++) {
            $profile = new ParentMatchingProfile([
                'preferred_child_sex' => 'any', 'home_study_verified' => true,
                'financial_capacity_score' => 90, 'housing_score' => 90, 'parenting_capacity_score' => 90,
            ]);
            foreach (array_keys(ParentMatchingProfile::READINESS_REQUIREMENTS) as $index => $field) {
                $profile->{$field} = (bool) ($mask & (1 << $index));
            }
            $parent = new User;
            $parent->id = $mask + 1;
            $parent->setRelation('matchingProfile', $profile);
            $parent->setRelation('adoptionCasesAsProspectiveParent', collect());
            $parents->push($parent);
        }
        $noProfile = new User;
        $noProfile->id = 9;
        $noProfile->setRelation('matchingProfile', null);
        $parents->push($noProfile);

        $result = (new GaleShapleyMatcher)->match(collect([$child]), $parents);
        $this->assertCount(1, $result['matches']);
        $this->assertSame(8, $result['matches'][0]['prospective_parent_id']);
        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 9], $result['unmatched_parents']);
        $this->assertTrue($result['matches'][0]['explanation']['parent_physically_capable']);

        $result = (new GaleShapleyMatcher)->match(collect([$child]), $parents->except(7));
        $this->assertSame([], $result['matches']);
        $this->assertSame([101], $result['unmatched_children']);
    }
}
