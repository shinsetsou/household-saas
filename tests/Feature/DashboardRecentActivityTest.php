<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Expense;
use App\Models\Household;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardRecentActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_when_org_has_no_contributions_or_expenses(): void
    {
        // Regression for the 500 on staging: an empty recentContributions
        // collection used to stay as an Eloquent\Collection after ->map(),
        // which then made Eloquent\Collection::merge() call getKey() on the
        // array items inside recentExpenses. Force-drop to a base
        // Support\Collection before merging.
        $plan = Plan::create([
            'slug' => 'community',
            'name' => 'Community',
            'price_cents' => 0,
            'max_households' => 25,
            'csv_import' => false,
            'api_access' => false,
        ]);

        $org = Organization::create([
            'name' => 'Empty Org',
            'slug' => 'empty-org',
            'monthly_due_amount' => 100,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
            'plan_id' => $plan->id,
            'trial_ends_at' => now()->addDays(7),
        ]);

        Subscription::create([
            'organization_id' => $org->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_TRIALING,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addDays(7),
        ]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $org->users()->attach($user->id, ['role' => 'admin']);

        $response = $this->actingAs($user)
            ->get(route('organizations.index', $org->slug));

        $response->assertOk();
    }

    public function test_dashboard_renders_when_org_has_only_expenses(): void
    {
        $plan = Plan::create([
            'slug' => 'community',
            'name' => 'Community',
            'price_cents' => 0,
            'max_households' => 25,
            'csv_import' => false,
            'api_access' => false,
        ]);

        $org = Organization::create([
            'name' => 'Expense Org',
            'slug' => 'expense-org',
            'monthly_due_amount' => 100,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
            'plan_id' => $plan->id,
            'trial_ends_at' => now()->addDays(7),
        ]);

        Subscription::create([
            'organization_id' => $org->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_TRIALING,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addDays(7),
        ]);

        Expense::create([
            'organization_id' => $org->id,
            'description' => 'Snacks',
            'amount' => 250,
            'receipt_status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $org->users()->attach($user->id, ['role' => 'admin']);

        $response = $this->actingAs($user)
            ->get(route('organizations.index', $org->slug));

        $response->assertOk();
    }

    public function test_dashboard_renders_when_org_has_only_contributions(): void
    {
        $plan = Plan::create([
            'slug' => 'community',
            'name' => 'Community',
            'price_cents' => 0,
            'max_households' => 25,
            'csv_import' => false,
            'api_access' => false,
        ]);

        $org = Organization::create([
            'name' => 'Contribution Org',
            'slug' => 'contribution-org',
            'monthly_due_amount' => 100,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
            'plan_id' => $plan->id,
            'trial_ends_at' => now()->addDays(7),
        ]);

        Subscription::create([
            'organization_id' => $org->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_TRIALING,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addDays(7),
        ]);

        $household = Household::create([
            'organization_id' => $org->id,
            'name' => 'Cruz',
            'block' => '1',
            'lot' => '1',
        ]);

        Contribution::create([
            'household_id' => $household->id,
            'amount' => 100,
            'status' => 'paid',
            'verification_status' => 'verified',
            'reference_number' => Str::uuid(),
        ]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $org->users()->attach($user->id, ['role' => 'admin']);

        $response = $this->actingAs($user)
            ->get(route('organizations.index', $org->slug));

        $response->assertOk();
    }
}
