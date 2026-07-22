# Changelog

All notable changes to this project. Newest first.

## [Unreleased] — 2026-07-08

### Fixed — Accept/Decline forms posted to unsigned URLs (403 invalid signature)

- **`resources/views/invitations/show.blade.php:15,21`** — both forms used the plain `route()` helper to build their `action` attribute, which generates an unsigned URL (`/invitations/{token}/accept`). The `invitations.accept` and `invitations.decline` routes are gated by `signed` middleware (line 131-132 of `routes/web.php`), so POSTing the form 403'd with "invalid signature". Switched both to `URL::signedRoute('invitations.accept' / 'invitations.decline', ['invitation' => $invitation->token])` so the form action includes the `?signature=...` query param. The Accept form on the show page now correctly submits with a fresh signature.

### Tests

- `tests/Feature/InvitationTest.php::test_accept_form_action_is_signed_url` — asserts the rendered show view contains the signed accept URL in the form's `action` attribute. Catches regressions if anyone reverts to `route()`.
- `tests/Feature/InvitationTest.php::test_decline_form_action_is_signed_url` — same guard for decline.
- Updated 4 existing tests that called `route('invitations.accept' / 'invitations.decline', $invitation->token)` to use `URL::signedRoute(...)` instead. The unsigned variants would 403 against the now-clearly-signed routes — they were passing only because the test client wasn't actually exercising the middleware in some setups, masking the bug.

## [Unreleased] — 2026-07-06

### Fixed — Member invitation flow end-to-end

The member invitation flow had three breakages that made invites unusable in practice. Fixed all three plus added a brand-new set-password screen so invitees without an existing account can actually join.

#### Bug 1 — Invitation URL returned 404 on direct access

- **`app/Models/OrganizationInvitation.php:46-49`** — added `getRouteKeyName(): 'token'`. Laravel's implicit route binding for `{invitation}` was looking up by primary key (`id`), but every URL builder (the email mailable, the `link` endpoint, the views) was generating paths from the 48-char `token`. The mismatch meant every invite URL hit `findOrFail(<token>)` → 404 model-not-found. The unit/feature tests were passing `route('invitations.show', $invitation->token)` but only because they faked at the route layer, never exercising the binding.
- **`app/Http/Controllers/Settings/MemberSettingsController.php:86`** — the `link` action was building an *unsigned* URL (`url('/invitations/'.$token)`) while the route is gated by `signed` middleware. Direct browser access would 403. Switched to `URL::signedRoute('invitations.show', ['invitation' => $invitation->token])`.
- **`resources/views/organizations/settings/members.blade.php:141,150,158`** — three `route(...)` calls passed `$invitation->id` for `link` / `resend` / `revoke`. All switched to `$invitation->token` to align with the new `getRouteKeyName`.
- **`tests/Feature/InvitationTest.php:199,333,352,367`** — updated matching `route(...)` calls.

#### Bug 2 — Invitation emails never reached Mailtrap

- **`app/Listeners/SendOrganizationInvitationEmail.php`** — removed `implements ShouldQueue`. With `QUEUE_CONNECTION=database`, the listener + the mailable both implemented `ShouldQueue`, so the email sat in the `jobs` table until `php artisan queue:work` ran. The existing `MailFailureListenerTest::test_invitation_listener_succeeds_normally` actually asserted `Mail::assertSent(... 1)` + `Mail::assertQueued(... 0)`, confirming the team intended synchronous sending. Removed the queue trait and `Queueable`/`SerializesModels` imports from the mailable for the same reason.
- The listener still catches `TransportException` and writes to `Log::error` so SMTP failures don't 500 the invite flow (per the [2026-07-04] hardening below).

#### Bug 3 — `@component('mail::message')` / `@component('mail::button')` threw `InvalidArgumentException: No hint path defined for [mail]`

- **`resources/views/emails/invitation/sent.blade.php`** — the view used Laravel's Markdown mail components, which require `php artisan vendor:publish --tag=laravel-mail` to drop the stubs into `resources/views/vendor/mail/`. The project never ran that publish. Rewrote as plain HTML using inline-styled markup, matching the existing `emails/contribution/added.blade.php` style. The mailable's `->view('emails.invitation.sent')` was already plain HTML — only the template body needed rewriting.

### Added — Auto-register + set-password flow for invited users

Visiting `/invitations/{token}` as an unauthenticated user used to bounce to `/login?email=...` with no way for the invitee to set a password. Replaced with auto-register + forced set-password:

