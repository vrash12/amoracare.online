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
}
