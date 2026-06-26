@extends('layouts.layout')

@section('title', 'Import Households')

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-xs text-slate-500 mb-4">
    <a href="{{ route('organizations.index', $organization->slug) }}" class="hover:text-brand-600 transition-colors">Dashboard</a>
    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg>
    <a href="{{ route('home', $organization->slug) }}" class="hover:text-brand-600 transition-colors">Households</a>
    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg>
    <span class="text-slate-700 font-medium">Import CSV</span>
</nav>

<x-page-header
    eyebrow="Bulk import"
    title="Import households"
    subtitle="Upload a CSV with name, block, and lot columns. Duplicates are skipped."
    tone="violet"
    icon="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
    <x-slot:actions>
        <x-brand-button variant="secondary" :href="route('households.create', $organization->slug)" icon="M12 4v16m8-8H4">Add one</x-brand-button>
    </x-slot:actions>
</x-page-header>

<div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3">
                <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 text-white shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Upload your CSV</h2>
                    <p class="text-xs text-slate-500">CSV files only. We'll validate the header and skip duplicates.</p>
                </div>
            </div>

            <form action="{{ route('households.import.store', $organization) }}" method="POST" enctype="multipart/form-data"
                  x-data="{ filename: '', dragOver: false, submitting: false }"
                  @submit="submitting = true"
                  class="px-6 py-6 space-y-5">
                @csrf

                <label
                    @dragover.prevent="dragOver = true"
                    @dragleave="dragOver = false"
                    @drop.prevent="dragOver = false; $event.target.querySelector('input[type=file]').files = $event.dataTransfer.files; $event.target.querySelector('input[type=file]').dispatchEvent(new Event('change'))"
                    :class="dragOver ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20' : 'border-slate-300 dark:border-slate-700'"
                    class="relative flex flex-col items-center justify-center w-full rounded-2xl border-2 border-dashed bg-white dark:bg-slate-900 px-6 py-12 text-center transition cursor-pointer">
                    <svg class="w-12 h-12 text-brand-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                        <span class="text-brand-600">Click to upload</span> or drag and drop
                    </p>
                    <p class="mt-1 text-xs text-slate-500">CSV files only</p>
                    <p x-show="filename" x-cloak class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-mono bg-slate-100 text-slate-700">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="filename"></span>
                    </p>
                    <input type="file" name="csv" accept=".csv,text/csv" required
                           @change="filename = $event.target.files[0]?.name || ''"
                           class="absolute inset-0 opacity-0 cursor-pointer">
                </label>

                @error('csv')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror

                <div class="flex items-center gap-3">
                    <a href="{{ route('home', $organization->slug) }}"
                       class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </a>
                    <button type="submit" :disabled="submitting"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-brand-600 to-violet-600 text-white shadow-sm hover:from-brand-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition disabled:opacity-60 disabled:cursor-not-allowed flex-1">
                        <span x-show="!submitting">Upload and import</span>
                        <span x-show="submitting" x-cloak>Importing…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Side: format + sample --}}
    <div class="space-y-6">
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm p-6">
            <h2 class="text-base font-semibold text-slate-900">CSV format</h2>
            <p class="mt-1 text-xs text-slate-500">
                Header row must be exactly
                <code class="px-1.5 py-0.5 bg-slate-100 rounded font-mono text-[11px]">name,block,lot</code>.
                Block and lot must be whole numbers.
            </p>
            <a href="{{ route('households.import.sample', $organization) }}" download
               class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:text-brand-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M4 6h16"/></svg>
                Download sample CSV
            </a>
            <div class="mt-4 rounded-lg ring-1 ring-slate-200 overflow-hidden">
                <table class="min-w-full text-xs">
                    <thead class="bg-slate-100">
                        <tr class="text-slate-600">
                            <th class="text-left px-3 py-2 font-semibold">name</th>
                            <th class="text-left px-3 py-2 font-semibold">block</th>
                            <th class="text-left px-3 py-2 font-semibold">lot</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white text-slate-700">
                        <tr class="border-t border-slate-200"><td class="px-3 py-2">Dela Cruz Residence</td><td class="px-3 py-2">1</td><td class="px-3 py-2">1</td></tr>
                        <tr class="border-t border-slate-200"><td class="px-3 py-2">Reyes Residence</td><td class="px-3 py-2">1</td><td class="px-3 py-2">2</td></tr>
                        <tr class="border-t border-slate-200"><td class="px-3 py-2">Santos Residence</td><td class="px-3 py-2">2</td><td class="px-3 py-2">5</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl bg-gradient-to-br from-brand-50 to-violet-50 dark:from-brand-900/20 dark:to-violet-900/20 ring-1 ring-brand-100 dark:ring-brand-800 p-6">
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-lg bg-brand-100 text-brand-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-brand-900 dark:text-brand-100">Heads up</p>
                    <p class="mt-1 text-xs text-brand-800 dark:text-brand-200">
                        Existing households with the same block + lot will be skipped to avoid duplicates.
                        Import is also blocked if your plan has reached its household cap.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection