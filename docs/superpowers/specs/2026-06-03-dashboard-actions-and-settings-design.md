# Dashboard Actions, User Menu & Org Settings — Design

**Date:** 2026-06-03
**Status:** Approved (pending user spec review)
**Scope:** Three connected gaps in the tenant experience that all touch the dashboard / nav surface and share authorization plumbing.

---

## 1. Problem

After the registration/onboarding wizard ships, admin users land on the tenant dashboard (`/{org}/dashboard`) ready to work. Three frequent admin actions are either missing or hidden:

1. **Adding households post-onboarding.** The wizard's CSV import step is the only surfaced path to add households. The existing single-household form (`households.create`) and CSV importer (`households.import.create`) exist but are not linked from the dashboard. Admins who skip the wizard's CSV step have no in-app path back to it.
2. **Signing out.** The shared tenant layout (`resources/views/layouts/layout.blade.php`) has no logout control. Currently a user can only sign out by typing `/logout` in the URL bar.
3. **Editing organization details.** There is no UI to change the org name or the monthly due amount after creation. Admins who set the wrong due during onboarding cannot correct it without a database edit.

## 2. Goals

- Dashboard header surfaces **two visible, colored CTAs** for adding households: one for the single-household form, one for the CSV importer.
- Tenant nav gains an **avatar dropdown** containing: user name + email, Profile link, Organization Settings link (admin-only), Sign out (POST).
- New `/{org:slug}/settings` page with a **tabbed layout**. General tab is functional (edits name + monthly_due_amount). Members and Billing tabs are visually present but disabled with a "Coming soon" label.
- All settings actions are **admin-only**, enforced by middleware AND hidden in the UI for non-admins.
- Audit log captures every settings change with a `changes` diff in metadata.

## 3. Non-Goals

- No new tables or columns. Reuses the existing `organization_user.role` pivot.
- No invite / member-management flow. The Members tab is a disabled placeholder.
- No billing integration. The Billing tab is a disabled placeholder.
- No optimistic-locking on settings updates (last-write-wins is acceptable in v1).
- No slug regeneration when org name changes. Existing tenant URLs stay stable.
- No team-switcher in the nav (users still navigate between orgs via `/dashboard` redirect logic).

## 4. User Flow

```
[/{org}/dashboard]
   ↓ header buttons (always visible to org members)
   ├─ "+ Add Household" → /{org}/households/create        (existing route)
   └─ "↗ Import CSV"    → /{org}/households/import        (existing route)

[Top nav, right side]
   [Org badge] [🌙 dark toggle] [👤 avatar ▾]
                                  ↓ click
                                  ┌──────────────────────────┐
                                  │ Name / email             │
                                  │ ─────────────────────── │
                                  │ Profile   → /profile     │
                                  │ Settings  → /{org}/settings  (admin only)
                                  │ ─────────────────────── │
                                  │ Sign out  → POST /logout │
                                  └──────────────────────────┘

[/{org}/settings] (admin only)
   ┌──────────────────────────────────────────────┐
   │ [General] [Members · soon] [Billing · soon]  │
   │ ───────────────────────────────────────────  │
   │ Organization name: [Sunshine HOA          ]  │
   │ Monthly due (₱):   [200.00                ]  │
   │                                              │
   │ [Save changes]                               │
   └──────────────────────────────────────────────┘
   ↓ PATCH /{org}/settings/general
   ↓ → audit log entry → flash success → stay on page
```

## 5. Components

### 5.1 Modified

- `resources/views/organizations/index.blade.php`
  - In the page-header right side actions block, add two new buttons **before** `Add Expense`:
    - `<a href="{{ route('households.create', $organization->slug) }}" class="btn-add-household">+ Add Household</a>` — indigo background.
    - `<a href="{{ route('households.import.create', $organization->slug) }}" class="btn-import-csv">↗ Import CSV</a>` — emerald background.
  - Add two new class definitions in the existing `<style>` block:
    - `.btn-add-household` → `bg-indigo-600 text-white hover:bg-indigo-700 ...` (same shape as existing `.btn-primary`).
    - `.btn-import-csv` → `bg-emerald-600 text-white hover:bg-emerald-700 ...`.

- `resources/views/layouts/layout.blade.php`
  - In the right-side actions block (around current lines 116–151), insert a user-avatar dropdown component between the dark-mode toggle and the mobile-menu button. The order becomes: `[Org badge] [Dark-mode toggle] [User dropdown] [Mobile menu button]`.
  - Add a `@push('scripts')`-style inline Alpine init or attach `x-data="{ open: false }"` directly on the dropdown wrapper. (Alpine is already loaded via `resources/js/app.js`.)
  - In the mobile-menu drawer, append two new rows at the bottom: `Profile` and a logout form button. This redundancy avoids forcing mobile users to find the small avatar.

