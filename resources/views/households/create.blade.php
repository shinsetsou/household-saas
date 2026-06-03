@extends('layouts.layout')

@section('title', 'Add Household')

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-xs text-gray-500 mb-6">
    @if(isset($organization))
    <a href="{{ route('organizations.index', $organization->slug) }}" class="hover:text-indigo-600 transition-colors">Dashboard</a>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
    @endif
    <span class="text-gray-700 font-medium">Add Household</span>
</nav>

<div class="flex justify-between">
<div class="max-w-lg">
    <div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add Households</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">add household one at a time.</p>
</div>
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

        {{-- Card header --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-indigo-100 text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                    </svg>
                </span>
                <div>
                    <h1 class="text-base font-semibold text-gray-900">Add Household</h1>
                    <p class="text-xs text-gray-500">Register a new household in the system</p>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ route('households.store', $organization) }}" method="POST" id="household-form" class="px-6 py-6 space-y-5">
            @csrf

            <div>
                <label for="name" class="form-label">
                    Household Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name"
                       value="{{ old('name') }}"
                       placeholder="e.g. Santos Family"
                       class="mt-1.5 form-input @error('name') form-input-error @enderror"
                       required>
                <p class="mt-1.5 text-xs text-gray-500">Typically the family surname or unit identifier.</p>
                @error('name')
                    <p class="mt-1.5 error-msg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="block" class="form-label">Block <span class="text-red-500">*</span></label>
                    <input type="number" id="block" name="block" value="{{ old('block') }}" min="1"
                           class="mt-1.5 form-input @error('block') form-input-error @enderror" required>
                    @error('block') <p class="mt-1.5 error-msg">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="lot" class="form-label">Lot <span class="text-red-500">*</span></label>
                    <input type="number" id="lot" name="lot" value="{{ old('lot') }}" min="1"
                           class="mt-1.5 form-input @error('lot') form-input-error @enderror" required>
                    @error('lot') <p class="mt-1.5 error-msg">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 pt-2">
                @if(isset($organization))
                <a href="{{ route('home', $organization->slug) }}"
                   class="flex-1 inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                @endif
                <button type="submit" id="submit-btn"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 active:bg-indigo-800 transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                    <span id="submit-label">Add Household</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="max-w-lg">
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Import Households</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Upload a CSV with name, block, and lot columns.</p>
</div>

<div class="max-w-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm px-6 py-6">
    <form action="{{ route('households.import.store', $organization) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        <div>
            <label for="csv" class="block text-sm font-medium text-gray-700 dark:text-gray-300">CSV file</label>
            <input id="csv" name="csv" type="file" accept=".csv,text/csv"
                   class="mt-2 block w-full text-sm text-gray-700 dark:text-gray-300">
            @error('csv')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Sample format</p>
                <a href="{{ route('households.import.sample', $organization) }}"
                   class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Download sample CSV</a>
            </div>
            <pre class="mt-2 overflow-x-auto rounded bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 px-3 py-2 text-xs text-gray-700 dark:text-gray-300"><code>name,block,lot
Dela Cruz Residence,1,1
Reyes Residence,1,2
Santos Residence,2,5</code></pre>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">First row must be the header. <code>block</code> and <code>lot</code> must be whole numbers.</p>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('households.index', $organization) }}" class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600">Cancel</a>
            <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 text-white">Import</button>
        </div>
    </form>
</div>
</div>
</div>

<style>
    .form-label { @apply block text-sm font-medium text-gray-700 }
    .form-input {
        @apply w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition;
    }
    .form-input-error { @apply border-red-400 focus:ring-red-500; }
    .error-msg { @apply flex items-center gap-1 text-xs text-red-600 }
</style>

<script>
    document.getElementById('household-form').addEventListener('submit', function () {
        const btn = document.getElementById('submit-btn');
        const label = document.getElementById('submit-label');
        btn.disabled = true;
        label.textContent = 'Adding…';
    });
</script>

@endsection
