@extends('layouts.layout')

@section('title', $household->name)

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-xs text-slate-500 mb-4">
    <a href="{{ route('organizations.index', $organization->slug) }}" class="hover:text-brand-600 transition-colors">Dashboard</a>
    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg>
    <a href="{{ route('home', $organization->slug) }}" class="hover:text-brand-600 transition-colors">Households</a>
    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg>
    <span class="text-slate-700 font-medium">{{ $household->name }}</span>
</nav>

<x-page-header
    eyebrow="Household"
    :title="$household->name"
    subtitle="Block {{ $household->block }} · Lot {{ $household->lot }}"
    tone="violet"
    icon="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3">
    <x-slot:actions>
        <x-brand-button variant="secondary" :href="route('home', $organization->slug)" icon="M9 707l6-6m-6 6l-6-6m6 6l-6 6m6-6l6-6">Back</x-brand-button>
        <x-brand-button variant="primary" :href="route('contributions.create', [$organization, $household])" icon="M12 4v16m8-8H4">Record payment</x-brand-button>
    </x-slot:actions>
</x-page-header>

{{-- Stat tiles --}}
@php
    $lastPaidAt = $lastPaidAt instanceof \Illuminate\Support\Carbon ? $lastPaidAt : ($lastPaidAt ? \Illuminate\Support\Carbon::parse($lastPaidAt) : null);
    $lastPaidHuman = $lastPaidAt ? $lastPaidAt->diffForHumans() : 'Never';
@endphp
<div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-5">
    <x-stat-card
        label="Total paid"
        :value="'₱'.number_format($totalContributions, 0)"
        hint="All-time contributions"
        tone="emerald"
        icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />

    <x-stat-card
        label="Verified payments"
        :value="number_format($verifiedCount)"
        :hint="$pendingCount > 0 ? $pendingCount.' pending review' : 'All caught up'"
        tone="brand"
        icon="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />

    <x-stat-card
        label="Last payment"
        :value="$lastPaidAt ? $lastPaidAt->format('M j, Y') : '—'"
        :hint="$lastPaidAt ? $lastPaidHuman : 'No payments recorded'"
        tone="violet"
        icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
</div>

{{-- Contribution history --}}
<div class="mt-6 rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <div>
                <h2 class="text-base font-semibold text-slate-900">Contribution history</h2>
                <p class="text-xs text-slate-500">{{ $contributions->total() }} record{{ $contributions->total() !== 1 ? 's' : '' }}</p>
            </div>
        </div>
    </div>

    @if($contributions->isEmpty())
        <x-empty-state
            tone="violet"
            icon="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
            title="No contributions yet"
            subtitle="Record the first payment for this household.">
            <x-slot:action>
                <x-brand-button variant="primary" :href="route('contributions.create', [$organization, $household])" icon="M12 4v16m8-8H4">Record payment</x-brand-button>
            </x-slot:action>
        </x-empty-state>
    @else

    <div class="flex justify-end items-center gap-2 px-5 py-3 border-b border-slate-100 bg-slate-50">
        <label for="itemsPerPage" class="text-xs font-medium text-slate-500">Rows per page:</label>
        <select id="itemsPerPage" onchange="updateItemsPerPage(this.value)"
                class="text-xs text-slate-700 bg-white border border-slate-200 rounded-md px-2 py-1 focus:outline-none focus:ring-1 focus:ring-brand-500">
            <option value="10" {{ $itemsPerPage == 10 ? 'selected' : '' }}>10</option>
            <option value="25" {{ $itemsPerPage == 25 ? 'selected' : '' }}>25</option>
            <option value="50" {{ $itemsPerPage == 50 ? 'selected' : '' }}>50</option>
        </select>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="th-cell">Date</th>
                    <th class="th-cell">Amount</th>
                    <th class="th-cell">Status</th>
                    <th class="th-cell">Verification</th>
                    <th class="th-cell">Billing Month</th>
                    <th class="th-cell">Receipt</th>
                    @auth
                    <th class="th-cell text-center">Actions</th>
                    @endauth
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($contributions as $contribution)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="td-cell text-slate-600 text-center">
                            {{ $contribution->created_at->format('M d, Y') }}
                        </td>
                        <td class="td-cell font-bold text-slate-900 text-center tabular-nums">
                            ₱{{ number_format($contribution->amount, 2) }}
                        </td>
                        <td class="td-cell text-center">
                            @if($contribution->status === 'paid')
                                <x-status-chip tone="emerald" icon="M5 13l4 4L19 7">Paid</x-status-chip>
                            @elseif($contribution->status === 'lacking')
                                <x-status-chip tone="amber" icon="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">Lacking</x-status-chip>
                            @else
                                <x-status-chip tone="slate">{{ ucfirst($contribution->status) }}</x-status-chip>
                            @endif
                        </td>
                        <td class="td-cell text-center">
                            @if($contribution->admin_verified == 1)
                                <x-status-chip tone="emerald" icon="M5 13l4 4L19 7">Verified</x-status-chip>
                            @elseif($contribution->admin_verified == 3)
                                <x-status-chip tone="rose" icon="M6 18L18 6M6 6l12 12">Disproved</x-status-chip>
                            @else
                                <x-status-chip tone="amber" icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">Pending</x-status-chip>
                            @endif
                        </td>
                        <td class="td-cell text-center text-slate-700 font-medium">{{ $contribution->billing_period_label }}</td>
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
                        @auth
                        <td class="td-cell text-center">
                            <form action="{{ route('contributions.destroy', [$organization, $household, $contribution]) }}" method="POST"
                                  onsubmit="return confirm('Delete this contribution? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 ring-1 ring-rose-200 transition">
                                    Delete
                                </button>
                            </form>
                        </td>
                        @endauth
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($contributions->hasPages())
    <div class="px-5 py-4 border-t border-slate-100">
        {{ $contributions->appends(['itemsPerPage' => $itemsPerPage])->links() }}
    </div>
    @endif

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