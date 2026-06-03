# Registration & Onboarding Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the dead-end post-registration flow with a guided 3-step wizard (org → households CSV → done), simplify the register form, and apply the BarangayBooks brand to all auth/onboarding screens.

**Architecture:** New `app/Http/Controllers/Onboarding/` namespace with three thin controllers backed by a single `OnboardingService` (transactional org creation + admin attach + audit log). A shared `onboarding.layout` Blade view provides wizard chrome and progress indicator. A `RedirectIfOnboarded` middleware and a `DashboardRedirectController` handle the zero-org / multi-org re-entry rules. Reuses the existing `HouseholdCsvImporter` and `AuditLog::record` services unchanged.

**Tech Stack:** Laravel 11, Blade, Tailwind CDN (matching landing page), Alpine.js, PHPUnit (SQLite `:memory:`), Pint.

**Spec:** `docs/superpowers/specs/2026-06-03-registration-onboarding-design.md`

---

## File Manifest

**New files:**
- `app/Services/OnboardingService.php` — transactional org-creation + admin-attach + audit-log.
- `app/Http/Controllers/Onboarding/OrganizationStepController.php` — step 1 controller.
- `app/Http/Controllers/Onboarding/HouseholdsStepController.php` — step 2 controller.
- `app/Http/Controllers/Onboarding/DoneStepController.php` — step 3 controller.
- `app/Http/Controllers/DashboardRedirectController.php` — `/dashboard` re-entry rules.
- `app/Http/Middleware/RedirectIfOnboarded.php` — bounces onboarded users out of steps 2/3 when no active session.
- `app/Http/Requests/OnboardingOrganizationRequest.php` — validation for step 1.
- `resources/views/components/brand-header.blade.php` — shared BarangayBooks logo + wordmark.
- `resources/views/onboarding/layout.blade.php` — wizard chrome (header + progress bar + card slot).
- `resources/views/onboarding/organization.blade.php` — step 1 view.
- `resources/views/onboarding/households.blade.php` — step 2 view (drop-zone, sample CSV link, skip).
- `resources/views/onboarding/done.blade.php` — step 3 success view.
- `public/samples/households-sample.csv` — header + 3 example rows.
- `tests/Feature/OnboardingTest.php` — feature tests for the full flow.
- `tests/Feature/Auth/RegistrationTest.php` — assertion update for new redirect target (only added if not present; modify if present).

**Modified files:**
- `app/Http/Controllers/Auth/RegisteredUserController.php` — drop `confirmed` rule, redirect to onboarding.
- `resources/views/auth/register.blade.php` — remove confirm field, add eye toggle, brand header.
- `resources/views/auth/login.blade.php` — brand header swap.
- `routes/web.php` — replace `/dashboard` closure with `DashboardRedirectController`, add `onboarding.*` route group.

---

## Task 1: BarangayBooks brand header component

Foundation component — used by every auth and onboarding view.

**Files:**
- Create: `resources/views/components/brand-header.blade.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/BrandHeaderTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class BrandHeaderTest extends TestCase
{
    public function test_register_page_renders_brand_header(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('BarangayBooks', false);
        $response->assertSee('from-brand-500', false);
        $response->assertSee('to-brand-700', false);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=BrandHeaderTest`
Expected: FAIL — `assertSee` for "BarangayBooks" misses (current register page shows Laravel logo).

- [ ] **Step 3: Create the brand-header component**

Create `resources/views/components/brand-header.blade.php`:

```blade
@props(['linkHome' => true])

@php
    $tag = $linkHome ? 'a' : 'span';
@endphp

<{{ $tag }}
    @if($linkHome) href="/" @endif
    {{ $attributes->class(['inline-flex items-center gap-2']) }}
>
    <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white font-bold shadow-sm shadow-brand-900/20">B</span>
    <span class="font-bold text-lg tracking-tight text-slate-900 dark:text-white">Barangay<span class="text-brand-600 dark:text-brand-500">Books</span></span>
</{{ $tag }}>
```

- [ ] **Step 4: Add the component to the register view (preview wiring only)**

Edit `resources/views/auth/register.blade.php` — wrap the form in a guest layout slot that includes the brand header above:

```blade
<x-guest-layout>
    <div class="flex justify-center mb-6">
        <x-brand-header />
    </div>

    {{-- existing form unchanged for now --}}
    <form method="POST" action="{{ route('register') }}">
        @csrf
        {{-- ... existing fields ... --}}
    </form>
</x-guest-layout>
```

Also update `resources/views/components/guest-layout.blade.php` (the Breeze guest layout) `<head>` to load the same Tailwind CDN config used on the landing page so `brand-*` classes resolve. Add inside the existing `<head>`:

```blade
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    brand: {
                        50:  '#eef2ff',
                        100: '#e0e7ff',
                        200: '#c7d2fe',
                        500: '#6366f1',
                        600: '#4f46e5',
                        700: '#4338ca',
                        800: '#3730a3',
                        900: '#312e81',
                    }
                }
            }
        }
    }
</script>
```