- **`database/migrations/2026_06_27_000002_add_force_change_password_to_users_table.php`** — adds `users.force_change_password` boolean (default `false`).
- **`app/Models/User.php`** — `force_change_password` fillable + cast to bool + `needsPasswordChange()` helper.
- **`app/Http/Controllers/InvitationController.php:30-72`** — when no user is logged in: look up the invitee's email; if no user exists, create one with a random 64-char password + `force_change_password = true` + `email_verified_at = now()` (the invite link itself proves inbox ownership); if a user already exists, set the flag. `Auth::login()`, store `invitation.intended_token` in the session, redirect to `/password/set`.
- **`app/Http/Controllers/Auth/PasswordSetController.php`** — new controller. `show()` renders the form; `store()` validates with `Password::defaults()`, hashes the password, clears `force_change_password`, regenerates the session, and redirects back to `/invitations/{intended_token}` (or `dashboard` if no token).
- **`app/Http/Middleware/ForcePasswordChange.php`** — new middleware (aliased as `force.password.change` in `bootstrap/app.php:31`). When `needsPasswordChange()` returns true, redirects to `password.set.show` except for `/password/set`, `/logout`, `/verify-email*`, `/email/verification-notification`. Applied to:
  - `routes/web.php:36` — `/dashboard`
  - `routes/web.php:39` — `/profile/*`
  - `routes/web.php:72` — `/{organization:slug}/...` tenant group
- **`resources/views/auth/set-password.blade.php`** — new view. Uses the existing `<x-guest-layout>` (brand panel + form panel) and `<x-password-input>` (show/hide toggle + strength meter) so it matches `auth/reset-password.blade.php` styling. Includes a "Wrong invitation? Sign out" escape hatch that posts to `/logout`.
- **`routes/auth.php:11,55-58`** — imports `PasswordSetController` and registers `GET /password/set` (name `password.set.show`) + `POST /password/set` (name `password.set.store`) inside the `auth` group, gated by `force.password.change`.

### Tests

- `tests/Feature/InvitationTest.php::test_invite_auto_registers_new_user_and_redirects_to_set_password` — verifies the no-account visitor is created, `force_change_password` is set, `email_verified_at` is set, the user is authenticated, and `invitation.intended_token` is in the session before redirecting to `/password/set`.
- `tests/Feature/InvitationTest.php::test_set_password_resumes_the_invitation` — verifies post-submit redirect to the original invite URL, flag cleared, session token consumed.
- `tests/Feature/InvitationTest.php::test_force_password_change_middleware_blocks_dashboard` — `/dashboard` redirects to `/password/set` while the flag is set.
- `tests/Feature/InvitationTest.php::test_force_password_change_allows_set_password_and_logout` — verifies the allowlist (the user can reach `/password/set` and `/logout` to escape).
- `tests/Feature/InvitationTest.php::test_signed_invite_url_works_end_to_end` — exercises the signed URL against a real logged-in invitee; the route binding by token is end-to-end verified.

### Fixed — Decline button silently killed invitations

- **`resources/views/invitations/show.blade.php`** — the Decline form was stacked directly under the Accept button and visually equivalent. `InvitationController::decline` writes `revoked_at = now()` immediately on POST, so an accidental click permanently revoked the invite. Three changes: (a) demoted the Decline button to a small underlined text link (`text-xs text-slate-500 hover:underline`) at the bottom of the card so it can't be tapped by accident; (b) added a stronger `onsubmit="return confirm('Decline this invitation? This will permanently revoke the link and the inviter will need to send a new one if you change your mind.');"` so the browser catches the click; (c) Accept remains unguarded (it's reversible — admin can remove the member).
- **`resources/views/organizations/settings/members.blade.php:158`** — admin-side Revoke form got a stronger confirm message: "Revoke this invitation? The link will stop working immediately. You can resend a fresh link from this page if needed." Previously was a one-line "Revoke this invitation?" — too easy to OK through.

### Added — `OrganizationInvitationObserver` audit trail

