<?php

namespace Tests\Unit;

use App\Models\Contribution;
use App\Models\Household;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdTest extends TestCase
{
    use RefreshDatabase;

    public function test_mass_assignment_saves_household_tenant_fields(): void
    {
        $organization = Organization::factory()->create();

        $household = Household::create([
            'organization_id' => $organization->id,
            'name' => 'Santos Family',
            'block' => 4,
            'lot' => 12,
        ]);

        $this->assertSame($organization->id, $household->organization_id);
        $this->assertSame('Santos Family', $household->name);
        $this->assertSame(4, $household->block);
        $this->assertSame(12, $household->lot);
    }

    public function test_household_belongs_to_organization_and_has_contributions(): void
    {
        $household = Household::factory()->create();
        Contribution::factory()->create(['household_id' => $household->id]);

        $this->assertInstanceOf(Organization::class, $household->organization);
        $this->assertCount(1, $household->contributions);
    }
}
