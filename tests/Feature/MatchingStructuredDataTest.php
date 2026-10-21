<?php

namespace Tests\Feature;

use App\Models\AdoptionMatchingResult;
use App\Models\AdoptionMatchingRun;
use App\Models\Child;
use App\Models\ParentMatchingProfile;
use App\Models\User;
use App\Services\AdoptionMatching\GaleShapleyMatcher;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class MatchingStructuredDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        view()->share('errors', new ViewErrorBag);
    }

    public function test_matching_result_shows_the_requested_child_structured_data(): void
    {
        $child = new Child([
            'child_code' => 'CH-0042',
            'first_name' => 'Jamie',
            'last_name' => 'Santos',
            'sex' => 'female',
            'adoption_eligibility_status' => 'eligible',
            'case_status' => 'available_for_adoption',
            'school_name' => 'Sample Elementary School',
            'health_status' => 'Routine care required',
            'background_summary' => 'Authorized background summary for review.',
            'is_special_needs' => true,
            'special_needs_categories' => ['sensory_impairment'],
        ]);
        $child->id = 42;

        $profile = new ParentMatchingProfile([
            'preferred_child_sex' => 'any',
            'home_study_verified' => true,
            'physically_capable' => true,
            'financially_capable' => true,
            'willing_to_support_child' => true,
        ]);
        $parent = new User(['name' => 'Parent Preview', 'email' => 'parent@example.invalid']);
        $parent->id = 7;
        $parent->setRelation('matchingProfile', $profile);
        $parent->setRelation('adoptionCasesAsProspectiveParent', collect());

        $result = new AdoptionMatchingResult([
            'child_score' => 88,
            'parent_score' => 91,
            'status' => 'recommended',
            'explanation' => [
                'child_school_name' => 'Sample Elementary School',
                'child_health_status' => 'Routine care required',
                'child_background_summary' => 'Authorized background summary for review.',
                'child_special_needs_categories' => ['Sensory impairment'],
                'rule_based_reasons' => [],
            ],
        ]);
        $result->id = 99;
        $result->setRelation('child', $child);
        $result->setRelation('prospectiveParent', $parent);

        $run = new AdoptionMatchingRun(['run_code' => 'MR-TEST-0001', 'status' => 'completed']);
        $run->id = 1;
        $run->setRelation('generator', null);
        $run->setRelation('results', collect([$result]));

        $this->view('admin.matching.show', ['run' => $run])
            ->assertSee('Child')
            ->assertSee('School Name')
            ->assertSee('Sample Elementary School')
            ->assertSee('Health Status')
            ->assertSee('Routine care required')
            ->assertSee('Background Summary')
            ->assertSee('Authorized background summary for review.')
            ->assertSee('Category if special needs')
            ->assertSee('Sensory impairment');
    }

    public function test_matcher_records_child_structured_fields_for_new_recommendations(): void
    {
        $child = new Child([
            'sex' => 'female',
            'adoption_eligibility_status' => 'eligible',
            'case_status' => 'available_for_adoption',
            'school_name' => 'Sample School',
            'health_status' => 'Stable',
            'background_summary' => 'Summary',
            'is_special_needs' => true,
            'special_needs_categories' => ['developmental_intellectual_disability'],
        ]);
        $child->id = 42;
        $parent = new User;
        $parent->id = 7;
        $parent->setRelation('matchingProfile', new ParentMatchingProfile([
            'preferred_child_sex' => 'any',
            'open_to_special_needs' => true,
            'home_study_verified' => true,
            'physically_capable' => true,
            'financially_capable' => true,
            'willing_to_support_child' => true,
        ]));
        $parent->setRelation('adoptionCasesAsProspectiveParent', collect());

        $output = (new GaleShapleyMatcher)->match(collect([$child]), collect([$parent]));
        $explanation = $output['matches'][0]['explanation'];

        $this->assertSame('Sample School', $explanation['child_school_name']);
        $this->assertSame('Stable', $explanation['child_health_status']);
        $this->assertSame('Summary', $explanation['child_background_summary']);
        $this->assertSame(['Developmental and intellectual disability'], $explanation['child_special_needs_categories']);
    }

    public function test_structured_categories_are_readable_including_empty_and_not_applicable_values(): void
    {
        $child = new Child(['first_name' => 'Test', 'last_name' => 'Child']);
        $parent = new User(['name' => 'Test Parent']);
        $parent->setRelation('matchingProfile', null);
        $result = new AdoptionMatchingResult(['status' => 'recommended', 'child_score' => 80, 'parent_score' => 80]);
        $result->id = 99;
        $result->setRelation('child', $child)->setRelation('prospectiveParent', $parent);
        $run = new AdoptionMatchingRun(['run_code' => 'MR-CATEGORIES']);
        $run->id = 1;
        $run->setRelation('generator', null)->setRelation('results', collect([$result]));

        foreach ([
            [false, [], 'Not applicable'],
            [true, [], 'Not recorded'],
            [true, ['sensory_impairment', 'developmental_intellectual_disability'], 'Sensory impairment, Developmental and intellectual disability'],
        ] as [$specialNeeds, $categories, $expected]) {
            $child->is_special_needs = $specialNeeds;
            $child->special_needs_categories = $categories;
            $result->explanation = [
                'child_special_needs' => $specialNeeds, 'child_special_needs_categories' => $categories,
                'empty_values' => [], 'test_flags' => ['confirmed' => true, 'notes' => ['First', 'Second']],
            ];
            $html = (string) $this->view('admin.matching.show', ['run' => $run]);
            $document = new \DOMDocument;
            @$document->loadHTML($html);
            $xpath = new \DOMXPath($document);
            $categoryCell = $xpath->query('//div[strong[normalize-space(.)="Child Special Needs Categories"]]/span')->item(0);
            $this->assertNotNull($categoryCell);
            $this->assertSame($expected, trim($categoryCell->textContent));
            $this->assertStringNotContainsString('[', $categoryCell->textContent);
            $this->assertSame('None', trim($xpath->query('//div[strong[normalize-space(.)="Empty Values"]]/span')->item(0)->textContent));
            $this->assertSame('Confirmed: Yes, Notes: First, Second', trim($xpath->query('//div[strong[normalize-space(.)="Test Flags"]]/span')->item(0)->textContent));
        }
    }
}
