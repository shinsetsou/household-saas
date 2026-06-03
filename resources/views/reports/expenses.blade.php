@extends('layouts.layout')

@section('title', 'Expense Report')

@section('content')
<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Expense Report</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $organization->name }}</p>
    </div>
    <div class="flex gap-2">
        <a class="btn-secondary" href="{{ route('reports.expenses.export.pdf', $organization) }}">PDF</a>
        <a class="btn-secondary" href="{{ route('reports.expenses.export.excel', $organization) }}">Excel</a>
    </div>
</div>

<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
        <thead><tr><th class="th-cell">Description</th><th class="th-cell">Amount</th><th class="th-cell">Date</th></tr></thead>
        <tbody>
            @foreach($expenses as $expense)
                <tr><td class="td-cell">{{ $expense->description }}</td><td class="td-cell">&#8369;{{ number_format($expense->amount, 2) }}</td><td class="td-cell">{{ $expense->created_at->format('Y-m-d') }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>

<style>
    .th-cell { @apply px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase; }
    .td-cell { @apply px-5 py-3 text-sm text-gray-700 dark:text-gray-300; }
    .btn-secondary { @apply px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600; }
</style>
@endsection
