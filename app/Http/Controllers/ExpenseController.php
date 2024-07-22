<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Organization;

class ExpenseController extends Controller
{
    public function index(Organization $organization, Request $request)
    {
        $query = Expense::where('organization_id', $organization->id);
        $itemsPerPage = $request->input('itemsPerPage', 10); // Default to 10 items per page

        if ($request->has('month')) {
            $query->whereMonth('created_at', $request->month);
        }

        if ($request->has('year')) {
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

        $organization->expenses()->create($validated);

        return redirect()->route('organizations.index', $organization)->with('success', 'Expense added successfully.');
    }
}
