# Contribution Verification Tabs + Verify Nav Badge Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add verification-status filter tabs + a verification badge column to the Contributions page, and a pending-count badge on the Verify nav link.

**Architecture:** `ContributionController::index` gains a `?status=` filter and per-status counts. The Contributions Blade view renders tabs + a status badge column. A View Composer bound to `layouts.layout` injects `$pendingVerificationCount` (distinct pending `reference_number` groups) for the Verify nav badge.

**Tech Stack:** Laravel 11, Blade, Tailwind (CDN), PHPUnit (SQLite `:memory:`).

**Spec:** `docs/superpowers/specs/2026-06-03-contribution-verification-tabs-and-badge-design.md`

---

## File Structure

- Modify: `app/Http/Controllers/ContributionController.php` — `index()` filter + counts.
- Modify: `resources/views/contributions/index.blade.php` — tabs + verification column.
- Modify: `app/Providers/AppServiceProvider.php` — register view composer.
- Modify: `resources/views/layouts/layout.blade.php` — badge markup (desktop + mobile).
- Create: `tests/Feature/ContributionVerificationViewTest.php` — all feature tests.

---

## Task 1: Filter + counts in `ContributionController::index`

**Files:**
- Modify: `app/Http/Controllers/ContributionController.php:17-25`
- Test: `tests/Feature/ContributionVerificationViewTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ContributionVerificationViewTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Household;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributionVerificationViewTest extends TestCase
{
    use RefreshDatabase;

    private function member(Organization $org): User
    {
        $user = User::factory()->create();
        $user->organizations()->attach($org->id, ['role' => 'admin']);

        return $user;
    }

    public function test_status_filter_shows_only_matching_contributions(): void
    {
        $org = Organization::factory()->create();
        $user = $this->member($org);
        $household = Household::factory()->create(['organization_id' => $org->id, 'name' => 'Reyes']);
        Contribution::factory()->create(['household_id' => $household->id, 'verification_status' => 'pending', 'amount' => 111]);
        Contribution::factory()->create(['household_id' => $household->id, 'verification_status' => 'verified', 'amount' => 222]);

        $pending = $this->actingAs($user)->get(route('contributions.index', [$org, 'status' => 'pending']));
        $pending->assertOk();
        $pending->assertSee('111.00');
        $pending->assertDontSee('222.00');

        $all = $this->actingAs($user)->get(route('contributions.index', $org));
        $all->assertSee('111.00');
        $all->assertSee('222.00');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ContributionVerificationViewTest`
Expected: FAIL — both amounts shown under `?status=pending` because the filter does not exist yet (`assertDontSee('222.00')` fails).

- [ ] **Step 3: Implement the filter + counts**

Replace `app/Http/Controllers/ContributionController.php:17-25` (the `index` method) with:

```php
    public function index(Organization $organization)
    {
        $allowed = ['verified', 'pending', 'rejected'];
        $status = in_array(request()->query('status'), $allowed, true)
            ? request()->query('status')
            : null;

        $counts = Contribution::forOrganization($organization)
            ->selectRaw('verification_status, COUNT(*) as aggregate')
            ->groupBy('verification_status')
            ->pluck('aggregate', 'verification_status');

        $totalCount = (int) $counts->sum();

        $contributions = Contribution::forOrganization($organization)
            ->when($status, fn ($query) => $query->where('verification_status', $status))
            ->with('household')
            ->latest()
            ->paginate(request()->input('itemsPerPage', 25))
            ->appends(['status' => $status]);

        return view('contributions.index', compact('contributions', 'organization', 'status', 'counts', 'totalCount'));
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ContributionVerificationViewTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/ContributionController.php tests/Feature/ContributionVerificationViewTest.php
git commit -m "feat(contributions): filter index by verification status"
```

---

## Task 2: Tabs + verification badge column in the view

**Files:**
- Modify: `resources/views/contributions/index.blade.php`
- Test: `tests/Feature/ContributionVerificationViewTest.php`

- [ ] **Step 1: Write the failing test**

Append this method to `ContributionVerificationViewTest`:

```php
    public function test_tabs_show_status_counts_and_badge_column(): void
    {
        $org = Organization::factory()->create();
        $user = $this->member($org);
        $household = Household::factory()->create(['organization_id' => $org->id]);
        Contribution::factory()->count(3)->create(['household_id' => $household->id, 'verification_status' => 'pending']);
        Contribution::factory()->create(['household_id' => $household->id, 'verification_status' => 'verified']);

        $response = $this->actingAs($user)->get(route('contributions.index', $org));

        $response->assertOk();
        $response->assertSee('Pending');
        $response->assertSee('Verified');
        // Pending tab shows its count of 3
        $response->assertSeeInOrder(['Pending', '3']);
        // Verification badge column header present
        $response->assertSee('Verification');
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_tabs_show_status_counts_and_badge_column`
Expected: FAIL — view has no "Verification" column header / tab markup yet.

