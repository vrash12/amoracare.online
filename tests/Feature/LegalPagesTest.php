<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_terms_and_privacy_pages_are_publicly_available(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Terms and Conditions')
            ->assertSee('AI legal guidance');

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Privacy Notice')
            ->assertSee('Your data-privacy rights');
    }

    public function test_public_application_links_to_both_legal_pages(): void
    {
        $this->get(route('parent.application.create'))
            ->assertOk()
            ->assertSee(route('legal.terms'))
            ->assertSee(route('legal.privacy'));
    }

    public function test_registration_displays_the_full_terms_in_a_keyboard_accessible_box(): void
    {
        $response = $this->get(route('parent.application.create'))->assertOk();

        $response->assertSee('id="registrationTerms"', false)
            ->assertSee('role="region" tabindex="0"', false)
            ->assertSee('aria-labelledby="termsHeading"', false)
            ->assertSee('Effective date: '.config('legal.effective_date'))
            ->assertSee('name="terms_accepted" value="1" required', false)
            ->assertSee('I have read and agree to the Terms and Conditions.')
            ->assertSeeInOrder([
                'id="registrationTerms"',
                '1. Purpose and scope',
                '5. AI legal guidance',
                '12. Questions and concerns',
                'id="terms_accepted"',
                'Submit Application',
            ], false);

        // Both pages must use the same policy text, not separate copies that can drift.
        foreach ([false, true] as $embedded) {
            $content = view('partials.terms-content', ['embedded' => $embedded])->render();
            $page = $embedded ? $response : $this->get(route('legal.terms'))->assertOk();
            $page->assertSee($content, false);
        }

        $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="terms_accepted"[^>]*checked/', $response->getContent());
    }

    public function test_terms_validation_error_is_linked_to_the_acceptance_checkbox(): void
    {
        $this->withViewErrors(['terms_accepted' => 'Please accept the terms.'])
            ->view('public.prospective-parent-application')
            ->assertSee('aria-describedby="legalAgreementHelp termsAcceptanceError"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('id="termsAcceptanceError"', false)
            ->assertSee('Please accept the terms.');
    }
}
