# Dashboard Actions, User Menu & Org Settings Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Surface two visible Add-Household CTAs on the tenant dashboard, add a user-avatar dropdown (Profile / Settings / Sign out) to the tenant nav, and introduce an admin-only tabbed Org Settings page that edits org name + monthly_due_amount.

**Architecture:** New `EnsureOrganizationAdmin` middleware gates admin-only routes by checking the existing `organization_user.role` pivot column. A new `OrganizationSettingsController` exposes `show` + `updateGeneral` under the tenant prefix. A `UpdateGeneralSettingsRequest` validates input; the controller computes a `before → after` diff for the audit log and skips logging on no-ops. The user dropdown is rendered inline in `resources/views/layouts/layout.blade.php` using Alpine (already loaded via `resources/js/app.js`).

**Tech Stack:** Laravel 11, Blade, Tailwind CDN, Alpine.js, PHPUnit (SQLite `:memory:`), Pint.

**Spec:** `docs/superpowers/specs/2026-06-03-dashboard-actions-and-settings-design.md`

---

## File Manifest

**New files:**
- `app/Http/Controllers/OrganizationSettingsController.php`
- `app/Http/Middleware/EnsureOrganizationAdmin.php`
- `app/Http/Requests/UpdateGeneralSettingsRequest.php`
- `resources/views/organizations/settings/layout.blade.php`
- `resources/views/organizations/settings/general.blade.php`
- `tests/Feature/DashboardActionsTest.php`
- `tests/Feature/EnsureOrganizationAdminMiddlewareTest.php`
- `tests/Feature/OrganizationSettingsTest.php`
- `tests/Feature/UserDropdownTest.php`

**Modified files:**
- `resources/views/organizations/index.blade.php` (dashboard header gets 2 new buttons)
- `resources/views/layouts/layout.blade.php` (user dropdown + optional `info` flash type + 2 mobile menu rows)
- `routes/web.php` (settings routes inside admin sub-group)

---

## Task 1: Dashboard "Add Household" + "Import CSV" buttons

**Files:**
- Create: `tests/Feature/DashboardActionsTest.php`
- Modify: `resources/views/organizations/index.blade.php` (page-header right-side actions block + `<style>` block)

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/DashboardActionsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_add_household_button(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        $response->assertSee('Add Household', false);
        $response->assertSee(route('households.create', $org->slug), false);
    }

    public function test_dashboard_shows_import_csv_button(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        $response->assertSee('Import CSV', false);
        $response->assertSee(route('households.import.create', $org->slug), false);
    }
}
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --filter=DashboardActionsTest`
Expected: FAIL for both tests — strings "Add Household" and "Import CSV" do not appear on the dashboard.

- [ ] **Step 3: Add the two buttons to the dashboard header**

Edit `resources/views/organizations/index.blade.php`. Locate the right-side actions block in the page header (currently contains `Add Expense` and `Verify Contributions` buttons). Insert two new buttons BEFORE the existing `Add Expense` link:

```blade
<a href="{{ route('households.create', $organization->slug) }}"
   class="btn-add-household">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
    </svg>
    Add Household
</a>
<a href="{{ route('households.import.create', $organization->slug) }}"
   class="btn-import-csv">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4zm0 7a2 2 0 100 4h12a2 2 0 100-4H4zm0 7a2 2 0 100 4h12a2 2 0 100-4H4z" clip-rule="evenodd"/>
    </svg>
    Import CSV
