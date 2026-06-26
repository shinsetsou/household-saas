<?php

namespace App\Http\Controllers;

use App\Events\ContributionCreated;
use App\Models\AuditLog;
use App\Models\Contribution;
use App\Models\Household;
use App\Models\Organization;
use App\Support\HouseholdTotalsCache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContributionController extends Controller
{
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

        $query = Contribution::forOrganization($organization)
            ->with('household')
            ->latest();

        if ($status) {
            $query->where('verification_status', $status);
        }

        $contributions = $query->paginate(request()->input('itemsPerPage', 25))
            ->appends(array_filter(['status' => $status]));

        $filteredTotal = (clone $query)->sum('amount');
        $verifiedTotal = Contribution::forOrganization($organization)
            ->where('verification_status', 'verified')
            ->sum('amount');

        return view('contributions.index', compact(
            'contributions',
            'organization',
            'status',
            'counts',
            'totalCount',
            'filteredTotal',
            'verifiedTotal',
        ));
    }

    //
    public function create(Organization $organization, Household $household)
    {
        abort_unless($household->organization_id === $organization->id, 404);

        return view('contributions.create', compact('household', 'organization'));
    }

    public function verify(Organization $organization)
    {
        // Logic for verifying contributions
        $itemsPerPage = request()->get('itemsPerPage', 10);
        // Retrieve contributions needing verification through households
        $contributions = Contribution::select(
            'contributions.reference_number',
            'contributions.household_id',
            DB::raw('SUM(contributions.amount) as total_amount'),
            DB::raw('MAX(contributions.photo) as photo'),
            DB::raw('MIN(contributions.month_for) as month_for'),
            'households.name as household_name',
            'households.block as block',
            'households.lot as lot'
            )
            ->join('households', 'contributions.household_id', '=', 'households.id')
            ->where('households.organization_id', $organization->id)
            ->where('contributions.verification_status', 'pending')
            ->groupBy('contributions.reference_number',
            'contributions.household_id',
            'households.name',
            'households.block',
            'households.lot'
            )
            ->orderBy('contributions.reference_number')
            ->paginate($itemsPerPage);

        // Hover preview: last 12 verified billing months per household on this page.
        // One query, indexed by household_id — the verify table only shows pending rows so
        // the set is small (≤ itemsPerPage households per page).
        $householdIds = $contributions->pluck('household_id')->unique()->values();
        $paymentHistory = Contribution::query()
            ->select('household_id', 'month_for', 'created_at')
            ->whereIn('household_id', $householdIds)
            ->where('verification_status', 'verified')
            ->where('status', 'paid')
            ->whereNotNull('month_for')
            ->orderByDesc('month_for')
            ->get()
            ->groupBy('household_id')
            ->map(fn ($rows) => $rows->take(12)->map(fn ($r) => $r->month_for->format('M Y'))->values());

        return view('contributions.verify', compact('organization', 'contributions', 'itemsPerPage', 'paymentHistory'));
    }

    public function verifyContribution(Request $request, Organization $organization, $reference_number)
    {
        $contributions = Contribution::forOrganization($organization)
            ->where('reference_number', $reference_number)
            ->get();

        // Check if any contributions were found
        if ($contributions->isEmpty()) {
            return back()->with('error', 'No contributions found with the specified reference number.');
        }

        $validated = $request->validate([
            'verification_status' => 'required|in:verified,rejected',
            'rejection_reason' => 'required_if:verification_status,rejected|nullable|string|max:255',
            'month_for' => 'nullable|date_format:Y-m',
        ]);

        $previousMonths = $contributions
            ->map(fn ($c) => optional($c->month_for)->toDateString())
            ->filter()
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($contributions, $validated, $request) {
            foreach ($contributions as $contribution) {
                $this->applyVerification($contribution, $validated, $request->user()->id);
            }
        });

        $newMonthFor = null;
        if ($request->filled('month_for')) {
            $newMonthFor = Carbon::createFromFormat('Y-m', $request->input('month_for'))->startOfMonth()->toDateString();
        }

        $metadata = ['reference_number' => $reference_number];
        if ($newMonthFor !== null && $previousMonths && $previousMonths !== [$newMonthFor]) {
            $metadata['month_for_changed'] = [
                'from' => $previousMonths,
                'to' => $newMonthFor,
            ];
        }

        AuditLog::record(
            $organization,
            $validated['verification_status'] === 'verified' ? 'contribution.verified' : 'contribution.rejected',
            $request->user(),
            $contributions->first(),
            $metadata
        );

        return back()->with('success', 'Contribution review saved successfully.');
    }

    public function bulkVerifyContribution(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'reference_numbers' => 'required|array|min:1|max:200',
            'reference_numbers.*' => 'string|max:64',
            'verification_status' => 'required|in:verified,rejected',
            'rejection_reason' => 'required_if:verification_status,rejected|nullable|string|max:255',
        ]);

        $referenceNumbers = array_values(array_unique($validated['reference_numbers']));

        // Pull all matching rows in one query — restrict to the current org via the
        // household join so a reference_number that exists in another org can't be
        // touched by a treasurer from this one.
        $contributions = Contribution::forOrganization($organization)
            ->whereIn('reference_number', $referenceNumbers)
            ->where('verification_status', 'pending')
            ->get();

        if ($contributions->isEmpty()) {
            return back()->with('error', 'No pending contributions matched the selected references.');
        }

        $userId = $request->user()->id;
        $isVerified = $validated['verification_status'] === 'verified';

        DB::transaction(function () use ($contributions, $validated, $userId, $isVerified) {
            foreach ($contributions as $contribution) {
                $this->applyVerification($contribution, $validated, $userId);
            }
        });

        AuditLog::record(
            $organization,
            $isVerified ? 'contribution.verified.bulk' : 'contribution.rejected.bulk',
            $request->user(),
            $contributions->first(),
            [
                'reference_numbers' => $contributions->pluck('reference_number')->unique()->values()->all(),
                'count' => $contributions->count(),
            ]
        );

        $verb = $isVerified ? 'verified' : 'rejected';
        $requested = count($referenceNumbers);
        $matched = $contributions->pluck('reference_number')->unique()->count();
        $skipped = $requested - $matched;

        $msg = $matched === 1
            ? "1 contribution {$verb}."
            : "{$matched} contributions {$verb}.";
        if ($skipped > 0) {
            $msg .= " {$skipped} reference(s) were already verified or didn't belong here and were skipped.";
        }

        return back()->with($matched > 0 ? 'success' : 'error', $msg);
    }

    /**
     * Apply verified/rejected status to a single contribution. Used by both the
     * single-row verify form and the bulk action so they share audit semantics.
     */
    protected function applyVerification(Contribution $contribution, array $validated, int $userId): void
    {
        $contribution->verification_status = $validated['verification_status'];
        $contribution->admin_verified = $validated['verification_status'] === 'verified' ? 1 : 3;
        $contribution->rejection_reason = $validated['verification_status'] === 'rejected' ? ($validated['rejection_reason'] ?? null) : null;
        $contribution->verified_by = $userId;
        $contribution->verified_at = now();

        // Bulk action does NOT override month_for — the treasurer edits each row's
        // month_for before clicking the bulk verify button. Single-row verify still
        // honors an explicit month_for override (kept for backwards compatibility).
        if (! empty($validated['month_for'])) {
            $newMonthFor = Carbon::createFromFormat('Y-m', $validated['month_for'])->startOfMonth()->toDateString();
            if ((string) $contribution->month_for !== $newMonthFor) {
                $contribution->month_for = $newMonthFor;
            }
        }

        $contribution->save();
    }

    public function store(Organization $organization, Household $household, Request $request)
    {
        abort_unless($household->organization_id === $organization->id, 404);

        $referenceNumber = (string) Str::uuid();
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'month_for' => 'nullable|date_format:Y-m',
            'photo' => 'required|mimetypes:image/jpeg,image/png,image/gif|max:2048',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('contributions', 's3');
        }

        $householdId = $household->id;
        $totalAmount = $request->amount;
        $monthlyContribution = (float) $organization->monthly_due_amount;

        // Pin every row to the same billing month so the delinquency + reports
        // aggregate over month_for rather than the upload date. Treasurer can
        // re-attribute any row from the verify screen.
        $monthFor = $request->filled('month_for')
            ? Carbon::createFromFormat('Y-m', $request->input('month_for'))->startOfMonth()
            : Carbon::now()->startOfMonth();

        if ($monthlyContribution <= 0) {
            if ($photoPath !== null) {
                Storage::disk('s3')->delete($photoPath);
            }

            return back()->withInput()->withErrors([
                'amount' => 'Monthly due amount is not configured for this organization.',
            ]);
        }

        // Start a transaction
        DB::beginTransaction();

        try {
            // Check for the most recent contribution
            $latestContribution = Contribution::forOrganization($organization)
                ->where('household_id', $householdId)
                ->orderBy('created_at', 'desc')
                ->lockForUpdate()
                ->first();

            $remainingAmount = $totalAmount;

            if ($latestContribution && $latestContribution->status == 'lacking') {
                $neededAmount = $monthlyContribution - $latestContribution->amount;

                if ($remainingAmount >= $neededAmount) {
                    // Update the lacking contribution to complete
                    $latestContribution->update([
                        'amount' => $monthlyContribution,
                        'month_for' => $monthFor->toDateString(),
                        'status' => 'paid',
                    ]);

                    $remainingAmount -= $neededAmount;
                } else {
                    // Add the remaining amount to the lacking contribution and exit
                    $latestContribution->update([
                        'amount' => $latestContribution->amount + $remainingAmount,
                        'month_for' => $monthFor->toDateString(),
                    ]);

                    DB::commit();

                    HouseholdTotalsCache::invalidate($organization, $household);

                    return redirect()->route('households.index', [
                        'organization' => $organization,
                        'block' => $household->block,
                        'lot' => $household->lot,
                    ])->with('success', 'Contribution added successfully.');
                }
            }

            // Calculate the number of full months covered by the remaining amount
            $fullMonths = floor($remainingAmount / $monthlyContribution);
            $remainingAmount %= $monthlyContribution;

            // Determine the starting month for new contributions
            $startDate = $latestContribution ? $latestContribution->created_at->addMonth() : Carbon::now();

            // Loop to create a contribution for each full month.
            // Each row gets its own reference_number so verification can target one
            // month at a time (the upload-level UUID stays in the audit metadata via event).
            for ($i = 0; $i < $fullMonths; $i++) {
                $rowDate = $monthFor->copy()->addMonths($i);
                $household->contributions()->create([
                    'household_id' => $householdId,
                    'amount' => $monthlyContribution,
                    'month_for' => $rowDate->toDateString(),
                    'photo' => $photoPath,
                    'status' => 'paid',
                    'verification_status' => 'pending',
                    'reference_number' => (string) Str::uuid(),
                    'created_at' => $rowDate,
                    'updated_at' => $rowDate,
                ]);
            }

            // If there's any remaining amount, create a new entry with a "lacking" status
            if ($remainingAmount > 0) {
                $lackingDate = $monthFor->copy()->addMonths($fullMonths);
                $household->contributions()->create([
                    'household_id' => $householdId,
                    'amount' => $remainingAmount,
                    'month_for' => $lackingDate->toDateString(),
                    'photo' => $photoPath,
                    'status' => 'lacking',
                    'verification_status' => 'pending',
                    'reference_number' => (string) Str::uuid(),
                    'created_at' => $lackingDate,
                    'updated_at' => $lackingDate,
                ]);
            }

            // Trigger the event — upload-level correlation id, distinct from per-row refs.
            event(new ContributionCreated([
                'household_id' => $householdId,
                'amount' => $totalAmount,
                'photo' => $photoPath,
                'reference_number' => $referenceNumber,
            ]));

            DB::commit();

            HouseholdTotalsCache::invalidate($organization, $household);

            return redirect()->route('households.index', [
                'organization' => $organization,
                'block' => $household->block,
                'lot' => $household->lot,
            ])->with('success', 'Contribution added successfully.');

        } catch (\Exception $e) {

            DB::rollBack();

            // Best-effort cleanup of the uploaded receipt so we don't leak orphans on B2.
            // Failures here are reported but don't mask the original exception.
            if ($photoPath !== null) {
                try {
                    Storage::disk('s3')->delete($photoPath);
                } catch (\Throwable $cleanupError) {
                    report($cleanupError);
                }
            }

            report($e);

            return redirect()->route('contributions.create', [$organization, $household])->with('error', 'Failed to add contribution. Please try again.');
        }
    }

    public function destroy(Organization $organization, Household $household, Contribution $contribution)
    {
        abort_unless($household->organization_id === $organization->id, 404);
        abort_unless($contribution->household_id === $household->id, 404);

        // Other contributions in the same upload share the same photo path, so only
        // delete the file from B2 when this is the last row referencing it.
        $photoPath = $contribution->photo;
        $sharedPhotoCount = $photoPath
            ? Contribution::forOrganization($organization)
                ->where('photo', $photoPath)
                ->whereKeyNot($contribution->id)
                ->count()
            : 0;

        $contribution->delete();

        if ($photoPath !== null && $sharedPhotoCount === 0) {
            try {
                Storage::disk('s3')->delete($photoPath);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        HouseholdTotalsCache::invalidate($organization, $household);

        AuditLog::record($organization, 'contribution.deleted', request()->user(), $contribution);

        return redirect()->route('households.show', [$organization, $household])->with('success', 'Contribution deleted successfully.');
    }
}
