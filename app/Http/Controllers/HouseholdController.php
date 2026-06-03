<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Household;
use App\Models\Organization;
use Illuminate\Http\Request;

class HouseholdController extends Controller
{
    public function index(Request $request, Organization $organization)
    {
        if (! empty($request->block) && ! empty($request->lot)) {

            $household = Household::with(['contributions' => function ($query) {
                $query->orderBy('created_at', 'desc'); // or any other column
            }])->where('block', $request->block)
                ->where('lot', $request->lot)->where('organization_id', $organization->id)->first();

            if (empty($household)) {
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

        if (! $request->user()) {
            return redirect()->route('login');
        }

        abort_unless($request->user()->belongsToOrganization($organization), 403);

        $households = Household::forOrganization($organization)
            ->orderBy('block')
            ->orderBy('lot')
            ->paginate($request->input('itemsPerPage', 25));

        return view('households.index', compact('households', 'organization'));
    }

    public function create(Organization $organization)
    {
        return view('households.create', compact('organization'));
    }

    public function store(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'block' => 'required|integer|min:1',
            'lot' => 'required|integer|min:1',
        ]);

        $organization->households()->create($validated);

        return redirect()->route('households.index', $organization)->with('success', 'Household created successfully.');
    }

    public function show(Organization $organization, Household $household, Request $request)
    {
        abort_unless($household->organization_id === $organization->id, 404);

        $itemsPerPage = $request->input('itemsPerPage', 10); // Default to 10 items per page
        $contributions = $household->contributions()->orderBy('created_at', 'desc')->paginate($itemsPerPage);

        // Calculate the total contributions for this household
        $totalContributions = $household->contributions()->sum('amount');

        // Calculate the total expenses for this household
        $totalExpenses = Expense::where('organization_id', $organization->id)->sum('amount');

        return view('households.show', compact('household', 'contributions', 'itemsPerPage', 'organization', 'totalContributions', 'totalExpenses'));
    }

    public function edit(Organization $organization, Household $household)
    {
        abort_unless($household->organization_id === $organization->id, 404);

        return view('households.edit', compact('household', 'organization'));
    }

    public function update(Request $request, Organization $organization, Household $household)
    {
        abort_unless($household->organization_id === $organization->id, 404);

        $request->validate([
            'name' => 'required|string|max:255',
            'block' => 'required|integer|min:1',
            'lot' => 'required|integer|min:1',
        ]);

        $household->update($request->only(['name', 'block', 'lot']));

        return redirect()->route('households.index', $organization)->with('success', 'Household updated successfully.');
    }

    public function destroy(Organization $organization, Household $household)
    {
        abort_unless($household->organization_id === $organization->id, 404);

        $household->delete();

        return redirect()->route('households.index', $organization)->with('success', 'Household deleted successfully.');
    }
}
