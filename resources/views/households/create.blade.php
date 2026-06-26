@extends('layouts.layout')

@section('title', 'Add Household')

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-xs text-slate-500 mb-4">
    <a href="{{ route('organizations.index', $organization->slug) }}" class="hover:text-brand-600 transition-colors">Dashboard</a>
    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg>
    <a href="{{ route('home', $organization->slug) }}" class="hover:text-brand-600 transition-colors">Households</a>
    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg>
    <span class="text-slate-700 font-medium">Add household</span>
</nav>

<x-page-header
    eyebrow="Register one household"
    title="Add household"
    subtitle="Capture a single household now — or import many from a CSV later."
    tone="violet"
    icon="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3">
    <x-slot:actions>
        <x-brand-button variant="secondary" :href="route('households.import.create', $organization->slug)" icon="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">Import many</x-brand-button>
    </x-slot:actions>
</x-page-header>

<div class="mt-6 max-w-xl">
    <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3">
            <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 text-white shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3"/></svg>
            </span>
            <div>
                <h2 class="text-base font-semibold text-slate-900">Household details</h2>
                <p class="text-xs text-slate-500">Block and lot must be whole numbers.</p>
            </div>
        </div>

        <form action="{{ route('households.store', $organization) }}" method="POST"
              x-data="{ submitting: false }"
              @submit="submitting = true"
              class="px-6 py-6 space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Household name <span class="text-rose-500">*</span></label>
                <input id="name" type="text" name="name"
                       value="{{ old('name') }}"
                       placeholder="e.g. Santos Family"
                       class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:bg-white focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none"
                       required autofocus>
                <p class="mt-1.5 text-xs text-slate-500">Typically the family surname or unit identifier.</p>
                @error('name')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="block" class="block text-sm font-medium text-slate-700">Block <span class="text-rose-500">*</span></label>
                    <input id="block" type="number" name="block" min="1"
                           value="{{ old('block') }}"
                           class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:bg-white focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none"
                           required>
                    @error('block')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="lot" class="block text-sm font-medium text-slate-700">Lot <span class="text-rose-500">*</span></label>
                    <input id="lot" type="number" name="lot" min="1"
                           value="{{ old('lot') }}"
                           class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:bg-white focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none"
                           required>
                    @error('lot')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <a href="{{ route('home', $organization->slug) }}"
                   class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 transition">
                    Cancel
                </a>
                <button type="submit" :disabled="submitting"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-brand-600 to-violet-600 text-white shadow-sm hover:from-brand-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition disabled:opacity-60 disabled:cursor-not-allowed flex-1">
                    <span x-show="!submitting">Add household</span>
                    <span x-show="submitting" x-cloak>Adding…</span>
                </button>
            </div>
        </form>
    </div>
</div>

@endsection