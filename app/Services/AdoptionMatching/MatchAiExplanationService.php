<?php

namespace App\Services\AdoptionMatching;

use App\Models\Child;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MatchAiExplanationService
{
    public function explain(
        array $matchExplanation,
        ?Child $child = null,
        ?User $parent = null,
        array $matchMeta = []
    ): string {
        $apiKey = config('services.openai.api_key');

        if (!$apiKey) {
            return $this->fallbackExplanation($matchExplanation, $child, $parent, $matchMeta);
        }

        try {
            $response = Http::timeout(45)
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->post('https://api.openai.com/v1/responses', [
                    'model' => config('services.openai.matching_model', 'gpt-4.1-mini'),
                    'temperature' => 0.55,
                    'max_output_tokens' => 280,
                    'instructions' => $this->instructions(),
                    'input' => $this->buildInput($matchExplanation, $child, $parent, $matchMeta),
                ]);

            if ($response->failed()) {
                Log::warning('OpenAI match explanation failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->fallbackExplanation($matchExplanation, $child, $parent, $matchMeta);
            }

            return $this->extractText($response->json())
                ?: $this->fallbackExplanation($matchExplanation, $child, $parent, $matchMeta);
        } catch (\Throwable $e) {
            Log::warning('OpenAI match explanation error.', [
                'message' => $e->getMessage(),
            ]);

            return $this->fallbackExplanation($matchExplanation, $child, $parent, $matchMeta);
        }
    }

    private function instructions(): string
    {
        return <<<TEXT
You are AmoraCare's adoption matching explanation assistant.

Your role:
- Explain why this specific child-parent match was recommended.
- Use only the structured data provided.
- Do not make the final adoption decision.
- Do not say the adoption is approved, guaranteed, final, or legally complete.
- Do not mention private names, confidential notes, or sensitive case details.
- Do not use the same generic explanation every time.
- Make each explanation specific to the actual match factors.
- Mention the strongest compatibility reason.
- Mention one thing the social worker, admin, or RACCO reviewer should still verify.
- Keep the tone warm, professional, and easy to understand.

Format:
Write 3 to 5 short sentences in one paragraph.
Avoid bullet points.
TEXT;
    }

    private function buildInput(
        array $matchExplanation,
        ?Child $child,
        ?User $parent,
        array $matchMeta
    ): string {
        $profile = $parent?->matchingProfile;

        $childAge = $matchExplanation['child_age'] ?? $this->calculateAge($child);

        $ageFit = $this->ageFitsPreference(
            $childAge,
            $profile?->min_child_age,
            $profile?->max_child_age
        );

        $sexFit = $this->sexFitsPreference(
            $child?->sex ?? $matchExplanation['child_sex'] ?? null,
            $profile?->preferred_child_sex ?? $matchExplanation['parent_preferred_child_sex'] ?? null
        );

        $specialNeedsFit = $this->specialNeedsFit(
            (bool) ($child?->is_special_needs ?? $matchExplanation['child_special_needs'] ?? false),
            (bool) ($profile?->open_to_special_needs ?? $matchExplanation['parent_open_to_special_needs'] ?? false)
        );

        $childScore = $matchExplanation['child_score'] ?? $matchMeta['child_score'] ?? null;
        $parentScore = $matchExplanation['parent_score'] ?? $matchMeta['parent_score'] ?? null;

        $overallScore = null;

        if ($childScore !== null && $parentScore !== null) {
            $overallScore = round(((float) $childScore + (float) $parentScore) / 2, 2);
        }

        $safeData = [
            'match_identity' => [
                'child_code' => $child?->child_code,
                'case_focus' => $this->buildCaseFocus($childAge, $child, $profile),
                'match_strength' => $this->matchStrengthLabel($overallScore),
            ],

            'scores_and_ranking' => [
                'child_score' => $childScore,
                'parent_score' => $parentScore,
                'overall_score' => $overallScore,
                'rank_for_child' => $matchMeta['rank_for_child'] ?? $matchExplanation['rank_for_child'] ?? null,
                'rank_for_parent' => $matchMeta['rank_for_parent'] ?? $matchExplanation['rank_for_parent'] ?? null,
            ],

            'child_factors' => [
                'child_age' => $childAge,
                'child_sex' => $child?->sex ?? $matchExplanation['child_sex'] ?? null,
                'child_case_status' => $child?->case_status ?? $matchExplanation['child_status'] ?? null,
                'child_adoption_eligibility' => $child?->adoption_eligibility_status ?? $matchExplanation['child_eligibility'] ?? null,
                'child_has_special_needs' => (bool) ($child?->is_special_needs ?? $matchExplanation['child_special_needs'] ?? false),
            ],

            'parent_matching_profile' => [
                'preferred_child_sex' => $profile?->preferred_child_sex ?? $matchExplanation['parent_preferred_child_sex'] ?? null,
                'minimum_preferred_age' => $profile?->min_child_age ?? $matchExplanation['parent_min_child_age'] ?? null,
                'maximum_preferred_age' => $profile?->max_child_age ?? $matchExplanation['parent_max_child_age'] ?? null,
                'open_to_special_needs' => (bool) ($profile?->open_to_special_needs ?? $matchExplanation['parent_open_to_special_needs'] ?? false),
                'home_study_verified' => (bool) ($profile?->home_study_verified ?? $matchExplanation['home_study_verified'] ?? false),
                'financial_capacity_score' => $profile?->financial_capacity_score ?? $matchExplanation['financial_capacity_score'] ?? null,
                'housing_score' => $profile?->housing_score ?? $matchExplanation['housing_score'] ?? null,
                'parenting_capacity_score' => $profile?->parenting_capacity_score ?? $matchExplanation['parenting_capacity_score'] ?? null,
            ],

            'fit_analysis' => [
                'age_fit' => $ageFit,
                'sex_preference_fit' => $sexFit,
                'special_needs_fit' => $specialNeedsFit,
                'main_strengths' => $this->mainStrengths($matchExplanation, $child, $parent, $overallScore),
                'review_focus' => $this->reviewFocus($matchExplanation, $child, $parent, $overallScore),
            ],

            'system_reasons' => $matchExplanation['rule_based_reasons'] ?? [],
            'required_reminder' => 'This is a system-generated recommendation only and must still be reviewed by authorized staff, social worker, and RACCO when applicable.',
        ];

        return "Create a unique explanation for this specific matching recommendation:\n\n"
            . json_encode($safeData, JSON_PRETTY_PRINT);
    }

    private function mainStrengths(
        array $matchExplanation,
        ?Child $child,
        ?User $parent,
        ?float $overallScore
    ): array {
        $profile = $parent?->matchingProfile;
        $strengths = [];

        if (($child?->adoption_eligibility_status ?? $matchExplanation['child_eligibility'] ?? null) === 'eligible') {
            $strengths[] = 'The child is marked eligible for adoption.';
        }

        if (($child?->case_status ?? $matchExplanation['child_status'] ?? null) === 'available_for_adoption') {
            $strengths[] = 'The child is available for adoption in the system.';
        }

        if ($profile?->home_study_verified ?? $matchExplanation['home_study_verified'] ?? false) {
            $strengths[] = 'The parent has a verified home study.';
        }

        $childAge = $matchExplanation['child_age'] ?? $this->calculateAge($child);

        if ($this->ageFitsPreference($childAge, $profile?->min_child_age, $profile?->max_child_age) === 'fits_preference') {
            $strengths[] = 'The child age fits the parent preference range.';
        }

        if ($this->sexFitsPreference($child?->sex, $profile?->preferred_child_sex) === 'fits_preference') {
            $strengths[] = 'The child sex fits the parent preference.';
        }

        if (($profile?->financial_capacity_score ?? 0) >= 85) {
            $strengths[] = 'The parent has strong financial capacity score.';
        }

        if (($profile?->housing_score ?? 0) >= 85) {
            $strengths[] = 'The parent has strong housing readiness score.';
        }

        if (($profile?->parenting_capacity_score ?? 0) >= 85) {
            $strengths[] = 'The parent has strong parenting capacity score.';
        }

        if ($overallScore !== null && $overallScore >= 85) {
            $strengths[] = 'The overall compatibility score is high.';
        }

        return array_values(array_unique($strengths));
    }

    private function reviewFocus(
        array $matchExplanation,
        ?Child $child,
        ?User $parent,
        ?float $overallScore
    ): array {
        $profile = $parent?->matchingProfile;
        $focus = [];

        $childAge = $matchExplanation['child_age'] ?? $this->calculateAge($child);

        if ($this->ageFitsPreference($childAge, $profile?->min_child_age, $profile?->max_child_age) !== 'fits_preference') {
            $focus[] = 'Review whether the child age is acceptable despite being outside the preferred range.';
        }

        if ($this->sexFitsPreference($child?->sex, $profile?->preferred_child_sex) !== 'fits_preference') {
            $focus[] = 'Confirm whether the parent is comfortable with the child profile outside the stated sex preference.';
        }

        if (
            (bool) ($child?->is_special_needs ?? $matchExplanation['child_special_needs'] ?? false) &&
            !(bool) ($profile?->open_to_special_needs ?? $matchExplanation['parent_open_to_special_needs'] ?? false)
        ) {
            $focus[] = 'Carefully review special-needs readiness before moving forward.';
        }

        if (($profile?->home_study_verified ?? $matchExplanation['home_study_verified'] ?? false) === false) {
            $focus[] = 'Verify the home study before considering case conversion.';
        }

        if (($profile?->financial_capacity_score ?? 0) < 80) {
            $focus[] = 'Review financial capacity documents.';
        }

        if (($profile?->housing_score ?? 0) < 80) {
            $focus[] = 'Review housing readiness.';
        }

        if (($profile?->parenting_capacity_score ?? 0) < 80) {
            $focus[] = 'Review parenting capacity assessment.';
        }

        if ($overallScore !== null && $overallScore < 70) {
            $focus[] = 'Review the match carefully because the overall score is not high.';
        }

        if (empty($focus)) {
            $focus[] = 'Review documents, case notes, and legal requirements before approving the next step.';
        }

        return array_values(array_unique($focus));
    }

    private function fallbackExplanation(
        array $matchExplanation,
        ?Child $child,
        ?User $parent,
        array $matchMeta
    ): string {
        $profile = $parent?->matchingProfile;

        $childAge = $matchExplanation['child_age'] ?? $this->calculateAge($child);
        $childScore = $matchExplanation['child_score'] ?? $matchMeta['child_score'] ?? 0;
        $parentScore = $matchExplanation['parent_score'] ?? $matchMeta['parent_score'] ?? 0;
        $overallScore = round(((float) $childScore + (float) $parentScore) / 2, 2);

        $strengths = $this->mainStrengths($matchExplanation, $child, $parent, $overallScore);
        $reviewFocus = $this->reviewFocus($matchExplanation, $child, $parent, $overallScore);

        $strengthSentence = !empty($strengths)
            ? implode(' ', array_slice($strengths, 0, 2))
            : 'This match was recommended based on the child record and the parent matching profile.';

        $ageText = $childAge !== null
            ? "The child is {$childAge} years old"
            : 'The child age should be verified';

        $preferenceText = $profile
            ? "and the parent preference range is {$profile->min_child_age} to {$profile->max_child_age} years old."
            : 'and the parent preference range should be reviewed.';

        return "{$strengthSentence} {$ageText} {$preferenceText} "
            . "The overall compatibility score is {$overallScore}%, so staff should review this recommendation with attention to "
            . strtolower($reviewFocus[0])
            . ' This is only a system-generated recommendation and still requires authorized human review.';
    }

    private function extractText(array $data): ?string
    {
        if (!empty($data['output_text'])) {
            return trim($data['output_text']);
        }

        foreach ($data['output'] ?? [] as $output) {
            foreach ($output['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && !empty($content['text'])) {
                    return trim($content['text']);
                }

                if (!empty($content['text']) && is_string($content['text'])) {
                    return trim($content['text']);
                }
            }
        }

        return null;
    }

    private function calculateAge(?Child $child): ?int
    {
        if (!$child || !$child->date_of_birth) {
            return null;
        }

        return $child->date_of_birth->age;
    }

    private function ageFitsPreference(?int $childAge, ?int $minAge, ?int $maxAge): string
    {
        if ($childAge === null) {
            return 'age_unknown';
        }

        if ($minAge !== null && $childAge < $minAge) {
            return 'below_preferred_range';
        }

        if ($maxAge !== null && $childAge > $maxAge) {
            return 'above_preferred_range';
        }

        return 'fits_preference';
    }

    private function sexFitsPreference(?string $childSex, ?string $preferredSex): string
    {
        if (!$childSex || !$preferredSex) {
            return 'preference_unknown';
        }

        if ($preferredSex === 'any') {
            return 'fits_preference';
        }

        return $childSex === $preferredSex ? 'fits_preference' : 'outside_preference';
    }

    private function specialNeedsFit(bool $childHasSpecialNeeds, bool $parentOpenToSpecialNeeds): string
    {
        if (!$childHasSpecialNeeds) {
            return 'not_applicable';
        }

        return $parentOpenToSpecialNeeds ? 'parent_is_open' : 'needs_careful_review';
    }

    private function matchStrengthLabel(?float $score): string
    {
        if ($score === null) {
            return 'Unknown';
        }

        if ($score >= 85) {
            return 'Strong Match';
        }

        if ($score >= 70) {
            return 'Good Match';
        }

        if ($score >= 50) {
            return 'Moderate Match';
        }

        return 'Needs Careful Review';
    }

    private function buildCaseFocus(?int $childAge, ?Child $child, $profile): string
    {
        $parts = [];

        if ($childAge !== null) {
            $parts[] = "child age {$childAge}";
        }

        if ($child?->sex) {
            $parts[] = "child sex {$child->sex}";
        }

        if ($child?->is_special_needs) {
            $parts[] = 'child has special-needs indicator';
        } else {
            $parts[] = 'no special-needs indicator';
        }

        if ($profile?->preferred_child_sex) {
            $parts[] = "parent prefers {$profile->preferred_child_sex}";
        }

        if ($profile?->min_child_age !== null || $profile?->max_child_age !== null) {
            $parts[] = "parent age range {$profile->min_child_age} to {$profile->max_child_age}";
        }

        return implode(', ', $parts);
    }
}