(Locate the existing guest layout with `php artisan vendor:publish --tag=laravel-views` if it isn't already at `resources/views/layouts/guest.blade.php`. If absent, create a new file at `resources/views/components/guest-layout.blade.php` mirroring the Breeze default and including the brand-header markup.)

- [ ] **Step 5: Run the test**

Run: `php artisan test --filter=BrandHeaderTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add resources/views/components/brand-header.blade.php resources/views/auth/register.blade.php resources/views/components/guest-layout.blade.php tests/Feature/BrandHeaderTest.php
git commit -m "feat(auth): add BarangayBooks brand header component"
```

---

## Task 2: Add brand header to login page

**Files:**
- Modify: `resources/views/auth/login.blade.php`

- [ ] **Step 1: Extend the brand-header test**

Edit `tests/Feature/BrandHeaderTest.php` — add:

```php
public function test_login_page_renders_brand_header(): void
{
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertSee('BarangayBooks', false);
}
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --filter=BrandHeaderTest::test_login_page_renders_brand_header`
Expected: FAIL — login still shows Breeze logo.

- [ ] **Step 3: Add brand header to login view**

Edit `resources/views/auth/login.blade.php` — add immediately inside `<x-guest-layout>`:

```blade
<x-guest-layout>
    <div class="flex justify-center mb-6">
        <x-brand-header />
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    {{-- existing form unchanged --}}
```

- [ ] **Step 4: Run — expect pass**

Run: `php artisan test --filter=BrandHeaderTest`
Expected: PASS (both tests).

- [ ] **Step 5: Commit**

```bash
git add resources/views/auth/login.blade.php tests/Feature/BrandHeaderTest.php
git commit -m "feat(auth): brand header on login page"
```

---

## Task 3: Simplify register form (drop confirm, add eye toggle)

**Files:**
- Modify: `resources/views/auth/register.blade.php`
- Modify: `app/Http/Controllers/Auth/RegisteredUserController.php`
- Test: `tests/Feature/Auth/RegistrationTest.php`

- [ ] **Step 1: Write the failing test**

Create or edit `tests/Feature/Auth/RegistrationTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_can_register_without_password_confirmation(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Ana Cruz',
            'email' => 'ana@example.com',
            'password' => 'super-secret-123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(); // exact target asserted in Task 5
        $this->assertDatabaseHas('users', ['email' => 'ana@example.com']);
    }

    public function test_register_page_does_not_render_confirm_password_field(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertDontSee('password_confirmation', false);
    }
}
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --filter=RegistrationTest`
Expected: FAIL — current controller requires `password_confirmed`, current view contains `password_confirmation` input.

- [ ] **Step 3: Update controller validation**

Edit `app/Http/Controllers/Auth/RegisteredUserController.php` — change the `password` rule from `['required', 'confirmed', Rules\Password::defaults()]` to `['required', Rules\Password::defaults()]`. Final `validate(...)` block:

```php
$request->validate([
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
    'password' => ['required', Rules\Password::defaults()],
]);
```

- [ ] **Step 4: Update register view**

Edit `resources/views/auth/register.blade.php` — remove the `<!-- Confirm Password -->` block entirely (the `<div class="mt-4">` containing `password_confirmation`). Replace the existing `<!-- Password -->` block with an Alpine-wrapped eye toggle:

```blade
<!-- Password -->
<div class="mt-4" x-data="{ show: false }">
    <x-input-label for="password" :value="__('Password')" />

    <div class="relative mt-1">
        <x-text-input id="password" class="block w-full pr-10"
                        :type="'password'"
                        x-bind:type="show ? 'text' : 'password'"
                        name="password"
                        required autocomplete="new-password" />
        <button type="button"
                @click="show = !show"
                class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-500 hover:text-slate-700"
                aria-label="Toggle password visibility">
            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
            </svg>
        </button>
    </div>

    <x-input-error :messages="$errors->get('password')" class="mt-2" />
</div>
```

Also ensure Alpine is loaded by the guest layout. If `resources/views/components/guest-layout.blade.php` lacks `@vite(['resources/js/app.js'])` or an Alpine CDN script, add inside `<head>`:

```blade
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>[x-cloak] { display: none !important; }</style>
```

- [ ] **Step 5: Run — expect pass**

Run: `php artisan test --filter=RegistrationTest`
Expected: PASS.

- [ ] **Step 6: Lint**

Run: `./vendor/bin/pint app/Http/Controllers/Auth/RegisteredUserController.php`
Expected: clean.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Auth/RegisteredUserController.php resources/views/auth/register.blade.php resources/views/components/guest-layout.blade.php tests/Feature/Auth/RegistrationTest.php
git commit -m "feat(auth): simplify register form (no confirm, eye toggle)"
```

---

## Task 4: OnboardingService (transactional org creation)

Foundation service — used by step 1 controller in Task 6.

**Files:**
- Create: `app/Services/OnboardingService.php`
- Test: `tests/Unit/OnboardingServiceTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/OnboardingServiceTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_organization_for_user_persists_org_attaches_admin_and_audits(): void
    {
        $user = User::factory()->create();
        $service = app(OnboardingService::class);

        $org = $service->createOrganizationForUser($user, [
            'name' => 'Sunshine HOA',
            'monthly_due_amount' => 250.00,
        ]);

        $this->assertInstanceOf(Organization::class, $org);
        $this->assertNotEmpty($org->slug);
        $this->assertSame('Sunshine HOA', $org->name);
        $this->assertEquals('250.00', $org->monthly_due_amount);

        $this->assertTrue($user->fresh()->belongsToOrganization($org));
        $this->assertSame('admin', $user->organizations()->where('organizations.id', $org->id)->first()->pivot->role);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'action' => 'organization.created',
            'auditable_type' => Organization::class,
            'auditable_id' => $org->id,
        ]);
        $log = AuditLog::where('organization_id', $org->id)->first();
        $this->assertSame(['via' => 'onboarding'], $log->metadata);
    }

    public function test_create_organization_rolls_back_on_attach_failure(): void
    {
        $user = User::factory()->create();
        $service = app(OnboardingService::class);

        $countBefore = Organization::count();

        // Force failure by passing an invalid type that throws on attach.
        $this->expectException(\TypeError::class);
        $service->createOrganizationForUser($user, [
            'name' => null, // invalid; org insert will throw
            'monthly_due_amount' => 100,
        ]);

        $this->assertSame($countBefore, Organization::count());
    }
}
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --filter=OnboardingServiceTest`
Expected: FAIL — class does not exist.

- [ ] **Step 3: Create the service**

Create `app/Services/OnboardingService.php`:

```php
<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OnboardingService
{
    public function createOrganizationForUser(User $user, array $data): Organization
    {
        return DB::transaction(function () use ($user, $data): Organization {
            $organization = Organization::create([
                'name' => $data['name'],
                'monthly_due_amount' => $data['monthly_due_amount'],
            ]);

            $user->organizations()->attach($organization->id, ['role' => 'admin']);

            AuditLog::record(
                $organization,
                'organization.created',
                $user,
                $organization,
                ['via' => 'onboarding'],
            );

            return $organization;
        });
    }
}
```

- [ ] **Step 4: Run — expect pass**

Run: `php artisan test --filter=OnboardingServiceTest`
Expected: PASS.

- [ ] **Step 5: Lint**

Run: `./vendor/bin/pint app/Services/OnboardingService.php`

- [ ] **Step 6: Commit**

```bash
git add app/Services/OnboardingService.php tests/Unit/OnboardingServiceTest.php
git commit -m "feat(onboarding): add OnboardingService for transactional org creation"
```

---

## Task 5: Redirect from register to onboarding step 1

**Files:**
- Modify: `app/Http/Controllers/Auth/RegisteredUserController.php`
- Modify: `routes/web.php` (add a stub route so the named target resolves)

- [ ] **Step 1: Update the registration test assertion**

Edit `tests/Feature/Auth/RegistrationTest.php` — replace the `assertRedirect()` line in `test_new_user_can_register_without_password_confirmation` with:

```php
$response->assertRedirect(route('onboarding.organization.create'));
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --filter=RegistrationTest::test_new_user_can_register_without_password_confirmation`
Expected: FAIL — route `onboarding.organization.create` does not exist.

- [ ] **Step 3: Add a stub route**

Edit `routes/web.php` — insert before `Route::get('/{organization:slug}', ...)`:

```php
Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('organization', fn () => 'step 1 placeholder')->name('organization.create');
});
```

(This stub is replaced with a real controller in Task 6.)

- [ ] **Step 4: Update the controller redirect**

Edit `app/Http/Controllers/Auth/RegisteredUserController.php` — change the final return from `return redirect(route('dashboard', absolute: false));` to:

```php
return redirect()->route('onboarding.organization.create');
```

- [ ] **Step 5: Run — expect pass**

Run: `php artisan test --filter=RegistrationTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Auth/RegisteredUserController.php routes/web.php tests/Feature/Auth/RegistrationTest.php
git commit -m "feat(auth): redirect new users to onboarding wizard"
```

---

## Task 6: Onboarding wizard layout + step 1 (organization)

**Files:**
- Create: `resources/views/onboarding/layout.blade.php`
- Create: `resources/views/onboarding/organization.blade.php`
- Create: `app/Http/Requests/OnboardingOrganizationRequest.php`
- Create: `app/Http/Controllers/Onboarding/OrganizationStepController.php`
- Modify: `routes/web.php` (replace stub from Task 5)

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/OnboardingTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_step_1_displays_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('onboarding.organization.create'));

        $response->assertOk();
        $response->assertSee('Create your organization', false);
        $response->assertSee('monthly_due_amount', false);
        $response->assertSee('Step 1 of 3', false);
    }

    public function test_step_1_store_creates_org_attaches_admin_and_redirects_to_step_2(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('onboarding.organization.store'), [
            'name' => 'Sunshine HOA',
            'monthly_due_amount' => 250,
        ]);

        $response->assertRedirect(route('onboarding.households.create'));
        $this->assertDatabaseHas('organizations', ['name' => 'Sunshine HOA']);

        $org = Organization::where('name', 'Sunshine HOA')->first();
        $this->assertTrue($user->fresh()->belongsToOrganization($org));
        $response->assertSessionHas('onboarding.organization_id', $org->id);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $org->id,
            'action' => 'organization.created',
        ]);
    }

    public function test_step_1_store_validates_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('onboarding.organization.store'), [
            'name' => '',
            'monthly_due_amount' => 250,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_step_1_requires_authentication(): void
    {
        $response = $this->get(route('onboarding.organization.create'));

        $response->assertRedirect(route('login'));
    }
}
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --filter=OnboardingTest`
Expected: FAIL — store route missing, view missing.

