@extends('layouts.layout')

@section('title', 'Households')

@section('content')

{{-- Page header --}}
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">All Households</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ $households->count() }} household{{ $households->count() !== 1 ? 's' : '' }} registered
            </p>
        </div>
        @if(isset($organization))
        <a href="{{ route('households.create', $organization->slug) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
            </svg>
            Add Household
        </a>
        @endif
    </div>
</div>

{{-- Table / empty state --}}
@if($households->isEmpty())
    {{-- Empty state --}}
    <div class="flex flex-col items-center justify-center py-20 bg-white border border-dashed border-gray-300 rounded-2xl">
        <span class="flex items-center justify-center w-14 h-14 rounded-2xl bg-gray-100 text-gray-400 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
            </svg>
        </span>
        <p class="text-sm font-semibold text-gray-900 mb-1">No households yet</p>
        <p class="text-sm text-gray-500 mb-6">Add the first household to get started.</p>
        @if(isset($organization))
        <a href="{{ route('households.create', $organization->slug) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
            Add Household
        </a>
        @endif
    </div>
@else
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="th-cell">Name</th>
                        <th class="th-cell">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($households as $household)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="td-cell">
                            <div class="flex items-center gap-3">
                                <span class="flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold uppercase">
                                    {{ strtoupper(substr($household->name, 0, 1)) }}
                                </span>
                                <span class="text-sm font-medium text-gray-900">{{ $household->name }}</span>
                            </div>
                        </td>
                        <td class="td-cell">
                            <div class="flex items-center gap-2">
                                @if(isset($organization))
                                <a href="{{ route('households.show', [$organization, $household]) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-md bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors border border-indigo-200">
                                    View
                                </a>
                                @endif
                <form action="{{ route('households.destroy', [$organization, $household]) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Delete this household? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-md bg-red-50 text-red-700 hover:bg-red-100 transition-colors border border-red-200">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<style>
    .th-cell { @apply px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider; }
    .td-cell { @apply px-5 py-3.5 text-sm text-gray-700 }
</style>

@endsection
