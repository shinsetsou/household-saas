<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_has_full_seo_metadata(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $response->assertSee('<title>BarangayBooks', false);
        $response->assertSee('HOA dues collection', false);
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('Philippine Homeowners Associations', false);

        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('<meta property="og:description"', false);
        $response->assertSee('<meta property="og:image" content="'.url('og/default.png').'"', false);
        $response->assertSee('<meta property="og:type" content="website"', false);
        $response->assertSee('<meta property="og:locale" content="en-PH"', false);

        $response->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
        $response->assertSee('<meta name="twitter:title"', false);

        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type":"Organization"', false);
        $response->assertSee('"@type":"SoftwareApplication"', false);
        $response->assertSee('"@type":"FAQPage"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);

        $response->assertSee('<link rel="icon" href="/favicon.ico"', false);
        $response->assertSee('<link rel="manifest" href="/site.webmanifest">', false);
    }

    public function test_public_org_home_is_indexable_and_has_canonical(): void
    {
        $org = Organization::factory()->create(['name' => 'Sunshine HOA']);

        $response = $this->get(route('home', $org->slug));

        $response->assertOk();

        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('<meta property="og:title" content="Household lookup', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type":"Organization"', false);

        $response->assertDontSee('<meta name="robots" content="noindex', false);
    }

    public function test_login_page_is_noindex(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $response->assertSee('<meta name="googlebot" content="noindex, nofollow">', false);
        $response->assertSee('<title>BarangayBooks</title>', false);
        $response->assertSee('<meta property="og:title"', false);
    }

    public function test_register_page_is_noindex(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_dashboard_is_noindex(): void
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org->slug));

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_profile_page_is_noindex(): void
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_organization_dashboard_is_noindex(): void
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org->slug));

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_organization_settings_is_noindex(): void
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.settings.show', $org->slug));

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_privacy_and_terms_have_meta_description_but_no_noindex(): void
    {
        foreach (['privacy', 'terms'] as $routeName) {
            $response = $this->get(route($routeName));
            $response->assertOk();
            $response->assertSee('<meta name="description"', false);
            $response->assertSee('<link rel="canonical"', false);
            $response->assertSee('<meta property="og:title"', false);
            $response->assertDontSee('<meta name="robots" content="noindex', false);
        }
    }

    public function test_sitemap_returns_valid_xml_with_marketing_urls(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');

        $xml = $response->getContent();
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xml);
        $this->assertStringContainsString('<loc>'.route('register').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('privacy').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('terms').'</loc>', $xml);
    }

    public function test_robots_txt_disallows_authed_paths_and_references_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /dashboard', $content);
        $this->assertStringContainsString('Disallow: /profile', $content);
        $this->assertStringContainsString('Disallow: /onboarding/', $content);
        $this->assertStringContainsString('Disallow: /invitations/', $content);
        $this->assertStringContainsString('Disallow: /billing/', $content);
        $this->assertStringContainsString('Sitemap:', $content);
        $this->assertStringContainsString('/sitemap.xml', $content);
    }

    public function test_html_lang_is_en_ph(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<html lang="en-PH"', false);
    }

    public function test_seo_meta_does_not_emit_duplicate_canons(): void
    {
        $response = $this->get('/');

        $content = $response->getContent();

        $this->assertSame(1, substr_count($content, 'rel="canonical"'));
    }
}
