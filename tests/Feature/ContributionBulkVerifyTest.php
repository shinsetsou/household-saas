<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Household;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributionBulkVerifyTest extends TestCase
{
    use RefreshDatabase;

    private function member(Organization $org, string $role = 'admin'): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($org->id, ['role' => $role]);

        return $user;
    }

    public function test_admin_can_bulk_verify_multiple_pending_contributions(): void
    {
        $org = Organization::factory()->create();
        $household = Household::factory()->create(['organization_id' => $org->id]);
        $user = $this->member($org);

        $a = Contribution::factory()->create([
            'household_id' => $household->id,
            'reference_number' => 'ref-a',
            'verification_status' => 'pending',
        ]);
        $b = Contribution::factory()->create([
            'household_id' => $household->id,
            'reference_number' => 'ref-b',
            'verification_status' => 'pending',
        ]);
        $c = Contribution::factory()->create([
            'household_id' => $household->id,
            'reference_number' => 'ref-c',
            'verification_status' => 'pending',
        ]);

        $response = $this->actingAs($user)->patch(
            route('contributions.verify.bulk', $org),
            [
                'reference_numbers' => ['ref-a', 'ref-b', 'ref-c'],
                'verification_status' => 'verified',
            ]
        );

        $response->assertSessionHas('success');
        foreach ([$a, $b, $c] as $row) {
            $row->refresh();
            $this->assertSame('verified', $row->verification_status);
            $this->assertSame(1, $row->admin_verified);
            $this->assertSame($user->id, $row->verified_by);
            $this->assertNotNull($row->verified_at);
        }

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'contribution.verified.bulk',
            'organization_id' => $org->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_bulk_reject_requires_rejection_reason(): void
    {
        $org = Organization::factory()->create();
        $household = Household::factory()->create(['organization_id' => $org->id]);
        $user = $this->member($org);

        $contribution = Contribution::factory()->create([
            'household_id' => $household->id,
            'reference_number' => 'ref-x',
            'verification_status' => 'pending',
        ]);

        $response = $this->actingAs($user)->patch(
            route('contributions.verify.bulk', $org),
            [
                'reference_numbers' => ['ref-x'],
                'verification_status' => 'rejected',
            ]
        );

        $response->assertSessionHasErrors('rejection_reason');
        $contribution->refresh();
        $this->assertSame('pending', $contribution->verification_status);
    }

    public function test_bulk_reject_persists_reason_for_each_row(): void
    {
        $org = Organization::factory()->create();
        $household = Household::factory()->create(['organization_id' => $org->id]);
        $user = $this->member($org);

        $rows = Contribution::factory()->count(2)->create([
            'household_id' => $household->id,
            'verification_status' => 'pending',
        ]);

        $this->actingAs($user)->patch(
            route('contributions.verify.bulk', $org),
            [
                'reference_numbers' => $rows->pluck('reference_number')->all(),
                'verification_status' => 'rejected',
                'rejection_reason' => 'Photo too blurry',
            ]
        )->assertSessionHas('success');

        foreach ($rows as $row) {
            $row->refresh();
            $this->assertSame('rejected', $row->verification_status);
            $this->assertSame(3, $row->admin_verified);
            $this->assertSame('Photo too blurry', $row->rejection_reason);
        }
    }

    public function test_bulk_verify_skips_rows_already_verified(): void
    {
        $org = Organization::factory()->create();
        $household = Household::factory()->create(['organization_id' => $org->id]);
        $user = $this->member($org);

        $pending = Contribution::factory()->create([
            'household_id' => $household->id,
            'reference_number' => 'ref-pending',
            'verification_status' => 'pending',
        ]);
        $alreadyVerified = Contribution::factory()->create([
            'household_id' => $household->id,
            'reference_number' => 'ref-already',
            'verification_status' => 'verified',
        ]);

        $response = $this->actingAs($user)->patch(
            route('contributions.verify.bulk', $org),
            [
                'reference_numbers' => ['ref-pending', 'ref-already'],
                'verification_status' => 'verified',
            ]
        );

        $response->assertSessionHas('success');
        $pending->refresh();
        $alreadyVerified->refresh();

        $this->assertSame('verified', $pending->verification_status);
        // Already-verified row keeps its original verified_by — bulk action must
        // never overwrite existing verifications.
        $this->assertNotSame($user->id, $alreadyVerified->verified_by);
    }

    public function test_bulk_verify_does_not_touch_other_organizations_references(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $houseA = Household::factory()->create(['organization_id' => $orgA->id]);
        $houseB = Household::factory()->create(['organization_id' => $orgB->id]);

        $userA = $this->member($orgA);

        // Org B's contribution. The treasurer from org A must NOT be able to verify it,
        // even if the reference number is in the request payload.
        $otherOrgRow = Contribution::factory()->create([
            'household_id' => $houseB->id,
            'reference_number' => 'ref-orgb',
            'verification_status' => 'pending',
        ]);

        $this->actingAs($userA)->patch(
            route('contributions.verify.bulk', $orgA),
            [
                'reference_numbers' => ['ref-orgb'],
                'verification_status' => 'verified',
            ]
        )->assertSessionHas('error');

        $otherOrgRow->refresh();
        $this->assertSame('pending', $otherOrgRow->verification_status);
    }

    public function test_bulk_verify_validates_reference_numbers_array(): void
    {
        $org = Organization::factory()->create();
        $user = $this->member($org);

        $this->actingAs($user)->patch(
            route('contributions.verify.bulk', $org),
            [
                'reference_numbers' => [],
                'verification_status' => 'verified',
            ]
        )->assertSessionHasErrors('reference_numbers');
    }

    public function test_viewer_role_cannot_bulk_verify(): void
    {
        $org = Organization::factory()->create();
        $household = Household::factory()->create(['organization_id' => $org->id]);
        $viewer = $this->member($org, 'viewer');
        Contribution::factory()->create([
            'household_id' => $household->id,
            'reference_number' => 'ref-x',
            'verification_status' => 'pending',
        ]);

        $this->actingAs($viewer)->patch(
            route('contributions.verify.bulk', $org),
            [
                'reference_numbers' => ['ref-x'],
                'verification_status' => 'verified',
            ]
        )->assertForbidden();

        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'contribution.verified.bulk',
        ]);
    }

    public function test_treasurer_role_can_bulk_verify(): void
    {
        $org = Organization::factory()->create();
        $household = Household::factory()->create(['organization_id' => $org->id]);
        $treasurer = $this->member($org, 'treasurer');
        Contribution::factory()->create([
            'household_id' => $household->id,
            'reference_number' => 'ref-tr',
            'verification_status' => 'pending',
        ]);

        $this->actingAs($treasurer)->patch(
            route('contributions.verify.bulk', $org),
            [
                'reference_numbers' => ['ref-tr'],
                'verification_status' => 'verified',
            ]
        )->assertSessionHas('success');
    }
}
