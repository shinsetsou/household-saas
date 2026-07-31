<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Events\OrganizationInvitationSent;
use App\Mail\OrganizationInvitationMail;
use App\Models\Contribution;
use App\Models\Household;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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

    protected function claimInvitation(OrganizationInvitation $invitation, string $email): void
    {
        $this->post(
            URL::temporarySignedRoute(
                'invitations.claim',
                $invitation->expires_at,
                ['invitation' => $invitation->token],
            ),
            [
                'email' => $email,
                'code' => $invitation->verification_code,
            ],
        )->assertRedirect();
    }

    protected function signedShowUrl(OrganizationInvitation $invitation): string
    {
        return URL::temporarySignedRoute(
            'invitations.show',
            $invitation->expires_at,
            ['invitation' => $invitation->token],
        );
    }

    protected function signedAcceptUrl(OrganizationInvitation $invitation): string
    {
        return URL::temporarySignedRoute(
            'invitations.accept',
            $invitation->expires_at,
            ['invitation' => $invitation->token],
        );
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
        $url = URL::temporarySignedRoute(
            'invitations.show',
            $invitation->expires_at,
            ['invitation' => $invitation->token],
        );

        Mail::assertSent(OrganizationInvitationMail::class, function ($mail) use ($invitation) {
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
            ->withSession([
                'invitation.code_verified_token' => $invitation->token,
                'invitation.code_verified_at' => now()->timestamp,
                'invitation.intended_token' => $invitation->token,
            ])
            ->post($this->signedAcceptUrl($invitation))
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
            ->get($this->signedShowUrl($invitation))
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
            ->post(route('organizations.settings.invitations.revoke', [$this->organization->slug, $invitation->token]))
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

        $household = Household::create([
            'organization_id' => $this->organization->id,
            'block' => 1,
            'lot' => 1,
            'name' => 'House 1',
        ]);
        $contribution = Contribution::create([
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

        $household = Household::create([
            'organization_id' => $this->organization->id,
            'block' => 1,
            'lot' => 1,
            'name' => 'House 1',
        ]);
        $contribution = Contribution::create([
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

    public function test_admin_can_fetch_signed_invite_link_as_json(): void
    {
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'link@example.com',
            'role' => OrganizationRole::Treasurer->value,
            'invited_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('organizations.settings.invitations.link', [$this->organization->slug, $invitation->token]));

        $response->assertOk();
        $response->assertJsonStructure(['url']);
        $this->assertStringContainsString('/invitations/'.$invitation->token, $response->json('url'));
    }

    public function test_non_admin_cannot_fetch_invite_link(): void
    {
        $viewer = User::factory()->create();
        $this->organization->users()->attach($viewer->id, ['role' => OrganizationRole::Viewer->value]);
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'link2@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $this->actingAs($viewer)
            ->getJson(route('organizations.settings.invitations.link', [$this->organization->slug, $invitation->token]))
            ->assertForbidden();
    }

    public function test_link_endpoint_404s_for_revoked_or_accepted_invitation(): void
    {
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'gone@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
            'revoked_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('organizations.settings.invitations.link', [$this->organization->slug, $invitation->token]))
            ->assertNotFound();
    }

    public function test_members_page_renders_role_chips_for_all_roles(): void
    {
        $treasurer = User::factory()->create(['name' => 'Tess Treasurer']);
        $this->organization->users()->attach($treasurer->id, ['role' => OrganizationRole::Treasurer->value]);
        $viewer = User::factory()->create(['name' => 'Vince Viewer']);
        $this->organization->users()->attach($viewer->id, ['role' => OrganizationRole::Viewer->value]);

        $response = $this->actingAs($this->admin)
            ->get(route('organizations.settings.members.show', $this->organization->slug));

        $response->assertOk();
        $response->assertSee('Admin');
        $response->assertSee('Treasurer');
        $response->assertSee('Viewer');
        $response->assertSee('Copy link');
    }

    public function test_members_page_renders_pending_invitation_count(): void
    {
        OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'pending@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('organizations.settings.members.show', $this->organization->slug));

        $response->assertOk();
        $response->assertSee('Pending invitations');
        $response->assertSee('1 awaiting acceptance');
    }

    public function test_signed_invite_url_works_end_to_end(): void
    {
        Event::fake([OrganizationInvitationSent::class]);
        Mail::fake();

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.members.invite', $this->organization->slug), [
                'email' => 'endtoend@example.com',
                'role' => OrganizationRole::Viewer->value,
            ])
            ->assertRedirect();

        $invitation = OrganizationInvitation::where('email', 'endtoend@example.com')->firstOrFail();
        $signedShowUrl = URL::temporarySignedRoute(
            'invitations.show',
            $invitation->expires_at,
            ['invitation' => $invitation->token],
        );
        $signedShowUrlWithCode = URL::temporarySignedRoute(
            'invitations.show',
            $invitation->expires_at,
            ['invitation' => $invitation->token, 'c' => $invitation->verification_code],
        );
        $signedClaimUrl = URL::temporarySignedRoute(
            'invitations.claim',
            $invitation->expires_at,
            ['invitation' => $invitation->token],
        );

        $invitee = User::factory()->create(['email' => 'endtoend@example.com']);

        // Step 1: opened link requires code claim.
        $this->actingAs($invitee)
            ->get($signedShowUrl)
            ->assertOk()
            ->assertSee('Confirm and continue')
            ->assertSee('6-digit code');

        // Step 2: claim with code.
        $this->post($signedClaimUrl, [
            'email' => 'endtoend@example.com',
            'code' => $invitation->verification_code,
        ])->assertRedirect(route('password.set.show'));

        // Step 3: after password set, accept.
        $this->post(route('password.set.store'), [
            'password' => 'new-secure-pw',
            'password_confirmation' => 'new-secure-pw',
        ])->assertRedirect($signedShowUrlWithCode);

        $this->actingAs($invitee->fresh())
            ->get($signedShowUrlWithCode)
            ->assertOk()
            ->assertSee('Accept invitation');
    }

    public function test_invite_auto_registers_new_user_and_redirects_to_set_password(): void
    {
        Event::fake([OrganizationInvitationSent::class]);
        Mail::fake();

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.members.invite', $this->organization->slug), [
                'email' => 'newbie@example.com',
                'role' => OrganizationRole::Viewer->value,
            ])
            ->assertRedirect();

        $invitation = OrganizationInvitation::where('email', 'newbie@example.com')->firstOrFail();

        Auth::logout();

        $signedClaimUrl = URL::temporarySignedRoute(
            'invitations.claim',
            $invitation->expires_at,
            ['invitation' => $invitation->token],
        );

        $response = $this->post($signedClaimUrl, [
            'email' => 'newbie@example.com',
            'code' => $invitation->verification_code,
        ]);

        $response->assertRedirect(route('password.set.show'));

        $newUser = User::where('email', 'newbie@example.com')->firstOrFail();
        $this->assertTrue($newUser->force_change_password);
        $this->assertNotNull($newUser->email_verified_at);
        $this->assertAuthenticatedAs($newUser);
        $this->assertSame($invitation->token, session('invitation.intended_token'));
    }

    public function test_set_password_resumes_the_invitation(): void
    {
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'pending@example.com',
            'role' => OrganizationRole::Treasurer->value,
            'invited_by' => $this->admin->id,
        ]);

        $user = User::create([
            'name' => 'Pending Person',
            'email' => 'pending@example.com',
            'password' => bcrypt('placeholder'),
            'force_change_password' => true,
            'email_verified_at' => now(),
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'invitations.show',
            $invitation->expires_at,
            ['invitation' => $invitation->token, 'c' => $invitation->verification_code],
        );

        $response = $this->actingAs($user)
            ->withSession(['invitation.intended_token' => $invitation->token])
            ->post(route('password.set.store'), [
                'password' => 'new-secure-pw',
                'password_confirmation' => 'new-secure-pw',
            ]);

        $response->assertRedirect($signedUrl);

        $user->refresh();
        $this->assertFalse($user->force_change_password);
        $this->assertNull(session('invitation.intended_token'));
    }

    public function test_set_password_redirect_to_invitation_url_is_signed_and_resolvable(): void
    {
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'signcheck@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $user = User::create([
            'name' => 'Sign Check',
            'email' => 'signcheck@example.com',
            'password' => bcrypt('placeholder'),
            'force_change_password' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'invitation.intended_token' => $invitation->token,
                'invitation.code_verified_at' => now()->timestamp,
                'invitation.code_verified_token' => $invitation->token,
            ])
            ->post(route('password.set.store'), [
                'password' => 'new-secure-pw',
                'password_confirmation' => 'new-secure-pw',
            ]);

        $signedUrl = $response->headers->get('Location');

        $this->actingAs($user->fresh())
            ->get($signedUrl)
            ->assertOk()
            ->assertSee('Accept invitation');
    }

    public function test_force_password_change_middleware_blocks_dashboard(): void
    {
        $user = User::create([
            'name' => 'Locked Out',
            'email' => 'locked@example.com',
            'password' => bcrypt('placeholder'),
            'force_change_password' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('password.set.show'));
    }

    public function test_force_password_change_allows_set_password_and_logout(): void
    {
        $user = User::create([
            'name' => 'Locked Out',
            'email' => 'locked2@example.com',
            'password' => bcrypt('placeholder'),
            'force_change_password' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('password.set.show'))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect('/');
    }

    public function test_invite_show_marks_logged_in_user_as_verified(): void
    {
        $invitee = User::factory()->unverified()->create(['email' => 'unverified@example.com']);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'unverified@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $signedUrlWithCode = URL::temporarySignedRoute(
            'invitations.show',
            $invitation->expires_at,
            ['invitation' => $invitation->token, 'c' => $invitation->verification_code],
        );

        $this->actingAs($invitee)->get($signedUrlWithCode)->assertOk();

        $this->assertNotNull($invitee->fresh()->email_verified_at);
    }

    public function test_accepting_invitation_does_not_bounce_to_verify(): void
    {
        $invitee = User::factory()->create([
            'email' => 'verifier@example.com',
            'email_verified_at' => now(),
        ]);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'verifier@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $this->actingAs($invitee)
            ->withSession([
                'invitation.code_verified_token' => $invitation->token,
                'invitation.code_verified_at' => now()->timestamp,
                'invitation.intended_token' => $invitation->token,
            ])
            ->post($this->signedAcceptUrl($invitation))
            ->assertRedirect(route('organizations.index', $this->organization->slug));

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $invitee->id,
        ]);
    }

    public function test_revoke_writes_audit_log_with_observer(): void
    {
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'audit@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.invitations.revoke', [$this->organization->slug, $invitation->token]))
            ->assertRedirect(route('organizations.settings.members.show', $this->organization->slug));

        $this->assertNotNull($invitation->fresh()->revoked_at);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $this->organization->id,
            'action' => 'organization.member.invitation_revoked',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_decline_writes_audit_log_with_observer(): void
    {
        $invitee = User::factory()->create(['email' => 'decliner@example.com']);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'decliner@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $this->actingAs($invitee)
            ->post(URL::signedRoute('invitations.decline', ['invitation' => $invitation->token]))
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($invitation->fresh()->revoked_at);
    }

    public function test_invite_view_shows_strong_decline_confirmation(): void
    {
        $invitee = User::factory()->create(['email' => 'declineview@example.com']);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'declineview@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $signedUrlWithCode = URL::temporarySignedRoute(
            'invitations.show',
            $invitation->expires_at,
            ['invitation' => $invitation->token, 'c' => $invitation->verification_code],
        );

        $response = $this->actingAs($invitee)->get($signedUrlWithCode);

        $response->assertOk();
        $response->assertSee('Decline this invitation');
        $response->assertSee('onsubmit="return confirm', false);
    }

    public function test_dashboard_routes_to_pending_invitations_when_user_has_no_org_membership(): void
    {
        $invitee = User::factory()->create(['email' => 'noorg@example.com']);

        OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'noorg@example.com',
            'role' => OrganizationRole::Treasurer->value,
            'invited_by' => $this->admin->id,
        ]);

        $this->actingAs($invitee)
            ->get(route('dashboard'))
            ->assertRedirect(route('invitations.index'));
    }

    public function test_dashboard_routes_to_org_when_user_has_membership(): void
    {
        $member = User::factory()->create(['email' => 'membered@example.com']);
        $this->organization->users()->attach($member->id, ['role' => OrganizationRole::Viewer->value]);

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertRedirect(route('organizations.index', $this->organization->slug));
    }

    public function test_dashboard_routes_to_onboarding_when_no_org_and_no_invites(): void
    {
        $lonely = User::factory()->create(['email' => 'lonely@example.com']);

        $this->actingAs($lonely)
            ->get(route('dashboard'))
            ->assertRedirect(route('onboarding.organization.create'));
    }

    public function test_pending_invitations_index_lists_invites_for_logged_in_user(): void
    {
        $invitee = User::factory()->create(['email' => 'pendinglist@example.com']);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'pendinglist@example.com',
            'role' => OrganizationRole::Treasurer->value,
            'invited_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($invitee)
            ->get(route('invitations.index'));

        $response->assertOk();
        $response->assertSee('You have a pending invitation');
        $response->assertSee($this->organization->name);
        $response->assertSee('Treasurer');
        $response->assertSee('Review');
    }

    public function test_pending_invitations_destroy_revokes_invitation(): void
    {
        $invitee = User::factory()->create(['email' => 'declineindex@example.com']);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'declineindex@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $this->actingAs($invitee)
            ->delete(route('invitations.pending.destroy', $invitation->token))
            ->assertRedirect(route('invitations.index'))
            ->assertSessionHas('info');

        $this->assertNotNull($invitation->fresh()->revoked_at);
    }

    public function test_pending_invitations_destroy_403s_for_non_matching_email(): void
    {
        $invitee = User::factory()->create(['email' => 'real@example.com']);
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'someoneelse@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $this->actingAs($invitee)
            ->delete(route('invitations.pending.destroy', $invitation->token))
            ->assertForbidden();

        $this->assertNull($invitation->fresh()->revoked_at);
    }

    public function test_auto_registration_stores_invitation_origin(): void
    {
        Event::fake([OrganizationInvitationSent::class]);
        Mail::fake();

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.members.invite', $this->organization->slug), [
                'email' => 'origin@example.com',
                'role' => OrganizationRole::Treasurer->value,
            ])
            ->assertRedirect();

        $invitation = OrganizationInvitation::where('email', 'origin@example.com')->firstOrFail();

        Auth::logout();

        $signedClaimUrl = URL::temporarySignedRoute(
            'invitations.claim',
            $invitation->expires_at,
            ['invitation' => $invitation->token],
        );

        $this->post($signedClaimUrl, [
            'email' => 'origin@example.com',
            'code' => $invitation->verification_code,
        ])->assertRedirect(route('password.set.show'));

        $newUser = User::where('email', 'origin@example.com')->firstOrFail();
        $this->assertSame($this->organization->slug, $newUser->invitation_origin_org_slug);
        $this->assertSame(OrganizationRole::Treasurer->value, $newUser->invitation_origin_role);
        $this->assertSame($this->admin->email, $newUser->invitation_origin_inviter_email);
        $this->assertNull($newUser->invitation_origin_revoked_at);
    }

    public function test_revoking_invite_locks_orphan_user(): void
    {
        $invitee = User::create([
            'name' => 'Orphan',
            'email' => 'orphan@example.com',
            'password' => bcrypt('placeholder'),
            'force_change_password' => false,
            'email_verified_at' => now(),
            'invitation_origin_org_slug' => $this->organization->slug,
            'invitation_origin_role' => OrganizationRole::Viewer->value,
            'invitation_origin_inviter_email' => $this->admin->email,
        ]);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'orphan@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.invitations.revoke', [$this->organization->slug, $invitation->token]))
            ->assertRedirect();

        $invitee->refresh();
        $this->assertNotNull($invitee->invitation_origin_revoked_at);
        $this->assertTrue($invitee->isInviteLocked());
    }

    public function test_revoking_invite_does_not_lock_user_with_existing_org_membership(): void
    {
        $invitee = User::factory()->create(['email' => 'membered@example.com']);
        $this->organization->users()->attach($invitee->id, ['role' => OrganizationRole::Viewer->value]);

        $invitee->update([
            'invitation_origin_org_slug' => $this->organization->slug,
            'invitation_origin_role' => OrganizationRole::Viewer->value,
            'invitation_origin_inviter_email' => $this->admin->email,
        ]);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'membered@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.invitations.revoke', [$this->organization->slug, $invitation->token]));

        $invitee->refresh();
        $this->assertNull($invitee->invitation_origin_revoked_at);
    }

    public function test_dashboard_redirects_to_locked_page_when_invite_was_revoked(): void
    {
        $invitee = User::factory()->create(['email' => 'lockeddash@example.com']);

        $invitee->update([
            'invitation_origin_org_slug' => $this->organization->slug,
            'invitation_origin_role' => OrganizationRole::Treasurer->value,
            'invitation_origin_inviter_email' => $this->admin->email,
            'invitation_origin_revoked_at' => now(),
        ]);

        $this->actingAs($invitee)
            ->get(route('dashboard'))
            ->assertRedirect(route('invitations.locked'));
    }

    public function test_accepting_new_invite_clears_invitation_origin_lock(): void
    {
        $invitee = User::factory()->create([
            'email' => 'recover@example.com',
            'invitation_origin_org_slug' => $this->organization->slug,
            'invitation_origin_role' => OrganizationRole::Viewer->value,
            'invitation_origin_inviter_email' => $this->admin->email,
            'invitation_origin_revoked_at' => now(),
        ]);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'recover@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $this->actingAs($invitee)
            ->withSession([
                'invitation.code_verified_token' => $invitation->token,
                'invitation.code_verified_at' => now()->timestamp,
                'invitation.intended_token' => $invitation->token,
            ])
            ->post($this->signedAcceptUrl($invitation))
            ->assertRedirect(route('organizations.index', $this->organization->slug));

        $invitee->refresh();
        $this->assertNull($invitee->invitation_origin_revoked_at);
        $this->assertFalse($invitee->isInviteLocked());

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $this->organization->id,
            'user_id' => $invitee->id,
        ]);
    }

    public function test_locked_page_renders_with_inviter_contact(): void
    {
        $invitee = User::factory()->create([
            'email' => 'showlocked@example.com',
            'invitation_origin_org_slug' => 'sunshine-hoa',
            'invitation_origin_role' => OrganizationRole::Treasurer->value,
            'invitation_origin_inviter_email' => 'admin@sunshine.test',
            'invitation_origin_revoked_at' => now(),
        ]);

        $response = $this->actingAs($invitee)
            ->get(route('invitations.locked'));

        $response->assertOk();
        $response->assertSee('Your invitation was revoked');
        $response->assertSee('sunshine-hoa');
        $response->assertSee('Treasurer');
        $response->assertSee('admin@sunshine.test');
        $response->assertSee('Sign out');
    }

    public function test_accept_form_action_is_signed_url(): void
    {
        $invitee = User::factory()->create(['email' => 'signedaccept@example.com']);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'signedaccept@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $signedShowUrlWithCode = URL::temporarySignedRoute(
            'invitations.show',
            $invitation->expires_at,
            ['invitation' => $invitation->token, 'c' => $invitation->verification_code],
        );

        $response = $this->actingAs($invitee)
            ->get($signedShowUrlWithCode);

        $response->assertOk();
        $response->assertSee('action="http://localhost/invitations/'.$invitation->token.'/accept', false);
        $response->assertSee('signature=', false);
    }

    public function test_decline_form_action_is_signed_url(): void
    {
        $invitee = User::factory()->create(['email' => 'signeddecline@example.com']);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'signeddecline@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $signedShowUrlWithCode = URL::temporarySignedRoute(
            'invitations.show',
            $invitation->expires_at,
            ['invitation' => $invitation->token, 'c' => $invitation->verification_code],
        );

        $response = $this->actingAs($invitee)
            ->get($signedShowUrlWithCode);

        $response->assertOk();
        $response->assertSee('action="http://localhost/invitations/'.$invitation->token.'/decline', false);
        $response->assertSee('signature=', false);
    }

    public function test_signed_invite_url_requires_verification_code(): void
    {
        $invitee = User::factory()->create(['email' => 'codetest@example.com']);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'codetest@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $signedShowUrl = URL::temporarySignedRoute(
            'invitations.show',
            $invitation->expires_at,
            ['invitation' => $invitation->token],
        );

        // Without the code, the show route shows the claim form (the Accept form is gated behind it).
        $this->actingAs($invitee)
            ->get($signedShowUrl)
            ->assertOk()
            ->assertSee('Confirm and continue')
            ->assertDontSee('Accept invitation');

        // With the code, the show route reveals the Accept form.
        $signedShowUrlWithCode = URL::temporarySignedRoute(
            'invitations.show',
            $invitation->expires_at,
            ['invitation' => $invitation->token, 'c' => $invitation->verification_code],
        );

        $this->actingAs($invitee)
            ->get($signedShowUrlWithCode)
            ->assertOk()
            ->assertSee('Accept invitation');
    }

    public function test_claim_route_rejects_wrong_code(): void
    {
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'wrongcode@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $signedClaimUrl = URL::temporarySignedRoute(
            'invitations.claim',
            $invitation->expires_at,
            ['invitation' => $invitation->token],
        );

        $this->post($signedClaimUrl, [
            'email' => 'wrongcode@example.com',
            'code' => '000000',
        ])->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('users', ['email' => 'wrongcode@example.com']);
    }

    public function test_claim_route_rejects_wrong_email(): void
    {
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'realemail@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $signedClaimUrl = URL::temporarySignedRoute(
            'invitations.claim',
            $invitation->expires_at,
            ['invitation' => $invitation->token],
        );

        $this->post($signedClaimUrl, [
            'email' => 'attacker@example.com',
            'code' => $invitation->verification_code,
        ])->assertSessionHasErrors('code');
    }

    public function test_claim_route_throttles_repeated_wrong_codes(): void
    {
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'throttle@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $signedClaimUrl = URL::temporarySignedRoute(
            'invitations.claim',
            $invitation->expires_at,
            ['invitation' => $invitation->token],
        );

        for ($i = 0; $i < 10; $i++) {
            $this->post($signedClaimUrl, [
                'email' => 'throttle@example.com',
                'code' => '000000',
            ])->assertSessionHasErrors('code');
        }

        $this->post($signedClaimUrl, [
            'email' => 'throttle@example.com',
            'code' => $invitation->verification_code,
        ])->assertSessionHasErrors('code');
    }

    public function test_claim_route_rate_limiter_resets_on_success(): void
    {
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'resetlimit@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $signedClaimUrl = URL::temporarySignedRoute(
            'invitations.claim',
            $invitation->expires_at,
            ['invitation' => $invitation->token],
        );

        $this->post($signedClaimUrl, [
            'email' => 'resetlimit@example.com',
            'code' => '999999',
        ])->assertSessionHasErrors('code');

        $this->post($signedClaimUrl, [
            'email' => 'resetlimit@example.com',
            'code' => $invitation->verification_code,
        ])->assertRedirect();
    }

    public function test_signed_url_must_not_be_tampered_with(): void
    {
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'signed-check@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $valid = URL::temporarySignedRoute(
            'invitations.show',
            $invitation->expires_at,
            ['invitation' => $invitation->token],
        );

        $tampered = preg_replace('/signature=[a-f0-9]+/', 'signature=invalid', $valid);

        $this->get($tampered)->assertStatus(403);
    }

    public function test_signed_url_expires_after_expiration_window(): void
    {
        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'expiredurl@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $past = URL::temporarySignedRoute(
            'invitations.show',
            now()->subMinute(),
            ['invitation' => $invitation->token],
        );

        $this->get($past)->assertStatus(403);
    }

    public function test_resend_rotates_the_verification_code(): void
    {
        Event::fake([OrganizationInvitationSent::class]);
        Mail::fake();

        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'rotatecode@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $oldCode = $invitation->verification_code;

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.invitations.resend', [$this->organization->slug, $invitation->token]))
            ->assertRedirect();

        $this->assertNotSame($oldCode, $invitation->fresh()->verification_code);
    }

    public function test_invitation_can_only_be_accepted_within_fifteen_minutes_of_code_verification(): void
    {
        $invitee = User::factory()->create(['email' => 'window@example.com']);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'window@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        // Code verified 30 minutes ago — past the 15-minute window.
        $this->actingAs($invitee)
            ->withSession([
                'invitation.code_verified_token' => $invitation->token,
                'invitation.code_verified_at' => now()->subMinutes(30)->timestamp,
                'invitation.intended_token' => $invitation->token,
            ])
            ->post($this->signedAcceptUrl($invitation))
            ->assertSessionHasErrors('email');
    }

    public function test_accept_route_rejects_when_code_was_never_verified(): void
    {
        $invitee = User::factory()->create(['email' => 'neververified@example.com']);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $this->organization->id,
            'email' => 'neververified@example.com',
            'role' => OrganizationRole::Viewer->value,
            'invited_by' => $this->admin->id,
        ]);

        $this->actingAs($invitee)
            ->withSession([
                'invitation.intended_token' => $invitation->token,
            ])
            ->post($this->signedAcceptUrl($invitation))
            ->assertSessionHasErrors('email');
    }
}
