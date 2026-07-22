<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PendingInvitationBannerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email' => 'invitee@example.com']);
        $this->org = Organization::factory()->create(['name' => 'Sunshine HOA']);
    }

    public function test_banner_appears_on_dashboard_when_pending_invitation_exists(): void
    {
        OrganizationInvitation::create([
            'organization_id' => $this->org->id,
            'email' => 'invitee@example.com',
            'role' => OrganizationRole::Treasurer->value,
            'invited_by' => User::factory()->create()->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('You have a pending invitation')
            ->assertSee('Sunshine HOA')
            ->assertSee('Treasurer');
    }

    public function test_banner_uses_plural_when_multiple_invites(): void
    {
        $otherOrg = Organization::factory()->create(['name' => 'Other HOA']);

        OrganizationInvitation::create([
            'organization_id' => $this->org->id,
            'email' => 'invitee@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => User::factory()->create()->id,
        ]);
        OrganizationInvitation::create([
            'organization_id' => $otherOrg->id,
            'email' => 'invitee@example.com',
            'role' => OrganizationRole::Admin->value,
            'invited_by' => User::factory()->create()->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('You have 2 pending invitations')
            ->assertSee('Sunshine HOA')
            ->assertSee('Other HOA');
    }

    public function test_banner_hidden_when_no_pending_invitations(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('pending invitation');
    }

    public function test_banner_hidden_for_already_accepted_invitation(): void
    {
        OrganizationInvitation::create([
            'organization_id' => $this->org->id,
            'email' => 'invitee@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => User::factory()->create()->id,
            'accepted_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('pending invitation');
    }

    public function test_banner_hidden_for_revoked_invitation(): void
    {
        OrganizationInvitation::create([
            'organization_id' => $this->org->id,
            'email' => 'invitee@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => User::factory()->create()->id,
            'revoked_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('pending invitation');
    }

    public function test_banner_hidden_for_expired_invitation(): void
    {
        OrganizationInvitation::create([
            'organization_id' => $this->org->id,
            'email' => 'invitee@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => User::factory()->create()->id,
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('pending invitation');
    }

    public function test_banner_review_button_links_to_signed_show_url(): void
    {
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->org->id,
            'email' => 'invitee@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => User::factory()->create()->id,
        ]);

        $expectedUrl = URL::signedRoute('invitations.show', ['invitation' => $invitation]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($expectedUrl, false);
    }

    public function test_banner_appears_on_tenant_dashboard_too(): void
    {
        $otherOrg = Organization::factory()->create();
        $otherOrg->users()->attach($this->user->id, ['role' => OrganizationRole::Viewer->value]);

        OrganizationInvitation::create([
            'organization_id' => $this->org->id,
            'email' => 'invitee@example.com',
            'role' => OrganizationRole::Admin->value,
            'invited_by' => User::factory()->create()->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('organizations.index', $otherOrg->slug))
            ->assertOk()
            ->assertSee('Sunshine HOA');
    }

    public function test_banner_uses_user_email_for_match(): void
    {
        $otherUser = User::factory()->create(['email' => 'someoneelse@example.com']);

        OrganizationInvitation::create([
            'organization_id' => $this->org->id,
            'email' => 'someoneelse@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => User::factory()->create()->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('pending invitation');

        $this->actingAs($otherUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('pending invitation');
    }
}
