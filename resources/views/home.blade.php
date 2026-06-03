@extends('layouts.layout')

@section('title', $organization->name . ' — Households')

@section('content')

{{-- Page header --}}
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Household Lookup</h1>
            <p class="mt-1 text-sm text-gray-500">Search for a household by block and lot number</p>
        </div>

        @auth
        <a href="{{ route('organizations.index', $organization->slug) }}"
           class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
            </svg>
            Back to Dashboard
        </a>
        @endauth
    </div>
</div>

{{-- Search card --}}
<div class="max-w-lg mx-auto">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-8">

        {{-- Icon --}}
        <div class="flex justify-center mb-6">
            <span class="flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-100 text-indigo-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                </svg>
            </span>
        </div>

        <h2 class="text-lg font-semibold text-center text-gray-900 mb-1">Find Household</h2>
        <p class="text-sm text-center text-gray-500 mb-8">Enter the block and lot number to look up a household record.</p>

        <form action="{{ '/' . $organization->slug . '/households' }}" method="GET">
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="block" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Block No.
                    </label>
                    <input type="number" id="block" name="block" placeholder="e.g. 3"
                           value="{{ request('block') }}"
                           class="form-input"
                           min="1">
                </div>
                <div>
                    <label for="lot" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Lot No.
                    </label>
                    <input type="number" id="lot" name="lot" placeholder="e.g. 12"
                           value="{{ request('lot') }}"
                           class="form-input"
                           min="1">
                </div>
            </div>

            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 active:bg-indigo-800 transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                </svg>
                Search Household
            </button>
        </form>
    </div>

    {{-- Tip --}}
    <p class="mt-4 text-center text-xs text-gray-400">
        Both block and lot number are required to locate a household.
    </p>
</div>

<style>
    .form-input {
        @apply w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition;
    }
</style>

@endsection
