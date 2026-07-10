@extends('layouts.layout')

@section('title', 'Expenses — ' . $organization->name)

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-xs text-slate-500 mb-4">
    <a href="{{ route('organizations.index', $organization->slug) }}" class="hover:text-brand-600 transition-colors">Dashboard</a>
    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 20 20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg>
    <span class="text-slate-700 font-medium">Expenses</span>
</nav>

{{-- Hero --}}
<x-page-header
    eyebrow="Operating costs"
    title="Expenses"
    subtitle="Track and review organization expenditures"
    tone="rose"
    icon="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
    <x-slot:actions>
        <x-brand-button variant="danger" :href="route('expenses.create', $organization->slug)" icon="M12 4v16m8-8H4">Add expense</x-brand-button>
    </x-slot:actions>
</x-page-header>

{{-- Stat tiles --}}
@php
    $hasFilter = request('month') || request('year') || request('status');
@endphp
<div class="mt-6 grid grid-cols-1 sm:grid-cols-4 gap-5">
    <x-stat-card
        :label="$hasFilter ? 'Filtered total' : 'Total expenses'"
        :value="'₱'.number_format($filteredTotal, 0)"
        :hint="$hasFilter ? 'Matching the filters below' : 'All time'"
        tone="rose"
        icon="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
    <x-stat-card
        label="Total entries"
        :value="number_format($filteredCount)"
        hint="Matching the current filters"
        tone="slate"
        icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
    <x-stat-card
        label="Outstanding"
        :value="'₱'.number_format($unpaidTotal, 0)"
        hint="Pending + unpaid"
        tone="amber"
        icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
    <x-stat-card
        label="Year-to-date"
        :value="'₱'.number_format($ytdTotal, 0)"
        hint="Spent in {{ now()->year }}"
        tone="violet"
        icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
</div>

