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

        $contributions = Contribution::forOrganization($organization)
            ->when($status, fn ($query) => $query->where('verification_status', $status))
            ->with('household')
            ->latest()
            ->paginate(request()->input('itemsPerPage', 25))
            ->appends(array_filter(['status' => $status]));

        return view('contributions.index', compact('contributions', 'organization', 'status', 'counts', 'totalCount'));
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
            DB::raw('SUM(contributions.amount) as total_amount'),
            DB::raw('MAX(contributions.photo) as photo'),
            'households.name as household_name',
            'households.block as block',
            'households.lot as lot'
            )
            ->join('households', 'contributions.household_id', '=', 'households.id')
            ->where('households.organization_id', $organization->id)
            ->where('contributions.verification_status', 'pending')
            ->groupBy('contributions.reference_number',
            'households.name',
            'households.block',
            'households.lot'
            )
            ->paginate($itemsPerPage);

        return view('contributions.verify', compact('organization', 'contributions', 'itemsPerPage'));
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
        ]);

        foreach ($contributions as $contribution) {
            $contribution->verification_status = $validated['verification_status'];
            $contribution->admin_verified = $validated['verification_status'] === 'verified' ? 1 : 3;
            $contribution->rejection_reason = $validated['verification_status'] === 'rejected' ? $validated['rejection_reason'] : null;
            $contribution->verified_by = $request->user()->id;
            $contribution->verified_at = now();
            $contribution->save();
        }

        AuditLog::record(
            $organization,
            $validated['verification_status'] === 'verified' ? 'contribution.verified' : 'contribution.rejected',
            $request->user(),
            $contributions->first(),
            ['reference_number' => $reference_number]
        );

        return back()->with('success', 'Contribution review saved successfully.');
    }

    public function store(Organization $organization, Household $household, Request $request)
    {
        abort_unless($household->organization_id === $organization->id, 404);

        $referenceNumber = (string) Str::uuid();
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'photo' => 'required|mimetypes:image/jpeg,image/png,image/gif|max:2048',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('contributions', 's3');
        }

        $householdId = $household->id;
        $totalAmount = $request->amount;
        $monthlyContribution = (float) $organization->monthly_due_amount;

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
                        'status' => 'paid',
                    ]);

                    $remainingAmount -= $neededAmount;
                } else {
                    // Add the remaining amount to the lacking contribution and exit
                    $latestContribution->update([
                        'amount' => $latestContribution->amount + $remainingAmount,
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
                $household->contributions()->create([
                    'household_id' => $householdId,
                    'amount' => $monthlyContribution,
                    'photo' => $photoPath,
                    'status' => 'paid',
                    'verification_status' => 'pending',
                    'reference_number' => (string) Str::uuid(),
                    'created_at' => $startDate->copy()->addMonths($i),
                    'updated_at' => $startDate->copy()->addMonths($i),
                ]);
            }

            // If there's any remaining amount, create a new entry with a "lacking" status
            if ($remainingAmount > 0) {
                $household->contributions()->create([
                    'household_id' => $householdId,
                    'amount' => $remainingAmount,
                    'photo' => $photoPath,
                    'status' => 'lacking',
                    'verification_status' => 'pending',
                    'reference_number' => (string) Str::uuid(),
                    'created_at' => $startDate->copy()->addMonths($fullMonths),
                    'updated_at' => $startDate->copy()->addMonths($fullMonths),
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
