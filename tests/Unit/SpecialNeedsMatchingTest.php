<?php

namespace Tests\Unit;

use App\Models\Child;
use App\Models\ParentMatchingProfile;
use App\Models\User;
use App\Services\AdoptionMatching\GaleShapleyMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SpecialNeedsMatchingTest extends TestCase
{
    public static function specialNeedsPreferences(): array
    {
        return [
            'no special needs, parent declines' => [false, false, true],
            'no special needs, parent accepts' => [false, true, true],
            'special needs, parent accepts' => [true, true, true],
            'special needs, parent declines' => [true, false, false],
        ];
    }

    #[DataProvider('specialNeedsPreferences')]
    public function test_matching_respects_the_parent_special_needs_preference(
        bool $childHasSpecialNeeds,
        bool $parentAcceptsSpecialNeeds,
        bool $shouldMatch
    ): void {
        $child = $this->child(101, $childHasSpecialNeeds);
        $parent = $this->parent(201, $parentAcceptsSpecialNeeds);

        $result = (new GaleShapleyMatcher)->match(collect([$child]), collect([$parent]));

        $this->assertCount($shouldMatch ? 1 : 0, $result['matches']);
        $this->assertSame($shouldMatch ? [] : [101], $result['unmatched_children']);
        $this->assertSame($shouldMatch ? [] : [201], $result['unmatched_parents']);
        if ($shouldMatch) {
            $this->assertSame(101, $result['matches'][0]['child_id']);
            $this->assertSame(201, $result['matches'][0]['prospective_parent_id']);
        }
    }

    public function test_a_declining_parent_can_only_receive_the_child_without_special_needs(): void
    {
        // Put the special-needs child first to exercise the previous proposal bug.
        $result = (new GaleShapleyMatcher)->match(
            collect([$this->child(101, true), $this->child(102, false)]),
            collect([$this->parent(201, false)])
        );

        $this->assertCount(1, $result['matches']);
        $this->assertSame(102, $result['matches'][0]['child_id']);
        $this->assertSame(201, $result['matches'][0]['prospective_parent_id']);
        $this->assertSame([101], $result['unmatched_children']);
    }

    public function test_scores_cannot_override_a_parent_who_declines_special_needs(): void
    {
        $result = (new GaleShapleyMatcher)->match(
            collect([$this->child(101, true)]),
            collect([$this->parent(201, false, 100), $this->parent(202, true, 10)])
        );

        $this->assertCount(1, $result['matches']);
        $this->assertSame(202, $result['matches'][0]['prospective_parent_id']);
        $this->assertSame([201], $result['unmatched_parents']);
    }

    private function child(int $id, bool $specialNeeds): Child
    {
        $child = new Child([
            'sex' => 'female',
            'adoption_eligibility_status' => 'eligible',
            'case_status' => 'available_for_adoption',
            'is_special_needs' => $specialNeeds,
        ]);
        $child->id = $id;

        return $child;
    }

    private function parent(int $id, bool $acceptsSpecialNeeds, int $capacity = 80): User
    {
        $parent = new User;
        $parent->id = $id;
        $parent->setRelation('matchingProfile', new ParentMatchingProfile([
            'preferred_child_sex' => 'any',
            'open_to_special_needs' => $acceptsSpecialNeeds,
            'home_study_verified' => true,
            'physically_capable' => true,
            'financially_capable' => true,
            'willing_to_support_child' => true,
            'financial_capacity_score' => $capacity,
            'housing_score' => $capacity,
            'parenting_capacity_score' => $capacity,
        ]));
        $parent->setRelation('adoptionCasesAsProspectiveParent', collect());

        return $parent;
    }
}
