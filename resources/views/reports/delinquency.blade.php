@extends('layouts.layout')

@section('title', 'Delinquency Report')

@section('content')
<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Delinquency Report</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $organization->name }}</p>
    </div>
    <a class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600" href="{{ route('reports.delinquency.export.csv', $organization) }}">CSV</a>
</div>

<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
        <thead><tr><th class="th-cell">Household</th><th class="th-cell">Block</th><th class="th-cell">Lot</th><th class="th-cell">Months Missing</th><th class="th-cell">Amount Due</th></tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr><td class="td-cell">{{ $row['household']->name }}</td><td class="td-cell">{{ $row['household']->block }}</td><td class="td-cell">{{ $row['household']->lot }}</td><td class="td-cell">{{ implode(', ', $row['months_missing']) }}</td><td class="td-cell">&#8369;{{ number_format($row['amount_due'], 2) }}</td></tr>
            @empty
                <tr><td class="td-cell" colspan="5">No delinquent households for this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<style>
    .th-cell { @apply px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase; }
    .td-cell { @apply px-5 py-3 text-sm text-gray-700 dark:text-gray-300; }
</style>
@endsection
