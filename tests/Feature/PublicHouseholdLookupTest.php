<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Household;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicHouseholdLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_renders_search_hero_for_guests(): void
    {
        $org = Organization::factory()->create(['name' => 'Villa Cainta']);

        $response = $this->get(route('home', $org->slug));

        $response->assertOk();
        $response->assertSee('Find your household');
        $response->assertSee('Block number');
        $response->assertSee('Lot number');
        $response->assertSee('Find my household');
        $response->assertSee('Villa Cainta');
    }

    public function test_public_home_search_finds_household_and_shows_submit_cta(): void
    {
        $org = Organization::factory()->create();
        $household = Household::factory()->create([
            'organization_id' => $org->id,
            'name' => 'Dela Cruz Residence',
            'block' => 3,
            'lot' => 9,
        ]);

        $response = $this->get(route('home', [$org->slug, 'block' => 3, 'lot' => 9]));

        $response->assertOk();
        $response->assertSee('Dela Cruz Residence');
        $response->assertSee('Block 3');
        $response->assertSee('Lot 9');
        $response->assertSee('Submit payment');
        $response->assertSee(route('contributions.create', [$org->slug, $household->id]), false);
    }

    public function test_public_home_shows_error_when_household_not_found(): void
    {
        $org = Organization::factory()->create();

        $response = $this->get(route('home', [$org->slug, 'block' => 99, 'lot' => 99]));

        $response->assertOk();
        $response->assertSee('No household found');
    }

    public function test_public_home_does_not_leak_other_organizations(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        Household::factory()->create(['organization_id' => $orgA->id, 'name' => 'AlphaOnly', 'block' => 1, 'lot' => 1]);
        Household::factory()->create(['organization_id' => $orgB->id, 'name' => 'BetaOnly', 'block' => 1, 'lot' => 1]);

        $response = $this->get(route('home', [$orgA->slug, 'block' => 1, 'lot' => 1]));

        $response->assertOk();
        $response->assertSee('AlphaOnly');
        $response->assertDontSee('BetaOnly');
    }

    public function test_contribution_create_renders_for_found_household(): void
    {
        $org = Organization::factory()->create();
        $household = Household::factory()->create([
            'organization_id' => $org->id,
            'name' => 'Dela Cruz Residence',
            'block' => 3,
            'lot' => 9,
        ]);

        $response = $this->get(route('contributions.create', [$org, $household]));

        $response->assertOk();
        $response->assertSee('Submit payment');
        $response->assertSee('Dela Cruz Residence');
        $response->assertSee('Monthly due');
        $response->assertSee('Proof of payment');
    }

    public function test_public_block_lot_lookup_renders_show_view_with_all_stats(): void
    {
        // Regression: the public lookup renders households.show directly, so the
        // view needs verifiedCount / pendingCount / lastPaidAt passed through.
        // Without them, hitting /households?block=9&lot=1 throws
        // "Undefined variable $lastPaidAt".
        $org = Organization::factory()->create();
        $household = Household::factory()->create([
            'organization_id' => $org->id,
            'name' => 'Cruz Residence',
            'block' => 9,
            'lot' => 1,
        ]);

        $response = $this->get(route('households.index', [$org->slug, 'block' => 9, 'lot' => 1]));

        $response->assertOk();
        $response->assertSee('Cruz Residence');
        // The stat-tile labels from the redesigned show view must render.
        $response->assertSee('Total paid');
        $response->assertSee('Verified payments');
        $response->assertSee('Last payment');
    }

    public function test_last_paid_at_renders_diff_for_humans_when_household_has_payment(): void
    {
        // Regression: ->max('created_at') returns a raw string in MySQL,
        // which crashed ->diffForHumans() in the show view.
        $org = Organization::factory()->create();
        $household = Household::factory()->create([
            'organization_id' => $org->id,
            'block' => 1,
            'lot' => 1,
        ]);
        Contribution::factory()->create([
            'household_id' => $household->id,
            'verification_status' => 'verified',
            'created_at' => now()->subDays(3),
        ]);

        $response = $this->get(route('households.index', [$org->slug, 'block' => 1, 'lot' => 1]));

        $response->assertOk();
        $response->assertSee('3 days ago');
    }

    public function test_last_paid_at_handles_null_when_household_has_no_payments(): void
    {
        $org = Organization::factory()->create();
        $household = Household::factory()->create([
            'organization_id' => $org->id,
            'block' => 1,
            'lot' => 1,
        ]);

        $response = $this->get(route('households.index', [$org->slug, 'block' => 1, 'lot' => 1]));

        $response->assertOk();
        // null lastPaidAt must render "No payments recorded" without crashing.
        $response->assertSee('No payments recorded');
    }
}
