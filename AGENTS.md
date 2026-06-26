# AGENTS.md

This file provides guidance to Codex (Codex.ai/code) when working with code in this repository.

## Commands

```bash
# Install
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan storage:link   # Required for contribution photo uploads

# Dev servers
php artisan serve          # http://localhost:8000
npm run dev                # Vite HMR on http://localhost:5173

# Sail (Docker)
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run dev

# Build
npm run build

# Test
php artisan test
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --filter=DelinquencyServiceTest
php artisan test tests/Unit/DelinquencyServiceTest.php

# Lint (PHP)
./vendor/bin/pint          # Fix style
./vendor/bin/pint --test   # Check only
```

`phpunit.xml` forces SQLite `:memory:`, `array` mail, `sync` queue — tests need no DB setup.

## Deploy

Three host configs are checked in:

- `nixpacks.toml` — Railway / Fly.io. Pins PHP 8.2 + Node 20, runs `composer install` → `npm ci` → `npm run build` (required — Alpine.js is bundled by Vite, `@vite` breaks silently if `public/build/` is missing) → `config/route/view:cache`. Start cmd runs `migrate --force` then `artisan serve`.
- `railway.json` — companion to `nixpacks.toml`; declares the release + start commands and healthcheck on `/`.
- `render.yaml` — Render.com blueprint with a MySQL sidecar.
- `Procfile` — Heroku-style fallback (`web:` + `release:`) for hosts that read it.
- `runtime.txt` — pins `php-8.2` for PaaS that read it (Render, Heroku).
- `.env.production.example` — production env template with all required vars (MySQL, SESSION/CACHE/QUEUE=database, Backblaze B2, SMTP). Reference only; never commit secrets.

Production env vars must be set **before** first deploy — `config:cache` bakes env into a cached file, so changed vars need a redeploy.

See `docs/RAILWAY_DEPLOY.md` for the full walkthrough, including the **B2 key "not entitled"** gotcha (Application Key must be scoped to `AWS_BUCKET` with `listFiles/readFiles/writeFiles/deleteFiles`).

For billing setup, see `docs/PAYMONGO_SETUP.md` — covers API keys, webhook registration, signature verification, and the 3-tier plan catalog.

For member invitations, see `docs/MEMBERS.md` — covers the role enum, invitation lifecycle, last-admin protection, and audit log entries.

## Architecture

Laravel 11 + Blade + Tailwind (CDN, not compiled) + Alpine + Axios. Vite only builds `resources/css/app.css` and `resources/js/app.js`. Auth scaffolded with Laravel Breeze (Blade stack).

### Multi-tenancy

All tenant routes live under `/{organization:slug}` via route model binding (`Organization::getRouteKeyName()` returns `slug`, populated by `cviebrock/eloquent-sluggable`). Membership is a many-to-many through the `organization_user` pivot with a `role` column (`admin` | `treasurer` | `viewer` — see `app/Enums/OrganizationRole.php`). `EnsureOrganizationMember` middleware (`app/Http/Middleware/EnsureOrganizationMember.php`) checks `User::belongsToOrganization()` and 403s otherwise — apply it together with `auth` on any tenant-write route. For finer role gating, use `EnsureOrganizationRole:admin,treasurer` middleware (`app/Http/Middleware/EnsureOrganizationRole.php`). Admin-only routes use `EnsureOrganizationAdmin`.

Public tenant routes (no auth): `home` (organization show), `households.index` (rate-limited `throttle:10,1` — block+lot lookup for residents), `contributions.create`, `contributions.store`. Everything else requires `auth + EnsureOrganizationMember`.

### Data Model

```
Organization (slug, monthly_due_amount, subscription_status, trial_ends_at, plan_id)
  ├── belongsToMany User       (organization_user pivot: role)
  ├── hasMany Household        (unique on organization_id, block, lot)
  │     └── hasMany Contribution
  ├── hasMany Expense
  ├── hasMany OrganizationInvitation (pending invites)
  ├── hasOne Subscription      (latestOfMany)
  ├── hasMany Payment
  └── belongsTo Plan           (max_households, csv_import, api_access, price_cents)

Plan (slug, name, price_cents, max_households, csv_import, api_access)
Subscription (organization_id, plan_id, status, current_period_start/end, paymongo_subscription_id)
Payment (organization_id, paymongo_payment_id, paymongo_checkout_session_id, amount_cents, status, paid_at)

AuditLog (polymorphic auditable, JSON metadata)
```