- `routes/web.php`
  - Inside the existing `Route::middleware(['auth', EnsureOrganizationMember::class])->group(...)` for the tenant prefix, append:
    ```php
    Route::middleware(EnsureOrganizationAdmin::class)->group(function () {
        Route::get('settings', [OrganizationSettingsController::class, 'show'])->name('organizations.settings.show');
        Route::patch('settings/general', [OrganizationSettingsController::class, 'updateGeneral'])->name('organizations.settings.update-general');
    });
    ```
  - Add `use App\Http\Controllers\OrganizationSettingsController;` and `use App\Http\Middleware\EnsureOrganizationAdmin;` at the top alongside existing imports.

### 5.2 New

- `app/Http/Controllers/OrganizationSettingsController.php`
  - `show(Organization $organization)` → `return view('organizations.settings.general', compact('organization'));`
  - `updateGeneral(UpdateGeneralSettingsRequest $request, Organization $organization)`:
    1. `$before = $organization->only(['name', 'monthly_due_amount']);`
    2. `$organization->fill($request->validated())->save();`
    3. `$after = $organization->only(['name', 'monthly_due_amount']);`
    4. `$diff = array_diff_assoc($after, $before);` (coerced via string-compare since `monthly_due_amount` is `decimal:2`)
    5. If `$diff` is empty → flash info "No changes to save." and `redirect()->route('organizations.settings.show', $organization->slug)` without audit-log write.
    6. Otherwise → write `AuditLog::record($organization, 'organization.settings.updated', $request->user(), $organization, ['changes' => $changesArray])` where `$changesArray` is keyed by field name with `[before, after]` tuples.
    7. Flash `success` "Organization settings saved." and `redirect()->route('organizations.settings.show', $organization->slug)`. (Using a named-route redirect instead of `back()` keeps the destination deterministic even if the form was posted from an unexpected referer — e.g., a re-submit after a token refresh.)

- `app/Http/Requests/UpdateGeneralSettingsRequest.php`
  - `authorize()` → `true` (middleware gate is canonical).
  - `rules()` → `['name' => ['required', 'string', 'max:255'], 'monthly_due_amount' => ['required', 'numeric', 'min:0']]`.

- `app/Http/Middleware/EnsureOrganizationAdmin.php`
  - Logic:
    ```php
    $org = $request->route('organization');
    $user = $request->user();
    if (! $user || ! $org instanceof Organization) abort(403);
    $isAdmin = $user->organizations()
        ->where('organizations.id', $org->id)
        ->wherePivot('role', 'admin')
        ->exists();
    if (! $isAdmin) abort(403);
    return $next($request);
    ```
  - Modeled after `EnsureOrganizationMember` for consistency.

- `resources/views/organizations/settings/layout.blade.php`
  - Reuses tenant `@extends('layouts.layout')`. Renders breadcrumb (Dashboard → Settings) + page header + tab bar.
  - Tab bar shows three pills in this order: **General** (active link), **Members** (disabled grey pill with `title="Coming soon"`), **Billing** (disabled grey pill with `title="Coming soon"`).
  - Has a `@yield('tab-content')` slot for tab-specific content.

- `resources/views/organizations/settings/general.blade.php`
  - `@extends('organizations.settings.layout')` with `@section('tab-content')` containing:
    - Card with form:
      - `name` text input (required, current value prefilled, `@error` block).
      - `monthly_due_amount` number input with `step="0.01"` and `min="0"`, peso-symbol prefix (`₱`).
      - Submit button "Save changes" (indigo primary).
    - Form posts to `route('organizations.settings.update-general', $organization->slug)` via PATCH (with `@method('PATCH')`).

- `app/View/Components/UserDropdown.php` (optional — could be inline in the layout if simpler)
  - If extracted: receives the current `$organization` (nullable) and renders the avatar + dropdown markup.
  - Decision: **render inline in `layouts/layout.blade.php`** for v1. Extraction can happen later if a second template needs the same dropdown. YAGNI.

### 5.3 Audit Log

`organization.settings.updated` action, metadata shape:

```json
{
  "changes": {
    "name": ["Sunshine HOA", "Sunshine Village HOA"],
    "monthly_due_amount": ["200.00", "250.00"]
  }
}
```

Only fields that actually changed appear in the `changes` map. Unchanged fields are omitted. Empty diff → no audit row written.

## 6. Authorization