- [ ] **Step 3: Create the form request**

Create `app/Http/Requests/OnboardingOrganizationRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OnboardingOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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

- [ ] **Step 4: Create the controller**

Create `app/Http/Controllers/Onboarding/OrganizationStepController.php`:

```php
<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\OnboardingOrganizationRequest;
use App\Services\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrganizationStepController extends Controller
{
    public function create(): View
    {
        return view('onboarding.organization');
    }

    public function store(OnboardingOrganizationRequest $request, OnboardingService $service): RedirectResponse
    {
        $organization = $service->createOrganizationForUser(
            $request->user(),
            $request->validated(),
        );

        $request->session()->put('onboarding.organization_id', $organization->id);

        return redirect()->route('onboarding.households.create');
    }
}
```

- [ ] **Step 5: Create the wizard layout**

Create `resources/views/components/onboarding/layout.blade.php` (placed under `components/` so `<x-onboarding.layout>` auto-resolves):

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Onboarding' }} &mdash; BarangayBooks</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: {
                            50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',500:'#6366f1',
                            600:'#4f46e5',700:'#4338ca',800:'#3730a3',900:'#312e81',
                        }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; } body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }</style>
</head>
<body class="antialiased bg-slate-50 text-slate-900 min-h-screen">

<header class="px-4 sm:px-6 lg:px-8 py-6">
    <x-brand-header />
</header>

<main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
    {{-- Progress bar --}}
    <div class="mb-8">
        <p class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Step {{ $step }} of 3</p>
        <div class="mt-2 flex gap-2">
            @for ($i = 1; $i <= 3; $i++)
                <div class="flex-1 h-1.5 rounded-full {{ $i <= $step ? 'bg-brand-600' : 'bg-slate-200' }}"></div>
            @endfor
        </div>
    </div>

    <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm px-6 py-8 sm:px-10 sm:py-10">
        {{ $slot }}
    </div>
</main>

</body>
</html>
```

