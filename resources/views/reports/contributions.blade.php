@extends('layouts.layout')

@section('title', 'Contribution Report — ' . $organization->name)

@section('content')

<x-page-header
    eyebrow="Report"
    title="Contributions"
    subtitle="Detailed report of every contribution across your organization."
    tone="emerald"
    icon="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
    <x-slot:actions>
        <x-brand-button variant="secondary" :href="route('reports.contributions.export.pdf', $organization)" icon="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">PDF</x-brand-button>
        <x-brand-button variant="secondary" :href="route('reports.contributions.export.excel', $organization)" icon="M9 17v-2a2 2 0 012-2h2a2 2 0 012 2v2m4 0V7a2 2 0 00-2-2H9a2 2 0 00-2 2v10a2 2 0 002 2h6a2 2 0 002-2z">Excel</x-brand-button>
    </x-slot:actions>
</x-page-header>

{{-- Stat tiles --}}
<div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <x-stat-card label="Verified total" :value="'₱'.number_format($verifiedTotal, 0)" hint="Filtered total" tone="emerald" icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
    <x-stat-card label="Verified count" :value="number_format($verifiedCount)" hint="Approved contributions" tone="brand" icon="M5 13l4 4L19 7" />
    <x-stat-card label="Pending review" :value="number_format($pendingCount)" hint="Awaiting treasurer" :tone="$pendingCount > 0 ? 'amber' : 'slate'" icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
    <x-stat-card label="Rejected" :value="number_format($rejectedCount)" hint="Disproved submissions" :tone="$rejectedCount > 0 ? 'rose' : 'slate'" icon="M6 18L18 6M6 6l12 12" />
</div>

{{-- 12-month trend --}}
<div class="mt-6 rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm p-6">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Last 12 months</h2>
            <p class="text-xs text-slate-500">Verified contributions per month.</p>
        </div>
    </div>
    <div class="flex items-end gap-2 h-40">
        @foreach($monthly as $point)
            @php $height = max(2, (int) round($point['total'] / $monthlyMax * 100)); @endphp
            <div class="flex-1 flex flex-col items-center justify-end h-full">
                <span class="text-[10px] font-semibold text-slate-500 mb-1">₱{{ number_format($point['total'] / 1000, 1) }}k</span>
                <div class="w-full rounded-t-lg bg-gradient-to-t from-emerald-500 to-green-500 shadow-sm hover:opacity-80 transition" style="height: {{ $height }}%"></div>
                <span class="mt-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">{{ $point['label'] }}</span>
            </div>
        @endforeach
    </div>
</div>

{{-- Filters + table --}}
<div class="mt-6 rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <form method="GET" action="{{ route('reports.contributions.index', $organization) }}" class="flex flex-wrap items-end gap-3">
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
                <a href="{{ route('reports.contributions.index', $organization) }}" class="text-xs text-slate-500 hover:text-slate-700">Clear</a>
            @endif
        </form>

        <span class="text-xs font-semibold text-slate-700">Total: ₱{{ number_format($verifiedTotal, 2) }}</span>
    </div>

    @if($contributions->isEmpty())
        <x-empty-state
            tone="emerald"
            icon="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
            title="No contributions found"
            subtitle="Try adjusting your filters." />
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr class="h-12">
                        <th class="th-cell">Household</th>
                        <th class="th-cell">Amount</th>
                        <th class="th-cell">Status</th>
                        <th class="th-cell">Verification</th>
                        <th class="th-cell">Billing Month</th>
                        <th class="th-cell">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($contributions as $contribution)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="td-cell">
                                <div class="flex items-center gap-3">
                                    <span class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-violet-600 text-white text-xs font-bold uppercase">
                                        {{ strtoupper(substr($contribution->household->name ?? '?', 0, 1)) }}
                                    </span>
                                    <span class="text-sm font-semibold text-slate-900 capitalize">{{ $contribution->household->name ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="td-cell text-center font-bold text-slate-900 tabular-nums">₱{{ number_format($contribution->amount, 2) }}</td>
                            <td class="td-cell text-center">
                                @if($contribution->status === 'paid')
                                    <x-status-chip tone="emerald" icon="M5 13l4 4L19 7">Paid</x-status-chip>
                                @elseif($contribution->status === 'lacking')
                                    <x-status-chip tone="amber" icon="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">Lacking</x-status-chip>
                                @else
                                    <x-status-chip tone="slate">{{ ucfirst($contribution->status ?? '—') }}</x-status-chip>
                                @endif
                            </td>
                            <td class="td-cell text-center">
                                @if($contribution->verification_status === 'verified')
                                    <x-status-chip tone="emerald" icon="M5 13l4 4L19 7">Verified</x-status-chip>
                                @elseif($contribution->verification_status === 'rejected')
                                    <x-status-chip tone="rose" icon="M6 18L18 6M6 6l12 12">Rejected</x-status-chip>
                                @else
                                    <x-status-chip tone="amber" icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">Pending</x-status-chip>
                                @endif
                            </td>
                            <td class="td-cell text-center text-slate-700 font-medium">{{ $contribution->billing_period_label }}</td>
                            <td class="td-cell text-center text-slate-500">{{ $contribution->created_at->format('M d, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $contributions->links() }}
        </div>
    @endif
</div>

<style>
    .th-cell { @apply px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider; }
    .td-cell { @apply px-5 py-3.5 text-sm; }
</style>

@endsection