- [ ] **Step 3: Add the tab bar**

In `resources/views/contributions/index.blade.php`, replace the page-header block (lines 7-15, the `{{-- Page header --}}` div) with:

```blade
{{-- Page header --}}
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">All Contributions</h1>
            <p class="mt-1 text-sm text-gray-500">Complete record of household payments</p>
        </div>
    </div>
</div>

{{-- Verification status tabs --}}
@php
    $tabs = [
        null        => ['label' => 'All',      'count' => $totalCount],
        'verified'  => ['label' => 'Verified', 'count' => (int) ($counts['verified'] ?? 0)],
        'pending'   => ['label' => 'Pending',  'count' => (int) ($counts['pending'] ?? 0)],
        'rejected'  => ['label' => 'Rejected', 'count' => (int) ($counts['rejected'] ?? 0)],
    ];
@endphp
<div class="mb-6 flex flex-wrap gap-1 border-b border-gray-200">
    @foreach($tabs as $value => $tab)
        @php $active = $status === $value; @endphp
        <a href="{{ route('contributions.index', array_filter([$organization->slug, 'status' => $value])) }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium border-b-2 -mb-px transition
                  {{ $active ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            {{ $tab['label'] }}
            @if($tab['count'] > 0)
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-xs font-semibold
                             {{ $active ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $tab['count'] }}
                </span>
            @endif
        </a>
    @endforeach
</div>
```

Note: `route('contributions.index', array_filter([$organization->slug, 'status' => $value]))` — `array_filter` drops the `status` key when `$value` is `null` (the All tab), producing a clean URL.

- [ ] **Step 4: Add the Verification column**

In the same file, add a header cell after the `Status` header (`<th class="th-cell">Status</th>`):

```blade
                    <th class="th-cell">Verification</th>
```

And add the matching body cell after the Status `<td>` block (after its closing `</td>` on the `@endif` line), before the Date cell:

```blade
                    <td class="td-cell">
                        @if($contribution->verification_status === 'verified')
                            <span class="status-badge-green">Verified</span>
                        @elseif($contribution->verification_status === 'rejected')
                            <span class="status-badge-red">Rejected</span>
                        @else
                            <span class="status-badge-gray">Pending</span>
                        @endif
                    </td>
```

Add the `status-badge-red` style to the `<style>` block at the bottom, next to the existing badge styles:

```blade
    .status-badge-red    { @apply inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 }
```

- [ ] **Step 5: Adapt the empty state**

Replace the empty-state paragraph copy (the two `<p>` lines inside the `$contributions->isEmpty()` block) with:

```blade
    <p class="text-sm font-semibold text-gray-900 mb-1">
        {{ $status ? 'No '.$status.' contributions' : 'No contributions recorded' }}
    </p>
    <p class="text-sm text-gray-500">
        {{ $status ? 'Try a different tab.' : 'Contributions will appear here after households submit payments.' }}
    </p>
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=test_tabs_show_status_counts_and_badge_column`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add resources/views/contributions/index.blade.php tests/Feature/ContributionVerificationViewTest.php
git commit -m "feat(contributions): verification tabs and status column"
```

---

## Task 3: View Composer for the pending-count badge

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Feature/ContributionVerificationViewTest.php`

- [ ] **Step 1: Write the failing test**

Append to `ContributionVerificationViewTest`:

```php
    public function test_verify_badge_counts_distinct_pending_submissions(): void
    {
        $org = Organization::factory()->create();
        $user = $this->member($org);
        $household = Household::factory()->create(['organization_id' => $org->id]);

        // Two pending rows sharing one reference_number = one submission.
        Contribution::factory()->count(2)->create([
            'household_id' => $household->id,
            'reference_number' => 'ref-shared',
            'verification_status' => 'pending',
        ]);
        // A second distinct pending submission.
        Contribution::factory()->create([
            'household_id' => $household->id,
            'reference_number' => 'ref-other',
            'verification_status' => 'pending',
        ]);
        // A verified row must not count.
        Contribution::factory()->create([
            'household_id' => $household->id,
            'reference_number' => 'ref-verified',
            'verification_status' => 'verified',
        ]);

        $response = $this->actingAs($user)->get(route('contributions.index', $org));

        $response->assertOk();
        // Badge bubble on the Verify link shows 2 distinct pending submissions.
        $response->assertViewHas('pendingVerificationCount', 2);
    }

    public function test_verify_badge_zero_when_nothing_pending(): void
    {
        $org = Organization::factory()->create();
        $user = $this->member($org);
        $household = Household::factory()->create(['organization_id' => $org->id]);
        Contribution::factory()->create(['household_id' => $household->id, 'verification_status' => 'verified']);

        $response = $this->actingAs($user)->get(route('contributions.index', $org));

        $response->assertViewHas('pendingVerificationCount', 0);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_verify_badge_counts_distinct_pending_submissions`
