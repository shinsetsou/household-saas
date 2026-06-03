<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Expense;
use App\Models\Household;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MicroSaasMvpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_officer_routes(): void
    {
        $organization = Organization::factory()->create();
        $household = Household::factory()->create(['organization_id' => $organization->id]);

        $this->get(route('organizations.index', $organization))->assertRedirect(route('login'));
        $this->get(route('contributions.index', $organization))->assertRedirect(route('login'));
        $this->get(route('expenses.index', $organization))->assertRedirect(route('login'));
        $this->get(route('households.create', $organization))->assertRedirect(route('login'));
        $this->delete(route('contributions.destroy', [$organization, $household, Contribution::factory()->create(['household_id' => $household->id])]))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_only_access_assigned_organizations(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => 'owner']);

        $this->actingAs($user)->get(route('organizations.index', $organization))->assertOk();
        $this->actingAs($user)->get(route('organizations.index', $otherOrganization))->assertForbidden();
    }

    public function test_contributions_index_is_tenant_scoped(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => 'treasurer']);
        $household = Household::factory()->create(['organization_id' => $organization->id, 'name' => 'Visible Household']);
        $otherHousehold = Household::factory()->create(['organization_id' => $otherOrganization->id, 'name' => 'Hidden Household']);
        Contribution::factory()->create(['household_id' => $household->id]);
        Contribution::factory()->create(['household_id' => $otherHousehold->id]);

        $response = $this->actingAs($user)->get(route('contributions.index', $organization));

        $response->assertOk();
        $response->assertSee('Visible Household');
        $response->assertDontSee('Hidden Household');
    }

    public function test_household_detail_and_contribution_delete_reject_cross_tenant_records(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => 'owner']);
        $otherHousehold = Household::factory()->create(['organization_id' => $otherOrganization->id]);
        $otherContribution = Contribution::factory()->create(['household_id' => $otherHousehold->id]);

        $this->actingAs($user)->get(route('households.show', [$organization, $otherHousehold]))->assertNotFound();
        $this->actingAs($user)->delete(route('contributions.destroy', [$organization, $otherHousehold, $otherContribution]))->assertNotFound();
    }

    public function test_public_household_lookup_and_payment_submission_are_tenant_scoped(): void
    {
        Storage::fake('public');
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $household = Household::factory()->create(['organization_id' => $organization->id, 'block' => 1, 'lot' => 2, 'name' => 'Visible Household']);
        Household::factory()->create(['organization_id' => $otherOrganization->id, 'block' => 1, 'lot' => 2, 'name' => 'Hidden Household']);

        $lookup = $this->get(route('households.index', [$organization, 'block' => 1, 'lot' => 2]));
        $lookup->assertOk()->assertSee('Visible Household')->assertDontSee('Hidden Household');

        $this->post(route('contributions.store', [$organization, $household]), [
            'amount' => 200,
            'photo' => $this->fakePng('receipt.png'),
        ])->assertRedirect(route('households.show', [$organization, $household]));

        $this->assertDatabaseHas('contributions', [
            'household_id' => $household->id,
            'verification_status' => 'pending',
        ]);
    }

    public function test_lookup_route_is_throttled(): void
    {
        $organization = Organization::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            $this->get(route('households.index', [$organization, 'block' => 999, 'lot' => 999]));
        }

        $this->get(route('households.index', [$organization, 'block' => 999, 'lot' => 999]))->assertTooManyRequests();
    }

    public function test_verify_and_reject_workflows_update_grouped_contributions_and_audit_log(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => 'treasurer']);
        $household = Household::factory()->create(['organization_id' => $organization->id]);
        Contribution::factory()->count(2)->create([
            'household_id' => $household->id,
            'reference_number' => 'ref-123',
            'verification_status' => 'pending',
        ]);

        $this->actingAs($user)->get(route('contributions.verify', $organization))->assertOk()->assertSee('ref-123');
        $this->actingAs($user)->patch(route('contributions.verify.store', [$organization, 'ref-123']), [
            'verification_status' => 'verified',
        ])->assertRedirect();

        $this->assertSame(2, Contribution::where('reference_number', 'ref-123')->where('verification_status', 'verified')->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'contribution.verified']);

        Contribution::factory()->create([
            'household_id' => $household->id,
            'reference_number' => 'ref-456',
            'verification_status' => 'pending',
        ]);

        $this->actingAs($user)->patch(route('contributions.verify.store', [$organization, 'ref-456']), [
            'verification_status' => 'rejected',
        ])->assertSessionHasErrors('rejection_reason');

        $this->actingAs($user)->patch(route('contributions.verify.store', [$organization, 'ref-456']), [
            'verification_status' => 'rejected',
            'rejection_reason' => 'Unreadable receipt',
        ])->assertRedirect();

        $this->assertDatabaseHas('contributions', ['reference_number' => 'ref-456', 'verification_status' => 'rejected']);
        $this->actingAs($user)->get(route('contributions.verify', $organization))->assertOk()->assertDontSee('ref-456');
        $this->assertDatabaseHas('audit_logs', ['action' => 'contribution.rejected']);
    }

    public function test_csv_import_permissions_and_validation(): void
    {
        $organization = Organization::factory()->create();
        $otherUser = User::factory()->create();
        $user = User::factory()->create();
        $user->organizations()->attach($organization, ['role' => 'owner']);

        $this->get(route('households.import.create', $organization))->assertRedirect(route('login'));
        $this->actingAs($otherUser)->get(route('households.import.create', $organization))->assertForbidden();

        $this->actingAs($user)->post(route('households.import.store', $organization), [
            'csv' => UploadedFile::fake()->createWithContent('households.csv', "name,block,lot\nReyes,1,2\n"),
        ])->assertRedirect(route('households.index', $organization));

        $this->assertDatabaseHas('households', ['organization_id' => $organization->id, 'name' => 'Reyes', 'block' => 1, 'lot' => 2]);
    }

    public function test_reports_are_authenticated_tenant_scoped_and_exportable(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $user->organizations()->attach($organization, ['role' => 'viewer']);
        $household = Household::factory()->create(['organization_id' => $organization->id, 'name' => 'Visible Household']);
        $otherHousehold = Household::factory()->create(['organization_id' => $otherOrganization->id, 'name' => 'Hidden Household']);
        Contribution::factory()->create(['household_id' => $household->id, 'verification_status' => 'verified']);
        Contribution::factory()->create(['household_id' => $otherHousehold->id, 'verification_status' => 'verified']);
        Expense::factory()->create(['organization_id' => $organization->id, 'description' => 'Visible Expense']);
        Expense::factory()->create(['organization_id' => $otherOrganization->id, 'description' => 'Hidden Expense']);

        $this->get(route('reports.contributions.index', $organization))->assertRedirect(route('login'));

        $this->actingAs($user)->get(route('reports.contributions.index', $organization))->assertOk()->assertSee('Visible Household')->assertDontSee('Hidden Household');
        $this->actingAs($user)->get(route('reports.expenses.index', $organization))->assertOk()->assertSee('Visible Expense')->assertDontSee('Hidden Expense');
        $this->actingAs($user)->get(route('reports.contributions.export.pdf', $organization))->assertOk();
        $this->actingAs($user)->get(route('reports.contributions.export.excel', $organization))->assertOk();
        $this->actingAs($user)->get(route('reports.expenses.export.pdf', $organization))->assertOk();
        $this->actingAs($user)->get(route('reports.expenses.export.excel', $organization))->assertOk();
        $this->actingAs($user)->get(route('reports.delinquency.index', $organization))->assertOk();
        $this->actingAs($user)->get(route('reports.delinquency.export.csv', $organization))->assertOk();
    }

    public function test_privacy_terms_and_upload_validation(): void
    {
        Storage::fake('public');
        $organization = Organization::factory()->create();
        $household = Household::factory()->create(['organization_id' => $organization->id]);

        $this->get('/privacy')->assertOk();
        $this->get('/terms')->assertOk();

        $this->post(route('contributions.store', [$organization, $household]), [
            'amount' => 200,
            'photo' => UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('photo');

        $this->post(route('contributions.store', [$organization, $household]), [
            'amount' => 200,
            'photo' => UploadedFile::fake()->create('large.jpg', 3000, 'image/jpeg'),
        ])->assertSessionHasErrors('photo');
    }

    private function fakePng(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
        );
    }
}
