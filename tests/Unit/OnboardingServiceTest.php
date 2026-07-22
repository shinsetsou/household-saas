<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\BillingService;
use App\Services\OnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::create([
            'slug' => 'community',
            'name' => 'Community',
            'price_cents' => 19900,
            'max_households' => 50,
            'sort_order' => 1,
        ]);
    }

    public function test_create_organization_for_user_persists_org_attaches_admin_and_audits(): void
    {
        $user = User::factory()->create();
        $service = app(OnboardingService::class);

        $org = $service->createOrganizationForUser($user, [
            'name' => 'Sunshine HOA',
            'monthly_due_amount' => 250.00,
        ]);

        $this->assertInstanceOf(Organization::class, $org);
        $this->assertNotEmpty($org->slug);
        $this->assertSame('Sunshine HOA', $org->name);
        $this->assertEquals('250.00', $org->monthly_due_amount);

        $this->assertTrue($user->fresh()->belongsToOrganization($org));
        $pivotRole = $user->organizations()->where('organizations.id', $org->id)->first()->pivot->role;
        $this->assertSame('admin', $pivotRole);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'action' => 'organization.created',
            'auditable_type' => Organization::class,
            'auditable_id' => $org->id,
        ]);

        $log = AuditLog::where('organization_id', $org->id)->first();
        $this->assertSame(['via' => 'onboarding'], $log->metadata);
    }

    public function test_create_organization_for_user_starts_trial(): void
    {
        $user = User::factory()->create();
        $service = app(OnboardingService::class);

        $org = $service->createOrganizationForUser($user, [
            'name' => 'Trial HOA',
            'monthly_due_amount' => 200,
        ]);

        $this->assertSame(Organization::SUBSCRIPTION_TRIALING, $org->fresh()->subscription_status);
        $this->assertNotNull($org->fresh()->trial_ends_at);
        $this->assertTrue($org->fresh()->trial_ends_at->isFuture());
        $this->assertNotNull($org->fresh()->plan_id);

        $this->assertDatabaseHas('subscriptions', [
            'organization_id' => $org->id,
            'status' => Subscription::STATUS_TRIALING,
        ]);
    }

    public function test_trial_start_failure_rolls_back_org_creation(): void
    {
        $user = User::factory()->create();

        $stubBilling = $this->getMockBuilder(BillingService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['startTrial'])
            ->getMock();
        $stubBilling->method('startTrial')
            ->willThrowException(new \RuntimeException('Subscription insert failed'));

        $this->app->instance(BillingService::class, $stubBilling);

        $service = $this->app->make(OnboardingService::class);

        try {
            $service->createOrganizationForUser($user, [
                'name' => 'Should Not Persist',
                'monthly_due_amount' => 200,
            ]);
            $this->fail('Expected RuntimeException to bubble up.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Subscription insert failed', $e->getMessage());
        }

        $this->assertDatabaseMissing('organizations', ['name' => 'Should Not Persist']);
        $this->assertDatabaseMissing('organization_user', ['user_id' => $user->id]);
    }
}
