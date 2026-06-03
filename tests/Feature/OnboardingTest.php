<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
