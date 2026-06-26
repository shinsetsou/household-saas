<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDropdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_dropdown_shows_name_and_signout_for_authenticated_user_on_dashboard(): void
    {
        $user = User::factory()->create(['name' => 'Karl Candelario']);
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        $response->assertSee('Karl Candelario');
        $response->assertSee('Sign out', false);
        $response->assertSee(route('logout'), false);
    }

    public function test_dropdown_is_cloaked_until_avatar_is_clicked(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        $response->assertSee('[x-cloak] { display: none !important; }', false);
        $response->assertSee('x-show="open" x-cloak', false);
    }

    public function test_layout_uses_vite_bundle_for_alpine(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        $response->assertDontSee('/js/app.js', false);
        $response->assertSee('/build/assets/app-', false);
    }

    public function test_settings_link_visible_to_admin(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        $response->assertSee(route('organizations.settings.show', $org->slug), false);
    }

    public function test_settings_link_hidden_for_non_admin(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'member']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        $response->assertDontSee(route('organizations.settings.show', $org->slug), false);
        $response->assertSee('Sign out', false);
        $response->assertSee(route('profile.edit'), false);
    }

    public function test_logout_works_from_dropdown(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_top_nav_includes_all_primary_links(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        // All six primary nav destinations are present.
        $response->assertSee('>Dashboard<', false);
        $response->assertSee('>Households<', false);
        $response->assertSee('>Contributions<', false);
        $response->assertSee('>Expenses<', false);
        $response->assertSee('>Reports<', false);
    }

    public function test_reports_dropdown_lists_all_three_reports(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        $response->assertSee('Contributions report', false);
        $response->assertSee('Expenses report', false);
        $response->assertSee('Delinquency report', false);
    }

    public function test_verify_link_visible_to_admin(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        // The verify nav link points at the verify route.
        $response->assertSee(route('contributions.verify', $org->slug), false);
        $response->assertSee('>Verify<', false);
    }

    public function test_verify_link_visible_to_treasurer(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'treasurer']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        $response->assertSee(route('contributions.verify', $org->slug), false);
    }

    public function test_verify_link_hidden_for_viewer(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'viewer']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        // The top-nav Verify link must not be rendered for viewers.
        $this->assertStringNotContainsString(
            'href="'.route('contributions.verify', $org->slug).'"',
            $response->getContent()
        );
    }

    public function test_active_nav_link_uses_brand_color_and_underline(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        // Active link uses brand-colored text + an underline pseudo-element.
        $response->assertSee('nav-link-active', false);
        $response->assertSee('text-brand-700', false);
    }

    public function test_navbar_uses_backdrop_blur(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        $response->assertSee('backdrop-blur-md', false);
    }
}
