# Contribution Verification: Filter Tabs + Verify Nav Badge

**Date:** 2026-06-03
**Status:** Approved design (pending spec review)

## Problem

1. The Contributions page (`contributions.index`) lists every contribution in one
   undifferentiated table. It shows the payment `status` (paid/lacking) but not the
   `verification_status` (verified/pending/rejected). Treasurers cannot tell at a glance
   which payments still need verification versus which are already verified.
2. The "Verify" nav link gives no signal of how much work is waiting. A treasurer must
   open the page to discover whether anything needs review.

## Goals

- Let users filter the Contributions table by verification status via tabs.
- Surface the `verification_status` of each contribution in the table.
- Show a count badge on the "Verify" nav link indicating outstanding work.

## Non-Goals

- No change to the verification workflow itself (`verify` / `verifyContribution`).
- No change to the public resident-facing household dashboard.
- No real-time/live updates — counts reflect the request that rendered the page.

## Part 1 — Contributions Page Filter Tabs

### Controller: `ContributionController::index`

Current:

```php
public function index(Organization $organization)
{
    $contributions = Contribution::forOrganization($organization)
        ->with('household')
        ->latest()
        ->paginate(request()->input('itemsPerPage', 25));

    return view('contributions.index', compact('contributions', 'organization'));
}
```

New behaviour:

- Accept an optional `?status=` query, one of `verified|pending|rejected`. Any other
  value (including absent/empty) means "all".
- When a valid status is present, constrain the query with
  `where('verification_status', $status)`.
- Compute per-status counts for the tab labels in a single grouped query:
  `Contribution::forOrganization($organization)
      ->selectRaw('verification_status, COUNT(*) as aggregate')
      ->groupBy('verification_status')->pluck('aggregate', 'verification_status')`.
  Expose as `$counts` (a keyed collection; missing keys default to 0 in the view) plus
  `$totalCount` for the "All" tab.
- Append `status` to the paginator via `->appends(['status' => $status])` so pagination
  preserves the active filter.
- Pass `$status` (the validated/normalised active filter, or `null` for all) to the view.

Validation: normalise with
`$status = in_array($request->query('status'), ['verified','pending','rejected'], true) ? $request->query('status') : null;`
No exception on bad input — silently falls back to "all".

### View: `resources/views/contributions/index.blade.php`

- Add a tab bar above the table: **All**, **Verified**, **Pending**, **Rejected**.
  - Each tab is a link to `route('contributions.index', [$organization, 'status' => <value>])`
    (All omits the param).
  - Active tab determined by `$status`. Active styling uses the existing indigo accent.
  - Pending and Rejected tabs show their count when > 0 (e.g. "Pending 3").
- Add a **Verification** column to the table with a status badge, following the design
  system: green = verified, gray = pending, red = rejected.
- Empty-state copy adapts: if a filter is active and yields nothing, say
  "No <status> contributions." instead of the generic empty message.

## Part 2 — Verify Nav Badge

### Count semantics

The Verify page groups pending contributions by `reference_number` (one row per
submission/receipt). The badge counts **distinct pending `reference_number` groups** so
it matches the number of rows a treasurer will actually see and act on.

```php
Contribution::forOrganization($organization)
    ->where('verification_status', 'pending')
    ->distinct()
    ->count('reference_number');
```

### Delivery: View Composer

The nav lives in `resources/views/layouts/layout.blade.php` and is shared across all
authenticated tenant pages. Computing the count inline in Blade would run an ad-hoc query
inside the template and repeat on every render path.

Instead, bind a View Composer to `layouts.layout` that injects
`$pendingVerificationCount`:

- Only compute when there is an authenticated user who belongs to the organization
  resolved from the route (`request()->route('organization')`). Otherwise inject `0`.
- Register the composer in a service provider (`AppServiceProvider::boot` via
  `View::composer('layouts.layout', ContributionVerificationComposer::class)`, or a small
  closure if a dedicated class feels heavy — implementation plan decides).
- Reuse the `$navOrg` resolution logic already in the layout (organization from the
  passed variable or the route).

### View changes (`layout.blade.php`)

- Desktop "Verify" link (line ~108) and mobile "Verify Contributions" link (line ~234):
  append a small red rounded badge showing `$pendingVerificationCount` when it is > 0.
  Hidden entirely at 0.
- Badge styling: `inline-flex items-center justify-center` red bubble consistent with the
  Danger `red-500/600` token in the design system.

## Data Model

No schema changes. Reads `contributions.verification_status` (already indexed) and
`reference_number` (already indexed). No migration.

## Testing

Feature tests (`tests/Feature/`):

1. **Index status filter** — seed verified + pending + rejected contributions; assert
   `?status=pending` shows only pending rows and hides others; assert `?status=` (all)
   shows everything.
2. **Tab counts** — assert the rendered page contains the correct pending/rejected counts
   in the tab labels.
3. **Badge count = distinct pending submissions** — seed two pending contributions sharing
   one `reference_number` plus one pending with a different `reference_number`; assert the
   composer-provided count is 2, not 3.
4. **Badge hidden at zero** — org with no pending contributions: assert no badge markup /
   count rendered on the Verify link.
5. **Badge scoped to org + membership** — a member of org A does not see org B's pending
   count; a non-member request does not compute a count.

## Risks / Edge Cases

- `verification_status` legacy rows: column is backfilled per CLAUDE.md, so all rows have a
  value. Rows with an unexpected value fall outside all three tabs but still appear under
  "All".
- Performance: two extra lightweight indexed queries per Contributions page load (filter +
  grouped counts) and one per authenticated nav render (badge). All on indexed columns;
  acceptable at expected scale.
```
