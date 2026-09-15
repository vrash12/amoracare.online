<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Routing\RouteCollection;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    private const CSRF_TOKEN = 'welcome-page-test-csrf-token-1234567890123';

    public function test_guests_can_sign_in_or_start_an_application(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Secure Login')
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('href="'.route('parent.application.create').'"', false)
            ->assertDontSee('Open Dashboard')
            ->assertDontSee('action="'.route('logout').'"', false);
    }

    #[DataProvider('portalGuards')]
    public function test_each_authenticated_guard_has_dashboard_and_targeted_logout_controls(string $guard): void
    {
        $this->actingAs($this->portalUser(), $guard);

        $response = $this->withSession(['_token' => self::CSRF_TOKEN])
            ->get(route('home'));

        $this->assertPortalControls($response, $guard);
    }

    public static function portalGuards(): array
    {
        return [
            'administrator' => ['admin'],
            'prospective parent' => ['prospective_parent'],
            'external reviewer' => ['external_reviewer'],
            'legacy web session' => ['web'],
        ];
    }

    public function test_preferred_authenticated_guard_wins_when_multiple_portals_are_signed_in(): void
    {
        $this->actingAs($this->portalUser(), 'prospective_parent')
            ->actingAs($this->portalUser(), 'admin');

        $response = $this->withSession([
            '_token' => self::CSRF_TOKEN,
            'active_auth_guard' => 'prospective_parent',
        ])->get(route('home'));

        $this->assertPortalControls($response, 'prospective_parent');
    }

    #[DataProvider('unavailablePreferences')]
    public function test_unavailable_guard_preference_falls_back_to_a_signed_in_portal(mixed $preference): void
    {
        $this->actingAs($this->portalUser(), 'admin')
            ->actingAs($this->portalUser(), 'prospective_parent');

        $response = $this->withSession([
            '_token' => self::CSRF_TOKEN,
            'active_auth_guard' => $preference,
        ])->get(route('home'));

        $this->assertPortalControls($response, 'admin');
    }

    public static function unavailablePreferences(): array
    {
        return [
            'unknown guard' => ['not-a-configured-guard'],
            'signed-out portal' => ['external_reviewer'],
            'malformed preference' => [['admin']],
        ];
    }

    public function test_older_deployments_without_optional_routes_can_render_the_homepage(): void
    {
        $this->useLegacyRoutes();

        $this->get('/')
            ->assertOk()
            ->assertSee('Ask about applying')
            ->assertSee('Application inquiries')
            ->assertSee('href="'.route('login').'"', false)
            ->assertDontSee('Start an application')
            ->assertDontSee('Legal information');
    }

    public function test_legacy_web_authentication_works_without_role_guard_configuration(): void
    {
        $this->useLegacyRoutes();
        config(['auth.role_guards' => []]);
        $this->actingAs($this->portalUser(), 'web');

        $this->get('/')
            ->assertOk()
            ->assertSee('Open Dashboard')
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('name="guard" value="web"', false)
            ->assertDontSee('Secure Login');
    }

    private function useLegacyRoutes(): void
    {
        $routes = new RouteCollection;

        foreach (app('router')->getRoutes() as $route) {
            if (! in_array($route->getName(), ['parent.application.create', 'legal.terms', 'legal.privacy'], true)) {
                $routes->add($route);
            }
        }

        $routes->refreshNameLookups();
        app('router')->setRoutes($routes);
        app('url')->setRoutes($routes);
    }

    private function portalUser(): User
    {
        // Rendering the landing page only needs a guard user; no records are saved.
        return new User([
            'name' => 'Portal Test User',
            'email' => 'portal@example.test',
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function assertPortalControls(TestResponse $response, string $guard): void
    {
        $response->assertOk()
            ->assertSee('Open Dashboard')
            ->assertSee('href="'.route('dashboard').'"', false)
            ->assertSee('href="'.route('parent.application.create').'"', false)
            ->assertDontSee('Secure Login');

        $document = new DOMDocument;
        $previousErrorHandling = libxml_use_internal_errors(true);

        try {
            $document->loadHTML($response->getContent());
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorHandling);
        }

        $xpath = new DOMXPath($document);
        $forms = $xpath->query('//form[@action="'.route('logout').'"]');
        $this->assertCount(1, $forms);
        $form = $forms->item(0);
        $this->assertSame('POST', strtoupper($form->getAttribute('method')));
        $this->assertSame($guard, $xpath->evaluate('string(.//input[@type="hidden" and @name="guard"]/@value)', $form));
        $this->assertSame(self::CSRF_TOKEN, $xpath->evaluate('string(.//input[@type="hidden" and @name="_token"]/@value)', $form));
        $this->assertSame('Log out', trim($xpath->evaluate('string(.//button[@type="submit"])', $form)));
    }
}
