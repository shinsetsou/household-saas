<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ExpenseController extends Controller
{
    public function index(Organization $organization, Request $request)
    {
        $expenses = Expense::forOrganization($organization)
            ->when($request->filled('month'), fn ($query) => $query->whereMonth('created_at', $request->month))
            ->when($request->filled('year'), fn ($query) => $query->whereYear('created_at', $request->year))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('itemsPerPage', 10));

        return view('expenses.index', compact('organization', 'expenses'));
    }

    public function create(Organization $organization)
    {
        return view('expenses.create', compact('organization'));
    }

    public function store(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'description' => 'required|string|max:255',
        ]);

        $expense = $organization->expenses()->create($validated);

        AuditLog::record($organization, 'expense.created', $request->user(), $expense, ['amount' => $expense->amount]);

        Cache::forget("organization:{$organization->id}:expenses_sum");

        return redirect()->route('organizations.index', $organization)->with('success', 'Expense added successfully.');
    }
}
