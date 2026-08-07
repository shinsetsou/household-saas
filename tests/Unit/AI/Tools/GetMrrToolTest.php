<?php

declare(strict_types=1);

namespace Tests\Unit\AI\Tools;

use App\AI\Tools\Founder\GetMrrTool;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetMrrToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_zero_when_no_active_subscriptions(): void
    {
        $tool = new GetMrrTool;

        $result = $tool->execute([])->toArray();

        $this->assertTrue($result['ok']);
        $this->assertSame(0, $result['data']['mrr_usd_cents']);
        $this->assertSame(0, $result['data']['active_subscriptions']);
    }

    public function test_sums_active_and_trialing_plan_prices(): void
    {
        $planA = Plan::create([
            'slug' => 'basic',
            'name' => 'Basic',
            'price_cents' => 10000,
            'currency' => 'PHP',
            'interval' => 'month',
            'max_households' => 50,
            'csv_import' => false,
            'api_access' => false,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $planB = Plan::create([
            'slug' => 'pro',
            'name' => 'Pro',
            'price_cents' => 30000,
            'currency' => 'PHP',
            'interval' => 'month',
            'max_households' => 200,
            'csv_import' => true,
            'api_access' => true,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a', 'monthly_due_amount' => 100]);
        $orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b', 'monthly_due_amount' => 100]);

        Subscription::create([
            'organization_id' => $orgA->id,
            'plan_id' => $planA->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);
        Subscription::create([
            'organization_id' => $orgB->id,
            'plan_id' => $planB->id,
            'status' => Subscription::STATUS_TRIALING,
        ]);

        $result = (new GetMrrTool)->execute([])->toArray();

        $this->assertSame(40000, $result['data']['mrr_usd_cents']);
        $this->assertSame(2, $result['data']['active_subscriptions']);
    }
}