- **`app/Observers/OrganizationInvitationObserver.php`** — new observer. On the `updating` event, captures any transition of `revoked_at` from `null` to non-null, and any transition of `accepted_at` from `null` to non-null. Logs to `Log::warning('invitation.revoked', [...])` with full request context (route name, HTTP method, path, IP, authenticated user id/email). Also writes an `audit_logs` row with `action = organization.member.invitation_revoked` and `source = observer`. This makes future "who revoked this?" mysteries answerable from the DB alone.
- **`app/Providers/AppServiceProvider.php:24`** — registered `OrganizationInvitation::observe(OrganizationInvitationObserver::class)`.
- `tests/Feature/InvitationTest.php::test_revoke_writes_audit_log_with_observer` — verifies the audit row is written when an admin clicks Revoke.
- `tests/Feature/InvitationTest.php::test_decline_writes_audit_log_with_observer` — verifies the same on Decline.
- `tests/Feature/InvitationTest.php::test_invite_view_shows_strong_decline_confirmation` — verifies the show view still contains the `onsubmit="return confirm(..."` attribute (catches regressions if anyone refactors the view).

### Fixed — Invitees who skipped Accept were dumped into the onboarding flow

- **`app/Http/Controllers/DashboardRedirectController.php`** — was redirecting to `onboarding.organization.create` whenever the user had no org membership, even if they had a pending invitation sitting in their inbox. So a Treasurer invitee who set their password but never clicked Accept would land on the "create your HOA" wizard — the system treated them as a brand-new user. New ordering: **org membership → pending invitations → onboarding**. If they have pending invites, they're sent to a list view to review them.
- **`app/Http/Middleware/RedirectIfOnboarded.php`** — same logic applied to the onboarding wizard guard. Users without orgs but with pending invites are redirected to the invite list instead of the wizard.
- **`app/Http/Controllers/PendingInvitationsController.php`** — new controller. `index()` lists the user's pending invitations (with inviter, role, signed URL, days-to-expire), `destroy()` lets them decline from the list. Email-match guard prevents accidentally declining someone else's invite.
- **`resources/views/invitations/pending.blade.php`** — new view. Card per pending invitation: org logo initial, inviter name, role chip, expiry countdown, "Review & accept" button (links to the signed `invitations.show` URL), "Decline" text link with confirm dialog.
- **`routes/web.php:133-136`** — new `GET /invitations` (name `invitations.index`) and `DELETE /invitations/{invitation}` (name `invitations.pending.destroy`) inside the `auth + verified` group.
- 5 new tests in `tests/Feature/InvitationTest.php`: dashboard routing order (pending > onboarding), index view renders, destroy sets revoked_at, destroy 403s for non-matching email, dashboard routes to org when member.

### Fixed — Email verification required after accepting an invite

- **`app/Http/Controllers/InvitationController.php:40`** — was using `$existing->email_verified_at ?? now()` (preserve-if-set). If an invitee's `User` row existed with `email_verified_at = null` (e.g. they registered earlier but never clicked the verify link), the null-coalesce left it null, and `EnsureEmailIsVerified` bounced them to `/verify-email` after Accept. Changed to `now()` unconditionally — the invite link itself is the trust proof.
- **`app/Http/Controllers/InvitationController.php:64-66`** — added a check for the case where a user is *already logged in* (e.g. they have an active session, click an invite link from their email) and their `email_verified_at` is null. The view branch was leaving it untouched. Now `forceFill(['email_verified_at' => now()])` runs before rendering the invite page, so the subsequent Accept isn't bounced.
- `tests/Feature/InvitationTest.php::test_invite_show_marks_logged_in_user_as_verified` — `User::factory()->unverified()` clicks the invite show, gets verified.
- `tests/Feature/InvitationTest.php::test_accepting_invitation_does_not_bounce_to_verify` — full accept → org dashboard redirect.

### Fixed — `set-password` redirected to an unsigned invitation URL (403 invalid signature)

- **`app/Http/Controllers/Auth/PasswordSetController.php:34`** — was using `route('invitations.show', ['invitation' => $token])` to build the post-submit redirect. That helper generates an unsigned URL (`/invitations/{token}`), but the route is gated by `signed` middleware → 403 "invalid signature". Switched to `URL::signedRoute(...)` so the redirect contains a fresh signature tied to the token.
- `tests/Feature/InvitationTest.php::test_set_password_resumes_the_invitation` updated to assert the signed URL form.
- `tests/Feature/InvitationTest.php::test_set_password_redirect_to_invitation_url_is_signed_and_resolvable` — new. Pulls the `Location` header from the actual response and follows it, asserting 200 + "Accept invitation" copy. Catches the regression end-to-end.

## [Unreleased] — 2026-07-07

### Fixed — Invitees with revoked invites silently became admins of a brand-new org

**The bug**: when a Treasurer (or any role) was invited, set their password, then had their invite revoked (or it expired) before clicking Accept, their `/dashboard` redirect treated them as a brand-new user and dumped them into the onboarding wizard. The wizard then auto-attached them as **Admin** of a fresh org (`OnboardingService::createOrganizationForUser` line 32) — disconnected from the HOA they were originally invited to. No warning, no audit trail, real risk of identity confusion and accidental admin powers.

