@extends('layouts.layout')

@section('title', 'Delinquency Report — ' . $organization->name)

@section('content')

<x-page-header
    eyebrow="Report"
    title="Delinquency"
    subtitle="Households that haven't met their monthly due."
    tone="amber"
    icon="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
    <x-slot:actions>
        <x-brand-button variant="secondary" :href="route('reports.delinquency.export.csv', $organization)" icon="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m4 0V7a2 2 0 00-2-2H9a2 2 0 00-2 2v10a2 2 0 002 2h6a2 2 0 002-2z">Export CSV</x-brand-button>
    </x-slot:actions>
</x-page-header>

{{-- Stat tiles --}}
<div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-5">
    <x-stat-card
        label="Delinquent"
        :value="number_format($delinquentCount)"
        :hint="$totalHouseholds > 0 ? 'Of '.$totalHouseholds.' households' : 'No households yet'"
        :tone="$delinquentCount > 0 ? 'rose' : 'emerald'"
        icon="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />

    <x-stat-card
        label="Amount due"
        :value="'₱'.number_format($totalDue, 0)"
        hint="Total outstanding"
        :tone="$totalDue > 0 ? 'amber' : 'emerald'"
        icon="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />

    <x-stat-card
        label="Healthy"
        :value="$healthyPct.'%'"
        hint="Households on track"
        :tone="$healthyPct >= 80 ? 'emerald' : ($healthyPct >= 50 ? 'amber' : 'rose')"
        icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
</div>

{{-- Period picker --}}
<div class="mt-6">
    <form method="GET" action="{{ route('reports.delinquency.index', $organization) }}"
          class="flex flex-wrap items-end gap-3 rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm px-5 py-4">
        <div>
            <label for="month" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Month</label>
            <select name="month" id="month" class="text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                @for ($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ $month == $i ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($i)->format('F') }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label for="year" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Year</label>
            <input type="number" name="year" id="year" value="{{ $year }}" min="2000" max="{{ now()->year + 1 }}"
                   class="w-24 text-sm font-bold bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-brand-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-brand-500 transition">
            Update
        </button>
        <span class="ml-auto text-xs text-slate-500">{{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}</span>
    </form>
</div>

{{-- Table --}}
<div class="mt-6">
    @if(empty($rows))
        <x-empty-state
            tone="emerald"
            icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
            title="No delinquents this period"
            subtitle="Every household has paid their monthly due. Keep it up!" />
    @else
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
                <span class="text-xs text-slate-500">Ranked by amount due, highest first</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr class="h-12">
                            <th class="th-cell">Household</th>
                            <th class="th-cell">Block / Lot</th>
                            <th class="th-cell">Months Missing</th>
                            <th class="th-cell">Latest Payment</th>
                            <th class="th-cell text-right">Amount Due</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach(collect($rows)->sortByDesc('amount_due') as $row)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="td-cell">
                                    <div class="flex items-center gap-3">
                                        <span class="flex-shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-full bg-gradient-to-br from-rose-500 to-red-600 text-white text-xs font-bold uppercase">
                                            {{ strtoupper(substr($row['household']->name ?? '?', 0, 1)) }}
                                        </span>
                                        <span class="text-sm font-semibold text-slate-900 capitalize">{{ $row['household']->name }}</span>
                                    </div>
                                </td>
                                <td class="td-cell">
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-status-chip tone="slate">Block {{ $row['household']->block }}</x-status-chip>
                                        <x-status-chip tone="slate">Lot {{ $row['household']->lot }}</x-status-chip>
                                    </span>
                                </td>
                                <td class="td-cell">
                                    @foreach($row['months_missing'] as $missing)
                                        <x-status-chip tone="amber" class="mr-1 mb-1">{{ $missing }}</x-status-chip>
                                    @endforeach
                                </td>
                                <td class="td-cell text-slate-500">
                                    {{ optional($row['latest_payment_date'])->format('M d, Y') ?? '—' }}
                                </td>
                                <td class="td-cell text-right font-extrabold text-rose-700 tabular-nums whitespace-nowrap">
                                    ₱{{ number_format($row['amount_due'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<style>
    .th-cell { @apply px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider; }
    .td-cell { @apply px-5 py-3.5 text-sm; }
</style>

@endsection