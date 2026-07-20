<?php

namespace App\Jobs;

use App\Models\AdoptionMatchingResult;
use App\Services\AdoptionMatching\MatchAiExplanationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateMatchAiExplanation implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 90;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public int $matchingResultId
    ) {
    }

    public function handle(
        MatchAiExplanationService $aiExplanationService
    ): void {
        $matchingResult = AdoptionMatchingResult::query()
            ->with([
                'child',
                'prospectiveParent.matchingProfile',
            ])
            ->find($this->matchingResultId);

        if (!$matchingResult) {
            return;
        }

        $explanation = $this->normalizeExplanation(
            $matchingResult->explanation
        );

        /*
         * Avoid generating the same explanation twice when a job retries
         * after the database update has already succeeded.
         */
        if (
            ($explanation['ai_status'] ?? null) === 'completed'
            && filled($explanation['ai_summary'] ?? null)
        ) {
            return;
        }

        $explanation['ai_status'] = 'processing';
        $matchingResult->update([
            'explanation' => $explanation,
        ]);

        try {
            $summary = $aiExplanationService->explain(
                $explanation,
                $matchingResult->child,
                $matchingResult->prospectiveParent,
                [
                    'child_score' =>
                        $matchingResult->child_score,
                    'parent_score' =>
                        $matchingResult->parent_score,
                    'rank_for_child' =>
                        $matchingResult->rank_for_child,
                    'rank_for_parent' =>
                        $matchingResult->rank_for_parent,
                ]
            );

            $explanation['ai_summary'] = $summary;
            $explanation['ai_status'] = 'completed';
            $explanation['ai_generated_at'] = now()
                ->toIso8601String();

            unset($explanation['ai_error']);

            $matchingResult->update([
                'explanation' => $explanation,
            ]);
        } catch (Throwable $exception) {
            $explanation['ai_status'] = 'failed';
            $explanation['ai_error'] =
                'The AI explanation could not be generated. The matching scores remain available for human review.';

            $matchingResult->update([
                'explanation' => $explanation,
            ]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $matchingResult = AdoptionMatchingResult::find(
            $this->matchingResultId
        );

        if (!$matchingResult) {
            return;
        }

        $explanation = $this->normalizeExplanation(
            $matchingResult->explanation
        );

        $explanation['ai_status'] = 'failed';
        $explanation['ai_error'] =
            'AI explanation generation failed after several attempts.';

        $matchingResult->update([
            'explanation' => $explanation,
        ]);
    }

    private function normalizeExplanation(
        mixed $explanation
    ): array {
        if (is_array($explanation)) {
            return $explanation;
        }

        if (is_string($explanation)) {
            $decoded = json_decode(
                $explanation,
                true
            );

            return is_array($decoded)
                ? $decoded
                : [];
        }

        return [];
    }
}