- [ ] **Step 6: Create the step 1 view**

Create `resources/views/onboarding/organization.blade.php`:

```blade
<x-onboarding.layout :step="1" title="Create your organization">
    <h1 class="text-2xl font-bold tracking-tight">Create your organization</h1>
    <p class="mt-2 text-slate-600">Name your HOA and set the monthly due. You can change these later in settings.</p>

    <form method="POST" action="{{ route('onboarding.organization.store') }}" class="mt-8 space-y-5" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">Organization name</label>
            <input id="name" name="name" type="text" required autofocus value="{{ old('name') }}"
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="monthly_due_amount" class="block text-sm font-medium text-slate-700">Monthly due (&#8369;)</label>
            <input id="monthly_due_amount" name="monthly_due_amount" type="number" step="0.01" min="0" required value="{{ old('monthly_due_amount', 200) }}"
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
            <p class="mt-1 text-xs text-slate-500">The amount each household contributes per month.</p>
            @error('monthly_due_amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="pt-2">
            <button type="submit" :disabled="submitting"
                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold shadow-sm transition disabled:opacity-60">
                <span x-show="!submitting">Continue to households</span>
                <span x-show="submitting" x-cloak>Creating&hellip;</span>
            </button>
        </div>
    </form>
</x-onboarding.layout>
```

- [ ] **Step 7: Replace the stub route with real wiring**

Edit `routes/web.php` — replace the Task-5 stub `onboarding` group with:

```php
use App\Http\Controllers\Onboarding\OrganizationStepController;

Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('organization', [OrganizationStepController::class, 'create'])->name('organization.create');
    Route::post('organization', [OrganizationStepController::class, 'store'])->name('organization.store');
    Route::get('households', fn () => 'step 2 placeholder')->name('households.create'); // replaced in Task 7
});
```

- [ ] **Step 8: Run — expect pass**

Run: `php artisan test --filter=OnboardingTest`
Expected: PASS (the four step-1 tests).

- [ ] **Step 9: Lint**

Run: `./vendor/bin/pint app/Http/Controllers/Onboarding app/Http/Requests/OnboardingOrganizationRequest.php`

- [ ] **Step 10: Commit**

```bash
git add app/Http/Controllers/Onboarding/OrganizationStepController.php app/Http/Requests/OnboardingOrganizationRequest.php resources/views/components/onboarding/layout.blade.php resources/views/onboarding/organization.blade.php routes/web.php tests/Feature/OnboardingTest.php
git commit -m "feat(onboarding): step 1 — create organization"
```

---

## Task 7: Onboarding step 2 (households CSV upload) + RedirectIfOnboarded middleware + sample CSV

**Files:**
- Create: `app/Http/Controllers/Onboarding/HouseholdsStepController.php`
- Create: `app/Http/Middleware/RedirectIfOnboarded.php`
- Create: `resources/views/onboarding/households.blade.php`
- Create: `public/samples/households-sample.csv`
- Modify: `routes/web.php`

- [ ] **Step 1: Extend the feature test**

Append to `tests/Feature/OnboardingTest.php`:

