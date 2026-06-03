<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_records_user_organization_action_and_metadata(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $log = AuditLog::record($organization, 'expense.created', $user, null, ['amount' => 500]);

        $this->assertSame($user->id, $log->user_id);
        $this->assertSame($organization->id, $log->organization_id);
        $this->assertSame('expense.created', $log->action);
        $this->assertSame(['amount' => 500], $log->metadata);
    }
}
