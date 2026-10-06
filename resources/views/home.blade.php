@extends('layouts.layout')

@section('title', $organization->name . ' — Household Lookup')

@section('content')

@php
    $foundHousehold = null;
    $searched = request()->filled('block') && request()->filled('lot');

    if ($searched) {
        $foundHousehold = \App\Models\Household::where('organization_id', $organization->id)
            ->where('block', request('block'))
            ->where('lot', request('lot'))
            ->first();
    }
@endphp

@auth
    <nav class="flex items-center gap-2 text-xs text-slate-500 mb-4">
        <a href="{{ route('organizations.index', $organization->slug) }}" class="hover:text-brand-600 transition-colors">Dashboard</a>
        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg>
        <span class="text-slate-700 font-medium">Household lookup</span>
    </nav>
@endauth

<x-page-header
    :eyebrow="$organization->name"
    title="Find your household"
    subtitle="Enter your block and lot number to look up your record and submit a payment."
    tone="violet"
    icon="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z">
    @auth
        <x-slot:actions>
            <x-brand-button variant="secondary" :href="route('organizations.index', $organization->slug)" icon="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3">Dashboard</x-brand-button>
        </x-slot:actions>
    @endauth
</x-page-header>

<div class="mt-8 max-w-2xl mx-auto space-y-6">

    {{-- Search card --}}
    <div class="rounded-2xl bg-white dark:bg-gray-800 ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex items-center gap-3">
            <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 text-white shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Household lookup</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Your household record is one search away.</p>
            </div>
        </div>

        <form action="{{ '/' . $organization->slug }}" method="GET" class="px-6 py-6 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="block" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Block number</label>
                    <input type="number" name="block" id="block" placeholder="e.g. 3" min="1" required
                           value="{{ request('block') }}"
                           class="mt-2 block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-4 py-3 text-base font-semibold focus:bg-white dark:focus:bg-slate-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
                </div>
                <div>
                    <label for="lot" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Lot number</label>
                    <input type="number" name="lot" id="lot" placeholder="e.g. 12" min="1" required
                           value="{{ request('lot') }}"
                           class="mt-2 block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-4 py-3 text-base font-semibold focus:bg-white dark:focus:bg-slate-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
                </div>
            </div>

            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-violet-600 px-4 py-3 text-sm font-semibold text-white shadow-brand-glow hover:from-brand-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Find my household
            </button>

            <p class="text-center text-xs text-slate-400 dark:text-slate-500">
                Both block and lot are required. Don't know yours? Check your move-in documents or ask your treasurer.
            </p>
        </form>
    </div>

    {{-- Search result --}}
    @if($searched)
        @if($foundHousehold)
            <div class="rounded-2xl bg-gradient-to-br from-brand-50 to-violet-50 dark:from-brand-900/20 dark:to-violet-900/20 ring-1 ring-brand-200 dark:ring-brand-800 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-brand-200 dark:border-brand-800 flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 text-white text-lg font-bold uppercase shadow-sm">
                        {{ strtoupper(substr($foundHousehold->name, 0, 1)) }}
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ $foundHousehold->name }}</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-brand-100 text-brand-700 ring-1 ring-brand-200">Block {{ $foundHousehold->block }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-brand-100 text-brand-700 ring-1 ring-brand-200 ml-1">Lot {{ $foundHousehold->lot }}</span>
                        </p>
                    </div>
                </div>

                <div class="px-6 py-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <p class="text-sm text-slate-700 dark:text-slate-200">
                        Ready to record a payment? Click below to upload your receipt.
                    </p>
                    <a href="{{ route('contributions.create', [$organization, $foundHousehold]) }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-violet-600 px-5 py-3 text-sm font-semibold text-white shadow-brand-glow hover:from-brand-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Submit payment
                    </a>
                </div>
            </div>
        @else
            <div class="rounded-2xl bg-white dark:bg-gray-800 ring-1 ring-rose-200 dark:ring-rose-800 shadow-sm overflow-hidden">
                <div class="px-6 py-5 flex items-start gap-3">
                    <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-xl bg-rose-100 text-rose-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">No household found</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                            We couldn't find a household at
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono text-xs">Block {{ request('block') }}</span>
                            Lot
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono text-xs">{{ request('lot') }}</span>.
                            Double-check your numbers or contact your treasurer.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    @endif

    @guest
        <div class="rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800/50 dark:to-slate-900/50 ring-1 ring-slate-200 dark:ring-slate-700 p-5">
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Treasurer or officer?</p>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Sign in</a>
                        to access the admin dashboard, contribution records, and reports.
                    </p>
                </div>
            </div>
        </div>
    @endguest

    {{-- Manual payment instructions --}}
    @if(filled($organization->manual_payment_instructions))
        <div class="rounded-2xl bg-white dark:bg-gray-800 ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex items-center gap-3">
                <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
                <div>
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">How to pay dues</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Use these details when sending your payment.</p>
                </div>
            </div>
            <div class="px-6 py-5">
                <p class="whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-200">{{ $organization->manual_payment_instructions }}</p>
            </div>
        </div>
    @endif
</div>

@endsection