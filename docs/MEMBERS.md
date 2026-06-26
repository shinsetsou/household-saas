# Member Management

HOA boards rarely run as a single user — they have a treasurer verifying payments, board members viewing reports, and the admin managing everything. This document covers how organizations on BarangayBooks invite, manage, and remove members.

## Roles

Roles are defined in [`app/Enums/OrganizationRole.php`](../app/Enums/OrganizationRole.php).

| Role       | What they can do                                                       |
|------------|-------------------------------------------------------------------------|
| Admin      | Everything: billing, members, households, contributions, expenses, reports. |
| Treasurer  | Verify contributions, record expenses, run reports, manage households. No billing, no member changes. |
| Viewer     | Read-only access to dashboard, reports, and household/contribution lists. |

Capabilities are encoded on the enum (`canManageBilling()`, `canVerifyContributions()`, `canRecordExpenses()`) and enforced by middleware + service-layer checks. The string column `organization_user.role` still holds the raw value (`admin` | `treasurer` | `viewer`).

The first user to create an organization is always attached as **Admin**.

## Inviting a member

1. **Admin** opens `/{org}/settings/members`.
2. Submits email + role via the "Invite a member" form.
3. `InvitationService::invite()` validates:
   - Email is not already a member of the org (case-insensitive).
   - Email does not already have a pending invite for the org.
4. A row is written to `organization_invitations` with a 48-char `token` and 14-day `expires_at`.
5. `AuditLog` records `organization.member.invited`.
6. `OrganizationInvitationSent` event fires.
7. `SendOrganizationInvitationEmail` listener queues `OrganizationInvitationMail`, which contains a signed URL to `/invitations/{token}`.

### Email template

`resources/views/emails/invitation/sent.blade.php` renders a Markdown email with:

- The inviter's name and organization name.
- The role label + a short description of what the role can do.
- A signed "Accept invitation" button (link expires with the invitation).
- Expiry date.

Mail uses the configured `MAIL_MAILER` — log driver in dev/tests, SMTP in production.

## Accepting an invitation

1. Recipient clicks the signed URL in the email OR clicks the "Review" button in the in-app pending-invitations banner (rendered in `layouts/layout.blade.php` for any authed user with pending invites).
2. `GET /invitations/{token}` (`InvitationController::show`):
   - If the invitation is not pending → renders `invitations/invalid.blade.php` (already accepted / revoked / expired states).
   - If the user is not logged in → redirects to `/login?email={invitation.email}` and stashes the token in session.
   - If the logged-in user's email does not match → returns a validation error explaining the mismatch.
   - Otherwise → renders `invitations/show.blade.php` with Accept / Decline buttons.
3. **Accept** (`POST /invitations/{token}/accept`):
   - Verifies the invitation is still pending.
   - Verifies the logged-in user's email matches the invitee email.
   - In a transaction: attaches the user to `organization_user` with the invited role, marks `accepted_at`, writes `AuditLog` action `organization.member.joined`.
   - Redirects to `/{org}/dashboard` with success flash.
4. **Decline** (`POST /invitations/{token}/decline`): sets `revoked_at = now()`. The user can then ignore it.

### Existing-user flow

If the invitee already has a BarangayBooks account (matched by email), the existing user accepts through the same flow after logging in. We do **not** auto-attach to existing accounts without their explicit accept — this preserves the email-address-of-record invariant.

### In-app discovery

Users who sign in with pending invitations see a banner at the top of every page listing each pending invite (org name, role, expiry) with a "Review" button. The banner queries `OrganizationInvitation::where('email', auth()->user()->email)->pending()->with('organization')`. It disappears once all invitations are accepted, declined, revoked, or expired.

## Revoking an invitation

Admins can revoke a pending invite from the members tab. This sets `revoked_at = now()` and writes `AuditLog` action `organization.member.invitation_revoked`. The signed URL stops working immediately.

## Resending an invitation

"Resend" bumps `expires_at` by 14 days and re-fires the `OrganizationInvitationSent` event (which sends another email). The token does **not** rotate; if you want fresh tokens on resend for security reasons, change `InvitationService::resend` to call `Str::random(48)` and update.

## Removing a member

Admins can remove any non-last-admin member via the members table's "Remove" button. The service:

