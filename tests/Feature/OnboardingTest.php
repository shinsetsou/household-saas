<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_step_1_displays_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('onboarding.organization.create'));

        $response->assertOk();
        $response->assertSee('Create your organization', false);
        $response->assertSee('monthly_due_amount', false);
        $response->assertSee('Step 1 of 3', false);
    }

    public function test_step_1_store_creates_org_attaches_admin_and_redirects_to_step_2(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('onboarding.organization.store'), [
            'name' => 'Sunshine HOA',
            'monthly_due_amount' => 250,
        ]);

        $response->assertRedirect(route('onboarding.households.create'));
        $this->assertDatabaseHas('organizations', ['name' => 'Sunshine HOA']);

        $org = Organization::where('name', 'Sunshine HOA')->first();
        $this->assertTrue($user->fresh()->belongsToOrganization($org));
        $response->assertSessionHas('onboarding.organization_id', $org->id);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $org->id,
            'action' => 'organization.created',
        ]);
    }

    public function test_step_1_store_validates_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('onboarding.organization.store'), [
            'name' => '',
            'monthly_due_amount' => 250,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_step_1_requires_authentication(): void
    {
        $response = $this->get(route('onboarding.organization.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_step_2_displays_upload_form_with_sample_link(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);
        session(['onboarding.organization_id' => $org->id]);

        $response = $this->actingAs($user)->get(route('onboarding.households.create'));

        $response->assertOk();
        $response->assertSee('Add your households', false);
        $response->assertSee('Step 2 of 3', false);
        $response->assertSee('households-sample.csv', false);
        $response->assertSee('Skip for now', false);
    }

    public function test_step_2_redirects_to_step_1_when_session_missing(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('onboarding.households.create'));

        $response->assertRedirect(route('onboarding.organization.create'));
    }

    public function test_step_2_imports_valid_csv_and_redirects_to_step_3(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);
        session(['onboarding.organization_id' => $org->id]);

        $csv = "name,block,lot\nCruz Family,7,12\nReyes Family,3,4\n";
        $file = UploadedFile::fake()->createWithContent('households.csv', $csv);

        $response = $this->actingAs($user)->post(route('onboarding.households.store'), ['file' => $file]);

        $response->assertRedirect(route('onboarding.done.show'));
        $this->assertSame(2, $org->households()->count());
    }

    public function test_step_2_invalid_csv_rerenders_with_errors(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);
        session(['onboarding.organization_id' => $org->id]);

        $csv = "name,block,lot\n,7,12\n";
        $file = UploadedFile::fake()->createWithContent('households.csv', $csv);

        $response = $this->actingAs($user)->post(route('onboarding.households.store'), ['file' => $file]);

        $response->assertSessionHasErrors('file');
        $this->assertSame(0, $org->households()->count());
    }

    public function test_step_2_skip_redirects_to_step_3_without_creating_households(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);
        session(['onboarding.organization_id' => $org->id]);

        $response = $this->actingAs($user)->post(route('onboarding.households.skip'));

        $response->assertRedirect(route('onboarding.done.show'));
        $this->assertSame(0, $org->households()->count());
    }

    public function test_redirect_if_onboarded_bounces_when_no_active_session(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);
        // No session('onboarding.organization_id') set

        $response = $this->actingAs($user)->get(route('onboarding.households.create'));

        $response->assertRedirect(route('organizations.index', $org));
    }
}
