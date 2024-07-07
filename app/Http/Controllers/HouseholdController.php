<?php
namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Household;
use App\Models\Organization;
use Illuminate\Http\Request;

class HouseholdController extends Controller
{
    public function index(Request $request, Organization $organization )
    {
        if (!empty($request->block) && !empty($request->lot)) {

            $household = Household::with(['contributions' => function ($query) {
                $query->orderBy('created_at', 'desc'); // or any other column
            }])->where('block', $request->block)
            ->where('lot', $request->lot)->where('organization_id', $organization->id)->first();

            if(empty($household)) {
                return redirect()->back()->with('organization', $organization);
            }

            // Calculate the total contributions for this household
            $totalContributions = $household->contributions()->sum('amount');

            // Calculate the total expenses for this household
            $totalExpenses = Expense::where('organization_id', $organization->id)->sum('amount');

            $itemsPerPage = $request->input('itemsPerPage', 10); // Default to 10 items per page
            $contributions = $household->contributions()->orderBy('created_at', 'desc')->paginate($itemsPerPage);

            return view('households.show', compact('household', 'contributions', 'itemsPerPage', 'organization', 'totalContributions', 'totalExpenses'));
        }

        $households = Household::all();
        return view('households.index', compact('households'));
    }

    public function create()
    {
        return view('households.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Household::create($request->all());

        return redirect()->route('households.index')->with('success', 'Household created successfully.');
    }

    public function show(Organization $organization, Household $household, Request $request)
    {
        $household = Household::find($household->id);
        $itemsPerPage = $request->input('itemsPerPage', 10); // Default to 10 items per page
        $contributions = $household->contributions()->orderBy('created_at', 'desc')->paginate($itemsPerPage);

        // Calculate the total contributions for this household
        $totalContributions = $household->contributions()->sum('amount');

        // Calculate the total expenses for this household
        $totalExpenses = Expense::where('organization_id', $organization->id)->sum('amount');

        return view('households.show', compact('household', 'contributions', 'itemsPerPage', 'organization', 'totalContributions', 'totalExpenses'));
    }

    public function edit(Household $household)
    {
        return view('households.edit', compact('household'));
    }

    public function update(Request $request, Household $household)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $household->update($request->all());

        return redirect()->route('households.index')->with('success', 'Household updated successfully.');
    }

    public function destroy(Household $household)
    {
        $household->delete();

        return redirect()->route('households.index')->with('success', 'Household deleted successfully.');
    }
}
