<?php
// app/Http/Controllers/ExpensesController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Organization;

class OrganizationController extends Controller
{
    public function index(Organization $organization, Request $request)
    {
        $year = $request->input('year', date('Y'));

        // Get total contributions for the organization for the specified year
        $totalContributions = $organization->households()
            ->join('contributions', 'households.id', '=', 'contributions.household_id')
            ->whereYear('contributions.created_at', $year)
            ->sum('contributions.amount');

        // Get total expenses for the organization for the specified year
        $totalExpenses = $organization->expenses()
            ->whereYear('created_at', $year)
            ->sum('amount');

        return view('organizations.index', compact('organization', 'year', 'totalContributions', 'totalExpenses'));
    }

    public function show(Organization $organization)
    {
        //dd($organization);
        return view('home', compact('organization'));
    }



    public function store(Request $request, Organization $organization)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Household::create($request->all());

        return redirect()->route('households.index')->with('success', 'Organization created successfully.');
    }
}