</a>
```

- [ ] **Step 4: Add the two style class definitions**

In the same file, edit the existing `<style>` block (currently has `.stat-card`, `.btn-primary`, `.btn-secondary`). Append two new rules:

```blade
.btn-add-household {
    @apply inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 active:bg-indigo-800 transition-colors shadow-sm;
}
.btn-import-csv {
    @apply inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 active:bg-emerald-800 transition-colors shadow-sm;
}
```

- [ ] **Step 5: Run — expect pass**

Run: `php artisan test --filter=DashboardActionsTest`
Expected: PASS (both tests).

Run the full suite to confirm no regressions:

Run: `php artisan test`
Expected: all PASS.

- [ ] **Step 6: Commit**

```bash
git add resources/views/organizations/index.blade.php tests/Feature/DashboardActionsTest.php
git commit -m "feat(dashboard): add household + import CSV buttons in header"
```

---

## Task 2: `EnsureOrganizationAdmin` middleware

**Files:**
- Create: `app/Http/Middleware/EnsureOrganizationAdmin.php`
- Create: `tests/Feature/EnsureOrganizationAdminMiddlewareTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/EnsureOrganizationAdminMiddlewareTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureOrganizationAdmin;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureOrganizationAdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', EnsureOrganizationAdmin::class])
            ->get('/_test/{organization:slug}/admin-only', fn () => 'ok');
    }

    public function test_admin_passes(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get("/_test/{$org->slug}/admin-only");

        $response->assertOk();
        $response->assertSee('ok');
    }

    public function test_non_admin_member_gets_403(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'member']);

        $response = $this->actingAs($user)->get("/_test/{$org->slug}/admin-only");

        $response->assertForbidden();
    }

    public function test_non_member_gets_403(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        // user not attached

        $response = $this->actingAs($user)->get("/_test/{$org->slug}/admin-only");

        $response->assertForbidden();
    }

    public function test_unauthenticated_gets_redirected_to_login(): void
    {
        $org = Organization::factory()->create();

        $response = $this->get("/_test/{$org->slug}/admin-only");

        $response->assertRedirect(route('login'));
    }
}
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --filter=EnsureOrganizationAdminMiddlewareTest`
Expected: FAIL — class `App\Http\Middleware\EnsureOrganizationAdmin` does not exist.

- [ ] **Step 3: Create the middleware**

Create `app/Http/Middleware/EnsureOrganizationAdmin.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->route('organization');
        $user = $request->user();

        if (! $organization instanceof Organization || ! $user) {
            abort(403);
        }

        $isAdmin = $user->organizations()
            ->where('organizations.id', $organization->id)
            ->wherePivot('role', 'admin')
            ->exists();

        if (! $isAdmin) {
            abort(403);
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Run — expect pass**

Run: `php artisan test --filter=EnsureOrganizationAdminMiddlewareTest`
Expected: PASS (all four tests).

- [ ] **Step 5: Lint**

Run: `./vendor/bin/pint app/Http/Middleware/EnsureOrganizationAdmin.php tests/Feature/EnsureOrganizationAdminMiddlewareTest.php`

- [ ] **Step 6: Commit**

```bash
git add app/Http/Middleware/EnsureOrganizationAdmin.php tests/Feature/EnsureOrganizationAdminMiddlewareTest.php
git commit -m "feat(auth): EnsureOrganizationAdmin middleware via pivot role"
```

---

## Task 3: Org Settings page shell (GET only — show)

**Files:**
- Create: `app/Http/Controllers/OrganizationSettingsController.php`
- Create: `resources/views/organizations/settings/layout.blade.php`
- Create: `resources/views/organizations/settings/general.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/OrganizationSettingsTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/OrganizationSettingsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_settings_page(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.settings.show', $org));

        $response->assertOk();
        $response->assertSee('Organization settings', false);
        $response->assertSee('General', false);
        $response->assertSee('Members', false);
        $response->assertSee('Billing', false);
        $response->assertSee('Coming soon', false);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="monthly_due_amount"', false);
    }

    public function test_non_admin_member_gets_403(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'member']);

        $response = $this->actingAs($user)->get(route('organizations.settings.show', $org));

        $response->assertForbidden();
    }

    public function test_non_member_gets_403(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();

        $response = $this->actingAs($user)->get(route('organizations.settings.show', $org));

        $response->assertForbidden();
    }
}
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --filter=OrganizationSettingsTest`
Expected: FAIL — route `organizations.settings.show` does not exist.

- [ ] **Step 3: Create the controller (show method only for this task)**

Create `app/Http/Controllers/OrganizationSettingsController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\View\View;

class OrganizationSettingsController extends Controller
{
    public function show(Organization $organization): View
    {
        return view('organizations.settings.general', compact('organization'));
    }
}
```

- [ ] **Step 4: Create the settings layout view (tabs)**

Create `resources/views/organizations/settings/layout.blade.php`:

```blade
@extends('layouts.layout')

@section('title', 'Organization settings — ' . $organization->name)

@section('content')

{{-- Breadcrumb --}}
<nav class="text-sm text-gray-500 mb-3">
    <a href="{{ route('organizations.index', $organization->slug) }}" class="hover:text-gray-700">Dashboard</a>
    <span class="mx-2">/</span>
    <span class="text-gray-700">Settings</span>
</nav>

{{-- Page header --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Organization settings</h1>
    <p class="mt-1 text-sm text-gray-500">Manage {{ $organization->name }}'s configuration.</p>
</div>

{{-- Tab bar --}}
<div class="border-b border-gray-200 mb-6">
    <nav class="flex gap-1" aria-label="Settings tabs">
        <a href="{{ route('organizations.settings.show', $organization->slug) }}"
           class="px-4 py-2 text-sm font-semibold border-b-2 -mb-px {{ ($activeTab ?? 'general') === 'general' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-600 hover:text-gray-900' }}">
            General
        </a>
        <span class="px-4 py-2 text-sm font-medium text-gray-400 cursor-not-allowed flex items-center gap-1.5" title="Coming soon">
            Members
            <span class="text-[10px] uppercase tracking-wider bg-gray-100 text-gray-500 rounded px-1.5 py-0.5">Coming soon</span>
        </span>
        <span class="px-4 py-2 text-sm font-medium text-gray-400 cursor-not-allowed flex items-center gap-1.5" title="Coming soon">
            Billing
            <span class="text-[10px] uppercase tracking-wider bg-gray-100 text-gray-500 rounded px-1.5 py-0.5">Coming soon</span>
        </span>
    </nav>
</div>

@yield('tab-content')

@endsection
```

- [ ] **Step 5: Create the General tab view (form is read-only display here; submission wired in Task 4)**

Create `resources/views/organizations/settings/general.blade.php`:

```blade
@extends('organizations.settings.layout', ['activeTab' => 'general'])

@section('tab-content')

<div class="max-w-2xl bg-white border border-gray-200 rounded-xl shadow-sm p-6">
    <form method="POST" action="{{ route('organizations.settings.update-general', $organization->slug) }}" class="space-y-5">
        @csrf
        @method('PATCH')

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Organization name</label>
            <input id="name" name="name" type="text" required
                   value="{{ old('name', $organization->name) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="monthly_due_amount" class="block text-sm font-medium text-gray-700">Monthly due (&#8369;)</label>
            <input id="monthly_due_amount" name="monthly_due_amount" type="number" step="0.01" min="0" required
                   value="{{ old('monthly_due_amount', $organization->monthly_due_amount) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            <p class="mt-1 text-xs text-gray-500">Each household contributes this amount per month.</p>
            @error('monthly_due_amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="pt-2">
            <button type="submit" class="inline-flex items-center px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition-colors shadow-sm">
                Save changes
            </button>
        </div>
    </form>
</div>

@endsection
```

- [ ] **Step 6: Wire the settings GET route**

Edit `routes/web.php`. Find the existing `Route::middleware(['auth', EnsureOrganizationMember::class])->group(...)` block under the `Route::prefix('/{organization:slug}')`. Inside that block, after the existing routes, add a new admin sub-group:

```php
use App\Http\Controllers\OrganizationSettingsController;
use App\Http\Middleware\EnsureOrganizationAdmin;
// ...

Route::middleware(EnsureOrganizationAdmin::class)->group(function () {
    Route::get('settings', [OrganizationSettingsController::class, 'show'])->name('organizations.settings.show');
    // PATCH route added in Task 4
});
```

The `use` statements go at the top of the file with existing imports.

- [ ] **Step 7: Run — expect pass**

Run: `php artisan test --filter=OrganizationSettingsTest`
Expected: PASS for the three tests above.

Run full suite:

Run: `php artisan test`
Expected: all PASS.

- [ ] **Step 8: Lint**

Run: `./vendor/bin/pint app/Http/Controllers/OrganizationSettingsController.php`

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/OrganizationSettingsController.php resources/views/organizations/settings/layout.blade.php resources/views/organizations/settings/general.blade.php routes/web.php tests/Feature/OrganizationSettingsTest.php
git commit -m "feat(settings): admin-only org settings page shell"
```

---

## Task 4: Update Org Settings (PATCH) + form request + audit log + no-op skip

**Files:**
- Create: `app/Http/Requests/UpdateGeneralSettingsRequest.php`
- Modify: `app/Http/Controllers/OrganizationSettingsController.php`
- Modify: `routes/web.php` (add PATCH route)
- Modify: `tests/Feature/OrganizationSettingsTest.php` (append 5 tests)

- [ ] **Step 1: Append failing tests**

Append to `tests/Feature/OrganizationSettingsTest.php` (inside the same class):

```php
public function test_admin_can_update_name_and_due(): void
{
    $user = User::factory()->create();
    $org = Organization::factory()->create([
        'name' => 'Old HOA',
        'monthly_due_amount' => 200,
    ]);
    $user->organizations()->attach($org->id, ['role' => 'admin']);

    $response = $this->actingAs($user)->patch(
        route('organizations.settings.update-general', $org),
        ['name' => 'New HOA', 'monthly_due_amount' => 350]
    );

    $response->assertRedirect(route('organizations.settings.show', $org->fresh()->slug));
    $response->assertSessionHas('success');

    $org->refresh();
    $this->assertSame('New HOA', $org->name);
    $this->assertEquals('350.00', $org->monthly_due_amount);

    $this->assertDatabaseHas('audit_logs', [
        'organization_id' => $org->id,
        'user_id' => $user->id,
        'action' => 'organization.settings.updated',
    ]);

    $log = \App\Models\AuditLog::where('organization_id', $org->id)
        ->where('action', 'organization.settings.updated')
        ->first();

    $this->assertSame(['Old HOA', 'New HOA'], $log->metadata['changes']['name']);
    $this->assertSame(['200.00', '350.00'], $log->metadata['changes']['monthly_due_amount']);
}

public function test_update_validates_name_required(): void
{
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $originalName = $org->name;
    $user->organizations()->attach($org->id, ['role' => 'admin']);

    $response = $this->actingAs($user)->patch(
        route('organizations.settings.update-general', $org),
        ['name' => '', 'monthly_due_amount' => 250]
    );

    $response->assertSessionHasErrors('name');
    $this->assertSame($originalName, $org->fresh()->name);
}

public function test_update_validates_monthly_due_non_negative(): void
{
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org->id, ['role' => 'admin']);

    $response = $this->actingAs($user)->patch(
        route('organizations.settings.update-general', $org),
        ['name' => 'Test HOA', 'monthly_due_amount' => -1]
    );

    $response->assertSessionHasErrors('monthly_due_amount');
}

