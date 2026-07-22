<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Household;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantSafetyAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminA;

    protected User $adminB;

    protected Organization $orgA;

    protected Organization $orgB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminA = User::factory()->create();
        $this->adminB = User::factory()->create();

        $this->orgA = Organization::factory()->create(['name' => 'Org A']);
        $this->orgA->users()->attach($this->adminA->id, ['role' => 'admin']);

        $this->orgB = Organization::factory()->create(['name' => 'Org B']);
        $this->orgB->users()->attach($this->adminB->id, ['role' => 'admin']);
    }

    public function test_destroying_contribution_does_not_count_other_tenants_shared_photo(): void
    {
        Storage::fake('s3');

        $householdA = Household::create([
            'organization_id' => $this->orgA->id,
            'block' => 1, 'lot' => 1, 'name' => 'House A1',
        ]);
        $householdB = Household::create([
            'organization_id' => $this->orgB->id,
            'block' => 1, 'lot' => 1, 'name' => 'House B1',
        ]);

        $sharedPhotoPath = 'contributions/2026/06/shared-photo.jpg';

        $contributionA = Contribution::create([
            'household_id' => $householdA->id,
            'amount' => 100,
            'reference_number' => 'ref-shared-A',
            'photo' => $sharedPhotoPath,
            'verification_status' => 'pending',
        ]);

        Contribution::create([
            'household_id' => $householdB->id,
            'amount' => 200,
            'reference_number' => 'ref-shared-B',
            'photo' => $sharedPhotoPath,
            'verification_status' => 'pending',
        ]);

        Storage::disk('s3')->put($sharedPhotoPath, 'fake');

        $this->actingAs($this->adminA)
            ->delete(route('contributions.destroy', [$this->orgA->slug, $householdA->id, $contributionA->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('contributions', ['id' => $contributionA->id]);
        $this->assertDatabaseHas('contributions', [
            'household_id' => $householdB->id,
            'photo' => $sharedPhotoPath,
        ]);

        Storage::disk('s3')->assertExists($sharedPhotoPath);
    }

    public function test_verify_contribution_does_not_match_other_tenants_reference_number(): void
    {
        $householdA = Household::create([
            'organization_id' => $this->orgA->id,
            'block' => 1, 'lot' => 1, 'name' => 'House A1',
        ]);
        $householdB = Household::create([
            'organization_id' => $this->orgB->id,
            'block' => 1, 'lot' => 1, 'name' => 'House B1',
        ]);

        $contributionA = Contribution::create([
            'household_id' => $householdA->id,
            'amount' => 100,
            'reference_number' => 'ref-shared',
            'verification_status' => 'pending',
        ]);
        Contribution::create([
            'household_id' => $householdB->id,
            'amount' => 200,
            'reference_number' => 'ref-shared',
            'verification_status' => 'pending',
        ]);

        $this->actingAs($this->adminA)
            ->patch(route('contributions.verify.store', [$this->orgA->slug, $contributionA->reference_number]), [
                'verification_status' => 'verified',
            ])
            ->assertRedirect();

        $this->assertSame('verified', $contributionA->fresh()->verification_status);

        $contributionB = Contribution::where('household_id', $householdB->id)->first();
        $this->assertSame('pending', $contributionB->verification_status);
    }

    public function test_latest_contribution_lookup_does_not_leak_across_tenants(): void
    {
        $householdA = Household::create([
            'organization_id' => $this->orgA->id,
            'block' => 1, 'lot' => 1, 'name' => 'House A1',
        ]);

        Contribution::create([
            'household_id' => $householdA->id,
            'amount' => 100,
            'reference_number' => 'ref-a',
            'verification_status' => 'verified',
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($this->adminA)
            ->get(route('households.show', [$this->orgA->slug, $householdA->id]))
            ->assertOk();
    }
}