Expected: FAIL — `pendingVerificationCount` not bound to any view.

- [ ] **Step 3: Register the composer**

In `app/Providers/AppServiceProvider.php`, add imports below the namespace's existing `use`:

```php
use App\Models\Contribution;
use App\Models\Organization;
use Illuminate\Support\Facades\View;
```

Replace the `boot()` body (`//`) with:

```php
        View::composer('layouts.layout', function ($view): void {
            $count = 0;
            $org = request()->route('organization');
            $user = auth()->user();

            if ($org instanceof Organization && $user && $user->belongsToOrganization($org)) {
                $count = Contribution::forOrganization($org)
                    ->where('verification_status', 'pending')
                    ->distinct()
                    ->count('reference_number');
            }

            $view->with('pendingVerificationCount', $count);
        });
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=test_verify_badge`
Expected: PASS (both badge tests).

- [ ] **Step 5: Commit**

```bash
git add app/Providers/AppServiceProvider.php tests/Feature/ContributionVerificationViewTest.php
git commit -m "feat(nav): compute pending verification count for nav badge"
```

---

## Task 4: Badge markup in the layout

**Files:**
- Modify: `resources/views/layouts/layout.blade.php:108-111` (desktop) and `:234` (mobile)
- Test: `tests/Feature/ContributionVerificationViewTest.php`

- [ ] **Step 1: Write the failing test**

Append to `ContributionVerificationViewTest`:

```php
    public function test_verify_link_renders_count_badge_when_pending(): void
    {
        $org = Organization::factory()->create();
        $user = $this->member($org);
        $household = Household::factory()->create(['organization_id' => $org->id]);
        Contribution::factory()->create([
            'household_id' => $household->id,
            'reference_number' => 'ref-x',
            'verification_status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('contributions.index', $org));

        $response->assertOk();
        // The Verify link carries a visible badge element with the count.
        $response->assertSee('verify-badge', false);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_verify_link_renders_count_badge_when_pending`
Expected: FAIL — no `verify-badge` markup in the layout.

- [ ] **Step 3: Add the desktop badge**

In `resources/views/layouts/layout.blade.php`, replace the desktop Verify link (lines 108-111):

```blade
                <a href="{{ route('contributions.verify', $navOrg->slug) }}"
                   class="nav-link {{ request()->routeIs('contributions.verify') ? 'nav-link-active' : '' }}">
                    Verify
                </a>
```

with:

```blade
                <a href="{{ route('contributions.verify', $navOrg->slug) }}"
                   class="nav-link inline-flex items-center gap-1.5 {{ request()->routeIs('contributions.verify') ? 'nav-link-active' : '' }}">
                    Verify
                    @if(($pendingVerificationCount ?? 0) > 0)
                        <span class="verify-badge inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-red-600 text-white text-xs font-semibold">
                            {{ $pendingVerificationCount }}
                        </span>
                    @endif
                </a>
```

- [ ] **Step 4: Add the mobile badge**

Replace the mobile Verify link (line 234):

```blade
                <a href="{{ route('contributions.verify', $navOrg->slug) }}" class="mobile-nav-link">Verify Contributions</a>
```

with:

```blade
                <a href="{{ route('contributions.verify', $navOrg->slug) }}" class="mobile-nav-link flex items-center justify-between">
                    <span>Verify Contributions</span>
                    @if(($pendingVerificationCount ?? 0) > 0)
                        <span class="verify-badge inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-red-600 text-white text-xs font-semibold">
                            {{ $pendingVerificationCount }}
                        </span>
                    @endif
                </a>
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=test_verify_link_renders_count_badge_when_pending`
Expected: PASS.

- [ ] **Step 6: Run the full suite + pint**

Run: `php artisan test`
Expected: all pass.
Run: `./vendor/bin/pint app/ tests/`
Expected: style clean.

- [ ] **Step 7: Commit**

```bash
git add resources/views/layouts/layout.blade.php tests/Feature/ContributionVerificationViewTest.php
git commit -m "feat(nav): render pending count badge on Verify link"
```

---

## Self-Review Notes

- **Spec coverage:** Part 1 tabs → Task 2; status filter + counts → Task 1; verification column → Task 2; Part 2 count semantics (distinct pending refs) → Task 3; badge markup desktop+mobile → Task 4; member/org scoping → Task 3 composer guard. All 5 spec tests mapped across Tasks 1-4.
- **Type consistency:** `$status`, `$counts`, `$totalCount`, `$pendingVerificationCount` names used identically in controller, composer, and views.
- **No schema change** — confirmed, reads indexed `verification_status` + `reference_number`.
