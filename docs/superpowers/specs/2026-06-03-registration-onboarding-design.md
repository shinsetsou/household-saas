# Registration & Onboarding Refinement — Design

**Date:** 2026-06-03
**Status:** Approved (pending user spec review)
**Scope:** Refine user registration form, introduce a 3-step onboarding wizard, and align auth/onboarding visuals with the BarangayBooks landing-page brand.

---

## 1. Problem

The landing page (`resources/views/welcome.blade.php`) promises Philippine HOA boards a "5-minute path from signup to first peso collected." The current flow does not deliver on that promise:

1. **Register form is heavier than it needs to be.** Four fields (name, email, password, confirm password) with no show/hide affordance.
2. **Post-register dead-end.** `RegisteredUserController::store` redirects to `/dashboard`, which renders the Breeze placeholder `"You're logged in!"`. A new user has zero organizations, no tenant context, and no link toward creating one. The "5 minutes" promise dies here.
3. **Brand inconsistency.** Auth screens use the default Breeze `<x-guest-layout>` (Laravel application logo). The landing page already uses a distinctive BarangayBooks mark (`bg-gradient-to-br from-brand-500 to-brand-700` rounded "B" tile + "Barangay**Books**" wordmark). New users see one brand on the landing page and a different one the moment they click "Start free trial."

## 2. Goals

- New user reaches a usable tenant dashboard in **at most three guided steps** after registering.
- Registration form has **three input fields** (name, email, password) with a single eye toggle for password visibility.
- BarangayBooks brand mark and wordmark appear on every auth and onboarding screen, identical to the landing page treatment.
- Zero-state for the existing `/dashboard` route resolves itself: existing users land on their org; brand-new users land in the wizard.

## 3. Non-Goals

- No new database tables or columns. Onboarding progress is derived from existence checks (`user->organizations()->exists()`, `org->households()->exists()`), not stored as a `users.onboarding_step` enum.
- No replacement of the landing-page logo. The gradient "B" tile + wordmark stays.
- No social login, no email verification step changes, no password-reset flow changes.
- No changes to multi-organization invitation flow — that is downstream of this work.

## 4. User Flow

```
[Landing /]
   ↓ "Start free trial"
[/register]                                 Name, Email, Password (eye toggle), Submit
   ↓ POST → user created, Auth::login()
[/onboarding/organization]                  Wizard 1/3 — Org name + monthly due
   ↓ POST → OnboardingService creates Organization, attaches user as 'admin'
[/onboarding/households]                    Wizard 2/3 — CSV upload OR "Skip for now"
   ↓ POST (csv) | GET (skip)
[/onboarding/done]                          Wizard 3/3 — Success card, "Go to dashboard"
   ↓
[/{organization:slug}/dashboard]            Existing OrganizationController@index
```

**Re-entry rules** (applied to the existing `/dashboard` route):

| User state                         | `/dashboard` resolves to                          |
|------------------------------------|---------------------------------------------------|
| Zero organizations                 | `route('onboarding.organization.create')`         |
| One or more organizations          | Most-recently-joined org's tenant dashboard       |

A logged-in user already in `≥ 1` org who hits `/onboarding/*` is bounced to their tenant dashboard by `RedirectIfOnboarded` middleware.

## 5. Components

### 5.1 Modified

- `app/Http/Controllers/Auth/RegisteredUserController.php`
  - Drop the `'confirmed'` rule from the `password` validation array.
  - After `Auth::login($user)` → `redirect()->route('onboarding.organization.create')` instead of `route('dashboard')`.
- `routes/web.php`
  - Add `Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(...)` containing `organization.create|store`, `households.create|store|skip`, `done.show`. Only the `households.*` and `done.show` routes pass through `RedirectIfOnboarded`; `organization.create|store` stays open so multi-org users can re-use step 1.
  - Wrap the existing `/dashboard` GET in a controller method (new `DashboardRedirectController@__invoke`) that performs the re-entry rules from §4.
