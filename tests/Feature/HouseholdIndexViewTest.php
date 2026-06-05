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
        $response->assertSeeInOrder(['Name', 'Block', 'Lot', 'Actions']);
        $response->assertSee('Dela Cruz Residence');
    }

    public function test_index_redirects_guest_to_login(): void
    {
        $org = Organization::factory()->create();

        $this->get(route('households.index', $org))->assertRedirect(route('login'));
    }
}