Cross-tenant query safety: prefer `forOrganization($org)` scopes on `Contribution` and `Household` rather than re-deriving `whereHas`. `Contribution::scopeForOrganization` walks through `household` so contributions can be filtered by org without an explicit `organization_id` column on the table.

### Contribution Verification

`contributions.verification_status` is the source of truth: `pending` | `verified` | `rejected` (string enum, indexed). The legacy `admin_verified` tinyInt column still exists for backwards compat but is backfilled by `2026_05_24_000001_add_mvp_multitenancy_columns.php` — new code should read/write `verification_status`, `rejection_reason`, `verified_by`, `verified_at`. The UUID `reference_number` is indexed and used in the verify endpoint.

Verify + reject routes are gated by `EnsureOrganizationRole:admin,treasurer` — viewers cannot verify.

### Event Flow

- `ContributionController::store` → fires `ContributionCreated` → `SendContributionAddedEmail` listener → `ContributionAdded` Mailable. Bound in `app/Providers/EventServiceProvider.php`. Mail defaults to Mailtrap; tests use `array` driver.
- `InvitationService::invite()` → fires `OrganizationInvitationSent` → `SendOrganizationInvitationEmail` → `OrganizationInvitationMail` (signed `/invitations/{token}` URL).

### Services Layer

Non-trivial domain logic sits in `app/Services/`:
- `DelinquencyService::forOrganization($org, $year, $month)` — returns rows of households below `monthly_due_amount` of verified `paid` contributions for the month, or with any `lacking` contribution. Used by `ReportController::delinquency`.
- `HouseholdCsvImporter` — validates a CSV (`name,block,lot` header), checks duplicates against existing rows + within the file, returns `HouseholdCsvImportResult` (count + errors). Wrapped in a DB transaction.
- `BillingService` (`app/Services/Billing/BillingService.php`) — PayMongo checkout + webhook handling. `startCheckout` creates a Checkout Session and a pending `payments` row; `handlePaymentPaid` is idempotent (looks up by `paymongo_payment_id` then `paymongo_checkout_session_id`, `updateOrCreate`s the subscription, marks the payment `paid`); `cancelSubscription` flips org to `canceled`; `startTrial` creates the initial `trialing` subscription. Uses `Illuminate\Support\Facades\Http` with basic auth — no SDK.
- `PayMongoClient` (`app/Services/PayMongo/PayMongoClient.php`) — thin REST wrapper. `createCheckoutSession`, `getCheckoutSession`, `getPayment`, `createCustomer`.
- `InvitationService` (`app/Services/InvitationService.php`) — invite / accept / revoke / resend / removeMember / changeRole. Guards: existing-member rejection, duplicate-pending rejection, last-admin removal/demotion rejection, wrong-email accept rejection. All writes log to `AuditLog`.

Add new domain ops here rather than expanding controllers.

### Reports & Exports

`ReportController` exposes contributions / expenses / delinquency reports. Exports go through `barryvdh/laravel-dompdf` for PDF and native CSV/Excel-compatible streams (no `maatwebsite/excel`). Routes: `reports.{contributions,expenses,delinquency}.export.{pdf,excel,csv}`.

### Authorization

Policies live in `app/Policies/` (`Organization`, `Household`, `Contribution`, `Expense`). They piggyback on org membership — generally `User::belongsToOrganization($org)` plus role check. Use `$this->authorize('action', $model)` in controllers; do NOT rely on middleware alone for write actions.

### Audit Log

`AuditLog::record($org, $action, $user, $auditable, $metadata)` writes a single row. Call it from controllers/services after state-changing operations (verifications, deletions, imports). Polymorphic `auditable_type` / `auditable_id` plus JSON `metadata`.

## Design System

All views: **Breadcrumb → Page Header (title + CTA) → Content card.**

