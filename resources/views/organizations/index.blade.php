@extends('layouts.layout')

@section('title', $organization->name . ' Dashboard')

@section('content')

@php
    $balance = $totalContributions - $totalExpenses;
    $collectionRate = $expectedMonthly > 0 ? min(100, round(($currentMonthPaid / $expectedMonthly) * 100)) : 0;
    $householdCount = $organization->households()->count();
@endphp

{{-- Trial / expired banners (preserved from original) --}}
@if($organization->isExpired())
    <div class="mb-6 rounded-2xl bg-gradient-to-r from-red-50 to-rose-50 ring-1 ring-red-200 px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-start gap-3">
            <span class="flex-shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-100 text-red-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </span>
            <div>
                <h2 class="text-sm font-semibold text-red-800">Your trial has ended</h2>
                <p class="mt-0.5 text-sm text-red-700">New contributions, expenses, and member invites are disabled until you pick a plan.</p>
            </div>
        </div>
        <x-brand-button variant="danger" href="{{ route('organizations.settings.billing.show', $organization->slug) }}">Choose a plan</x-brand-button>
    </div>
@elseif($organization->isOnTrial() && ($days = $organization->daysUntilTrialEnds()) !== null && $days <= 7)
    <div class="mb-6 rounded-2xl bg-gradient-to-r from-amber-50 to-yellow-50 ring-1 ring-amber-200 px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-start gap-3">
            <span class="flex-shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-lg bg-amber-100 text-amber-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <div>
                <h2 class="text-sm font-semibold text-amber-900">
                    @if($days === 0) Your trial ends today
                    @elseif($days === 1) Your trial ends tomorrow
                    @else Your trial ends in {{ $days }} days
                    @endif
                </h2>
                <p class="mt-0.5 text-sm text-amber-800">Pick a plan to keep your HOA's books running.</p>
            </div>
        </div>
        <x-brand-button variant="primary" href="{{ route('organizations.settings.billing.show', $organization->slug) }}">Choose a plan</x-brand-button>
    </div>
@endif

{{-- Hero --}}
<x-page-header
    :title="$organization->name"
    subtitle="Organization overview and financial summary"
    icon="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
    <x-slot:actions>
        <x-brand-button variant="secondary" :href="route('contributions.verify', $organization->slug)" icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
            Verify
            @if(($pendingVerificationCount ?? 0) > 0)
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-rose-500 text-white text-[10px] font-bold">{{ $pendingVerificationCount }}</span>
            @endif
        </x-brand-button>
        <x-brand-button variant="danger" :href="route('expenses.create', $organization->slug)" icon="M12 4v16m8-8H4">Add expense</x-brand-button>
        <x-brand-button variant="primary" :href="route('households.create', $organization->slug)" icon="M12 4v16m8-8H4">Add household</x-brand-button>
    </x-slot:actions>
</x-page-header>

{{-- Year filter --}}
<div class="mt-6 flex items-center justify-between">
    <form action="{{ route('organizations.index', $organization->slug) }}" method="GET" class="inline-flex items-center gap-2 rounded-xl bg-white ring-1 ring-slate-200 px-3 py-2 shadow-sm">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <label for="year" class="text-xs font-medium text-slate-500">Year</label>
        <input type="number" id="year" name="year" value="{{ $year }}" min="2000" max="{{ date('Y') + 1 }}" class="w-20 text-sm font-bold bg-transparent border-0 text-slate-900 focus:outline-none focus:ring-0 p-0">
        <button type="submit" class="text-xs font-semibold text-brand-600 hover:text-brand-700 transition">Apply</button>
    </form>
</div>

{{-- Stat tiles --}}
<div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <x-stat-card
        label="Households"
        :value="number_format($householdCount)"
        hint="Registered households"
        tone="violet"
        icon="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3"
        :href="route('households.index', $organization->slug)" />

    <x-stat-card
        label="Contributions {{ $year }}"
        :value="'₱'.number_format($totalContributions, 0)"
        :hint="($currentMonthPending ?? 0) > 0 ? $currentMonthPending.' pending this month' : 'All verified this month'"
        tone="emerald"
        icon="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />

    <x-stat-card
        label="Expenses {{ $year }}"
        :value="'₱'.number_format($totalExpenses, 0)"
        hint="Spent this year"
        tone="rose"
        icon="M9 14l-4-4 4-4m6 8l4-4-4-4" />

    <x-stat-card
        label="Balance"
        :value="'₱'.number_format(abs($balance), 0)"
        :hint="($balance >= 0 ? 'Surplus' : 'Deficit').' for '.$year"
        :tone="$balance >= 0 ? 'brand' : 'rose'"
        icon="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
</div>