public function test_no_op_update_skips_audit_log(): void
{
    $user = User::factory()->create();
    $org = Organization::factory()->create([
        'name' => 'Steady HOA',
        'monthly_due_amount' => 200,
    ]);
    $user->organizations()->attach($org->id, ['role' => 'admin']);

    $response = $this->actingAs($user)->patch(
        route('organizations.settings.update-general', $org),
        ['name' => 'Steady HOA', 'monthly_due_amount' => 200]
    );

    $response->assertRedirect(route('organizations.settings.show', $org->slug));
    $response->assertSessionHas('info');
    $this->assertDatabaseMissing('audit_logs', [
        'organization_id' => $org->id,
        'action' => 'organization.settings.updated',
    ]);
}

public function test_slug_unchanged_after_rename(): void
{
    $user = User::factory()->create();
    $org = Organization::factory()->create(['name' => 'Sunshine HOA']);
    $originalSlug = $org->slug;
    $user->organizations()->attach($org->id, ['role' => 'admin']);

    $this->actingAs($user)->patch(
        route('organizations.settings.update-general', $org),
        ['name' => 'Sunshine Village HOA', 'monthly_due_amount' => $org->monthly_due_amount]
    );

    $this->assertSame($originalSlug, $org->fresh()->slug);
}
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --filter=OrganizationSettingsTest`
Expected: FAIL for all 5 new tests — PATCH route does not exist yet.

- [ ] **Step 3: Create the form request**

Create `app/Http/Requests/UpdateGeneralSettingsRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by EnsureOrganizationAdmin middleware
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'monthly_due_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
```

- [ ] **Step 4: Extend the controller with `updateGeneral`**

Edit `app/Http/Controllers/OrganizationSettingsController.php`. Final file:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGeneralSettingsRequest;
use App\Models\AuditLog;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrganizationSettingsController extends Controller
{
    public function show(Organization $organization): View
    {
        return view('organizations.settings.general', compact('organization'));
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request, Organization $organization): RedirectResponse
    {
        $before = [
            'name' => $organization->name,
            'monthly_due_amount' => (string) $organization->monthly_due_amount,
        ];

        $organization->fill($request->validated())->save();

        $after = [
            'name' => $organization->name,
            'monthly_due_amount' => (string) $organization->monthly_due_amount,
        ];

        $changes = [];
        foreach ($before as $field => $oldValue) {
            if ($oldValue !== $after[$field]) {
                $changes[$field] = [$oldValue, $after[$field]];
            }
        }

        if ($changes === []) {
            return redirect()
                ->route('organizations.settings.show', $organization->slug)
                ->with('info', 'No changes to save.');
        }

        AuditLog::record(
            $organization,
            'organization.settings.updated',
            $request->user(),
            $organization,
            ['changes' => $changes],
        );

        return redirect()
            ->route('organizations.settings.show', $organization->slug)
            ->with('success', 'Organization settings saved.');
    }
}
```

