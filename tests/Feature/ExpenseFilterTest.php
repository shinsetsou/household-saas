<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseFilterTest extends TestCase
{
    use RefreshDatabase;

    private function actingMember(Organization $org): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        return $user;
    }

    public function test_expenses_index_renders_stat_tiles(): void
    {
        $org = Organization::factory()->create();
        $user = $this->actingMember($org);
        Expense::factory()->create(['organization_id' => $org->id, 'amount' => 250, 'description' => 'Hall cleaning']);
        Expense::factory()->create(['organization_id' => $org->id, 'amount' => 100, 'description' => 'Light bulbs']);

        $response = $this->actingAs($user)->get(route('expenses.index', $org));

        $response->assertOk();
        $response->assertSee('Total expenses', false);
        $response->assertSee('Total entries', false);
        $response->assertSee('Year-to-date', false);
        // Filtered sum should be 350.00 (formatted with thousands separator by number_format(0))
        $response->assertSee('350', false);
    }

    public function test_empty_filter_values_show_all_expenses(): void
    {
        $org = Organization::factory()->create();
        $user = $this->actingMember($org);
        Expense::factory()->create(['organization_id' => $org->id, 'description' => 'January Power', 'created_at' => '2026-01-15']);
        Expense::factory()->create(['organization_id' => $org->id, 'description' => 'June Water', 'created_at' => '2026-06-20']);

        // "All months" / "All years" submit empty strings — must NOT filter everything out.
        $response = $this->actingAs($user)->get(route('expenses.index', [$org, 'month' => '', 'year' => '']));

        $response->assertOk();
        $response->assertSee('January Power');
        $response->assertSee('June Water');
    }

    public function test_month_filter_narrows_results(): void
    {
        $org = Organization::factory()->create();
        $user = $this->actingMember($org);
        Expense::factory()->create(['organization_id' => $org->id, 'description' => 'January Power', 'created_at' => '2026-01-15']);
        Expense::factory()->create(['organization_id' => $org->id, 'description' => 'June Water', 'created_at' => '2026-06-20']);

        $response = $this->actingAs($user)->get(route('expenses.index', [$org, 'month' => 6, 'year' => '']));

        $response->assertOk();
        $response->assertSee('June Water');
        $response->assertDontSee('January Power');
    }
}
