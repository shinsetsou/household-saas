<?php

namespace Tests\Unit;

use App\Models\Expense;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_belongs_to_organization_and_scope_filters_by_organization(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $included = Expense::factory()->create(['organization_id' => $organization->id]);
        Expense::factory()->create(['organization_id' => $otherOrganization->id]);

        $this->assertInstanceOf(Organization::class, $included->organization);
        $this->assertSame([$included->id], Expense::forOrganization($organization)->pluck('id')->all());
    }
}
