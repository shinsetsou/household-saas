<?php

namespace Tests\Feature;

use App\Models\AuditLog;
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

    public function test_admin_can_update_name_and_due(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create([
            'name' => 'Old HOA',
            'monthly_due_amount' => 200,
        ]);
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->patch(
            route('organizations.settings.update-general', $org),
            ['name' => 'New HOA', 'monthly_due_amount' => 350]
        );

        $response->assertRedirect(route('organizations.settings.show', $org->fresh()->slug));
        $response->assertSessionHas('success');

        $org->refresh();
        $this->assertSame('New HOA', $org->name);
        $this->assertEquals('350.00', $org->monthly_due_amount);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'action' => 'organization.settings.updated',
        ]);

        $log = AuditLog::where('organization_id', $org->id)
            ->where('action', 'organization.settings.updated')
            ->first();

        $this->assertSame(['Old HOA', 'New HOA'], $log->metadata['changes']['name']);
        $this->assertSame(['200.00', '350.00'], $log->metadata['changes']['monthly_due_amount']);
    }

    public function test_update_validates_name_required(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $originalName = $org->name;
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->patch(
            route('organizations.settings.update-general', $org),
            ['name' => '', 'monthly_due_amount' => 250]
        );

        $response->assertSessionHasErrors('name');
        $this->assertSame($originalName, $org->fresh()->name);
    }

    public function test_update_validates_monthly_due_non_negative(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->patch(
            route('organizations.settings.update-general', $org),
            ['name' => 'Test HOA', 'monthly_due_amount' => -1]
        );

        $response->assertSessionHasErrors('monthly_due_amount');
    }

    public function test_no_op_update_skips_audit_log(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create([
            'name' => 'Steady HOA',
            'monthly_due_amount' => 200,
        ]);
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->patch(
            route('organizations.settings.update-general', $org),
            ['name' => 'Steady HOA', 'monthly_due_amount' => 200]
        );

        $response->assertRedirect(route('organizations.settings.show', $org->slug));
        $response->assertSessionHas('info');
        $this->assertDatabaseMissing('audit_logs', [
            'organization_id' => $org->id,
            'action' => 'organization.settings.updated',
        ]);
    }

    public function test_admin_can_update_manual_payment_instructions(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->patch(
            route('organizations.settings.update-general', $org),
            [
                'name' => $org->name,
                'monthly_due_amount' => $org->monthly_due_amount,
                'manual_payment_instructions' => "GCash: 0917 123 4567\nBPI Savings: 1234 5678 9012",
            ]
        );

        $response->assertRedirect(route('organizations.settings.show', $org->fresh()->slug));
        $response->assertSessionHas('success');

        $org->refresh();
        $this->assertSame("GCash: 0917 123 4567\nBPI Savings: 1234 5678 9012", $org->manual_payment_instructions);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'action' => 'organization.settings.updated',
        ]);
    }

    public function test_update_can_clear_manual_payment_instructions(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create([
            'manual_payment_instructions' => 'Old instructions',
        ]);
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->patch(
            route('organizations.settings.update-general', $org),
            [
                'name' => $org->name,
                'monthly_due_amount' => $org->monthly_due_amount,
                'manual_payment_instructions' => '',
            ]
        );

        $response->assertRedirect(route('organizations.settings.show', $org->slug));
        $response->assertSessionHas('success');

        $this->assertNull($org->fresh()->manual_payment_instructions);
    }

    public function test_update_validates_manual_payment_instructions_max_length(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->patch(
            route('organizations.settings.update-general', $org),
            [
                'name' => $org->name,
                'monthly_due_amount' => $org->monthly_due_amount,
                'manual_payment_instructions' => str_repeat('a', 5001),
            ]
        );

        $response->assertSessionHasErrors('manual_payment_instructions');
    }

    public function test_manual_payment_instructions_render_escaped_on_lookup_page(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create([
            'manual_payment_instructions' => '<script>alert(1)</script>GCash: 0917 123 4567',
        ]);
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->get(route('home', $org->slug));

        $response->assertOk();
        $response->assertSee('How to pay dues');
        $response->assertSee('GCash: 0917 123 4567');
        $response->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_manual_payment_instructions_hidden_on_lookup_page_when_empty(): void
    {
        $org = Organization::factory()->create([
            'manual_payment_instructions' => null,
        ]);

        $response = $this->get(route('home', $org->slug));

        $response->assertOk();
        $response->assertDontSee('How to pay dues');
    }

    public function test_slug_unchanged_after_rename(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create(['name' => 'Sunshine HOA']);
        $originalSlug = $org->slug;
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $this->actingAs($user)->patch(
            route('organizations.settings.update-general', $org),
            ['name' => 'Sunshine Village HOA', 'monthly_due_amount' => $org->monthly_due_amount]
        );

        $this->assertSame($originalSlug, $org->fresh()->slug);
    }
}