- [ ] **Step 5: Wire the PATCH route**

Edit `routes/web.php` — inside the `EnsureOrganizationAdmin` sub-group created in Task 3, add the PATCH line so the sub-group reads:

```php
Route::middleware(EnsureOrganizationAdmin::class)->group(function () {
    Route::get('settings', [OrganizationSettingsController::class, 'show'])->name('organizations.settings.show');
    Route::patch('settings/general', [OrganizationSettingsController::class, 'updateGeneral'])->name('organizations.settings.update-general');
});
```

- [ ] **Step 6: Extend the layout to render the `info` flash type**

Edit `resources/views/layouts/layout.blade.php`. Find the flash-messages block (currently handles `session('success')` and `session('error')`). Add a third condition for `info`:

```blade
@if(session('info'))
<div class="flex items-center gap-3 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 text-sm">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm-1 4a1 1 0 011-1h1a1 1 0 110 2v3a1 1 0 11-2 0v-4z" clip-rule="evenodd"/>
    </svg>
    <span>{{ session('info') }}</span>
</div>
@endif
```

Place it after the `error` block, inside the same `@if(session('success') || session('error'))` outer wrapper — actually, the existing outer wrapper is conditional on `success || error`. To also catch `info`, change the outer condition:

```blade
@if(session('success') || session('error') || session('info'))
```

