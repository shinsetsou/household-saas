@extends('layouts.layout')

@section('title', 'Expenses — ' . $organization->name)

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-xs text-gray-500 mb-6">
    <a href="{{ route('organizations.index', $organization->slug) }}" class="hover:text-indigo-600 transition-colors">Dashboard</a>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
    <span class="text-gray-700 font-medium">Expenses</span>
</nav>

{{-- Page header --}}
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Expenses</h1>
            <p class="mt-1 text-sm text-gray-500">Track and review organization expenditures</p>
        </div>
        <a href="{{ route('expenses.create', $organization->slug) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
            </svg>
            Add Expense
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="mb-6">
    <form action="{{ route('expenses.index', $organization) }}" method="GET">
        <div class="flex flex-wrap items-end gap-3 bg-white border border-gray-200 rounded-xl px-4 py-4 shadow-sm">
            <div>
                <label for="month" class="block text-xs font-medium text-gray-600 mb-1.5">Month</label>
                <select name="month" id="month"
                        class="text-sm text-gray-700 bg-gray-50 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">All months</option>
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($i)->format('F') }}
                        </option>
                    @endfor
                </select>
            </div>
            <div>
                <label for="year" class="block text-xs font-medium text-gray-600 mb-1.5">Year</label>
                <select name="year" id="year"
                        class="text-sm text-gray-700 bg-gray-50 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">All years</option>
                    @for ($i = now()->year; $i >= 2000; $i--)
                        <option value="{{ $i }}" {{ request('year') == $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/>
                    </svg>
                    Filter
                </button>
                @if(request('month') || request('year'))
                <a href="{{ route('expenses.index', $organization) }}"
                   class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium rounded-lg text-gray-600 hover:text-gray-900 transition-colors">
                    Clear
                </a>
                @endif
            </div>
        </div>
    </form>
</div>

{{-- Table / empty state --}}
@if($expenses->isEmpty())
<div class="flex flex-col items-center justify-center py-20 bg-white border border-dashed border-gray-300 rounded-2xl">
    <span class="flex items-center justify-center w-14 h-14 rounded-2xl bg-gray-100 text-gray-400 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm4.707 3.707a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L8.414 10l1.293-1.293zm4 1.414a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L10.586 9l-1.293-1.293a1 1 0 011.414-1.414l3 3z" clip-rule="evenodd"/>
        </svg>
    </span>
    <p class="text-sm font-semibold text-gray-900 mb-1">No expenses found</p>
    <p class="text-sm text-gray-500 mb-6">
        {{ request('month') || request('year') ? 'Try adjusting your filters.' : 'Add the first expense to start tracking.' }}
    </p>
    @if(!request('month') && !request('year'))
    <a href="{{ route('expenses.create', $organization->slug) }}"
       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
        Add Expense
    </a>
    @endif
</div>
@else

<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

    {{-- Items per page --}}
    <div class="flex justify-between items-center px-5 py-3 border-b border-gray-100 bg-gray-50">
        <span class="text-xs text-gray-500">{{ $expenses->total() }} expense{{ $expenses->total() !== 1 ? 's' : '' }}</span>
        <div class="flex items-center gap-2">
            <label for="itemsPerPage" class="text-xs font-medium text-gray-500">Rows:</label>
            <select id="itemsPerPage" onchange="updateItemsPerPage(this.value)"
                    class="text-xs text-gray-700 bg-white border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <option value="10" {{ $itemsPerPage == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ $itemsPerPage == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $itemsPerPage == 50 ? 'selected' : '' }}>50</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr class="h-12">
                    <th class="th-cell">Date</th>
                    <th class="th-cell">Amount</th>
                    <th class="th-cell">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($expenses as $expense)
                <tr class="h-12 hover:bg-gray-50 transition-colors">
                    <td class="td-cell text-center text-gray-600 whitespace-nowrap">
                        {{ $expense->created_at->format('M d, Y') }}
                    </td>
                    <td class="td-cell text-center font-semibold text-red-600 whitespace-nowrap">
                        &#8369;{{ number_format($expense->amount, 2) }}
                    </td>
                    <td class="td-cell text-center text-gray-700">
                        {{ $expense->description }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($expenses->hasPages())
    <div class="px-5 py-4 border-t border-gray-100">
        {{ $expenses->appends(['itemsPerPage' => $itemsPerPage, 'month' => request('month'), 'year' => request('year')])->links() }}
    </div>
    @endif

</div>
@endif

<style>
    .th-cell { @apply px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider; }
    .td-cell { @apply px-5 py-3.5 text-sm; }
</style>

<script>
    function updateItemsPerPage(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('itemsPerPage', value);
        window.location.href = url.toString();
    }
</script>

@endsection
