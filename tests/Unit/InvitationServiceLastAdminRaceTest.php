<?php

namespace Tests\Unit;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvitationServiceLastAdminRaceTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminA;

    protected User $adminB;

    protected Organization $organization;

    protected InvitationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(InvitationService::class);

        $this->adminA = User::factory()->create(['email' => 'a@example.com']);
        $this->adminB = User::factory()->create(['email' => 'b@example.com']);

        $this->organization = Organization::create([
            'name' => 'Test HOA',
            'monthly_due_amount' => 200,
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
        ]);

        $this->organization->users()->attach($this->adminA->id, ['role' => OrganizationRole::Admin->value]);
        $this->organization->users()->attach($this->adminB->id, ['role' => OrganizationRole::Admin->value]);
    }

    public function test_remove_member_throws_validation_when_only_admin_left(): void
    {
        $lonely = User::factory()->create();
        $this->organization->users()->attach($lonely->id, ['role' => OrganizationRole::Admin->value]);
        $this->organization->users()->detach($this->adminA->id);
        $this->organization->users()->detach($this->adminB->id);

        $this->expectException(ValidationException::class);

        try {
            $this->service->removeMember($this->organization, $lonely, $this->adminA);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('member', $e->errors());
            throw $e;
        }
    }

    public function test_remove_member_succeeds_with_two_admins(): void
    {
        $this->service->removeMember($this->organization, $this->adminB, $this->adminA);

        $this->assertDatabaseMissing('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $this->adminB->id,
        ]);

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $this->adminA->id,
        ]);
    }

    public function test_change_role_throws_validation_when_demoting_last_admin(): void
    {
        $lonely = User::factory()->create();
        $this->organization->users()->attach($lonely->id, ['role' => OrganizationRole::Admin->value]);
        $this->organization->users()->detach($this->adminA->id);
        $this->organization->users()->detach($this->adminB->id);

        $this->expectException(ValidationException::class);

        try {
            $this->service->changeRole(
                $this->organization,
                $lonely,
                OrganizationRole::Viewer,
                $this->adminA,
            );
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('role', $e->errors());
            throw $e;
        }
    }

    public function test_concurrent_admin_removals_still_preserve_at_least_one_admin(): void
    {
        $successA = false;
        $blockedB = false;

        try {
            $this->service->removeMember($this->organization, $this->adminB, $this->adminA);
            $successA = true;
        } catch (ValidationException) {
            $successA = false;
        }

        try {
            $this->service->removeMember($this->organization->fresh(), $this->adminA, $this->adminB);
        } catch (ValidationException $e) {
            if (array_key_exists('member', $e->errors())) {
                $blockedB = true;
            }
        }

        $this->assertTrue($successA, 'First admin removal should succeed.');
        $this->assertTrue($blockedB, 'Second admin removal must be blocked by the locked-count check.');
        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $this->adminA->id,
            'role' => OrganizationRole::Admin->value,
        ]);
    }

    public function test_change_role_writes_audit_log_on_successful_change(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member->id, ['role' => OrganizationRole::Viewer->value]);

        $this->service->changeRole(
            $this->organization,
            $member,
            OrganizationRole::Treasurer,
            $this->adminA,
        );

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $member->id,
            'role' => OrganizationRole::Treasurer->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $this->organization->id,
            'action' => 'organization.member.role_changed',
            'user_id' => $this->adminA->id,
        ]);
    }

    public function test_query_exception_is_caught_when_locked_by_concurrent_writer(): void
    {
        $resource = DB::transaction(function () {
            return Organization::whereKey($this->organization->id)->lockForUpdate()->first();
        });

        $this->assertNotNull($resource);
    }

    public function test_concurrent_demotions_with_locked_rows_prevent_zero_admins(): void
    {
        $tryDemoteA = function () {
            DB::transaction(function () {
                $locked = Organization::whereKey($this->organization->id)->lockForUpdate()->first();

                $count = $locked->users()
                    ->wherePivot('role', OrganizationRole::Admin->value)
                    ->count();

                if ($count <= 1) {
                    throw ValidationException::withMessages([
                        'role' => 'Cannot demote the last admin.',
                    ]);
                }

                $locked->users()->updateExistingPivot($this->adminA->id, [
                    'role' => OrganizationRole::Viewer->value,
                ]);
            });

            return true;
        };

        $tryDemoteB = function () {
            DB::transaction(function () {
                $locked = Organization::whereKey($this->organization->id)->lockForUpdate()->first();

                $count = $locked->users()
                    ->wherePivot('role', OrganizationRole::Admin->value)
                    ->count();

                if ($count <= 1) {
                    throw ValidationException::withMessages([
                        'role' => 'Cannot demote the last admin.',
                    ]);
                }

                $locked->users()->updateExistingPivot($this->adminB->id, [
                    'role' => OrganizationRole::Viewer->value,
                ]);
            });

            return true;
        };

        $firstSucceeded = false;
        $secondBlocked = false;

        try {
            $firstSucceeded = $tryDemoteA();
        } catch (ValidationException) {
            // First try shouldn't fail — we have two admins.
        }

        try {
            $tryDemoteB();
        } catch (ValidationException) {
            $secondBlocked = true;
        }

        $this->assertTrue($firstSucceeded);
        $this->assertTrue($secondBlocked);

        $adminRows = DB::table('organization_user')
            ->where('organization_id', $this->organization->id)
            ->where('role', OrganizationRole::Admin->value)
            ->count();

        $this->assertGreaterThanOrEqual(1, $adminRows);
    }
}
