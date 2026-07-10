<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Organization $organization, Request $request)
    {
        $itemsPerPage = $request->input('itemsPerPage', 10);

        $query = Expense::forOrganization($organization)
            ->when($request->filled('month'), fn ($q) => $q->whereMonth('created_at', $request->month))
            ->when($request->filled('year'), fn ($q) => $q->whereYear('created_at', $request->year))
            ->when($request->filled('status') && in_array($request->status, Expense::statuses(), true),
                fn ($q) => $q->where('status', $request->status));

        $filteredTotal = (clone $query)->sum('amount');
        $filteredCount = (clone $query)->count();
        $largest = (clone $query)->orderByDesc('amount')->first();
        $unpaidTotal = Expense::forOrganization($organization)->unpaid()->sum('amount');

        $expenses = (clone $query)
            ->orderBy('created_at', 'desc')
            ->paginate($itemsPerPage)
            ->appends(array_filter([
                'itemsPerPage' => $itemsPerPage,
                'month' => $request->input('month'),
                'year' => $request->input('year'),
                'status' => $request->input('status'),
            ]));

        $ytdTotal = Expense::forOrganization($organization)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        return view('expenses.index', compact(
            'organization',
            'expenses',
            'itemsPerPage',
            'filteredTotal',
            'filteredCount',
            'largest',
            'ytdTotal',
            'unpaidTotal',
        ));
    }

    public function create(Organization $organization)
    {
        return view('expenses.create', compact('organization'));
    }

    public function store(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
            'receipt' => 'nullable|mimetypes:image/jpeg,image/png,image/gif,image/webp,application/pdf|max:4096',
            'status' => ['nullable', Rule::in([Expense::STATUS_PENDING, Expense::STATUS_PAID])],
            'notes' => 'nullable|string|max:1000',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('expenses', 's3');
        }

        $status = $validated['status'] ?? Expense::STATUS_PENDING;

        $expense = $organization->expenses()->create([
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'receipt' => $receiptPath,
            'status' => $status,
            'paid_at' => $status === Expense::STATUS_PAID ? now() : null,
            'paid_by' => $status === Expense::STATUS_PAID ? $request->user()->id : null,
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::record($organization, 'expense.created', $request->user(), $expense, [
            'amount' => $expense->amount,
            'status' => $expense->status,
        ]);

        if ($status === Expense::STATUS_PAID) {
            AuditLog::record($organization, 'expense.paid', $request->user(), $expense, [
                'amount' => $expense->amount,
                'at_creation' => true,
            ]);
        }

        Cache::forget("organization:{$organization->id}:expenses_sum");

        return redirect()->route('expenses.index', $organization)->with('success', 'Expense added successfully.');
    }

    public function show(Organization $organization, Expense $expense)
    {
        abort_unless($expense->organization_id === $organization->id, 404);

        return view('expenses.show', compact('organization', 'expense'));
    }

    public function markPaid(Request $request, Organization $organization, Expense $expense)
    {
        abort_unless($expense->organization_id === $organization->id, 404);

        if ($expense->isPaid()) {
            return back()->with('error', 'Expense is already marked as paid.');
        }

        if ($expense->isCancelled()) {
            return back()->with('error', 'Cancelled expenses cannot be marked as paid.');
        }

        $expense->update([
            'status' => Expense::STATUS_PAID,
            'paid_at' => now(),
            'paid_by' => $request->user()->id,
        ]);

        AuditLog::record($organization, 'expense.paid', $request->user(), $expense, [
            'amount' => $expense->amount,
        ]);

        Cache::forget("organization:{$organization->id}:expenses_sum");

        return back()->with('success', 'Expense marked as paid.');
    }

    public function markCancelled(Request $request, Organization $organization, Expense $expense)
    {
        abort_unless($expense->organization_id === $organization->id, 404);

        $request->validate([
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        if ($expense->isCancelled()) {
            return back()->with('error', 'Expense is already cancelled.');
        }

        $expense->update([
            'status' => Expense::STATUS_CANCELLED,
        ]);

        AuditLog::record($organization, 'expense.cancelled', $request->user(), $expense, [
            'amount' => $expense->amount,
            'reason' => $request->input('cancellation_reason'),
        ]);

        Cache::forget("organization:{$organization->id}:expenses_sum");

        return back()->with('success', 'Expense cancelled.');
    }

    public function restore(Request $request, Organization $organization, Expense $expense)
    {
        abort_unless($expense->organization_id === $organization->id, 404);

        if (! $expense->isCancelled()) {
            return back()->with('error', 'Only cancelled expenses can be restored.');
        }

        $expense->update([
            'status' => Expense::STATUS_PENDING,
            'paid_at' => null,
            'paid_by' => null,
        ]);

        AuditLog::record($organization, 'expense.restored', $request->user(), $expense, [
            'amount' => $expense->amount,
        ]);

        Cache::forget("organization:{$organization->id}:expenses_sum");

        return back()->with('success', 'Expense restored to pending.');
    }
}
