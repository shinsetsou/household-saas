@extends('layouts.layout')

@section('title', 'Expense Report — ' . $organization->name)

@section('content')

<x-page-header
    eyebrow="Report"
    title="Expenses"
    subtitle="Track every peso your HOA has spent."
    tone="rose"
    icon="M9 14l-4-4 4-4m6 8l4-4-4-4">
    <x-slot:actions>
        <x-brand-button variant="secondary" :href="route('reports.expenses.export.pdf', $organization)" icon="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">PDF</x-brand-button>
        <x-brand-button variant="secondary" :href="route('reports.expenses.export.excel', $organization)" icon="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m4 0V7a2 2 0 00-2-2H9a2 2 0 00-2 2v10a2 2 0 002 2h6a2 2 0 002-2z">Excel</x-brand-button>
    </x-slot:actions>
</x-page-header>

{{-- Stat tiles --}}
<div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-5">
    <x-stat-card label="Filtered total" :value="'₱'.number_format($filteredTotal, 0)" hint="Matching the filters" tone="rose" icon="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
    <x-stat-card label="Total entries" :value="number_format($filteredCount)" hint="Expense records" tone="slate" icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
    <x-stat-card label="Year-to-date" :value="'₱'.number_format($ytdTotal, 0)" hint="Spent in {{ now()->year }}" tone="violet" icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
</div>

{{-- 12-month trend --}}
<div class="mt-6 rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm p-6">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Last 12 months</h2>
            <p class="text-xs text-slate-500">Total expenses per month.</p>
        </div>
    </div>
    <div class="flex items-end gap-2 h-40">
        @foreach($monthly as $point)
            @php $height = max(2, (int) round($point['total'] / $monthlyMax * 100)); @endphp
            <div class="flex-1 flex flex-col items-center justify-end h-full">
                <span class="text-[10px] font-semibold text-slate-500 mb-1">₱{{ number_format($point['total'] / 1000, 1) }}k</span>
                <div class="w-full rounded-t-lg bg-gradient-to-t from-rose-500 to-red-500 shadow-sm hover:opacity-80 transition" style="height: {{ $height }}%"></div>
                <span class="mt-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">{{ $point['label'] }}</span>
            </div>
        @endforeach
    </div>
</div>

{{-- Filters + table --}}
<div class="mt-6 rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <form method="GET" action="{{ route('reports.expenses.index', $organization) }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="month" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Month</label>
                <select name="month" id="month" class="text-xs text-slate-700 bg-white border border-slate-200 rounded-md px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">All</option>
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($i)->format('M') }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label for="year" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Year</label>
                <input type="number" name="year" id="year" value="{{ $year }}" min="2000" max="{{ now()->year + 1 }}"
                       class="w-20 text-xs font-bold bg-white border border-slate-200 rounded-md px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-brand-500">
            </div>
            <button type="submit" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Apply</button>
            @if(request('month') || request('year'))
                <a href="{{ route('reports.expenses.index', $organization) }}" class="text-xs text-slate-500 hover:text-slate-700">Clear</a>
            @endif
        </form>
        <span class="text-xs font-semibold text-slate-700">Total: ₱{{ number_format($filteredTotal, 2) }}</span>
    </div>

    @if($expenses->isEmpty())
        <x-empty-state
            tone="rose"
            icon="M9 14l-4-4 4-4m6 8l4-4-4-4"
            title="No expenses found"
            subtitle="Try adjusting your filters." />
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr class="h-12">
                        <th class="th-cell">Date</th>
                        <th class="th-cell">Description</th>
                        <th class="th-cell text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($expenses as $expense)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="td-cell text-slate-600 whitespace-nowrap">{{ $expense->created_at->format('M d, Y') }}</td>
                            <td class="td-cell">
                                <span class="inline-flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-rose-100 text-rose-600">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                    </span>
                                    <span class="text-sm font-medium text-slate-800">{{ $expense->description }}</span>
                                </span>
                            </td>
                            <td class="td-cell text-right font-bold text-rose-700 tabular-nums whitespace-nowrap">-₱{{ number_format($expense->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $expenses->links() }}
        </div>
    @endif
</div>

<style>
    .th-cell { @apply px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider; }
    .td-cell { @apply px-5 py-3.5 text-sm; }
</style>

@endsection