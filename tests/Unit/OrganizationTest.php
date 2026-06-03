<?php

namespace Tests\Unit;

use App\Models\Contribution;
use App\Models\Household;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_contributions_is_scoped_to_organization(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $household = Household::factory()->create(['organization_id' => $organization->id]);
        $otherHousehold = Household::factory()->create(['organization_id' => $otherOrganization->id]);

        Contribution::factory()->create(['household_id' => $household->id, 'amount' => 200]);
        Contribution::factory()->create(['household_id' => $otherHousehold->id, 'amount' => 999]);

        $this->assertSame(200.0, (float) $organization->totalContributions());
    }

    public function test_yearly_contributions_only_include_selected_year(): void
    {
        $organization = Organization::factory()->create();
        $household = Household::factory()->create(['organization_id' => $organization->id]);

        Contribution::factory()->create([
            'household_id' => $household->id,
            'amount' => 200,
            'created_at' => '2026-01-15 00:00:00',
        ]);
        Contribution::factory()->create([
            'household_id' => $household->id,
            'amount' => 300,
            'created_at' => '2025-01-15 00:00:00',
        ]);

        $this->assertSame(200.0, (float) $organization->yearlyContributions(2026));
    }
}
