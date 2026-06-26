@extends('layouts.layout')

@section('title', 'Households')

@section('content')

<x-page-header
    eyebrow="Members"
    :title="'Households'"
    subtitle="Every unit in your community — search by name, block, or lot."
    tone="violet"
    icon="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3">
    @auth
        <x-slot:actions>
            <x-brand-button variant="secondary" :href="route('households.import.create', $organization->slug)" icon="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">Import CSV</x-brand-button>
            <x-brand-button variant="primary" :href="route('households.create', $organization->slug)" icon="M12 4v16m8-8H4">Add household</x-brand-button>
        </x-slot:actions>
    @endauth
</x-page-header>

{{-- Stat tiles --}}
@php
    $totalHouseholds = $households->total();
    $blockCount = \App\Models\Household::forOrganization($organization)->distinct('block')->count('block');
    $totalWithContributions = \App\Models\Household::forOrganization($organization)
        ->whereHas('contributions', fn ($q) => $q->where('verification_status', 'verified'))
        ->count();
@endphp
<div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-5">
    <x-stat-card label="Households" :value="number_format($totalHouseholds)" hint="Total registered" tone="violet" icon="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3" />
    <x-stat-card label="Blocks" :value="number_format($blockCount)" hint="Distinct blocks" tone="brand" icon="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
    <x-stat-card label="Active payers" :value="number_format($totalWithContributions)" hint="With verified contributions" tone="emerald" icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
</div>

{{-- Search/filter bar --}}
<div class="mt-6">
    <form method="GET" action="{{ route('home', $organization->slug) }}" class="flex flex-col sm:flex-row sm:items-end gap-3 rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm px-5 py-4">
        <div class="flex-1">
            <label for="search" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Search</label>
            <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="q" id="search" value="{{ request('q') }}"
                       placeholder="Search by name, block, or lot…"
                       class="w-full pl-10 pr-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
            </div>
        </div>
        <div class="flex items-center gap-2">
            <x-brand-button type="submit" variant="primary" icon="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z">Search</x-brand-button>
        </div>
    </form>
</div>

{{-- Table / empty state --}}
<div class="mt-6">
    @if($households->isEmpty())
        <x-empty-state
            tone="violet"
            icon="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3"
            title="No households yet"
            subtitle="Add the first household to start collecting dues.">
            <x-slot:action>
                <x-brand-button variant="primary" :href="route('households.create', $organization->slug)" icon="M12 4v16m8-8H4">Add household</x-brand-button>
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100 bg-slate-50">
                <span class="text-xs text-slate-500">{{ $households->total() }} household{{ $households->total() !== 1 ? 's' : '' }}</span>
                <span class="text-xs font-semibold text-slate-700">Page {{ $households->currentPage() }} of {{ $households->lastPage() }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr class="h-12">
                            <th class="th-cell">Household</th>
                            <th class="th-cell">Block</th>
                            <th class="th-cell">Lot</th>
                            <th class="th-cell text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($households as $household)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="td-cell">
                                    <a href="{{ route('households.show', [$organization, $household]) }}" class="flex items-center gap-3 group">
                                        <span class="flex-shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-full bg-gradient-to-br from-brand-500 to-violet-600 text-white text-xs font-bold uppercase shadow-sm">
                                            {{ strtoupper(substr($household->name, 0, 1)) }}
                                        </span>
                                        <span class="text-sm font-semibold text-slate-900 capitalize group-hover:text-brand-600 transition">
                                            {{ $household->name }}
                                        </span>
                                    </a>
                                </td>
                                <td class="td-cell">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 ring-1 ring-slate-200">Block {{ $household->block }}</span>
                                </td>
                                <td class="td-cell text-slate-700 font-medium">Lot {{ $household->lot }}</td>
                                <td class="td-cell text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <a href="{{ route('households.show', [$organization, $household]) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-brand-50 text-brand-700 hover:bg-brand-100 ring-1 ring-brand-200 transition">
                                            View
                                        </a>
                                        @auth
                                            @if($navContext->isAdmin ?? false)
                                                <form action="{{ route('households.destroy', [$organization, $household]) }}" method="POST" class="inline"
                                                      onsubmit="return confirm('Delete this household? This action cannot be undone.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 ring-1 ring-rose-200 transition">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        @endauth
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($households->hasPages())
                <div class="px-5 py-4 border-t border-slate-100">
                    {{ $households->links() }}
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