- `resources/views/auth/register.blade.php`
  - Remove the `password_confirmation` block.
  - Wrap the password input in Alpine `x-data="{ show: false }"` and add a button toggling `:type="show ? 'text' : 'password'"`.
  - Replace the Breeze guest-layout chrome with the BarangayBooks header (gradient "B" tile + wordmark) above the card.
- `resources/views/auth/login.blade.php`
  - Same brand-header swap as register. No field changes.

### 5.2 New

- `app/Http/Controllers/Onboarding/OrganizationStepController.php`
  - `create()` → `view('onboarding.organization')`.
  - `store(OnboardingOrganizationRequest $request, OnboardingService $service)` → creates org, stores `session(['onboarding.organization_id' => $org->id])`, redirects to step 2.
- `app/Http/Controllers/Onboarding/HouseholdsStepController.php`
  - `create()` → `view('onboarding.households', ['organization' => $resolveFromSession()])`.
  - `store(Request $request, HouseholdCsvImporter $importer)` → runs importer inside an existing transaction, on success redirects to step 3, on failure re-renders with `HouseholdCsvImportResult.errors`.
  - `skip()` → redirect to step 3 with no households created.
- `app/Http/Controllers/Onboarding/DoneStepController.php`
  - `show()` → resolves the org from session (or last-joined org if session expired), renders `view('onboarding.done', compact('organization'))`.
- `app/Http/Controllers/DashboardRedirectController.php`
  - `__invoke()` — applies §4 re-entry rules.
- `app/Http/Middleware/RedirectIfOnboarded.php`
  - Applied to onboarding steps 2 and 3 only. Redirects to the user's tenant dashboard when the user has `≥ 1` organization AND `session('onboarding.organization_id')` is **not** set. This lets a user mid-wizard finish their flow (session key is set by step 1), while bouncing an already-onboarded user who navigates directly to `/onboarding/households` or `/onboarding/done` without going through step 1 first. Step 1 is not wrapped by this middleware, so multi-org users can re-use it to create additional organizations.
- `app/Http/Requests/OnboardingOrganizationRequest.php`
  - Validates `name` (required, string, max:255) and `monthly_due_amount` (required, numeric, min:0).
- `app/Services/OnboardingService.php`
  - `createOrganizationForUser(User $user, array $data): Organization` — DB transaction:
    1. Insert `Organization` (slug auto-generated by Sluggable).
    2. Attach `$user` to org with `role = 'admin'` on the pivot.
    3. Write `AuditLog::record($org, 'organization.created', $user, $org, ['via' => 'onboarding'])`.

### 5.3 New Views

`resources/views/onboarding/`:
- `layout.blade.php` — shared wizard chrome. Includes the BarangayBooks header (matches landing logo), a three-segment progress indicator, and a centered card slot. Tailwind CDN, dark-mode toggle to match `resources/views/layouts/layout.blade.php`.
- `organization.blade.php` — step 1 form (`name`, `monthly_due_amount`). Help text under monthly_due hints "You can change this later in settings." Submit shows loading state.
- `households.blade.php` — step 2. Drop-zone styled input, "Download sample CSV" button linking to `/samples/households-sample.csv`, an inline 3-row preview of the expected format (`name | block | lot` header + Cruz Family / 7 / 12 / Reyes Family / 3 / 4 etc.), and a prominent `Skip for now →` link beside the primary "Upload and continue" button. CSV errors render as a red list above the drop-zone on validation failure.
- `done.blade.php` — step 3. Success checkmark, recap card listing org name, household count, and a single primary CTA "Go to your dashboard" linking to `route('organizations.index', $organization)`.

### 5.4 Public Asset

- `public/samples/households-sample.csv` — content:
  ```csv
  name,block,lot
  Cruz Family,7,12
  Reyes Family,3,4
  Santos Family,9,21
  ```

## 6. Authorization & Data Integrity

