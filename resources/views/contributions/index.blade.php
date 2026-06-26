@extends('layouts.layout')

@section('title', 'Contributions')

@section('content')

<x-page-header
    eyebrow="All payments"
    title="Contributions"
    subtitle="Complete record of household payments"
    tone="emerald"
    icon="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
    <x-slot:actions>
        <x-brand-button variant="secondary" :href="route('contributions.verify', $organization->slug)" icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
            Verify
        </x-brand-button>
    </x-slot:actions>
</x-page-header>

{{-- Stat tiles --}}
<div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-5">
    <x-stat-card
        label="Total verified"
        :value="'₱'.number_format($verifiedTotal, 0)"
        hint="Across all time"
        tone="emerald"
        icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
    <x-stat-card
        label="Filtered total"
        :value="'₱'.number_format($filteredTotal, 0)"
        :hint="$status ? ucfirst($status).' records' : 'All records on this page'"
        :tone="$status === 'pending' ? 'amber' : ($status === 'rejected' ? 'rose' : 'brand')"
        icon="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
    <x-stat-card
        label="Total entries"
        :value="number_format($totalCount)"
        hint="All contributions recorded"
        tone="slate"
        icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
</div>

{{-- Verification status tabs --}}
@php
    $tabs = [
        null        => ['label' => 'All',      'count' => $totalCount, 'tone' => 'slate'],
        'verified'  => ['label' => 'Verified', 'count' => (int) ($counts['verified'] ?? 0), 'tone' => 'emerald'],
        'pending'   => ['label' => 'Pending',  'count' => (int) ($counts['pending'] ?? 0),  'tone' => 'amber'],
        'rejected'  => ['label' => 'Rejected', 'count' => (int) ($counts['rejected'] ?? 0), 'tone' => 'rose'],
    ];
@endphp
<div class="mt-6">
    <nav class="inline-flex flex-wrap gap-1 p-1 rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm" aria-label="Status filter">
        @foreach($tabs as $value => $tab)
            @php $active = ($value === '' || $value === null) ? is_null($status) : $status === $value; @endphp
            <a href="{{ route('contributions.index', array_filter([$organization->slug, 'status' => $value])) }}"
               @class([
                   'inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition',
                   'bg-gradient-to-r from-brand-600 to-violet-600 text-white shadow-sm' => $active,
                   'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! $active,
               ])>
                {{ $tab['label'] }}
                @if($tab['count'] > 0)
                    <span @class([
                        'inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-xs font-bold',
                        'bg-white/20 text-white' => $active,
                        'bg-slate-100 text-slate-700' => ! $active,
                    ])>{{ $tab['count'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>
</div>

{{-- Table / empty state --}}
<div class="mt-6">
    @if($contributions->isEmpty())
        <x-empty-state
            tone="emerald"
            icon="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
            :title="$status ? 'No '.ucfirst($status).' contributions' : 'No contributions recorded'"
            :subtitle="$status ? 'Try a different tab.' : 'Contributions will appear here after households submit payments.'" />
    @else
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 bg-slate-50">
                <span class="text-xs text-slate-500">{{ $contributions->count() }} record{{ $contributions->count() !== 1 ? 's' : '' }} on this page</span>
                <span class="text-xs font-semibold text-slate-700">Total: ₱{{ number_format($filteredTotal, 2) }}</span>
            </div>

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
                            <th class="th-cell">Receipt</th>
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
                                        <span class="text-sm font-semibold text-slate-900 capitalize">
                                            {{ $contribution->household->name ?? '—' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="td-cell text-center font-bold text-slate-900 tabular-nums">
                                    ₱{{ number_format($contribution->amount, 2) }}
                                </td>
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
                                <td class="td-cell text-center text-slate-700 font-medium">
                                    {{ $contribution->billing_period_label }}
                                </td>
                                <td class="td-cell text-center text-slate-500">
                                    {{ $contribution->created_at->format('M d, Y') }}
                                </td>
                                <td class="td-cell text-center">
                                    @if($contribution->photo)
                                        <a href="{{ $contribution->photo_url }}" target="_blank"
                                           class="inline-flex items-center justify-center w-10 h-10 rounded-xl overflow-hidden ring-1 ring-slate-200 hover:ring-2 hover:ring-brand-500 transition">
                                            <img src="{{ $contribution->photo_url }}" alt="Receipt" class="w-full h-full object-cover">
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($contributions->hasPages())
                <div class="px-5 py-4 border-t border-slate-100">
                    {{ $contributions->appends(['status' => $status])->links() }}
                </div>
            @endif
        </div>
    @endif
</div>

<style>
    .th-cell { @apply px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider; }
    .td-cell { @apply px-5 py-3.5 text-sm; }
</style>

@endsection