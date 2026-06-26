<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsurePlanAllowsHouseholdBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->organization = Organization::create([
            'name' => 'Test HOA',
            'monthly_due_amount' => 200,
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
        ]);
        $this->organization->users()->attach($this->admin->id, ['role' => 'admin']);
    }

    protected function communityPlan(int $limit = 50): Plan
    {
        return Plan::create([
            'slug' => 'community',
            'name' => 'Community',
            'price_cents' => 19900,
            'max_households' => $limit,
            'sort_order' => 1,
        ]);
    }

    protected function cityPlan(): Plan
    {
        return Plan::create([
            'slug' => 'city',
            'name' => 'City',
            'price_cents' => 99900,
            'max_households' => null,
            'sort_order' => 3,
        ]);
    }

    protected function fillHouseholds(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Household::create([
                'organization_id' => $this->organization->id,
                'block' => (int) ceil($i / 100),
                'lot' => $i,
                'name' => "House {$i}",
            ]);
        }
    }

    public function test_below_limit_allows_create(): void
    {
        $this->organization->update(['plan_id' => $this->communityPlan(50)->id]);
        $this->fillHouseholds(49);

        $this->assertFalse($this->organization->fresh()->hasReachedHouseholdLimit());

        $this->actingAs($this->admin)
            ->get(route('households.create', $this->organization->slug))
            ->assertOk();
    }

    public function test_exactly_at_limit_blocks_create(): void
    {
        $this->organization->update(['plan_id' => $this->communityPlan(50)->id]);
        $this->fillHouseholds(50);

        $this->assertTrue($this->organization->fresh()->hasReachedHouseholdLimit());

        $this->actingAs($this->admin)
            ->get(route('households.create', $this->organization->slug))
            ->assertRedirect(route('organizations.settings.billing.show', $this->organization->slug))
            ->assertSessionHas('error');
    }

    public function test_over_limit_still_blocks_create(): void
    {
        $this->organization->update(['plan_id' => $this->communityPlan(50)->id]);
        $this->fillHouseholds(75);

        $this->actingAs($this->admin)
            ->get(route('households.create', $this->organization->slug))
            ->assertRedirect(route('organizations.settings.billing.show', $this->organization->slug));
    }

    public function test_unlimited_plan_at_high_count_does_not_block(): void
    {
        $this->organization->update(['plan_id' => $this->cityPlan()->id]);
        $this->fillHouseholds(500);

        $this->actingAs($this->admin)
            ->get(route('households.create', $this->organization->slug))
            ->assertOk();
    }

    public function test_org_without_plan_blocks_create_with_no_plan_message(): void
    {
        $this->fillHouseholds(0);

        $this->assertSame(null, $this->organization->fresh()->householdLimit());
        $this->assertFalse($this->organization->fresh()->hasReachedHouseholdLimit());

        $this->actingAs($this->admin)
            ->get(route('households.create', $this->organization->slug))
            ->assertOk();
    }

    public function test_import_route_also_blocked_at_limit(): void
    {
        $this->organization->update(['plan_id' => $this->communityPlan(3)->id]);
        $this->fillHouseholds(3);

        $this->actingAs($this->admin)
            ->get(route('households.import.create', $this->organization->slug))
            ->assertRedirect(route('organizations.settings.billing.show', $this->organization->slug))
            ->assertSessionHas('error');
    }

    public function test_storage_route_also_blocked_at_limit(): void
    {
        $this->organization->update(['plan_id' => $this->communityPlan(2)->id]);
        $this->fillHouseholds(2);

        $this->actingAs($this->admin)
            ->post(route('households.store', $this->organization->slug), [
                'name' => 'New House',
                'block' => 1,
                'lot' => 99,
            ])
            ->assertRedirect(route('organizations.settings.billing.show', $this->organization->slug));
    }

    public function test_below_limit_stores_successfully(): void
    {
        $this->organization->update(['plan_id' => $this->communityPlan(50)->id]);
        $this->fillHouseholds(10);

        $this->actingAs($this->admin)
            ->post(route('households.store', $this->organization->slug), [
                'name' => 'New House',
                'block' => 1,
                'lot' => 11,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('households', [
            'organization_id' => $this->organization->id,
            'name' => 'New House',
            'block' => 1,
            'lot' => 11,
        ]);
    }

    public function test_middleware_passes_through_when_route_has_no_organization(): void
    {
        Route::get('/_test/no-org', fn () => 'ok')
            ->middleware(\App\Http\Middleware\EnsurePlanAllowsHousehold::class);

        $this->get('/_test/no-org')->assertOk()->assertSee('ok');
    }

    public function test_trialing_org_is_capped_at_trial_cap_regardless_of_plan(): void
    {
        $this->organization->update([
            'plan_id' => $this->communityPlan(50)->id,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
            'trial_ends_at' => now()->addDays(7),
        ]);
        $this->fillHouseholds(Organization::TRIAL_HOUSEHOLD_CAP);

        // 20 households on a trial that allows 50 in the plan — still blocked.
        $this->assertSame(Organization::TRIAL_HOUSEHOLD_CAP, $this->organization->fresh()->householdLimit());
        $this->assertTrue($this->organization->fresh()->hasReachedHouseholdLimit());

        $this->actingAs($this->admin)
            ->get(route('households.create', $this->organization->slug))
            ->assertRedirect(route('organizations.settings.billing.show', $this->organization->slug))
            ->assertSessionHas('error', fn (string $msg) => str_contains($msg, 'free trial'));
    }

    public function test_trialing_org_below_trial_cap_can_still_create(): void
    {
        $this->organization->update([
            'plan_id' => $this->communityPlan(50)->id,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
            'trial_ends_at' => now()->addDays(7),
        ]);
        $this->fillHouseholds(Organization::TRIAL_HOUSEHOLD_CAP - 1);

        $this->assertFalse($this->organization->fresh()->hasReachedHouseholdLimit());

        $this->actingAs($this->admin)
            ->post(route('households.store', $this->organization->slug), [
                'name' => 'One more house',
                'block' => 1,
                'lot' => 99,
            ])
            ->assertRedirect();
    }

    public function test_expired_trial_falls_back_to_plan_limit(): void
    {
        // Trial expired yesterday — org should be re-classified by the daily cron
        // (trials:expire) but householdLimit() must already stop applying the
        // trial cap the moment isOnTrial() returns false.
        $this->organization->update([
            'plan_id' => $this->communityPlan(50)->id,
            'subscription_status' => Organization::SUBSCRIPTION_PAST_DUE,
            'trial_ends_at' => now()->subDay(),
        ]);
        $this->fillHouseholds(20);

        $this->assertSame(50, $this->organization->fresh()->householdLimit());
        $this->assertFalse($this->organization->fresh()->hasReachedHouseholdLimit());

        $this->actingAs($this->admin)
            ->get(route('households.create', $this->organization->slug))
            ->assertOk();
    }

    public function test_active_subscription_falls_back_to_plan_limit(): void
    {
        $this->organization->update([
            'plan_id' => $this->communityPlan(50)->id,
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
            'trial_ends_at' => null,
        ]);
        $this->fillHouseholds(20);

        $this->assertSame(50, $this->organization->fresh()->householdLimit());
        $this->assertFalse($this->organization->fresh()->hasReachedHouseholdLimit());
    }
}