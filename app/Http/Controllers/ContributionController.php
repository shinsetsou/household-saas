<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Household;
use Illuminate\Support\Str;
use App\Models\Contribution;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\ContributionCreated;

class ContributionController extends Controller
{
    public function index(Organization $organization)
    {
        $contributions = Contribution::with('household')->get();
        return view('contributions.index', compact('contributions'));
    }
    //
    public function create(Organization $organization, Household $household)
    {
        return view('contributions.create', compact('household', 'organization'));
    }

    public function verify(Organization $organization)
    {
        // Logic for verifying contributions
        // Retrieve contributions needing verification through households
        $contributions = Contribution::select('reference_number', DB::raw('SUM(amount) as total_amount'))
        ->whereHas('household', function ($query) use ($organization) {
            $query->where('organization_id', $organization->id);
        })
        ->where('admin_verified', 0)
        ->groupBy('reference_number')
        ->get();

        return view('contributions.verify', compact('organization', 'contributions'));
    }

    public function verifyContribution(Request $request, Organization $organization, $reference_number)
    {
        // Find the contribution based on organization and reference number
        // Find all contributions with the same reference_number under the organization
        $contributions = Contribution::whereHas('household', function ($query) use ($organization) {
            $query->where('organization_id', $organization->id);
        })
        ->where('reference_number', $reference_number)
        ->get();

        // Check if any contributions were found
        if ($contributions->isEmpty()) {
            return back()->with('error', 'No contributions found with the specified reference number.');
        }

        // Update each contribution's admin_verified status
        foreach ($contributions as $contribution) {
            $contribution->admin_verified = 1;
            $contribution->save();
        }

        return back()->with('success', 'Contributions verified successfully.');
    }

    public function store(Organization $organization, Household $household, Request $request)
    {
        $reference_number = (string) Str::uuid();
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Add this validation rule
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('contributions', 'public');
        }

        $householdId = $household->id;
        $totalAmount = $request->amount;
        $monthlyContribution = 200; // Fixed monthly contribution

        // Start a transaction
        DB::beginTransaction();

        try {
       // Check for the most recent contribution
       $latestContribution = Contribution::where('household_id', $householdId)
       ->orderBy('created_at', 'desc')
       ->first();

        $remainingAmount = $totalAmount;

        if ($latestContribution && $latestContribution->status == 'lacking') {
            $neededAmount = $monthlyContribution - $latestContribution->amount;

            if ($remainingAmount >= $neededAmount) {
                // Update the lacking contribution to complete
                $latestContribution->update([
                    'amount' => $monthlyContribution,
                    'status' => 'paid'
                ]);

                $remainingAmount -= $neededAmount;
            } else {
                // Add the remaining amount to the lacking contribution and exit
                $latestContribution->update([
                    'amount' => $latestContribution->amount + $remainingAmount
                ]);

                DB::commit();
                return redirect()->route('contributions.index')->with('success', 'Contribution added successfully.');
            }
        }

        // Calculate the number of full months covered by the remaining amount
        $fullMonths = floor($remainingAmount / $monthlyContribution);
        $remainingAmount %= $monthlyContribution;

        // Determine the starting month for new contributions
        $startDate = $latestContribution ? $latestContribution->created_at->addMonth() : Carbon::now();


        // Loop to create a contribution for each full month
        for ($i = 0; $i < $fullMonths; $i++) {
            $household->contributions()->create([
                'household_id' => $householdId,
                'amount' => $monthlyContribution,
                'photo' => $photoPath,
                'status' => 'paid',
                'reference_number' => $reference_number,
                'created_at' => $startDate->copy()->addMonths($i),
                'updated_at' => $startDate->copy()->addMonths($i)
            ]);
        }

        // If there's any remaining amount, create a new entry with a "lacking" status
        if ($remainingAmount > 0) {
            $household->contributions()->create([
                'household_id' => $householdId,
                'amount' => $remainingAmount,
                'photo' => $photoPath,
                'status' => 'lacking',
                'reference_number' => $reference_number,
                'created_at' => $startDate->copy()->addMonths($fullMonths),
                'updated_at' => $startDate->copy()->addMonths($fullMonths)
            ]);
        }

        // Trigger the event
        event(new ContributionCreated(['household_id' => $householdId, 'amount' => $totalAmount, 'photo' => $photoPath, 'reference_number' => $reference_number]));

        DB::commit();

            return redirect()->route('households.show', [$organization, $household])->with('success', 'Contribution added successfully.');

        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()->route('contributions.create', [$organization, $household])->with('error', 'Failed to add contribution: ' . $e->getMessage());
        }
    }

    public function destroy(Organization $organization, Household $household, Contribution $contribution)
    {
        $contribution->delete();

        return redirect()->route('households.show', [$organization, $household])->with('success', 'Contribution deleted successfully.');
    }
}
