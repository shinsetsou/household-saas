@extends('layouts.layout')

@section('title', $organization->name . ' Dashboard')

@section('content')

{{-- Page header --}}
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $organization->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">Organization overview and financial summary</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('households.create', $organization->slug) }}"
               class="btn-add-household flex justify-center items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 active:bg-indigo-800 transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Add Household
            </a>
            <!--<a href="{{ route('households.import.create', $organization->slug) }}"
               class="btn-import-csv">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4zm0 7a2 2 0 100 4h12a2 2 0 100-4H4zm0 7a2 2 0 100 4h12a2 2 0 100-4H4z" clip-rule="evenodd"/>
                </svg>
                Import CSV
            </a>-->
            <a href="{{ route('expenses.create', $organization->slug) }}"
               class="flex justify-center items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700 active:bg-red-800 transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Add Expense
            </a>
            <a href="{{ route('contributions.verify', $organization->slug) }}"
               class="flex justify-center items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg bg-green-600 text-white hover:bg-green-700 active:bg-green-800 transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                Verify Contributions
            </a>
        </div>
    </div>
</div>

{{-- Year filter --}}
<div class="mb-8">
    <form action="{{ route('organizations.index', $organization->slug) }}" method="GET">
        <div class="inline-flex items-center gap-3 bg-white border border-gray-200 rounded-xl px-4 py-3 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
            </svg>
            <label for="year" class="text-sm font-medium text-gray-700">Year:</label>
            <input type="number" id="year" name="year" value="{{ $year }}"
                   min="2000" max="{{ date('Y') + 1 }}"
                   class="w-24 text-sm font-semibold bg-transparent border-0 text-gray-900 focus:outline-none focus:ring-0 p-0">
            <button type="submit"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors">
                Update
            </button>
        </div>
    </form>
</div>

{{-- Stats cards --}}
@php
    $balance = $totalContributions - $totalExpenses;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">

    {{-- Total Households --}}
    <a href="{{ route('households.index', $organization->slug) }}" >
    <div class="stat-card">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Households</span>
            <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                </svg>
            </span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $organization->households()->count() }}</p>
        <p class="mt-1 text-xs text-gray-500">Registered households</p>
    </div>
    </a>

    {{-- Total Contributions --}}
    <div class="stat-card">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Contributions</span>
            <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-green-100 text-green-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                    <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                </svg>
            </span>
        </div>
        <p class="text-3xl font-bold text-green-600">&#8369;{{ number_format($totalContributions, 2) }}</p>
        <p class="mt-1 text-xs text-gray-500">Collected in {{ $year }}</p>
    </div>

    {{-- Total Expenses --}}
    <div class="stat-card">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Expenses</span>
            <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-red-100 text-red-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm4.707 3.707a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L8.414 10l1.293-1.293zm4 1.414a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L10.586 9l-1.293-1.293a1 1 0 011.414-1.414l3 3z" clip-rule="evenodd"/>
                </svg>
            </span>
        </div>
        <p class="text-3xl font-bold text-red-600">&#8369;{{ number_format($totalExpenses, 2) }}</p>
        <p class="mt-1 text-xs text-gray-500">Spent in {{ $year }}</p>
    </div>

    {{-- Balance --}}
    <div class="stat-card {{ $balance >= 0 ? '' : 'ring-1 ring-red-200' }}">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Balance</span>
            <span class="flex items-center justify-center w-8 h-8 rounded-lg {{ $balance >= 0 ? 'bg-indigo-100 text-indigo-600' : 'bg-red-100 text-red-600' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                </svg>
            </span>
        </div>
        <p class="text-3xl font-bold {{ $balance >= 0 ? 'text-indigo-600' : 'text-red-600' }}">
            &#8369;{{ number_format(abs($balance), 2) }}
        </p>
        <p class="mt-1 text-xs text-gray-500">
            {{ $balance >= 0 ? 'Surplus for ' . $year : 'Deficit for ' . $year }}
        </p>
    </div>

</div>

{{-- Quick links / action cards --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
    <a href="{{ route('home', $organization->slug) }}"
       class="group flex items-center gap-4 p-5 bg-white border border-gray-200 rounded-xl shadow-sm hover:border-indigo-400 hover:shadow-md transition-all">
        <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-lg bg-blue-100 text-blue-600 group-hover:scale-110 transition-transform">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
            </svg>
        </span>
        <div>
            <p class="text-sm font-semibold text-gray-900">Manage Households</p>
            <p class="text-xs text-gray-500 mt-0.5">Search and view household records</p>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto text-gray-400 group-hover:text-indigo-500 transition-colors" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
        </svg>
    </a>

    <a href="{{ route('contributions.index', $organization->slug) }}"
       class="group flex items-center gap-4 p-5 bg-white border border-gray-200 rounded-xl shadow-sm hover:border-indigo-400 hover:shadow-md transition-all">
        <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-lg bg-green-100 text-green-600 group-hover:scale-110 transition-transform">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
            </svg>
        </span>
        <div>
            <p class="text-sm font-semibold text-gray-900">View Contributions</p>
            <p class="text-xs text-gray-500 mt-0.5">All payment records</p>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto text-gray-400 group-hover:text-indigo-500 transition-colors" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
        </svg>
    </a>

    <a href="{{ route('expenses.index', $organization->slug) }}"
       class="group flex items-center gap-4 p-5 bg-white border border-gray-200 rounded-xl shadow-sm hover:border-indigo-400 hover:shadow-md transition-all">
        <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-lg bg-red-100 text-red-600 group-hover:scale-110 transition-transform">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm4.707 3.707a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L8.414 10l1.293-1.293zm4 1.414a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L10.586 9l-1.293-1.293a1 1 0 011.414-1.414l3 3z" clip-rule="evenodd"/>
            </svg>
        </span>
        <div>
            <p class="text-sm font-semibold text-gray-900">View Expenses</p>
            <p class="text-xs text-gray-500 mt-0.5">All expense records</p>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto text-gray-400 group-hover:text-indigo-500 transition-colors" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
        </svg>
    </a>
</div>

<style>
    .stat-card {
        @apply bg-white border border-gray-200 rounded-xl p-5 shadow-sm;
    }
    .btn-primary {
        @apply inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 active:bg-indigo-800 transition-colors shadow-sm;
    }
    .btn-secondary {
        @apply inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 transition-colors shadow-sm;
    }
    .btn-add-household {
        @apply inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 active:bg-indigo-800 transition-colors shadow-sm;
    }
    .btn-import-csv {
        @apply inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 active:bg-emerald-800 transition-colors shadow-sm;
    }
</style>

@endsection
