<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_settings_page(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.settings.show', $org));

        $response->assertOk();
        $response->assertSee('Organization settings', false);
        $response->assertSee('General', false);
        $response->assertSee('Members', false);
        $response->assertSee('Billing', false);
        $response->assertSee('Coming soon', false);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="monthly_due_amount"', false);
    }

    public function test_non_admin_member_gets_403(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'member']);

        $response = $this->actingAs($user)->get(route('organizations.settings.show', $org));

        $response->assertForbidden();
    }

    public function test_non_member_gets_403(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();

        $response = $this->actingAs($user)->get(route('organizations.settings.show', $org));

        $response->assertForbidden();
    }
}
