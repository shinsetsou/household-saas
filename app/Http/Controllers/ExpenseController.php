<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\Organization;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Organization $organization, Request $request)
    {
        $query = Expense::where('organization_id', $organization->id);
        $itemsPerPage = $request->input('itemsPerPage', 10); // Default to 10 items per page

        if ($request->filled('month')) {
            $query->whereMonth('created_at', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);
        }

        $expenses = $query->orderBy('created_at', 'desc')->paginate($itemsPerPage);

        return view('expenses.index', compact('organization', 'expenses', 'itemsPerPage'));
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

        return redirect()->route('organizations.index', $organization)->with('success', 'Expense added successfully.');
    }
}
