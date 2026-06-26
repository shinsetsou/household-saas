<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Events\OrganizationInvitationSent;
use App\Mail\OrganizationInvitationMail;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->organization = Organization::create([
            'name' => 'Test HOA',
            'monthly_due_amount' => 200,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
        ]);
        $this->organization->users()->attach($this->admin->id, ['role' => OrganizationRole::Admin->value]);
    }

    public function test_admin_can_view_members_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('organizations.settings.members.show', $this->organization->slug))
            ->assertOk()
            ->assertSee('Test HOA')
            ->assertSee($this->admin->email);
    }

    public function test_non_admin_cannot_view_members_page(): void
    {
        $treasurer = User::factory()->create();
        $this->organization->users()->attach($treasurer->id, ['role' => OrganizationRole::Treasurer->value]);

        $this->actingAs($treasurer)
            ->get(route('organizations.settings.members.show', $this->organization->slug))
            ->assertForbidden();
    }

    public function test_admin_can_send_invite(): void
    {
        Event::fake([OrganizationInvitationSent::class]);
        Mail::fake();

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.members.invite', $this->organization->slug), [
                'email' => 'Newuser@example.com',
                'role' => OrganizationRole::Treasurer->value,
            ])
            ->assertRedirect(route('organizations.settings.members.show', $this->organization->slug))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('organization_invitations', [
            'organization_id' => $this->organization->id,
            'email' => 'newuser@example.com',
            'role' => OrganizationRole::Treasurer->value,
            'invited_by' => $this->admin->id,
        ]);

        Event::assertDispatched(OrganizationInvitationSent::class);
    }

    public function test_invite_is_email_case_insensitive(): void
    {
        Event::fake([OrganizationInvitationSent::class]);

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.members.invite', $this->organization->slug), [
                'email' => 'Treasurer@Example.COM',
                'role' => OrganizationRole::Viewer->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('organization_invitations', [
            'organization_id' => $this->organization->id,
            'email' => 'treasurer@example.com',
        ]);
    }

    public function test_invite_rejects_already_member(): void
    {
        $member = User::factory()->create(['email' => 'member@example.com']);
        $this->organization->users()->attach($member->id, ['role' => OrganizationRole::Viewer->value]);

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.members.invite', $this->organization->slug), [
                'email' => 'member@example.com',
                'role' => OrganizationRole::Viewer->value,
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('organization_invitations', [
            'organization_id' => $this->organization->id,
            'email' => 'member@example.com',
        ]);
    }

    public function test_invite_rejects_duplicate_pending(): void
    {
        OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'dup@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.members.invite', $this->organization->slug), [
                'email' => 'dup@example.com',
                'role' => OrganizationRole::Viewer->value,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_invitation_email_contains_signed_url(): void
    {
        Event::fake([OrganizationInvitationSent::class]);
        Mail::fake();

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.members.invite', $this->organization->slug), [
                'email' => 'invitee@example.com',
                'role' => OrganizationRole::Treasurer->value,
            ]);

        $invitation = OrganizationInvitation::where('email', 'invitee@example.com')->first();
        $url = URL::signedRoute('invitations.show', ['invitation' => $invitation->token]);

        Mail::assertSent(OrganizationInvitationMail::class, function ($mail) use ($url, $invitation) {
            return $mail->hasTo('invitee@example.com')
                && $mail->invitation->is($invitation);
        });
    }

    public function test_invitee_can_accept_invitation(): void
    {
        $invitee = User::factory()->create(['email' => 'invitee@example.com']);
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'invitee@example.com',
            'role' => OrganizationRole::Treasurer->value,
            'invited_by' => $this->admin->id,
        ]);

        $this->actingAs($invitee)
            ->post(route('invitations.accept', $invitation->token))
            ->assertRedirect(route('organizations.index', $this->organization->slug));

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $invitee->id,
            'role' => OrganizationRole::Treasurer->value,
        ]);

        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_other_user_cannot_accept_invitation(): void
    {
        $other = User::factory()->create(['email' => 'intruder@example.com']);
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'invitee@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $this->actingAs($other)
            ->get(route('invitations.show', $invitation->token))
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_revoke_invitation(): void
    {
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'revoke@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.invitations.revoke', [$this->organization->slug, $invitation->id]))
            ->assertRedirect(route('organizations.settings.members.show', $this->organization->slug));

        $this->assertNotNull($invitation->fresh()->revoked_at);
    }

    public function test_admin_can_remove_member(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member->id, ['role' => OrganizationRole::Viewer->value]);

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.members.remove', [$this->organization->slug, $member->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_cannot_remove_last_admin(): void
    {
        $this->actingAs($this->admin)
            ->post(route('organizations.settings.members.remove', [$this->organization->slug, $this->admin->id]))
            ->assertSessionHasErrors('member');

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_can_change_role(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member->id, ['role' => OrganizationRole::Viewer->value]);

        $this->actingAs($this->admin)
            ->patch(route('organizations.settings.members.role', [$this->organization->slug, $member->id]), [
                'role' => OrganizationRole::Treasurer->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $member->id,
            'role' => OrganizationRole::Treasurer->value,
        ]);
    }

    public function test_cannot_demote_last_admin(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('organizations.settings.members.role', [$this->organization->slug, $this->admin->id]), [
                'role' => OrganizationRole::Viewer->value,
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_treasurer_can_verify_contributions(): void
    {
        $treasurer = User::factory()->create();
        $this->organization->users()->attach($treasurer->id, ['role' => OrganizationRole::Treasurer->value]);

        $household = \App\Models\Household::create([
            'organization_id' => $this->organization->id,
            'block' => 1,
            'lot' => 1,
            'name' => 'House 1',
        ]);
        $contribution = \App\Models\Contribution::create([
            'household_id' => $household->id,
            'amount' => 100,
            'reference_number' => 'ref-1',
            'verification_status' => 'pending',
        ]);

        $this->actingAs($treasurer)
            ->patch(route('contributions.verify.store', [$this->organization->slug, $contribution->reference_number]))
            ->assertRedirect();

        $this->assertSame('verified', $contribution->fresh()->verification_status);
    }

    public function test_viewer_cannot_verify_contributions(): void
    {
        $viewer = User::factory()->create();
        $this->organization->users()->attach($viewer->id, ['role' => OrganizationRole::Viewer->value]);

        $household = \App\Models\Household::create([
            'organization_id' => $this->organization->id,
            'block' => 1,
            'lot' => 1,
            'name' => 'House 1',
        ]);
        $contribution = \App\Models\Contribution::create([
            'household_id' => $household->id,
            'amount' => 100,
            'reference_number' => 'ref-2',
            'verification_status' => 'pending',
        ]);

        $this->actingAs($viewer)
            ->patch(route('contributions.verify.store', [$this->organization->slug, $contribution->reference_number]))
            ->assertForbidden();
    }

    public function test_audit_log_records_member_changes(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member->id, ['role' => OrganizationRole::Viewer->value]);

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.members.remove', [$this->organization->slug, $member->id]))
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $this->organization->id,
            'action' => 'organization.member.removed',
            'user_id' => $this->admin->id,
        ]);
    }
}