```php
public function test_step_2_displays_upload_form_with_sample_link(): void
{
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org->id, ['role' => 'admin']);
    session(['onboarding.organization_id' => $org->id]);

    $response = $this->actingAs($user)->get(route('onboarding.households.create'));

    $response->assertOk();
    $response->assertSee('Add your households', false);
    $response->assertSee('Step 2 of 3', false);
    $response->assertSee('households-sample.csv', false);
    $response->assertSee('Skip for now', false);
}

public function test_step_2_redirects_to_step_1_when_session_missing(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('onboarding.households.create'));

    $response->assertRedirect(route('onboarding.organization.create'));
}

public function test_step_2_imports_valid_csv_and_redirects_to_step_3(): void
{
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org->id, ['role' => 'admin']);
    session(['onboarding.organization_id' => $org->id]);

    $csv = "name,block,lot\nCruz Family,7,12\nReyes Family,3,4\n";
    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('households.csv', $csv);

    $response = $this->actingAs($user)->post(route('onboarding.households.store'), ['file' => $file]);

    $response->assertRedirect(route('onboarding.done.show'));
    $this->assertSame(2, $org->households()->count());
}

public function test_step_2_invalid_csv_rerenders_with_errors(): void
{
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org->id, ['role' => 'admin']);
    session(['onboarding.organization_id' => $org->id]);

    $csv = "name,block,lot\n,7,12\n"; // missing name
    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('households.csv', $csv);

    $response = $this->actingAs($user)->post(route('onboarding.households.store'), ['file' => $file]);

    $response->assertSessionHasErrors('file');
    $this->assertSame(0, $org->households()->count());
}

public function test_step_2_skip_redirects_to_step_3_without_creating_households(): void
{
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org->id, ['role' => 'admin']);
    session(['onboarding.organization_id' => $org->id]);

    $response = $this->actingAs($user)->post(route('onboarding.households.skip'));

    $response->assertRedirect(route('onboarding.done.show'));
    $this->assertSame(0, $org->households()->count());
}

public function test_redirect_if_onboarded_bounces_when_no_active_session(): void
{
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org->id, ['role' => 'admin']);
    // No session('onboarding.organization_id')

    $response = $this->actingAs($user)->get(route('onboarding.households.create'));

    $response->assertRedirect(route('organizations.index', $org));
}
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --filter=OnboardingTest`
Expected: failures for the new tests.

- [ ] **Step 3: Create the middleware**

Create `app/Http/Middleware/RedirectIfOnboarded.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $sessionOrg = $request->session()->get('onboarding.organization_id');

        if ($user && $sessionOrg === null && $user->organizations()->exists()) {
            $latest = $user->organizations()->latest('organization_user.created_at')->first();
            return redirect()->route('organizations.index', $latest);
        }

        if ($user && $sessionOrg === null && ! $user->organizations()->exists()) {
            return redirect()->route('onboarding.organization.create');
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Create the controller**

Create `app/Http/Controllers/Onboarding/HouseholdsStepController.php`:

```php
<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\HouseholdCsvImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HouseholdsStepController extends Controller
{
    public function create(Request $request): View
    {
        $organization = $this->resolveOrganization($request);

        return view('onboarding.households', compact('organization'));
    }

    public function store(Request $request, HouseholdCsvImporter $importer): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $organization = $this->resolveOrganization($request);

        $result = $importer->import($organization, $request->file('file'));

        if (! $result->successful()) {
            return back()->withErrors(['file' => implode(' ', $result->errors)]);
        }

        return redirect()->route('onboarding.done.show');
    }

    public function skip(): RedirectResponse
    {
        return redirect()->route('onboarding.done.show');
    }

    private function resolveOrganization(Request $request): Organization
    {
        $id = $request->session()->get('onboarding.organization_id');

        if (! $id) {
            abort(redirect()->route('onboarding.organization.create'));
        }

        return Organization::findOrFail($id);
    }
}
```

(`abort(Response $response)` accepts a redirect response in Laravel 11.)

- [ ] **Step 5: Create the sample CSV**

Create `public/samples/households-sample.csv`:

```csv
name,block,lot
Cruz Family,7,12
Reyes Family,3,4
Santos Family,9,21
```

- [ ] **Step 6: Create the view**

Create `resources/views/onboarding/households.blade.php`:

```blade
<x-onboarding.layout :step="2" title="Add your households">
    <h1 class="text-2xl font-bold tracking-tight">Add your households</h1>
    <p class="mt-2 text-slate-600">Upload a CSV with your households. You can skip this and add them later.</p>

    @if ($errors->any())
        <div class="mt-4 rounded-lg bg-red-50 border border-red-200 p-4">
            <p class="text-sm font-semibold text-red-800">We couldn't import your CSV:</p>
            <ul class="mt-2 list-disc list-inside text-sm text-red-700 space-y-0.5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-6 rounded-lg bg-slate-50 border border-slate-200 p-4">
        <p class="text-sm font-semibold text-slate-800">CSV format</p>
        <p class="mt-1 text-xs text-slate-600">Header row must be exactly <code class="px-1 bg-white rounded">name,block,lot</code>. Block and lot must be whole numbers.</p>

        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead>
                    <tr class="text-slate-600">
                        <th class="text-left px-3 py-1.5 bg-white border border-slate-200">name</th>
                        <th class="text-left px-3 py-1.5 bg-white border border-slate-200">block</th>
                        <th class="text-left px-3 py-1.5 bg-white border border-slate-200">lot</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
                    <tr><td class="px-3 py-1.5 bg-white border border-slate-200">Cruz Family</td><td class="px-3 py-1.5 bg-white border border-slate-200">7</td><td class="px-3 py-1.5 bg-white border border-slate-200">12</td></tr>
                    <tr><td class="px-3 py-1.5 bg-white border border-slate-200">Reyes Family</td><td class="px-3 py-1.5 bg-white border border-slate-200">3</td><td class="px-3 py-1.5 bg-white border border-slate-200">4</td></tr>
                </tbody>
            </table>
        </div>

        <a href="/samples/households-sample.csv" download
           class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 hover:text-brand-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M4 6h16"/></svg>
            Download sample households-sample.csv
        </a>
    </div>

    <form method="POST" action="{{ route('onboarding.households.store') }}" enctype="multipart/form-data" class="mt-6" x-data="{ filename: '' }">
        @csrf
        <label class="block">
            <span class="block text-sm font-medium text-slate-700">CSV file</span>
            <input type="file" name="file" accept=".csv,text/csv" required
                   @change="filename = $event.target.files[0]?.name || ''"
                   class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100" />
            <p x-show="filename" x-cloak class="mt-1 text-xs text-slate-500">Selected: <span class="font-mono" x-text="filename"></span></p>
        </label>

        <div class="mt-6">
            <button type="submit" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold shadow-sm transition">
                Upload and continue
            </button>
        </div>
    </form>

    <form method="POST" action="{{ route('onboarding.households.skip') }}" class="mt-4">
        @csrf
        <button type="submit" class="text-sm font-medium text-slate-600 hover:text-slate-900 underline underline-offset-2">
            Skip for now &rarr;
        </button>
    </form>