| Action | Required | Enforced by |
|---|---|---|
| View `/{org}/dashboard` and household add buttons | Org member | Existing `auth + EnsureOrganizationMember` |
| Click "Add Household" / "Import CSV" | Org member | Same — existing routes already protected |
| Click "Profile" in dropdown | Authenticated | Existing `auth` middleware on `profile.edit` route |
| Click "Sign out" in dropdown | Authenticated | Existing `auth` middleware on `logout` route |
| See "Settings" link in dropdown | Org admin | Blade `@if` check + the underlying route is gated too |
| GET `/{org}/settings` | Org admin | `EnsureOrganizationAdmin` middleware |
| PATCH `/{org}/settings/general` | Org admin | Same middleware |

## 7. Edge Cases

| Case | Behavior |
|---|---|
| Non-admin member visits `/{org}/settings` directly | 403 from `EnsureOrganizationAdmin`. |
| Non-admin sees user dropdown | Settings link hidden; Profile + Sign out still visible. |
| User not in current org's pivot at all visits `/{org}/settings` | 403 — `EnsureOrganizationMember` fires first. |
| User in 0 orgs sees the layout (e.g., somehow on `/profile`) | `$navOrg` is null. Settings link hidden. Nav links hidden (already conditional). Dropdown still shows Profile + Sign out. |
| Submit settings form with identical values | Form validates, diff is empty, no audit log written, flash info "No changes to save." |
| Submit settings with empty name | Validation fails on `name`, form rerenders with `@error('name')` text. |
| Submit settings with `monthly_due_amount = -1` | Validation fails on `monthly_due_amount`. |
| Two admins update settings simultaneously | Last write wins. No optimistic lock. (Documented YAGNI; revisit when complaint surfaces.) |
| Org renamed → slug | Slug stays unchanged. `cviebrock/eloquent-sluggable` default does NOT regenerate slugs on update. Tenant URLs (`/sunshine-hoa/*`) keep working after the org becomes "Sunshine Village HOA". |
| Mobile user without dropdown JS | The avatar button is still tappable; the mobile-menu drawer also includes Profile + Sign out rows for redundancy. |
| User dropdown rendered when `auth()->user()` is null | Layout already gates nav block behind `@auth`; user dropdown is rendered inside that gate. |

## 8. Tests

`tests/Feature/OrganizationSettingsTest.php`:

1. `test_admin_can_view_settings_page` — admin GET returns 200, sees "General" tab as active, "Members" + "Billing" rendered as disabled.
2. `test_non_admin_member_gets_403` — user attached with `role='member'` gets 403 on GET.
3. `test_non_member_gets_403` — user not in pivot at all gets 403 (chained `EnsureOrganizationMember` denies first).
4. `test_admin_can_update_name_and_due` — PATCH with new values updates the DB row + writes one audit log with `action='organization.settings.updated'` and the expected `changes` metadata.
5. `test_update_validates_name_required` — empty name → session has error on `name`, no DB change.
6. `test_update_validates_monthly_due_non_negative` — `monthly_due_amount = -1` → session has error on `monthly_due_amount`.
7. `test_no_op_update_skips_audit_log` — PATCH with values matching current → zero new `audit_logs` rows.
8. `test_slug_unchanged_after_rename` — admin renames org, asserts `$org->fresh()->slug` equals the original slug.

`tests/Feature/UserDropdownTest.php`:

1. `test_dropdown_shows_name_and_signout_for_authenticated_user_on_dashboard` — `assertSee` user name + "Sign out".
2. `test_settings_link_visible_to_admin` — admin sees a link to `organizations.settings.show`.
3. `test_settings_link_hidden_for_non_admin` — non-admin member does NOT see the Settings link; still sees Profile + Sign out.
4. `test_logout_works_from_dropdown` — POST to logout, assert session becomes guest.

`tests/Feature/DashboardActionsTest.php`:

1. `test_dashboard_shows_add_household_button` — admin GET `/{org}/dashboard` shows "Add Household" with the correct href.
2. `test_dashboard_shows_import_csv_button` — admin GET shows "Import CSV" with the correct href.

Total: **14 new feature tests**. Use existing SQLite `:memory:` config; no setup changes needed.

## 9. Out of Scope (Follow-ups)

- Member management UI (the Members tab) — invite flow, role changes, removal.
- Billing UI — payment method, plan history.
- Optimistic locking on settings updates.
- Slug regeneration on rename + redirect from old slug.
- Avatar uploads (initials only for v1).
- Multi-org switcher in the user dropdown.
- Profile page (existing Breeze route is used as-is; styling cleanup is a separate ticket).
