<?php

// app/Http/Controllers/ExpensesController.php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\DelinquencyService;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index(Organization $organization, Request $request, DelinquencyService $delinquencyService)
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

        $currentMonthPaid = $organization->households()
            ->join('contributions', 'households.id', '=', 'contributions.household_id')
            ->whereMonth('contributions.created_at', now()->month)
            ->whereYear('contributions.created_at', now()->year)
            ->where('contributions.verification_status', 'verified')
            ->sum('contributions.amount');

        $currentMonthPending = $organization->households()
            ->join('contributions', 'households.id', '=', 'contributions.household_id')
            ->whereMonth('contributions.created_at', now()->month)
            ->whereYear('contributions.created_at', now()->year)
            ->where('contributions.verification_status', 'pending')
            ->count();

        $delinquentHouseholds = count($delinquencyService->forOrganization($organization, now()->year, now()->month));
        $expectedMonthly = $organization->households()->count() * (float) $organization->monthly_due_amount;

        return view('organizations.index', compact(
            'organization',
            'year',
            'totalContributions',
            'totalExpenses',
            'currentMonthPaid',
            'currentMonthPending',
            'delinquentHouseholds',
            'expectedMonthly'
        ));
    }

    public function show(Organization $organization)
    {
        // dd($organization);
        return view('home', compact('organization'));
    }
}
