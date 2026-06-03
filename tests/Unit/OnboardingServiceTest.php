<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingServiceTest extends TestCase
{
    use RefreshDatabase;

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
}