1. Reads the member's current role.
2. If they're the last admin → refuses with a flash error ("Cannot remove the last admin. Promote another member to admin first.").
3. Otherwise detaches the pivot row and writes `AuditLog` action `organization.member.removed`.

This is hard-delete-from-org only; the underlying `users` row is preserved.

## Changing a role

The members table has an inline role dropdown. Selecting a new value submits a `PATCH /settings/members/{member}/role` request. The service:

1. Reads the current role.
2. If the change would demote the last admin → refuses with a flash error.
3. Otherwise updates the pivot row and writes `AuditLog` action `organization.member.role_changed` with `from`/`to` values.

## Last-admin protection

Removing or demoting the **last** admin is impossible. Promote another member to admin first. This is enforced in:

- `InvitationService::removeMember()` — counts current admins; throws if removing the last one.
- `InvitationService::changeRole()` — same guard for demotion.

Both surface as flash validation errors via `ValidationException`.

## Role-based route gates

`EnsureOrganizationRole` middleware (`app/Http/Middleware/EnsureOrganizationRole.php`) blocks routes unless the current user's role is in an allowed list. Applied at:

| Route                                            | Allowed roles  |
|--------------------------------------------------|----------------|
| `DELETE /{org}/households/{h}/contributions/{c}` | admin, treasurer |
| `GET /{org}/contributions/verify`                | admin, treasurer |
| `PATCH /{org}/contributions/{ref}/verify`        | admin, treasurer |
| `POST /{org}/expenses` (resource)                | admin, treasurer |

Admin-only actions (members tab, billing tab, settings) stay behind `EnsureOrganizationAdmin`.

Viewer-only routes: dashboard, reports (read), public household/contribution lookup. These don't need the role middleware — `EnsureOrganizationMember` already gates by membership.

## Audit log

Every state change writes to `audit_logs` with `organization_id`, `user_id` (actor), and JSON metadata:

| Action                                      | Metadata                                     |
|---------------------------------------------|----------------------------------------------|
| `organization.member.invited`               | `{invitation_id, email, role}`               |
| `organization.member.invitation_revoked`    | `{invitation_id, email}`                     |
| `organization.member.invitation_resent`     | `{invitation_id}`                            |
| `organization.member.joined`                | `{invitation_id, role}`                      |
| `organization.member.removed`               | `{member_id, member_email, role}`            |
| `organization.member.role_changed`          | `{member_id, from, to}`                      |

## Security notes

- Invitation tokens are 48-char random strings (unguessable in practice). The accept URL is signed via Laravel's `URL::signedRoute`, which adds HMAC over the URL — tampering invalidates the signature.
- The accept endpoint re-verifies that the logged-in user's email matches the invitee email, preventing an attacker from using a stolen URL with a different account.
- Invitations expire after 14 days. Expired tokens still resolve (signed URLs don't time-expire by default — we rely on `invitation.expires_at` checked in the controller + scope). Consider enabling `temporarySignedRoute()` if you want the URL itself to expire.
- Admin-only routes use `EnsureOrganizationAdmin` middleware, which checks `pivot.role === 'admin'` via a real DB query, not a cached role.
- Removing the last admin is a hard error — there's no UI to demote yourself.

## Tests

`tests/Feature/InvitationTest.php` covers:

- Members page access (admin sees, treasurer gets 403)
- Invite flow: case-insensitive email, event dispatched, mail queued, validation errors for existing-member + duplicate-pending
- Accept: correct user attached with role, wrong user gets validation error, audit log written
- Revoke + resend
- Remove: normal flow + last-admin refusal
- Role change: normal flow + last-admin demotion refusal
- Role gates: treasurer can verify contributions, viewer cannot
- Audit log entries exist for member changes

Run with:

```bash
php artisan test --filter=InvitationTest
```

## Adding new roles

To add a fourth role (e.g. `Auditor` with read-only + audit-log access):

1. Add a case to `OrganizationRole` enum.
2. Add a default role column constraint if you want a DB-level constraint (currently the column is `string`).
3. Update `EnsureOrganizationRole` calls on routes you want to allow.
4. Update the description + capability helpers on the enum.
5. Update `resources/views/organizations/settings/members.blade.php` role badges if you want a distinct color.
6. Add tests covering the new role's permissions.