<?php

namespace Tests\Unit;

use App\Models\AdoptionCase;
use App\Support\AdoptionTimeline;
use PHPUnit\Framework\TestCase;

class AdoptionTimelineTest extends TestCase
{
    public function test_finalized_application_is_completed_at_one_hundred_percent(): void
    {
        $timeline = AdoptionTimeline::forStatus('finalized');

        $this->assertSame('Completed', AdoptionCase::STATUSES['finalized']);
        $this->assertSame('Completed', $timeline->last()['label']);
        $this->assertTrue($timeline->every(fn (array $step): bool => $step['state'] === 'completed'));
        $this->assertSame(100, AdoptionTimeline::progress($timeline));
        $this->assertFalse($timeline->contains('status', 'closed'));
        $this->assertFalse($timeline->contains('status', 'cancelled'));
    }

    public function test_active_cases_have_monotonic_stage_progress_starting_at_zero(): void
    {
        $lastProgress = -1;

        foreach (array_keys(AdoptionCase::STATUSES) as $status) {
            if (in_array($status, ['finalized', 'closed', 'cancelled'], true)) {
                continue;
            }

            $timeline = AdoptionTimeline::forStatus($status);
            $progress = AdoptionTimeline::progress($timeline);
            $this->assertGreaterThan($lastProgress, $progress);
            $this->assertLessThan(100, $progress);
            $this->assertCount(1, $timeline->where('state', 'current'));
            $this->assertSame($status, $timeline->firstWhere('state', 'current')['status']);
            $lastProgress = $progress;
        }

        $this->assertSame(0, AdoptionTimeline::progress(AdoptionTimeline::forStatus('draft')));
    }

    public function test_closed_cancelled_and_unknown_cases_do_not_imply_successful_completion(): void
    {
        foreach (['closed', 'cancelled'] as $status) {
            $timeline = AdoptionTimeline::forStatus($status);
            $this->assertSame($status, $timeline->sole()['status']);
            $this->assertSame('stopped', $timeline->sole()['state']);
            $this->assertNull(AdoptionTimeline::progress($timeline));
        }

        foreach ([null, 'unknown'] as $status) {
            $this->assertTrue(AdoptionTimeline::forStatus($status)->isEmpty());
            $this->assertNull(AdoptionTimeline::progress(AdoptionTimeline::forStatus($status)));
        }
    }
}