{{-- Second row: trend + collection rate --}}
<div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- 6-month trend --}}
    <div class="lg:col-span-2 rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Verified contributions — last 6 months</h2>
                <p class="text-xs text-slate-500">Track the momentum of collection.</p>
            </div>
            <a href="{{ route('contributions.index', $organization->slug) }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all →</a>
        </div>

        <div class="flex items-end gap-3 h-40">
            @foreach($monthlyTrend as $point)
                @php
                    $height = $trendMax > 0 ? max(2, (int) round($point['total'] / $trendMax * 100)) : 2;
                @endphp
                <div class="flex-1 flex flex-col items-center justify-end h-full">
                    <span class="text-[10px] font-semibold text-slate-500 mb-1">₱{{ number_format($point['total'] / 1000, 1) }}k</span>
                    <div class="w-full rounded-t-lg bg-gradient-to-t from-brand-500 to-violet-500 shadow-sm transition-all hover:opacity-80" style="height: {{ $height }}%"></div>
                    <span class="mt-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">{{ $point['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- This month collection --}}
    <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm p-6 flex flex-col">
        <div>
            <h2 class="text-base font-semibold text-slate-900">This month</h2>
            <p class="text-xs text-slate-500">Collected vs expected</p>
        </div>
        <div class="flex-1 flex flex-col justify-center mt-4">
            <p class="text-4xl font-extrabold text-slate-900">{{ $collectionRate }}%</p>
            <p class="mt-1 text-xs text-slate-500">₱{{ number_format($currentMonthPaid, 2) }} of ₱{{ number_format($expectedMonthly, 2) }} expected</p>
        </div>
        <div class="mt-4">
            <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full {{ $collectionRate >= 90 ? 'bg-gradient-to-r from-emerald-500 to-green-500' : ($collectionRate >= 50 ? 'bg-gradient-to-r from-brand-500 to-violet-500' : 'bg-gradient-to-r from-rose-500 to-red-500') }}"
                     style="width: {{ $collectionRate }}%"></div>
            </div>
            @if($delinquentHouseholds > 0)
                <a href="{{ route('reports.delinquency.index', $organization->slug) }}" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-rose-700 hover:text-rose-800">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    {{ $delinquentHouseholds }} household{{ $delinquentHouseholds === 1 ? '' : 's' }} behind this month →
                </a>
            @endif
        </div>
    </div>
</div>

{{-- Third row: recent activity + top households --}}
<div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- Recent activity --}}
    <div class="lg:col-span-2 rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Recent activity</h2>
                <p class="text-xs text-slate-500">Last 6 contributions and expenses</p>
            </div>
        </div>

        @if($recentActivity->isEmpty())
            <x-empty-state
                icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                title="No activity yet"
                subtitle="Once households submit payments or you log expenses, they'll show up here."
                tone="slate" />
        @else
            <ul class="divide-y divide-slate-100">
                @foreach($recentActivity as $item)
                    <li class="px-6 py-3 flex items-center gap-3">
                        <span @class([
                            'flex-shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-xl',
                            'bg-emerald-100 text-emerald-700' => $item['type'] === 'contribution',
                            'bg-rose-100 text-rose-700' => $item['type'] === 'expense',
                        ])>
                            @if($item['type'] === 'contribution')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-900 truncate">{{ $item['label'] }}</p>
                            <p class="text-xs text-slate-500">{{ $item['sub'] }} · {{ $item['when']->diffForHumans() }}</p>
                        </div>
                        <span @class([
                            'text-sm font-bold tabular-nums',
                            'text-emerald-700' => $item['amount'] > 0,
                            'text-rose-700' => $item['amount'] < 0,
                        ])>
                            {{ $item['amount'] > 0 ? '+' : '' }}₱{{ number_format(abs($item['amount']), 0) }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Top contributing households --}}
    <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <h2 class="text-base font-semibold text-slate-900">Top contributors</h2>
            <p class="text-xs text-slate-500">Verified this year</p>
        </div>

        @if($topHouseholds->isEmpty() || $topHouseholds->sum('verified_total') == 0)
            <x-empty-state
                icon="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857"
                title="No verified contributions yet"
                subtitle="Once the treasurer verifies payments, top members will appear here."
                tone="emerald" />
        @else
            <ul class="divide-y divide-slate-100">
                @foreach($topHouseholds as $household)
                    <li class="px-6 py-3 flex items-center gap-3">
                        <span class="flex-shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-full bg-gradient-to-br from-brand-500 to-violet-600 text-white text-xs font-bold">
                            {{ strtoupper(substr($household->name ?? '?', 0, 1)) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-900 truncate">{{ $household->name ?? 'Unknown' }}</p>
                            <p class="text-xs text-slate-500">Block {{ $household->block }} · Lot {{ $household->lot }}</p>
                        </div>
                        <span class="text-sm font-bold text-emerald-700 tabular-nums">₱{{ number_format($household->verified_total ?? 0, 0) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

@endsection