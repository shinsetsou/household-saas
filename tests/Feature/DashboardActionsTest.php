<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Household;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_add_household_button(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        $response->assertSee('Add Household', false);
        $response->assertSee(route('households.create', $org->slug), false);
    }

    public function test_dashboard_shows_import_csv_button(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        $response->assertSee('Import CSV', false);
        $response->assertSee(route('households.import.create', $org->slug), false);
    }

    public function test_total_contributions_counts_only_verified(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);
        $household = Household::factory()->create(['organization_id' => $org->id]);

        Contribution::factory()->create([
            'household_id' => $household->id,
            'amount' => 150,
            'verification_status' => 'verified',
        ]);
        // Pending + rejected must NOT be summed into the dashboard total.
        Contribution::factory()->create([
            'household_id' => $household->id,
            'amount' => 900,
            'verification_status' => 'pending',
        ]);
        Contribution::factory()->create([
            'household_id' => $household->id,
            'amount' => 700,
            'verification_status' => 'rejected',
        ]);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        $response->assertSee('150.00');
        $response->assertDontSee('900.00');
        $response->assertDontSee('1,750.00');
    }
}
