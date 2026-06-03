<?php

namespace Tests\Feature;

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
}