- Primary `indigo-600` | Success `green-600` | Warning `yellow-500` | Danger `red-500`
- Cards: `rounded-xl shadow-sm` with `px-6 py-6`
- Status badges: green = paid/verified, yellow = lacking, red = rejected/disproved, gray = pending
- Money: `&#8369;` + `number_format($val, 2)` (Philippine Peso)
- Forms: `@error('field')` validation display + JS loading state on submit
- Dark mode via `dark:` Tailwind prefix; theme persisted in `localStorage`, applied before paint to avoid flash

`resources/views/layouts/layout.blade.php` is the master tenant layout (CDN Tailwind, dark toggle). `app/View/Components/AppLayout.php` + `GuestLayout.php` are Breeze's auth layouts for `/profile`, `/login`, `/register`.

## Environment

Default uses SQLite (`database/database.sqlite`). Docker Compose spins up MySQL 8.0 on 3306 alongside the app (port 80) and Vite (port 5173). Mail is Mailtrap-configured in `.env`; tests force `MAIL_MAILER=array`.

## Email verification

- `User` implements `MustVerifyEmail`. New users are emailed a signed verification link on `Registered`.
- `'verified'` middleware (`EnsureEmailIsVerified`) is applied to all authenticated routes that touch org data: `/dashboard`, `/profile/*`, `/onboarding/*`, `/{org}/...` tenant group, and `/invitations/{invitation}/{accept,decline}`.
- The middleware allowlists `verify-email`, `email/verification-notification`, and `logout` so the verify/resend/logout loop stays usable for unverified users.
- `SendEmailVerificationNotification` listener (`app/Listeners/`) is bound to `Registered` in `EventServiceProvider`. It short-circuits if the user is already verified.

## SaaS billing & webhooks

- PayMongo checkout flow is in `BillingController` + `BillingService`. Webhook at `POST /billing/webhook` is **CSRF-exempt** (in `bootstrap/app.php`) and HMAC-verified by `VerifyPayMongoSignature` middleware using `PAYMONGO_WEBHOOK_SECRET`. Webhook must be the **raw** body — never parse before verification.
- 3-tier plan catalog is seeded by `DatabaseSeeder`. Default trial plan is Community (14 days, no card). `EnsurePlanAllowsHousehold` enforces `plan.max_households` on household create/import routes.
- **Trial expiry**: `php artisan trials:expire` runs daily at 00:05 (`routes/console.php`). Flips overdue orgs to `past_due`, sends warnings at T-7 and T-1 days. Idempotent — tracked via `trial_warning_sent_at` JSON column. `EnsureOrganizationActive` middleware blocks writes on `past_due`/`canceled`/`unpaid` orgs except billing + logout. Wire a real scheduler: Railway Cron / Render Cron / `scheduler:` Procfile process / system cron on `* * * * * php artisan schedule:run`.
- Settings tabs: General (admin), Members (admin), Billing (admin). Members + Billing are wired; expand them rather than creating new settings pages.

## Member management

- `OrganizationRole` enum (`Admin | Treasurer | Viewer`) drives all permission checks. Don't read `pivot.role` directly outside of `Organization::roleFor()` — go through the enum.
- Invitations auto-generate a 48-char token and 14-day expiry (`OrganizationInvitation::booted`). Public accept URLs are signed via `URL::signedRoute`; the controller re-checks that the logged-in user's email matches the invitee.
- Last-admin protection lives in `InvitationService::removeMember` and `changeRole`. Don't bypass it.
- Every invitation/membership mutation writes to `AuditLog` (action keys: `organization.member.{invited,invitation_revoked,invitation_resent,joined,removed,role_changed}`).

## Observability

- Errors and traces ship to Sentry via `sentry/sentry-laravel` (see `docs/OBSERVABILITY.md`). The integration is opt-in — set `SENTRY_LARAVEL_DSN` and you're done.
- `App\Support\SentryPiiScrubber` strips passwords, tokens, PayMongo/AWS secrets, and card-shaped numbers from every event before it leaves the app. Registered via `App\Providers\SentryServiceProvider`.
- Set `SENTRY_RELEASE` to your deploy SHA so errors group by release.