{{-- Filters --}}
<div class="mt-6">
    <form action="{{ route('expenses.index', $organization) }}" method="GET">
        <div class="flex flex-wrap items-end gap-3 rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm px-5 py-4">
            <div>
                <label for="month" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Month</label>
                <select name="month" id="month" class="text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    <option value="">All months</option>
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($i)->format('F') }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label for="year" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Year</label>
                <select name="year" id="year" class="text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    <option value="">All years</option>
                    @for ($i = now()->year; $i >= 2000; $i--)
                        <option value="{{ $i }}" {{ request('year') == $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label for="status" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Status</label>
                <select name="status" id="status" class="text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    <option value="">All statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <x-brand-button type="submit" variant="primary" icon="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">Filter</x-brand-button>
                @if($hasFilter)
                    <a href="{{ route('expenses.index', $organization) }}" class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                        Clear
                    </a>
                @endif
            </div>

            @if($largest)
                <div class="ml-auto hidden sm:flex items-center gap-2 text-xs text-slate-500">
                    <span>Largest entry:</span>
                    <x-status-chip tone="rose" icon="M12 4v16m8-8H4">₱{{ number_format($largest->amount, 2) }} · {{ \Illuminate\Support\Str::limit($largest->description, 30) }}</x-status-chip>
                </div>
            @endif
        </div>
    </form>
</div>

{{-- Table / empty state --}}
<div class="mt-6">
    @if($expenses->isEmpty())
        <x-empty-state
            tone="rose"
            icon="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
            title="No expenses found"
            :subtitle="$hasFilter ? 'Try adjusting your filters.' : 'Add the first expense to start tracking.'">
            <x-slot:action>
                @if(! $hasFilter)
                    <x-brand-button variant="danger" :href="route('expenses.create', $organization->slug)" icon="M12 4v16m8-8H4">Add expense</x-brand-button>
                @endif
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
            <div class="flex justify-between items-center px-5 py-3 border-b border-slate-100 bg-slate-50">
                <span class="text-xs text-slate-500">{{ $expenses->total() }} expense{{ $expenses->total() !== 1 ? 's' : '' }}</span>
                <div class="flex items-center gap-2">
                    <label for="itemsPerPage" class="text-xs font-medium text-slate-500">Rows:</label>
                    <select id="itemsPerPage" onchange="updateItemsPerPage(this.value)" class="text-xs text-slate-700 bg-white border border-slate-200 rounded-md px-2 py-1 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <option value="10" {{ $itemsPerPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ $itemsPerPage == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ $itemsPerPage == 50 ? 'selected' : '' }}>50</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr class="h-12">
                            <th class="th-cell">Date</th>
                            <th class="th-cell">Description</th>
                            <th class="th-cell">Status</th>
                            <th class="th-cell text-right">Amount</th>
                            <th class="th-cell text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($expenses as $expense)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="td-cell text-slate-600 whitespace-nowrap">
                                    <div class="text-sm">{{ $expense->created_at->format('M d, Y') }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $expense->created_at->format('g:i A') }}</div>
                                </td>
                                <td class="td-cell text-slate-800">
                                    <div class="flex items-center gap-2">
                                        @if($expense->receipt)
                                            <a href="{{ $expense->receipt_url }}" target="_blank" rel="noopener" class="flex-shrink-0">
                                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-100 text-indigo-600 hover:bg-indigo-200 transition" title="View receipt">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                </span>
                                            </a>
                                        @endif
                                        <div class="min-w-0">
                                            <a href="{{ route('expenses.show', [$organization, $expense]) }}" class="text-sm font-medium text-slate-800 hover:text-indigo-600 transition-colors">
                                                {{ $expense->description }}
                                            </a>
                                            @if($expense->notes)
                                                <div class="text-[11px] text-slate-400 truncate max-w-xs" title="{{ $expense->notes }}">{{ \Illuminate\Support\Str::limit($expense->notes, 60) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="td-cell whitespace-nowrap">
                                    @if($expense->status === 'paid')
                                        <x-status-chip tone="green" icon="M5 13l4 4L19 7">Paid</x-status-chip>
                                        @if($expense->paid_at)
                                            <div class="text-[11px] text-slate-400 mt-1">{{ $expense->paid_at->format('M d') }}</div>
                                        @endif
                                    @elseif($expense->status === 'cancelled')
                                        <x-status-chip tone="slate" icon="M6 18L18 6M6 6l12 12">Cancelled</x-status-chip>
                                    @else
                                        <x-status-chip tone="amber" icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">Pending</x-status-chip>
                                    @endif
                                </td>
                                <td class="td-cell text-right font-bold text-rose-700 tabular-nums whitespace-nowrap">
                                    -₱{{ number_format($expense->amount, 2) }}
                                </td>
                                <td class="td-cell text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 justify-end">
                                        @if($expense->status === 'pending')
                                            <form action="{{ route('expenses.mark-paid', [$organization, $expense]) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition-colors" title="Mark as paid">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    Mark paid
                                                </button>
                                            </form>
                                            <form action="{{ route('expenses.cancel', [$organization, $expense]) }}" method="POST" class="inline" onsubmit="return confirm('Cancel this expense? You can restore it later.')">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors" title="Cancel expense">
                                                    Cancel
                                                </button>
                                            </form>
                                        @elseif($expense->status === 'cancelled')
                                            <form action="{{ route('expenses.restore', [$organization, $expense]) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors" title="Restore to pending">
                                                    Restore
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('expenses.show', [$organization, $expense]) }}" class="inline-flex items-center justify-center w-7 h-7 rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors" title="View details">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($expenses->hasPages())
                <div class="px-5 py-4 border-t border-slate-100">
                    {{ $expenses->appends(['itemsPerPage' => $itemsPerPage, 'month' => request('month'), 'year' => request('year'), 'status' => request('status')])->links() }}
                </div>
            @endif
        </div>
    @endif
</div>

<style>
    .th-cell { @apply px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider; }
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