- [ ] **Step 7: Run — expect pass**

Run: `php artisan test --filter=OrganizationSettingsTest`
Expected: PASS for all 8 tests.

Run full suite:

Run: `php artisan test`
Expected: all PASS.

- [ ] **Step 8: Lint**

Run: `./vendor/bin/pint app/Http/Controllers/OrganizationSettingsController.php app/Http/Requests/UpdateGeneralSettingsRequest.php`

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/OrganizationSettingsController.php app/Http/Requests/UpdateGeneralSettingsRequest.php routes/web.php resources/views/layouts/layout.blade.php tests/Feature/OrganizationSettingsTest.php
git commit -m "feat(settings): PATCH update org name + monthly due with audit log"
```

---

## Task 5: User avatar dropdown in nav (Profile / Settings / Sign out)

**Files:**
- Modify: `resources/views/layouts/layout.blade.php` (nav right-side actions + mobile menu drawer)
- Create: `tests/Feature/UserDropdownTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/UserDropdownTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDropdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_dropdown_shows_name_and_signout_for_authenticated_user_on_dashboard(): void
    {
        $user = User::factory()->create(['name' => 'Karl Candelario']);
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        $response->assertSee('Karl Candelario');
        $response->assertSee('Sign out', false);
        $response->assertSee(route('logout'), false);
    }

    public function test_settings_link_visible_to_admin(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        $response->assertSee(route('organizations.settings.show', $org->slug), false);
    }

    public function test_settings_link_hidden_for_non_admin(): void
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'member']);

        $response = $this->actingAs($user)->get(route('organizations.index', $org));

        $response->assertOk();
        $response->assertDontSee(route('organizations.settings.show', $org->slug), false);
        $response->assertSee('Sign out', false);
        $response->assertSee(route('profile.edit'), false);
    }

    public function test_logout_works_from_dropdown(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --filter=UserDropdownTest`
Expected: FAIL for tests 1–3 (dropdown markup not present). Test 4 may pass because Breeze ships the logout route.

- [ ] **Step 3: Add the user dropdown to the layout**

Edit `resources/views/layouts/layout.blade.php`. Find the right-side actions block (currently contains org badge → dark-mode toggle → mobile menu button). Insert the dropdown between the dark-mode toggle and the mobile menu button.

The order becomes: `[Org badge] [🌙 Dark-mode toggle] [👤 User dropdown] [☰ Mobile menu]`.

Insert this snippet immediately AFTER the dark-mode toggle `<button>` and BEFORE the mobile menu button:

```blade
@auth
@php
    $userInitials = collect(explode(' ', trim(auth()->user()->name)))
        ->map(fn ($w) => mb_substr($w, 0, 1))
        ->take(2)
        ->join('');
    $isAdmin = $navOrg
        ? auth()->user()->organizations()
            ->where('organizations.id', $navOrg->id)
            ->wherePivot('role', 'admin')
            ->exists()
        : false;
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
    <button type="button"
            @click="open = !open"
            :aria-expanded="open"
            aria-haspopup="true"
            class="flex items-center gap-1.5 p-1 pr-2 rounded-full hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <span class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-600 text-white text-xs font-bold uppercase">
            {{ $userInitials ?: '?' }}
        </span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
        </svg>
    </button>

    <div x-show="open" x-cloak x-transition
         class="absolute right-0 mt-2 w-56 rounded-xl bg-white shadow-lg ring-1 ring-gray-200 py-1 z-50"
         role="menu" aria-orientation="vertical">
        <div class="px-4 py-3 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
            <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
        </div>

        <a href="{{ route('profile.edit') }}"
           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
            </svg>
            Profile
        </a>

        @if($navOrg && $isAdmin)
            <a href="{{ route('organizations.settings.show', $navOrg->slug) }}"
               class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                </svg>
                Settings
            </a>
        @endif

        <div class="border-t border-gray-100 mt-1 pt-1">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50" role="menuitem">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 4.25A2.25 2.25 0 015.25 2h5.5A2.25 2.25 0 0113 4.25v2a.75.75 0 01-1.5 0v-2a.75.75 0 00-.75-.75h-5.5a.75.75 0 00-.75.75v11.5c0 .414.336.75.75.75h5.5a.75.75 0 00.75-.75v-2a.75.75 0 011.5 0v2A2.25 2.25 0 0110.75 18h-5.5A2.25 2.25 0 013 15.75V4.25z"/>
                        <path d="M6 10a.75.75 0 01.75-.75h6.69L11.97 7.78a.75.75 0 011.06-1.06l2.75 2.75a.75.75 0 010 1.06l-2.75 2.75a.75.75 0 01-1.06-1.06l1.47-1.47H6.75A.75.75 0 016 10z"/>
                    </svg>
                    Sign out
                </button>
            </form>
        </div>
    </div>
</div>
@endauth
```

- [ ] **Step 4: Append rows to the mobile menu drawer**

Still in `resources/views/layouts/layout.blade.php`, find the mobile dropdown menu block (`<div id="mobile-menu" class="hidden sm:hidden ...">`). Inside its inner `<div class="px-3 py-2 space-y-1">`, after the existing nav links, append:

```blade
<hr class="my-2 border-gray-200">

@auth
<a href="{{ route('profile.edit') }}" class="mobile-nav-link">Profile</a>

@php
    $mobileIsAdmin = $navOrg
        ? auth()->user()->organizations()
            ->where('organizations.id', $navOrg->id)
            ->wherePivot('role', 'admin')
            ->exists()
        : false;
@endphp

@if($navOrg && $mobileIsAdmin)
    <a href="{{ route('organizations.settings.show', $navOrg->slug) }}" class="mobile-nav-link">Settings</a>
@endif

<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="mobile-nav-link w-full text-left text-red-600">Sign out</button>
</form>
@endauth
```

- [ ] **Step 5: Run — expect pass**

Run: `php artisan test --filter=UserDropdownTest`
Expected: PASS (all four tests).

Run full suite:

Run: `php artisan test`
Expected: all PASS.

- [ ] **Step 6: Commit**

```bash
git add resources/views/layouts/layout.blade.php tests/Feature/UserDropdownTest.php
git commit -m "feat(nav): user avatar dropdown with profile / settings / signout"
```

---

## Task 6: Full suite + Pint + manual smoke checklist

**Files:**
- No code changes. This task verifies regression-free state and produces the manual smoke list.

- [ ] **Step 1: Full test suite**

Run: `php artisan test`
Expected: all PASS (test count = previous count + 14).

If any test fails, halt and investigate. Do not proceed to lint until green.

- [ ] **Step 2: Pint check**

Run: `./vendor/bin/pint --test`
Expected: all files clean. If any need fixing, run `./vendor/bin/pint` then commit:

```bash
./vendor/bin/pint
git add -A
git commit -m "style: pint cleanup"
```

- [ ] **Step 3: Manual smoke checklist for the user**

Produce this VERBATIM in the report:

```
Manual smoke checklist (run in a real browser):

Dashboard buttons
1. Log in as an admin user with at least one organization.
2. Visit /<org-slug>/dashboard. Confirm two new buttons in the page header: "+ Add Household" (indigo) and "↗ Import CSV" (emerald), to the LEFT of "Add Expense" and "Verify Contributions."
3. Click "+ Add Household" — confirm it routes to the single-household create form.
4. Back to dashboard. Click "↗ Import CSV" — confirm it routes to the CSV import form.

User dropdown
5. On the dashboard, look at the top-right of the nav. Confirm an avatar circle showing your initials appears between the dark-mode toggle and the mobile menu button.
6. Click the avatar. Confirm the dropdown shows your full name + email, then Profile, Settings, divider, Sign out.
7. Click "Profile" — routes to /profile (Breeze).
8. Back to dashboard. Click "Settings" — routes to /<org-slug>/settings.
9. Back to dashboard. Click "Sign out" — confirm POST /logout fires and you land on /.

Settings page (admin)
10. Log back in as admin. Visit /<org-slug>/settings.
11. Confirm three tabs: "General" (active, indigo underline), "Members" (greyed, "Coming soon" pill), "Billing" (greyed, "Coming soon" pill).
12. Edit "Organization name" to a new value, change "Monthly due" to a new amount, click "Save changes."
13. Confirm green flash "Organization settings saved." and the form values now reflect the new state.
14. Re-submit the form with the same values. Confirm blue flash "No changes to save."
15. Set "Monthly due" to -1, click Save. Confirm red error under the field.
16. Clear "Organization name" and click Save. Confirm red error under the name field.

Settings page (non-admin)
17. Log out, log in as a non-admin org member. Visit /<org-slug>/settings — confirm 403.
18. On the dashboard for that org, click the avatar. Confirm "Profile" and "Sign out" appear but "Settings" does NOT.

Settings page (non-member)
19. Log out, log in as a user not attached to that org. Visit /<org-slug>/settings — confirm 403.

Mobile
20. Open the site on a mobile viewport. Tap the hamburger menu. Confirm the drawer includes Profile and Sign out (and Settings if you're an admin).
21. Tap the avatar in the top-right. Confirm the dropdown still works on a small screen (close it with Escape or by tapping outside).
```

- [ ] **Step 4: Report back**

No commit for this task unless Pint required changes (Step 2). Just produce the summary + smoke checklist for the user.

---

## Closeout

After all tasks complete:

- [ ] `php artisan test` — green.
- [ ] `./vendor/bin/pint --test` — clean.
- [ ] `git log --oneline c58a3bd..HEAD` — one commit per task (plus the spec commit). Clean messages.
- [ ] Hand off to user with the manual smoke checklist.

---

## Notes for the Implementing Engineer

- **Tailwind on tenant pages** uses the CDN. The `brand-*` palette is defined inline in `resources/views/layouts/layout.blade.php`. New utility classes inside the dashboard's local `<style>` block use `@apply`, which the CDN's JIT supports.
- **Alpine.js** is loaded via `resources/js/app.js` (which imports `alpinejs`, calls `Alpine.start()`). The Vite bundle is included via `@vite(['resources/css/app.css', 'resources/js/app.js'])` in the tenant layout — verify this is present; if not, the dropdown will not toggle. (If absent, add `<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js">` to the tenant layout `<head>`. Tests will pass either way because `assertSee` doesn't run JS.)
- **`organization_user.role`** is the canonical source. Existing onboarding code attaches the first user as `role='admin'`. Tests for non-admin paths attach with `role='member'` (any string other than `'admin'` works, but use `'member'` for consistency).
- **`AuditLog::record`** signature: `record($org, $action, $user, $auditable, $metadata)`. Metadata is cast to JSON array on save.
- **`Organization`** uses Sluggable. Default behavior: slug does NOT regenerate on update. Test 8 (`test_slug_unchanged_after_rename`) protects this assumption.
- **`monthly_due_amount`** is cast `decimal:2`. Reading via `$org->monthly_due_amount` returns a string like `"200.00"`. The controller's diff uses `(string)` casts on both sides to normalize.
- **Existing factory quirk:** `OrganizationFactory` writes a `status` column that is not in `$fillable`. Ignore — irrelevant to this work.
- **Branch:** Work happens on `feat/dashboard-actions-and-settings` (off `master`). The spec commit `c58a3bd` is the base; each task commit follows.
- **Do NOT touch** the pre-existing dirty working-tree files from before this session (e.g., `app/Models/Contribution.php`, `composer.lock`, `package.json`). Those are unrelated work-in-progress.
