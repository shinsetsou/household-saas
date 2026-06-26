<?php

namespace Tests\Unit;

use App\Models\Contribution;
use App\Models\Household;
use App\Models\Organization;
use App\Services\DelinquencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DelinquencyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fully_paid_household_is_not_delinquent(): void
    {
        $organization = Organization::factory()->create(['monthly_due_amount' => 200]);
        $household = Household::factory()->create(['organization_id' => $organization->id]);
        Contribution::factory()->create([
            'household_id' => $household->id,
            'amount' => 200,
            'status' => 'paid',
            'verification_status' => 'verified',
            'month_for' => now()->startOfMonth()->toDateString(),
            'created_at' => now()->startOfMonth(),
        ]);

        $rows = app(DelinquencyService::class)->forOrganization($organization, now()->year, now()->month);

        $this->assertSame([], $rows);
    }

    public function test_missing_month_is_delinquent(): void
    {
        $organization = Organization::factory()->create(['monthly_due_amount' => 200]);
        Household::factory()->create(['organization_id' => $organization->id]);

        $rows = app(DelinquencyService::class)->forOrganization($organization, now()->year, now()->month);

        $this->assertCount(1, $rows);
        $this->assertSame(200.0, $rows[0]['amount_due']);
    }

    public function test_lacking_or_unverified_contribution_is_delinquent(): void
    {
        $organization = Organization::factory()->create(['monthly_due_amount' => 200]);
        $household = Household::factory()->create(['organization_id' => $organization->id]);
        Contribution::factory()->create([
            'household_id' => $household->id,
            'amount' => 100,
            'status' => 'lacking',
            'verification_status' => 'pending',
            'month_for' => now()->startOfMonth()->toDateString(),
            'created_at' => now()->startOfMonth(),
        ]);

        $rows = app(DelinquencyService::class)->forOrganization($organization, now()->year, now()->month);

        $this->assertCount(1, $rows);
        $this->assertSame(200.0, $rows[0]['amount_due']);
    }

    public function test_delinquency_uses_month_for_not_created_at(): void
    {
        $organization = Organization::factory()->create(['monthly_due_amount' => 200]);
        $household = Household::factory()->create(['organization_id' => $organization->id]);

        // Paid in June but credited to May (resident paid late, treasurer corrected the month).
        Contribution::factory()->create([
            'household_id' => $household->id,
            'amount' => 200,
            'status' => 'paid',
            'verification_status' => 'verified',
            'month_for' => now()->startOfMonth()->subMonth()->toDateString(),
            'created_at' => now()->startOfMonth(),
        ]);

        // May should NOT be delinquent anymore, but the current month is.
        $previousMonth = app(DelinquencyService::class)
            ->forOrganization($organization, now()->subMonth()->year, now()->subMonth()->month);
        $this->assertSame([], $previousMonth);

        $currentMonth = app(DelinquencyService::class)
            ->forOrganization($organization, now()->year, now()->month);
        $this->assertCount(1, $currentMonth);
        $this->assertSame(200.0, $currentMonth[0]['amount_due']);
    }
}
