<?php

namespace Tests\Unit;

use App\Models\Contribution;
use App\Models\Household;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_contribution_belongs_to_household_and_defaults_to_pending_verification(): void
    {
        $contribution = Contribution::factory()->create();

        $this->assertInstanceOf(Household::class, $contribution->household);
        $this->assertSame('pending', $contribution->verification_status);
    }

    public function test_for_organization_scope_filters_contributions(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $household = Household::factory()->create(['organization_id' => $organization->id]);
        $otherHousehold = Household::factory()->create(['organization_id' => $otherOrganization->id]);
        $included = Contribution::factory()->create(['household_id' => $household->id]);
        Contribution::factory()->create(['household_id' => $otherHousehold->id]);

        $results = Contribution::forOrganization($organization)->pluck('id');

        $this->assertTrue($results->contains($included->id));
        $this->assertCount(1, $results);
    }
}
