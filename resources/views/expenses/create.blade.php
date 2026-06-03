@extends('layouts.layout')

@section('title', 'Add Expense')

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-xs text-gray-500 mb-6">
    <a href="{{ route('organizations.index', $organization->slug) }}" class="hover:text-indigo-600 transition-colors">Dashboard</a>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
    <a href="{{ route('expenses.index', $organization->slug) }}" class="hover:text-indigo-600 transition-colors">Expenses</a>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
    <span class="text-gray-700 font-medium">Add Expense</span>
</nav>

<div class="max-w-lg mx-auto">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">

        {{-- Card header --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-red-100 text-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm4.707 3.707a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L8.414 10l1.293-1.293zm4 1.414a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L10.586 9l-1.293-1.293a1 1 0 011.414-1.414l3 3z" clip-rule="evenodd"/>
                    </svg>
                </span>
                <div>
                    <h1 class="text-base font-semibold text-gray-900">Add Expense</h1>
                    <p class="text-xs text-gray-500">{{ $organization->name }}</p>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ route('expenses.store', $organization) }}" method="POST" id="expense-form" class="px-6 py-6 space-y-5">
            @csrf

            {{-- Description --}}
            <div>
                <label for="description" class="form-label">
                    Description <span class="text-red-500">*</span>
                </label>
                <input type="text" id="description" name="description"
                       value="{{ old('description') }}"
                       placeholder="e.g. Cleaning supplies, Maintenance fee…"
                       class="mt-1.5 form-input @error('description') form-input-error @enderror"
                       required>
                @error('description')
                    <p class="mt-1.5 error-msg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Amount --}}
            <div>
                <label for="amount" class="form-label">
                    Amount <span class="text-red-500">*</span>
                </label>
                <div class="relative mt-1.5">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 font-medium text-sm pointer-events-none">&#8369;</span>
                    <input type="number" id="amount" name="amount"
                           value="{{ old('amount') }}"
                           placeholder="0.00"
                           step="0.01" min="0"
                           class="form-input pl-8 @error('amount') form-input-error @enderror"
                           required>
                </div>
                @error('amount')
                    <p class="mt-1.5 error-msg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 pt-2">
                <a href="{{ route('expenses.index', $organization->slug) }}"
                   class="flex-1 inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" id="submit-btn"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 active:bg-indigo-800 transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                    <span id="submit-label">Save Expense</span>
                </button>
            </div>
        </form>
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
    document.getElementById('expense-form').addEventListener('submit', function () {
        const btn = document.getElementById('submit-btn');
        const label = document.getElementById('submit-label');
        btn.disabled = true;
        label.textContent = 'Saving…';
    });
</script>

@endsection