</x-onboarding.layout>
```

- [ ] **Step 7: Wire routes**

Edit `routes/web.php` — replace the Task-6 `households.create` placeholder with the full step 2 group. Final onboarding group:

```php
use App\Http\Controllers\Onboarding\HouseholdsStepController;
use App\Http\Controllers\Onboarding\OrganizationStepController;
use App\Http\Middleware\RedirectIfOnboarded;

Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('organization', [OrganizationStepController::class, 'create'])->name('organization.create');
    Route::post('organization', [OrganizationStepController::class, 'store'])->name('organization.store');

    Route::middleware(RedirectIfOnboarded::class)->group(function () {
        Route::get('households', [HouseholdsStepController::class, 'create'])->name('households.create');
        Route::post('households', [HouseholdsStepController::class, 'store'])->name('households.store');
        Route::post('households/skip', [HouseholdsStepController::class, 'skip'])->name('households.skip');
        Route::get('done', fn () => 'step 3 placeholder')->name('done.show'); // replaced in Task 8
    });
});
```

- [ ] **Step 8: Run — expect pass**

Run: `php artisan test --filter=OnboardingTest`
Expected: PASS for all step-2 and middleware tests added in Step 1.

- [ ] **Step 9: Lint**

Run: `./vendor/bin/pint app/Http/Controllers/Onboarding app/Http/Middleware/RedirectIfOnboarded.php`

- [ ] **Step 10: Commit**

```bash
git add app/Http/Controllers/Onboarding/HouseholdsStepController.php app/Http/Middleware/RedirectIfOnboarded.php resources/views/onboarding/households.blade.php public/samples/households-sample.csv routes/web.php tests/Feature/OnboardingTest.php
git commit -m "feat(onboarding): step 2 — household CSV upload with skip"
```

---

## Task 8: Onboarding step 3 (done) + clear session

**Files:**
- Create: `app/Http/Controllers/Onboarding/DoneStepController.php`
- Create: `resources/views/onboarding/done.blade.php`
- Modify: `routes/web.php` (replace `done.show` placeholder)

- [ ] **Step 1: Extend the feature test**

Append to `tests/Feature/OnboardingTest.php`:

```php
public function test_step_3_displays_success_with_dashboard_cta(): void
{
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org->id, ['role' => 'admin']);
    session(['onboarding.organization_id' => $org->id]);

    $response = $this->actingAs($user)->get(route('onboarding.done.show'));

    $response->assertOk();
    $response->assertSee('You\'re all set', false);
    $response->assertSee($org->name);
    $response->assertSee(route('organizations.index', $org), false);
    $response->assertSee('Step 3 of 3', false);
}

public function test_step_3_clears_onboarding_session_key(): void
{
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org->id, ['role' => 'admin']);
    session(['onboarding.organization_id' => $org->id]);

    $response = $this->actingAs($user)->get(route('onboarding.done.show'));

    $response->assertOk();
    $this->assertNull(session('onboarding.organization_id'));
}
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --filter=OnboardingTest`
Expected: FAIL for step-3 tests.

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/Onboarding/DoneStepController.php`:

```php
<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoneStepController extends Controller
{
    public function show(Request $request): View
    {
        $id = $request->session()->get('onboarding.organization_id');

        if (! $id) {
            abort(redirect()->route('onboarding.organization.create'));
        }

        $organization = Organization::findOrFail($id);
        $householdCount = $organization->households()->count();

        $request->session()->forget('onboarding.organization_id');

        return view('onboarding.done', compact('organization', 'householdCount'));
    }
}
```

- [ ] **Step 4: Create the view**

Create `resources/views/onboarding/done.blade.php`:

```blade
<x-onboarding.layout :step="3" title="You're all set">
    <div class="text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 text-emerald-600">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="mt-5 text-2xl font-bold tracking-tight">You're all set</h1>
        <p class="mt-2 text-slate-600">{{ $organization->name }} is ready to collect contributions.</p>
    </div>

    <div class="mt-8 rounded-lg bg-slate-50 border border-slate-200 p-5">
        <dl class="grid grid-cols-2 gap-y-3 text-sm">
            <dt class="text-slate-600">Organization</dt>
            <dd class="font-semibold text-right">{{ $organization->name }}</dd>
            <dt class="text-slate-600">Monthly due</dt>
            <dd class="font-semibold text-right">&#8369;{{ number_format((float) $organization->monthly_due_amount, 2) }}</dd>
            <dt class="text-slate-600">Households</dt>
            <dd class="font-semibold text-right">{{ $householdCount }}</dd>
        </dl>
    </div>

    <a href="{{ route('organizations.index', $organization) }}"
       class="mt-8 block text-center px-6 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold shadow-sm transition">
        Go to your dashboard
    </a>

    @if ($householdCount === 0)
        <a href="{{ route('households.import.create', $organization) }}"
           class="mt-3 block text-center text-sm font-medium text-slate-600 hover:text-slate-900 underline underline-offset-2">
            Add households later from settings &rarr;
        </a>
    @endif
</x-onboarding.layout>
```

- [ ] **Step 5: Wire route**

Edit `routes/web.php` — replace the `done.show` placeholder line with:

```php
use App\Http\Controllers\Onboarding\DoneStepController;

// inside the RedirectIfOnboarded sub-group:
Route::get('done', [DoneStepController::class, 'show'])->name('done.show');
```

- [ ] **Step 6: Run — expect pass**

Run: `php artisan test --filter=OnboardingTest`
Expected: PASS for step-3 tests.

- [ ] **Step 7: Lint + full test pass**

Run: `./vendor/bin/pint app/Http/Controllers/Onboarding/DoneStepController.php && php artisan test --filter=OnboardingTest`

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Onboarding/DoneStepController.php resources/views/onboarding/done.blade.php routes/web.php tests/Feature/OnboardingTest.php
git commit -m "feat(onboarding): step 3 — success screen"
```

---

## Task 9: Dashboard re-entry rules (zero orgs → wizard, ≥1 org → tenant dashboard)

**Files:**
- Create: `app/Http/Controllers/DashboardRedirectController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/OnboardingTest.php`:

```php
public function test_dashboard_with_zero_orgs_redirects_to_onboarding(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('onboarding.organization.create'));
}

public function test_dashboard_with_one_org_redirects_to_tenant_dashboard(): void
{
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org->id, ['role' => 'admin']);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('organizations.index', $org));
}

public function test_dashboard_with_multiple_orgs_redirects_to_most_recent(): void
{
    $user = User::factory()->create();
    $older = Organization::factory()->create();
    $newer = Organization::factory()->create();

    $user->organizations()->attach($older->id, ['role' => 'admin']);
    // Simulate older attach by hand
    \DB::table('organization_user')->where('organization_id', $older->id)->update([
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);
    $user->organizations()->attach($newer->id, ['role' => 'admin']);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('organizations.index', $newer));
}
```

- [ ] **Step 2: Run — expect failure**

Run: `php artisan test --filter=OnboardingTest::test_dashboard`
Expected: FAIL — current `/dashboard` renders Breeze placeholder.

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/DashboardRedirectController.php`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        $latest = $user->organizations()->latest('organization_user.created_at')->first();

        if ($latest === null) {
            return redirect()->route('onboarding.organization.create');
        }

        return redirect()->route('organizations.index', $latest);
    }
}
```

- [ ] **Step 4: Replace the dashboard route**

Edit `routes/web.php` — replace the closure-based `/dashboard` route:

```php
Route::get('/dashboard', \App\Http\Controllers\DashboardRedirectController::class)
    ->middleware(['auth'])
    ->name('dashboard');
```

Remove the `'verified'` middleware (registration does not enforce email verification today; adding it here would re-introduce a dead-end during onboarding). If the project later opts into email verification, add it at the registration/login level, not at this redirect.

- [ ] **Step 5: Run — expect pass**

Run: `php artisan test --filter=OnboardingTest`
Expected: PASS for all dashboard tests.

- [ ] **Step 6: Lint**

Run: `./vendor/bin/pint app/Http/Controllers/DashboardRedirectController.php`

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/DashboardRedirectController.php routes/web.php tests/Feature/OnboardingTest.php
git commit -m "feat(onboarding): /dashboard redirects based on org membership"
```

---

## Task 10: End-to-end smoke test + Pint pass + manual verification

**Files:**
- Test: `tests/Feature/OnboardingTest.php` (append one final end-to-end test)

