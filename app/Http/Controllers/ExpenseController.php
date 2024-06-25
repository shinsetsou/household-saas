<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Organization;

class ExpenseController extends Controller
{
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
