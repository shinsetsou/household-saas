<?php

namespace App\Http\Controllers\Settings;

use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\InviteMemberRequest;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberSettingsController extends Controller
{
    public function __construct(protected InvitationService $invitations)
    {
    }

    public function show(Organization $organization): View
    {
        $members = $organization->users()
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($organization) {
                return [
                    'user' => $user,
                    'role' => $organization->roleFor($user),
                    'joined_at' => $user->pivot->created_at,
                ];
            });

        $pendingInvitations = $organization->pendingInvitations()->get();

        return view('organizations.settings.members', [
            'organization' => $organization,
            'members' => $members,
            'pendingInvitations' => $pendingInvitations,
            'roles' => OrganizationRole::cases(),
        ]);
    }

    public function invite(InviteMemberRequest $request, Organization $organization): RedirectResponse
    {
        $this->invitations->invite(
            $organization,
            $request->user(),
            $request->validated('email'),
            OrganizationRole::from($request->validated('role')),
        );

        return redirect()
            ->route('organizations.settings.members.show', $organization->slug)
            ->with('success', 'Invitation sent.');
    }

    public function revoke(Request $request, Organization $organization, OrganizationInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->organization_id === $organization->id, 404);

        $this->invitations->revoke($invitation, $request->user());

        return redirect()
            ->route('organizations.settings.members.show', $organization->slug)
            ->with('info', 'Invitation revoked.');
    }

    public function resend(Request $request, Organization $organization, OrganizationInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->organization_id === $organization->id, 404);

        $this->invitations->resend($invitation, $request->user());

        return redirect()
            ->route('organizations.settings.members.show', $organization->slug)
            ->with('success', 'Invitation resent.');
    }

    public function removeMember(Request $request, Organization $organization, User $member): RedirectResponse
    {
        $this->invitations->removeMember($organization, $member, $request->user());

        return redirect()
            ->route('organizations.settings.members.show', $organization->slug)
            ->with('info', 'Member removed.');
    }

    public function changeRole(Request $request, Organization $organization, User $member): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'string', 'in:'.implode(',', array_column(OrganizationRole::cases(), 'value'))],
        ]);

        $this->invitations->changeRole(
            $organization,
            $member,
            OrganizationRole::from($data['role']),
            $request->user(),
        );

        return redirect()
            ->route('organizations.settings.members.show', $organization->slug)
            ->with('success', 'Role updated.');
    }
}