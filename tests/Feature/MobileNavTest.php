<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileNavTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrgAndAdmin(): array
    {
        $plan = Plan::create([
            'slug' => 'community',
            'name' => 'Community',
            'price_cents' => 0,
            'max_households' => 25,
            'csv_import' => false,
            'api_access' => false,
        ]);

        $org = Organization::create([
            'name' => 'Mobile Org',
            'slug' => 'mobile-org',
            'monthly_due_amount' => 100,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
            'plan_id' => $plan->id,
            'trial_ends_at' => now()->addDays(7),
        ]);

        Subscription::create([
            'organization_id' => $org->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_TRIALING,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addDays(7),
        ]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $org->users()->attach($user->id, ['role' => 'admin']);

        return [$user, $org];
    }

    public function test_brand_text_is_hidden_until_lg(): void
    {
        [$user, $org] = $this->makeOrgAndAdmin();

        $html = $this->actingAs($user)
            ->get(route('organizations.index', $org->slug))
            ->assertOk()
            ->getContent();

        // Brand text shows from lg+ so it doesn't crowd the nav at sm/md.
        $this->assertStringContainsString('hidden lg:inline', $html);
    }

    public function test_nav_styles_use_tailwindcss_style_tag(): void
    {
        [$user, $org] = $this->makeOrgAndAdmin();

        $html = $this->actingAs($user)
            ->get(route('organizations.index', $org->slug))
            ->assertOk()
            ->getContent();

        // Regression: the @apply rules live in a <style type="text/tailwindcss">
        // block so the Play CDN's JIT actually processes them. A plain
        // <style> block is silently dropped, which left .mobile-nav-link
        // unstyled and the hamburger dropdown looking cramped.
        $this->assertStringContainsString('<style type="text/tailwindcss">', $html);
        $this->assertStringContainsString('.mobile-nav-link', $html);
        $this->assertStringContainsString('.nav-link', $html);
    }

    public function test_hamburger_button_is_present_for_authenticated_user(): void
    {
        [$user, $org] = $this->makeOrgAndAdmin();

        $html = $this->actingAs($user)
            ->get(route('organizations.index', $org->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="mobile-menu-btn"', $html);
        $this->assertStringContainsString('aria-controls="mobile-menu"', $html);
        $this->assertStringContainsString('id="mobile-menu"', $html);
    }

    public function test_dark_mode_toggle_is_hidden_on_mobile(): void
    {
        [$user, $org] = $this->makeOrgAndAdmin();

        $html = $this->actingAs($user)
            ->get(route('organizations.index', $org->slug))
            ->assertOk()
            ->getContent();

        // Desktop theme toggle should be `hidden sm:inline-flex` so it's not
        // rendered into the cramped mobile bar.
        $this->assertMatchesRegularExpression(
            '/id="theme-toggle"[^>]*class="[^"]*hidden sm:inline-flex/',
            $html,
        );
    }

    public function test_mobile_menu_contains_theme_toggle(): void
    {
        [$user, $org] = $this->makeOrgAndAdmin();

        $html = $this->actingAs($user)
            ->get(route('organizations.index', $org->slug))
            ->assertOk()
            ->getContent();

        // The theme toggle that lives inside the mobile menu (so it isn't
        // competing for space in the top bar).
        $this->assertStringContainsString('id="mobile-theme-toggle"', $html);
    }

    public function test_mobile_menu_account_block_is_present(): void
    {
        [$user, $org] = $this->makeOrgAndAdmin();

        $html = $this->actingAs($user)
            ->get(route('organizations.index', $org->slug))
            ->assertOk()
            ->getContent();

        // Account block (avatar + name + email) lives inside the mobile menu,
        // not in a competing Alpine dropdown.
        $this->assertStringContainsString($user->name, $html);
        $this->assertStringContainsString($user->email, $html);
    }
}
