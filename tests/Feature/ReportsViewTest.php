<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Expense;
use App\Models\Household;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsViewTest extends TestCase
{
    use RefreshDatabase;

    private function admin(Organization $org): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        return $user;
    }

    public function test_contributions_report_renders_hero_and_stat_tiles(): void
    {
        $org = Organization::factory()->create();
        $user = $this->admin($org);
        $household = Household::factory()->create(['organization_id' => $org->id]);
        Contribution::factory()->create([
            'household_id' => $household->id,
            'amount' => 250,
            'verification_status' => 'verified',
        ]);
        Contribution::factory()->create([
            'household_id' => $household->id,
            'amount' => 250,
            'verification_status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('reports.contributions.index', $org));

        $response->assertOk();
        $response->assertSee('Verified total');
        $response->assertSee('Verified count');
        $response->assertSee('Pending review');
        $response->assertSee('Last 12 months');
        $response->assertSee('PDF');
        $response->assertSee('Excel');
    }

    public function test_expenses_report_renders_hero_and_stat_tiles(): void
    {
        $org = Organization::factory()->create();
        $user = $this->admin($org);
        Expense::create(['organization_id' => $org->id, 'amount' => 100, 'description' => 'Cleaning']);
        Expense::create(['organization_id' => $org->id, 'amount' => 50, 'description' => 'Lightbulbs']);

        $response = $this->actingAs($user)->get(route('reports.expenses.index', $org));

        $response->assertOk();
        $response->assertSee('Filtered total');
        $response->assertSee('Total entries');
        $response->assertSee('Year-to-date');
        $response->assertSee('Cleaning');
        $response->assertSee('Lightbulbs');
    }

    public function test_delinquency_report_renders_hero_and_empty_state_when_all_paid(): void
    {
        $org = Organization::factory()->create();
        $user = $this->admin($org);
        Household::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->get(route('reports.delinquency.index', $org));

        $response->assertOk();
        $response->assertSee('Delinquent');
        $response->assertSee('Amount due');
        $response->assertSee('Healthy');
        $response->assertSee('No delinquents this period');
    }
}