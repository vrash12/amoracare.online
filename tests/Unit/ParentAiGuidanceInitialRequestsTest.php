<?php

namespace Tests\Unit;

use App\Http\Controllers\Parent\ParentAiGuidanceController;
use App\Models\AdoptionCase;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ParentAiGuidanceInitialRequestsTest extends TestCase
{
    public function test_new_applicants_receive_getting_started_requests(): void
    {
        $requests = $this->initialRequestsFor(null);

        $this->assertCount(8, $requests);
        $this->assertContains('Begin an Application', array_column($requests, 'title'));
        $this->assertContains('Basic Qualifications', array_column($requests, 'title'));
        $this->assertNotContains('My Current Status', array_column($requests, 'title'));
    }

    public function test_parents_with_a_case_receive_application_specific_requests(): void
    {
        $adoptionCase = new AdoptionCase([
            'status' => 'assessment',
        ]);

        $requests = $this->initialRequestsFor($adoptionCase);

        $this->assertCount(8, $requests);
        $this->assertSame('My Current Status', $requests[0]['title']);
        $this->assertStringContainsString('Assessment', $requests[0]['question']);
        $this->assertContains('Documents Needing Action', array_column($requests, 'title'));
        $this->assertNotContains('Begin an Application', array_column($requests, 'title'));
    }

    private function initialRequestsFor(?AdoptionCase $adoptionCase): array
    {
        $method = new ReflectionMethod(ParentAiGuidanceController::class, 'initialChatRequests');

        return $method->invoke(new ParentAiGuidanceController, $adoptionCase);
    }
}
