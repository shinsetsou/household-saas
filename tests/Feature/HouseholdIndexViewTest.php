<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdIndexViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_households_with_block_and_lot_columns(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);
        Household::factory()->create([
            'organization_id' => $org->id,
            'name' => 'Dela Cruz Residence',
            'block' => 3,
            'lot' => 9,
        ]);

        $response = $this->actingAs($user)->get(route('households.index', $org));

        $response->assertOk();
        $response->assertSeeInOrder(['Household', 'Block', 'Lot', 'Actions']);
        $response->assertSee('Dela Cruz Residence');
        $response->assertSee('Block 3');
        $response->assertSee('Lot 9');
    }

    public function test_index_renders_new_hero_and_stat_tiles(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);
        Household::factory()->count(2)->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->get(route('households.index', $org));

        $response->assertOk();
        $response->assertSee('All households');
        $response->assertSee('Households'); // stat tile label
        $response->assertSee('Blocks'); // stat tile label
        $response->assertSee('Active payers'); // stat tile label
        $response->assertSee('Add household');
        $response->assertSee('Import CSV');
    }

    public function test_index_redirects_guest_to_login(): void
    {
        $org = Organization::factory()->create();

        $this->get(route('households.index', $org))->assertRedirect(route('login'));
    }
}
