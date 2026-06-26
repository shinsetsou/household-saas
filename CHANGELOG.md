# Changelog

All notable changes to this project. Newest first.

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

### Added — Sentry observability

- **`sentry/sentry-laravel` ^4.0** added to `composer.json`. Errors + performance traces + profiling. Errors sampled at 100% (`SENTRY_ERRORS_SAMPLE_RATE`), traces at 20% (`SENTRY_TRACES_SAMPLE_RATE`), profiles at 20% (`SENTRY_PROFILES_SAMPLE_RATE`). Tunable per env.
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