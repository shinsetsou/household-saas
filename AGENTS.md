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

## Architecture

Laravel 11 + Blade + Tailwind (CDN, not compiled) + Alpine + Axios. Vite only builds `resources/css/app.css` and `resources/js/app.js`. Auth scaffolded with Laravel Breeze (Blade stack).

### Multi-tenancy

All tenant routes live under `/{organization:slug}` via route model binding (`Organization::getRouteKeyName()` returns `slug`, populated by `cviebrock/eloquent-sluggable`). Membership is a many-to-many through the `organization_user` pivot with a `role` column. `EnsureOrganizationMember` middleware (`app/Http/Middleware/EnsureOrganizationMember.php`) checks `User::belongsToOrganization()` and 403s otherwise — apply it together with `auth` on any tenant-write route.

Public tenant routes (no auth): `home` (organization show), `households.index` (rate-limited `throttle:10,1` — block+lot lookup for residents), `contributions.create`, `contributions.store`. Everything else requires `auth + EnsureOrganizationMember`.

### Data Model

```
Organization (slug, monthly_due_amount)
  ├── belongsToMany User    (organization_user pivot: role)
  ├── hasMany Household     (unique on organization_id, block, lot)
  │     └── hasMany Contribution
  └── hasMany Expense

AuditLog (polymorphic auditable, JSON metadata)
```

Cross-tenant query safety: prefer `forOrganization($org)` scopes on `Contribution` and `Household` rather than re-deriving `whereHas`. `Contribution::scopeForOrganization` walks through `household` so contributions can be filtered by org without an explicit `organization_id` column on the table.

### Contribution Verification

`contributions.verification_status` is the source of truth: `pending` | `verified` | `rejected` (string enum, indexed). The legacy `admin_verified` tinyInt column still exists for backwards compat but is backfilled by `2026_05_24_000001_add_mvp_multitenancy_columns.php` — new code should read/write `verification_status`, `rejection_reason`, `verified_by`, `verified_at`. The UUID `reference_number` is indexed and used in the verify endpoint.

### Event Flow

`ContributionController::store` → fires `ContributionCreated` → `SendContributionAddedEmail` listener → `ContributionAdded` Mailable. Bound in `app/Providers/EventServiceProvider.php`. Mail defaults to Mailtrap; tests use `array` driver.

### Services Layer

Non-trivial domain logic sits in `app/Services/`:
- `DelinquencyService::forOrganization($org, $year, $month)` — returns rows of households below `monthly_due_amount` of verified `paid` contributions for the month, or with any `lacking` contribution. Used by `ReportController::delinquency`.
- `HouseholdCsvImporter` — validates a CSV (`name,block,lot` header), checks duplicates against existing rows + within the file, returns `HouseholdCsvImportResult` (count + errors). Wrapped in a DB transaction.

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
