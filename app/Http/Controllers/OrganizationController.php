<?php

// app/Http/Controllers/ExpensesController.php

namespace App\Http\Controllers;

use App\Models\Contribution;
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
            ->where('contributions.verification_status', 'verified')
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

        // Last 6 months of verified contributions — used for the trend sparkline.
        $monthlyTrend = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $sum = $organization->households()
                ->join('contributions', 'households.id', '=', 'contributions.household_id')
                ->whereMonth('contributions.created_at', $date->month)
                ->whereYear('contributions.created_at', $date->year)
                ->where('contributions.verification_status', 'verified')
                ->sum('contributions.amount');
            $monthlyTrend->push([
                'label' => $date->format('M'),
                'total' => (float) $sum,
            ]);
        }
        $trendMax = max($monthlyTrend->pluck('total')->max() ?: 1, 1);

        // Recent activity feed (contributions + expenses merged, last 6).
        // Eloquent\Collection::merge() calls getKey() on each item, so we have
        // to drop down to a base Support\Collection before merging the mapped
        // arrays (an empty result set would otherwise keep the Eloquent type
        // and blow up on $item->getKey()).
        $recentContributions = Contribution::forOrganization($organization)
            ->with('household')
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn ($c) => [
                'type' => 'contribution',
                'label' => $c->household?->name ?? 'Unknown household',
                'sub' => 'Contribution · '.$c->verification_status,
                'amount' => (float) $c->amount,
                'when' => $c->created_at,
            ])
            ->toBase();

        $recentExpenses = $organization->expenses()
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn ($e) => [
                'type' => 'expense',
                'label' => $e->description,
                'sub' => 'Expense',
                'amount' => -(float) $e->amount,
                'when' => $e->created_at,
            ])
            ->toBase();

        $recentActivity = $recentContributions
            ->merge($recentExpenses)
            ->sortByDesc('when')
            ->take(6)
            ->values();

        // Top 5 members by verified paid total this year.
        $topHouseholds = $organization->households()
            ->withSum(['contributions as verified_total' => function ($q) use ($year) {
                $q->whereYear('contributions.created_at', $year)
                    ->where('contributions.verification_status', 'verified');
            }], 'amount')
            ->orderByDesc('verified_total')
            ->limit(5)
            ->get();

        return view('organizations.index', compact(
            'organization',
            'year',
            'totalContributions',
            'totalExpenses',
            'currentMonthPaid',
            'currentMonthPending',
            'delinquentHouseholds',
            'expectedMonthly',
            'monthlyTrend',
            'trendMax',
            'recentActivity',
            'topHouseholds'
        ));
    }

    public function show(Organization $organization)
    {
        // dd($organization);
        return view('home', compact('organization'));
    }
}