- [ ] **Step 1: Write the end-to-end test**

Append:

```php
public function test_full_register_to_dashboard_flow(): void
{
    $registerResponse = $this->post(route('register'), [
        'name' => 'Ana Cruz',
        'email' => 'ana@example.com',
        'password' => 'super-secret-123',
    ]);
    $registerResponse->assertRedirect(route('onboarding.organization.create'));

    $this->get(route('onboarding.organization.create'))->assertOk();

    $step1 = $this->post(route('onboarding.organization.store'), [
        'name' => 'Sunshine HOA',
        'monthly_due_amount' => 250,
    ]);
    $step1->assertRedirect(route('onboarding.households.create'));

    $csv = "name,block,lot\nCruz Family,7,12\n";
    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('households.csv', $csv);
    $step2 = $this->post(route('onboarding.households.store'), ['file' => $file]);
    $step2->assertRedirect(route('onboarding.done.show'));

    $done = $this->get(route('onboarding.done.show'));
    $done->assertOk();
    $done->assertSee('Sunshine HOA');
    $done->assertSee('1'); // household count

    $user = \App\Models\User::where('email', 'ana@example.com')->first();
    $org = \App\Models\Organization::where('name', 'Sunshine HOA')->first();

    $this->assertTrue($user->belongsToOrganization($org));
    $this->assertSame(1, $org->households()->count());

    // After /onboarding/done, hitting /dashboard goes to the tenant dashboard.
    $this->get('/dashboard')->assertRedirect(route('organizations.index', $org));
}
```

- [ ] **Step 2: Run — expect pass**

Run: `php artisan test --filter=OnboardingTest::test_full_register_to_dashboard_flow`
Expected: PASS.

- [ ] **Step 3: Run the full suite to catch regressions**

Run: `php artisan test`
Expected: all tests pass. Any existing test that asserted `/dashboard` returns 200 will fail and must be updated to expect a redirect (search results first):

Run: `grep -rn "/dashboard" tests/` and update any 200-asserting test to assert a redirect to `route('organizations.index', $org)` (or `route('onboarding.organization.create')` for zero-org users).

- [ ] **Step 4: Lint everything**

Run: `./vendor/bin/pint`
Expected: all files clean.

- [ ] **Step 5: Manual smoke**

Run `php artisan serve` and `npm run dev` in two terminals. In a browser:

1. Visit `/register`. Confirm brand header + no confirm-password field + working eye toggle.
2. Submit. Confirm redirect to `/onboarding/organization`. Progress shows "Step 1 of 3."
3. Submit org form with name "Test HOA" and due 200. Confirm redirect to `/onboarding/households`.
4. Click "Download sample" — confirm CSV downloads with the three example rows.
5. Upload the sample CSV. Confirm redirect to `/onboarding/done` showing org name + 3 households.
6. Click "Go to your dashboard." Confirm tenant dashboard renders.
7. Visit `/onboarding/households` directly. Confirm bounce to tenant dashboard.
8. Log out. Re-register a new user. Repeat step 2. Confirm progress bar shows step 1 of 3 again.

- [ ] **Step 6: Final commit**

```bash
git add tests/Feature/OnboardingTest.php
git commit -m "test(onboarding): end-to-end register-to-dashboard flow"
```

---

## Closeout

After Task 10:

- [ ] Run `php artisan test` once more — all green.
- [ ] Run `./vendor/bin/pint --test` — clean.
- [ ] Skim `git log --oneline` since the spec commit (`ac9ffc6`) — confirm one commit per task, clean messages.
- [ ] Hand off to user for browser-based final verification.

---

## Notes for the Implementing Engineer

- The project uses **SQLite `:memory:`** for tests (see `phpunit.xml`). `RefreshDatabase` is appropriate. Do not introduce MySQL-specific SQL.
- **Tailwind on auth/onboarding pages** uses the CDN (`<script src="https://cdn.tailwindcss.com">`), not the compiled Vite pipeline. Inline the same `tailwind.config = { ... }` block used on the landing page so `brand-*`, `slate-*`, and `from-brand-500` classes resolve. Do NOT migrate the project to a compiled Tailwind pipeline as part of this work.
- **Blade component naming.** Place the wizard layout at `resources/views/components/onboarding/layout.blade.php` so `<x-onboarding.layout>` resolves via Blade's auto-discovery. Step views (`organization.blade.php`, `households.blade.php`, `done.blade.php`) stay at `resources/views/onboarding/` and are rendered via `view('onboarding.organization')`, etc.
- **Sluggable.** `Organization` uses `cviebrock/eloquent-sluggable` — `slug` is auto-populated from `name` on save. Do not pass `slug` explicitly in `OnboardingService::createOrganizationForUser`.
- **AuditLog.** Call signature is `AuditLog::record($org, $action, $user, $auditable, $metadata)`. `metadata` is cast to JSON array.
- **HouseholdCsvImporter** returns `HouseholdCsvImportResult { imported: int, errors: array }`. On any error, zero rows are written.
- **Existing tests.** A Breeze-style `RegistrationTest` may already exist at `tests/Feature/Auth/RegistrationTest.php`. If so, update it in Task 3/5 rather than creating a duplicate.
