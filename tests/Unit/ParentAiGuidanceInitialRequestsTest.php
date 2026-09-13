<?php

namespace Tests\Unit;

use App\Http\Controllers\Parent\ParentAiGuidanceController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ParentAiGuidanceInitialRequestsTest extends TestCase
{
    public function test_approved_faqs_include_account_and_document_answers(): void
    {
        $faqs = $this->frequentlyAskedQuestions();

        $this->assertCount(11, $faqs);
        $this->assertContains('create-account', array_column($faqs, 'id'));
        $this->assertContains('replace-document', array_column($faqs, 'id'));
        $this->assertContains('matching-privacy', array_column($faqs, 'id'));
    }

    public function test_each_approved_faq_has_display_and_answer_fields(): void
    {
        $faqs = $this->frequentlyAskedQuestions();

        foreach ($faqs as $faq) {
            $this->assertNotEmpty($faq['id']);
            $this->assertNotEmpty($faq['category']);
            $this->assertNotEmpty($faq['title']);
            $this->assertNotEmpty($faq['description']);
            $this->assertNotEmpty($faq['question']);
            $this->assertNotEmpty($faq['answer']);
            $this->assertNotEmpty($faq['icon']);
            $this->assertNotEmpty($faq['class']);
            $this->assertIsArray($faq['match_phrases']);
            $this->assertIsArray($faq['match_terms']);
        }
    }

    public function test_matcher_accepts_known_questions_and_defers_ambiguous_questions_to_ai(): void
    {
        $this->assertSame(
            'replace-document',
            $this->matchQuestion('Can I replace my submitted document with a corrected file?')['id']
        );
        $this->assertSame(
            'email-verification',
            $this->matchQuestion('I did not receive my OTP.')['id']
        );

        $this->assertNull($this->matchQuestion('What is my application status?'));
        $this->assertNull($this->matchQuestion('Why was my home study rejected?'));
        $this->assertNull($this->matchQuestion('What process should I take to appeal a denial?'));
        $this->assertNull($this->matchQuestion('What is the appeal procedure for a final decision?'));
        $this->assertNull($this->matchQuestion('Can I use hotpot for lunch?'));
    }

    private function frequentlyAskedQuestions(): array
    {
        $method = new ReflectionMethod(ParentAiGuidanceController::class, 'frequentlyAskedQuestions');

        return $method->invoke(new ParentAiGuidanceController);
    }

    private function matchQuestion(string $question): ?array
    {
        $method = new ReflectionMethod(ParentAiGuidanceController::class, 'matchFrequentlyAskedQuestion');

        return $method->invoke(new ParentAiGuidanceController, $question);
    }
}