- All `onboarding.*` routes require `auth` middleware. Steps 2 and 3 additionally require a session-bound organization id (`session('onboarding.organization_id')`); missing key → redirect to step 1.
- `OrganizationPolicy::create` returns `true` for any authenticated user (multi-org access is a stated landing-page feature). No further restriction added.
- Existing `EnsureOrganizationMember` is unchanged; it does not apply to onboarding routes because the user is creating the org they will then belong to.
- `OnboardingService::createOrganizationForUser` is wrapped in `DB::transaction(...)` so a failed pivot attach rolls back the org insert. The audit-log write is inside the transaction.

## 7. Edge Cases

| Case                                                               | Behavior                                                                       |
|--------------------------------------------------------------------|--------------------------------------------------------------------------------|
| User refreshes mid-wizard                                          | Session key persists; the page they reload picks them up where they were.      |
| User logs out mid-wizard, then logs back in                        | `/dashboard` redirect rule routes them to step 1 (zero orgs).                  |
| User submits invalid CSV at step 2                                 | Re-renders step 2 with errors; no households created; skip link still works.    |
| User with one org hits `/onboarding/households` or `/onboarding/done` | `RedirectIfOnboarded` middleware bounces to tenant dashboard.                  |
| Two tabs both submit step 1                                        | Second one succeeds (no uniqueness on org name); user lands on whichever finished last. Acceptable for v1. |
| Session expires between steps 1 and 2                              | Step 2 controller falls back to user's most recent org; if zero, redirects to step 1. |

## 8. Branding

The BarangayBooks header (a Blade partial at `resources/views/components/brand-header.blade.php`) renders:

```blade
<a href="/" class="inline-flex items-center gap-2">
    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white font-bold shadow-sm shadow-brand-900/20">B</span>
    <span class="font-bold text-lg tracking-tight">Barangay<span class="text-brand-600 dark:text-brand-500">Books</span></span>
</a>
```

This is the exact treatment used in `welcome.blade.php` lines 86–88. Auth pages (`login`, `register`, `forgot-password`, `reset-password`, `verify-email`, `confirm-password`) and all onboarding views render this component at the top of the centered card.

The Tailwind `brand` color palette is defined inline in `welcome.blade.php`. For consistent reuse on auth/onboarding pages (which currently use compiled Vite + Tailwind CLI defaults, not the landing's CDN config), the wizard layout will redeclare the same palette via the same inline `tailwind.config = { ... }` script as the landing page. This avoids a Tailwind compile-pipeline migration in this scope.

## 9. Tests

`tests/Feature/OnboardingTest.php`:

1. Fresh register POSTs land on `route('onboarding.organization.create')`.
2. Step 1 store creates an `Organization`, attaches the user with `role = 'admin'` on the pivot, writes one `audit_logs` row with `action = 'organization.created'`, and redirects to step 2.
3. Step 2 with a valid CSV imports the expected households and redirects to step 3.
4. Step 2 skip redirects to step 3 and leaves `households` table empty for that org.
5. Step 3 "Go to dashboard" link resolves to `route('organizations.index', $organization)`.
6. An onboarded user (≥ 1 org) without an active onboarding session hitting `/onboarding/households` or `/onboarding/done` is redirected to their tenant dashboard. A multi-org user actively mid-wizard (session key set by step 1) is allowed to proceed.
7. `/dashboard` GET with zero orgs redirects to onboarding step 1.
8. `/dashboard` GET with one org redirects to that org's tenant dashboard.
9. Step 2 with an invalid CSV (missing header, duplicate block+lot, etc.) re-renders the view with errors and creates zero households.
10. Register form rejects submissions where `password` is shorter than `Rules\Password::defaults()` minimum length.

Tests use the existing `phpunit.xml` configuration (SQLite `:memory:`, `array` mail, `sync` queue) — no setup changes required.

## 10. Out of Scope (Follow-ups)

- A dedicated "create another organization" page distinct from the wizard step 1 (for users who already have one org).
- An invite-team-member step in the wizard (would require email + tokenized join flow).
- Replacing the inline Tailwind CDN setup on auth/onboarding pages with the compiled Vite pipeline.
- Replacing the "B" gradient mark with a custom SVG.