**The fix**: track each user's invite origin. If the originating invite is revoked before they join, they get soft-locked out of the onboarding wizard with a clear explainer page. Recovery: accept a new invite to the same email and the lock clears automatically.

- **`database/migrations/2026_07_06_000001_add_invitation_origin_columns_to_users_table.php`** — adds four nullable columns to `users`: `invitation_origin_org_slug` (indexed), `invitation_origin_role`, `invitation_origin_inviter_email`, `invitation_origin_revoked_at` (indexed). All nullable so the migration is safe to run on existing rows.
- **`app/Models/User.php`** — added fillable entries, datetime cast for `invitation_origin_revoked_at`, and four helpers:
  - `wasInvited(): bool` — origin slug is set
  - `isInviteLocked(): bool` — revoke timestamp is set
  - `lockInviteOrigin(): void` — sets the timestamp (idempotent)
  - `clearInviteOriginLock(): void` — clears the timestamp (idempotent)
- **`app/Http/Controllers/InvitationController.php`** — auto-register branch (lines 30-58) now records the origin columns on `User::create([...])`, and on the "else" branch (existing user found by email) sets origin fields only if `wasInvited()` is false (don't overwrite a prior origin). New `locked()` action returns the explainer view.
- **`app/Services/InvitationService.php`** — `revoke()` calls a new protected `lockUserIfOrphan()` helper that finds the user by email and locks them if they have no org membership. `accept()` calls `$user->clearInviteOriginLock()` inside the transaction so the lock is cleared exactly when the user joins.
- **`app/Observers/OrganizationInvitationObserver.php`** — extended. The existing revoke-transition handler now also calls the new `lockOrphanedUserForInvite()` helper as a defense-in-depth layer. Idempotent — no-op if user already has any org membership.
- **`app/Http/Controllers/DashboardRedirectController.php`** — new redirect order: **org membership → pending invitations → invite locked → onboarding**. Locked users land on the explainer page, not the wizard.
- **`app/Http/Middleware/RedirectIfOnboarded.php`** — same redirect order added for the onboarding guard.
- **`routes/web.php:138`** — `GET /invitations/locked` (name `invitations.locked`) inside the `auth + verified` group.
- **`resources/views/invitations/locked.blade.php`** — new view. Card with rose-100 icon, "Your invitation was revoked" headline, body showing origin org + role + inviter email (mailto link), revoke timestamp, "Sign out" button, and "Create your own HOA instead →" escape hatch so the user is never trapped.

### Tests

6 new tests in `tests/Feature/InvitationTest.php`:

- `test_auto_registration_stores_invitation_origin` — verifies origin columns populated on User::create.
- `test_revoking_invite_locks_orphan_user` — verifies `lockUserIfOrphan()` sets the timestamp.
- `test_revoking_invite_does_not_lock_user_with_existing_org_membership` — verifies the "has any org membership" guard.
- `test_dashboard_redirects_to_locked_page_when_invite_was_revoked` — verifies the new dashboard redirect order.
- `test_accepting_new_invite_clears_invitation_origin_lock` — verifies the recovery path: revoke → re-invite → accept → lock cleared → user joins as intended.
- `test_locked_page_renders_with_inviter_contact` — verifies the view shows origin org slug, role, and inviter email.

## [Unreleased] — 2026-07-05

### Fixed — Mailtrap transport actually wired up

- **Replaced the symfony bridge + custom `Mail::extend` hack with the official `railsware/mailtrap-php` Laravel bridge.** The package lives at `vendor/railsware/mailtrap-php/src/Bridge/Laravel/MailtrapApiProvider.php`, is auto-discovered by Composer, and registers the `mailtrap` mailer via `Mail::extend('mailtrap', ...)` reading `MAILTRAP_HOST` / `MAILTRAP_API_KEY` / `MAILTRAP_INBOX_ID` from `config/services.php` (merged via `mergeConfigFrom`).
- **`composer.json`** now requires `railsware/mailtrap-php`, `symfony/http-client`, `symfony/mailer`, and `nyholm/psr7` (the `psr7` adapter is what the `symfony/http-client` needs to build Mailtrap HTTP requests).
- **`config/mail.php`** registers a `mailtrap` mailer entry with `transport: 'mailtrap'`. Without this, `MailManager::resolve` throws `Mailer [mailtrap] is not defined.`
- **`bootstrap/providers.php`** is not modified — the package is auto-discovered.
- **`AppServiceProvider::warnOnMisconfiguredMail()`** now detects the missing `railsware/mailtrap-php` package (via `class_exists(\Mailtrap\Bridge\Transport\MailtrapTransportFactory::class)`) and logs `mail.driver.mailtrap_package_missing`.
- **`.env.production.example`, `.env.example`, `docs/MAIL_SETUP.md`, `docs/RAILWAY_DEPLOY.md`, `AGENTS.md`** all updated. Env vars are now:
  ```
  MAIL_MAILER=mailtrap
  MAILTRAP_HOST=send.api.mailtrap.io     # or sandbox.api.mailtrap.io / bulk.api.mailtrap.io
  MAILTRAP_API_KEY=<token from Mailtrap dashboard → Email Sending → API tokens>
  MAILTRAP_INBOX_ID=<only for sandbox host>
  ```
  The old `MAIL_MAILTRAP_DSN` / `MAILTRAP_INBOX_ID` is gone — `MAILTRAP_INBOX_ID` is only required for sandbox mode.
- **`tests/Feature/MailtrapMailerTest.php`** rewritten — 3 tests verify the mailer resolves to `MailtrapApiTransport` for both transactional and sandbox hosts, and the package's service provider is loaded.
- **`tests/Feature/MailConfigWarningTest.php`** updated to check for the new `Mailtrap\Bridge\Transport\MailtrapTransportFactory` class.

### Added — Mailtrap Email API support

- **`mailtrap/mailtrap-laravel` listed as a `suggest` in `composer.json`**. Run `composer require mailtrap/mailtrap-laravel` to enable `MAIL_MAILER=mailtrap`. App stays install-clean without it; the package is optional.
- **`docs/MAIL_SETUP.md`** — full setup walkthrough. Documents the **Mailtrap sandbox SMTP vs Email API** distinction: sandbox is dev-only and unreachable from cloud IPs (Railway, Render, AWS). Symptoms: `Maximum execution time of 30 seconds exceeded` inside `Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream.php:154`. Switch to Email API or a different provider.
- **`.env.production.example`** documents `MAIL_MAILER=mailtrap` and adds `MAILTRAP_API_TOKEN` / `MAILTRAP_INBOX_ID` variables.

### Changed — Trust Railway's edge proxy

- **`bootstrap/app.php:21`** — `$middleware->trustProxies(at: '*')`. Forms / email links / signed URLs now respect `X-Forwarded-Proto: https` from Railway's load balancer. Without this, `url()` and `route()` would generate `http://` links even when the user is on HTTPS (caused "form is not secure" browser warnings on the verify-email resend button).

## [Unreleased] — 2026-07-04

### Changed — Mail listener hardening

- **All 3 mail listeners now swallow `TransportException` and log instead of bubbling up**:
  - `app/Listeners/SendEmailVerificationNotification.php` — verification email failures no longer break user registration. Logged via `Log::error`.
  - `app/Listeners/SendOrganizationInvitationEmail.php` — invitation email failures no longer break the invite flow. Logged.
  - `app/Listeners/SendContributionAddedEmail.php` — contribution-added email failures no longer break contribution creation. Logged.

  Before this change, a `MAIL_HOST` typo or a Mailtrap SMTP outage caused 500 errors on user registration, invitation accept, and contribution creation. Now these flows succeed; the email is logged and can be retried later.

### Tests

- `tests/Unit/Listeners/MailFailureListenerTest.php` — verifies the 3 listeners swallow `TransportException` and log via `Log::error`, and that successful sends don't log errors.

## [Unreleased] — 2026-07-03

### Changed — OnboardingService contract

- **Folded `BillingService::startTrial` into `OnboardingService::createOrganizationForUser`** (`app/Services/OnboardingService.php`). Previously the controller had to call `createOrganizationForUser` then `startTrial` in two steps. Now a single call creates the org, attaches the user as admin, writes the audit log, AND starts the trial — all in one DB transaction. If the trial start fails (e.g. Subscription insert fails), the Organization rolls back too. Atomicity guarantee.
- **Removed explicit `$service->startTrial($org)` call** from `OrganizationStepController::store`.
- **Removed public `OnboardingService::startTrial` method** (was a 1-line passthrough — now part of `createOrganizationForUser`).

### Tests

- `tests/Unit/OnboardingServiceTest.php` — extended with 2 new tests: `test_create_organization_for_user_starts_trial` (asserts org lands in `trialing` state, `trial_ends_at` is set in the future, and a Subscription row is written), and `test_trial_start_failure_rolls_back_org_creation` (mocks BillingService to throw, asserts no Organization / pivot rows persist).

## [Unreleased] — 2026-07-02

### Changed — Tenant safety + view refactor

- **Fixed unscoped photo query** (`app/Http/Controllers/ContributionController.php:271`). Previously `Contribution::where('photo', $path)` was global — if two orgs happened to use the same B2 key, deleting one contribution could mistakenly report the photo as fully-referenced and skip B2 cleanup. Now scoped via `Contribution::forOrganization($organization)`.
- **Scoped verifyContribution lookup** (`ContributionController.php:79`) to use `forOrganization` instead of inline `whereHas`.
- **Scoped latestContribution lookup** (`ContributionController.php:147`) similarly.
- **Scoped BillingService::handlePaymentPaid lookups** (`app/Services/Billing/BillingService.php:92-105`) by `organization_id` for defense in depth.
- **Moved role/initials logic out of layouts/layout.blade.php**. New `App\Support\NavContext` value object computed once per request via the `layouts.layout` View Composer in `AppServiceProvider`. The layout now reads `$navContext->organization`, `$navContext->isAdmin`, `$navContext->userInitials` instead of inline DB queries + `auth()->user()->organizations()->wherePivot('role', 'admin')->exists()`. Side benefit: legacy `'role' => 'member'` strings now yield `role === null` cleanly (was inconsistent before).

### Tests

- `tests/Feature/TenantSafetyAuditTest.php` — 3 tests: cross-tenant shared photo deletion, cross-tenant verify-by-reference-number, latest-contribution isolation.
- `tests/Feature/EnsurePlanAllowsHouseholdBoundaryTest.php` — 8 tests: limit-1 OK, exactly-at-limit blocked, over-limit blocked, unlimited plan OK at high count, no-plan OK, import route blocked, store route blocked, store under limit OK, middleware passes through when route has no org.
- `tests/Unit/Support/NavContextTest.php` — 9 tests covering all role states + initials edge cases.

## [Unreleased] — 2026-07-01

### Added — Sentry observability (optional install)

- **`App\Support\SentryPiiScrubber`** + **`App\Providers\SentryServiceProvider`** + **`config/sentry.php`** ship with the app. Errors + performance traces + profiling when `sentry/sentry-laravel` is installed. Errors sampled at 100% (`SENTRY_ERRORS_SAMPLE_RATE`), traces at 20% (`SENTRY_TRACES_SAMPLE_RATE`), profiles at 20% (`SENTRY_PROFILES_SAMPLE_RATE`). Tunable per env.
- **Sentry SDK is NOT a required composer dep** — install with `composer require sentry/sentry-laravel` when ready. The code degrades to a no-op when the package is absent (`SentryServiceProvider::boot()` checks `class_exists` first).
- **`config/sentry.php`** — env-driven. Lists ignored exceptions (`AuthenticationException`, `ValidationException`, `HttpException`) and ignored transactions (`/up`, `/billing/webhook`).
- **`App\Support\SentryPiiScrubber`** (`app/Support/SentryPiiScrubber.php`) — Sentry event processor that strips sensitive fields (passwords, tokens, PayMongo/AWS secrets, card-shaped numbers, etc.) recursively from breadcrumbs, request data, extras, and user context.
- **`App\Providers\SentryServiceProvider`** — registered via `bootstrap/app.php`. Registers the scrubber as a Sentry event processor and is a no-op when the SDK is absent or `SENTRY_LARAVEL_DSN` is unset.
- **`docs/OBSERVABILITY.md`** — full setup walkthrough: DSN, release tracking, scrubbing rules, alerting, cost control.

### Tests

- `tests/Unit/Support/SentryPiiScrubberTest.php` — exercises scrubbing rules via reflection (no SDK required).
- `tests/Feature/SentryServiceProviderTest.php` — verifies singleton registration and safe boot when SDK / DSN is absent.

## [Unreleased] — 2026-06-30

### Added — Pending-invitations banner

- **Banner in main tenant layout** (`resources/views/layouts/layout.blade.php`) — rendered for any authed user with one or more pending `OrganizationInvitation` rows (matched by email). Lists each invitation with org name, role label, expiry date, and a "Review" button linking to the signed `/invitations/{invitation}` show page. Hidden when no pending invites exist (or all are accepted / revoked / expired). Singular copy for 1 invite, plural for 2+.

### Tests

- `tests/Feature/PendingInvitationBannerTest.php` — 9 tests: banner shows on dashboard / tenant dashboard, single vs plural copy, hidden when no pending / accepted / revoked / expired, review button uses signed URL, email match is per-user.

## [Unreleased] — 2026-06-29

### Added — Email verification enforcement

- **`User` implements `Illuminate\Contracts\Auth\MustVerifyEmail`** (`app/Models/User.php`). All new users must verify their email before accessing tenant routes.
- **`EnsureEmailIsVerified` middleware** (`app/Http/Middleware/EnsureEmailIsVerified.php`) aliased as `'verified'` in `bootstrap/app.php`. Applied to `/dashboard`, `/profile*`, `/onboarding/*`, `/{org}/...` tenant group, and `/invitations/{invitation}/{accept,decline}`. Allowlist for `verify-email`, `email/verification-notification`, `logout` so the verify loop stays usable.
- **`SendEmailVerificationNotification` listener** (`app/Listeners/SendEmailVerificationNotification.php`) — bound to `Registered` event in `EventServiceProvider`. Sends `VerifyEmail` notification for unverified users only.
- **Verify-email page** (`resources/views/auth/verify-email.blade.php`) — BarangayBooks-branded copy, resend + logout buttons.

### Tests

- `tests/Feature/EmailVerificationEnforcementTest.php` — 10 tests: model implements contract, `Registered` event triggers verification email, verified user reaches dashboard, unverified user bounced from dashboard / tenant dashboard / onboarding / profile, unverified user can still see notice page / resend / log out, already-verified user does not get duplicate email.

## [Unreleased] — 2026-06-28

### Added — Trial expiry scheduler

- **`ExpireTrials` command** (`app/Console/Commands/ExpireTrials.php`) — `php artisan trials:expire [--dry-run]`. Daily at 00:05 via `routes/console.php`. Flips `subscription_status` to `past_due` for orgs whose `trial_ends_at` has passed, sends `TrialExpiredMail` to all admins. Sends `TrialEndingMail` at T-7 and T-1 days, idempotent via `trial_warning_sent_at` JSON column on `organizations`.
- **`EnsureOrganizationActive` middleware** (`app/Http/Middleware/EnsureOrganizationActive.php`) — applied to the tenant route group. Blocks mutating requests on `past_due`/`canceled`/`unpaid` orgs except for billing routes (`settings/billing/*`) and `logout`. GET requests still allowed so admins can see their data and the billing page.
- **`Organization::isExpired()`, `daysUntilTrialEnds()`, `hasWarningBeenSent()`, `markWarningSent()`** helpers (`app/Models/Organization.php`).
- **Trial + expired banners on the dashboard** (`resources/views/organizations/index.blade.php`).
- **`TrialEndingMail` / `TrialExpiredMail`** (`app/Mail/`) + Markdown templates.
- **`Procfile`** adds `scheduler: php artisan schedule:work` for hosts that read it. `nixpacks.toml` documents Railway Cron / Render Cron alternatives.

### Tests

- `tests/Feature/TrialExpiryTest.php` — 10 tests: command expires overdue, sends T-7 + T-1 warnings (idempotent), skips out-of-window, skips active subs, dry-run safe; expired org blocks writes, allows GETs, allows billing writes; dashboard banners render.

## [Unreleased] — 2026-06-27

### Added — PayMongo billing

- **3-tier plan catalog** seeded by `database/seeders/DatabaseSeeder.php`: Community (₱199, 50 households), Barangay (₱499, 250 households), City (₱999, unlimited + API).
- **`plans`, `subscriptions`, `payments` tables** via `database/migrations/2026_06_26_000001_create_billing_tables.php`.
- **`Organization` billing columns**: `subscription_status`, `trial_ends_at`, `subscription_ends_at`, `plan_id`, `paymongo_customer_id`.
- **Trial state machine**: every new org starts on a 14-day Community trial (`OnboardingService::startTrial`). Subscription status transitions through `trialing → active | past_due | canceled | unpaid`.
- **`PayMongoClient`** (`app/Services/PayMongo/PayMongoClient.php`) — Laravel HTTP-facade based, basic-auth with secret key.
- **`BillingService`** (`app/Services/Billing/BillingService.php`) — `startCheckout`, `handlePaymentPaid` (idempotent via `updateOrCreate` + dual session lookup), `cancelSubscription`, `startTrial`, `seedDefaultPlans`.
- **`BillingController`** (`app/Http/Controllers/BillingController.php`) — checkout redirect, return URLs, webhook.
- **`BillingSettingsController`** (`app/Http/Controllers/BillingSettingsController.php`) — billing tab view, cancel action.
- **`EnsurePlanAllowsHousehold` middleware** (`app/Http/Middleware/EnsurePlanAllowsHousehold.php`) — enforces `plan.max_households` cap on household create/import routes.
- **Settings Billing tab** (`resources/views/organizations/settings/billing.blade.php`) — current plan, status pill, household usage, plan picker, recent payments, cancel button. Replaces the "Coming soon" placeholder in `settings/layout.blade.php`.
- **Replaced landing "Founding HOA — Free" pricing** with 3-tier pricing cards (`resources/views/welcome.blade.php`).
- **`docs/PAYMONGO_SETUP.md`** — full PayMongo walkthrough: API keys, webhook registration, signature algorithm, test mode, plan catalog.
- **`.env.production.example`** updated with `PAYMONGO_*` vars.

### Added — Webhook signature verification

- **`VerifyPayMongoSignature` middleware** (`app/Http/Middleware/VerifyPayMongoSignature.php`) — HMAC-SHA256 of raw body, `hash_equals` for timing safety. Returns 401 on mismatch, 500 if `PAYMONGO_WEBHOOK_SECRET` is unset.
- **CSRF exclusion** for `/billing/webhook` in `bootstrap/app.php` via `validateCsrfTokens(except: ['billing/webhook'])`.
- **`BillingController::webhook`** slimmed to event handling only — signature verification is the middleware's job.

### Added — Member invitations

- **`OrganizationRole` enum** (`app/Enums/OrganizationRole.php`) — `Admin | Treasurer | Viewer` with `label()`, `description()`, capability helpers (`canManageBilling`, `canVerifyContributions`, `canRecordExpenses`).
- **`organization_invitations` table** (`database/migrations/2026_06_27_000001_create_organization_invitations_table.php`).
- **`OrganizationInvitation` model** (`app/Models/OrganizationInvitation.php`) with `isPending/isExpired/isAccepted/isRevoked`, scoped `pending()` query, auto-generated 48-char token, 14-day expiry.
- **`InvitationService`** (`app/Services/InvitationService.php`) — `invite`, `accept`, `revoke`, `resend`, `removeMember`, `changeRole`. Guards: rejects existing-member invites, duplicate pendings, last-admin demote/remove, wrong-email accept. All writes logged to `AuditLog`.
- **`OrganizationInvitationSent` event** → **`SendOrganizationInvitationEmail` listener** → **`OrganizationInvitationMail`** (signed `invitations.show` URL). Bound in `EventServiceProvider`.
- **`MemberSettingsController`** (`app/Http/Controllers/Settings/MemberSettingsController.php`) — list + invite form + role dropdown + remove + pending invites table.
- **`InvitationController`** (`app/Http/Controllers/InvitationController.php`) — public show/accept/decline. Wrong email → error with explanatory message.
- **`EnsureOrganizationRole` middleware** (`app/Http/Middleware/EnsureOrganizationRole.php`) — accepts `:admin,treasurer` style args. Gates `contributions.verify.*`, `contributions.destroy`, `expenses` resource.
- **Settings Members tab** (`resources/views/organizations/settings/members.blade.php`) replaces "Coming soon" placeholder.
- **Public invitation pages** (`resources/views/invitations/show.blade.php`, `invalid.blade.php`) and email template (`resources/views/emails/invitation/sent.blade.php`).
- **Role helpers** on `User`: `roleInOrganization`, `isAdminOf`, `hasRoleInOrganization`, `pendingInvitations`.
- **`Organization::roleFor(?User)`**, `invitations()`, `pendingInvitations()` relations.
- **`docs/MEMBERS.md`** — invitation flow walkthrough, role matrix, security notes.

### Tests

- `tests/Feature/BillingTest.php` — 14 tests: page access, checkout redirect + Payment row, webhook → activation, idempotency, signature verification (valid/missing/invalid/tampered/secret-unset), return URLs, household-limit gate.
- `tests/Feature/InvitationTest.php` — 17 tests: members page access, invite flow (case-insensitive email, dispatch, mail, validation), accept (correct user attached, wrong user blocked), revoke/resend, remove + last-admin guard, change role + last-admin guard, role gates on contributions, audit log entries.

## [Earlier]

See `docs/RAILWAY_DEPLOY.md` for the multi-tenant MVP history (organizations + slug routing + middleware isolation, contribution verification, audit log, deploy story).