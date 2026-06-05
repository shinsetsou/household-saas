@extends('layouts.layout')

@section('title', 'Verify Contributions')

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-xs text-gray-500 mb-6">
    <a href="{{ route('organizations.index', $organization->slug) }}" class="hover:text-indigo-600 transition-colors">Dashboard</a>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
    <span class="text-gray-700 font-medium">Verify Contributions</span>
</nav>

{{-- Page header --}}
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Pending Verifications</h1>
            <p class="mt-1 text-sm text-gray-500">
                Review and verify contribution submissions for {{ $organization->name }}
            </p>
        </div>
        <a href="{{ route('organizations.index', $organization->slug) }}"
           class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
            </svg>
            Back to Dashboard
        </a>
    </div>
</div>

{{-- Table card --}}
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

    {{-- Card header --}}
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
        <div class="flex items-center gap-2">
            <span class="flex items-center justify-center w-7 h-7 rounded-md bg-yellow-100 text-yellow-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                </svg>
            </span>
            <h2 class="text-sm font-semibold text-gray-900">Awaiting Review</h2>
        </div>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
            {{ $contributions->total() }} pending
        </span>
    </div>

    @if($contributions->isEmpty())
    {{-- Empty state --}}
    <div class="flex flex-col items-center justify-center py-20 px-5">
        <span class="flex items-center justify-center w-14 h-14 rounded-2xl bg-green-100 text-green-500 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
        </span>
        <p class="text-sm font-semibold text-gray-900 mb-1">All caught up!</p>
        <p class="text-sm text-gray-500">No contributions are pending verification right now.</p>
    </div>
    @else

    {{-- Items per page --}}
    <div class="flex justify-end items-center gap-2 px-5 py-3 border-b border-gray-100 bg-gray-50">
        <label for="itemsPerPage" class="text-xs font-medium text-gray-500">Rows per page:</label>
        <select id="itemsPerPage" onchange="updateItemsPerPage(this.value)"
                class="text-xs text-gray-700 bg-white border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <option value="10" {{ $itemsPerPage == 10 ? 'selected' : '' }}>10</option>
            <option value="25" {{ $itemsPerPage == 25 ? 'selected' : '' }}>25</option>
            <option value="50" {{ $itemsPerPage == 50 ? 'selected' : '' }}>50</option>
        </select>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th-cell">Reference</th>
                    <th class="th-cell">Household</th>
                    <th class="th-cell">Receipt</th>
                    <th class="th-cell">Total Amount</th>
                    <th class="th-cell text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($contributions as $contribution)
                <tr class="hover:bg-gray-50 transition-colors">

                    {{-- Reference number --}}
                    <td class="td-cell text-center">
                        <span class="font-mono text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                            {{ substr($contribution->reference_number, 0, 8) }}…
                        </span>
                    </td>

                    {{-- Household --}}
                    <td class="td-cell justify-content-center">
                        <div class="flex items-center justify-center gap-2.5">
                            <span class="flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold uppercase">
                                {{ strtoupper(substr($contribution->household_name ?? '?', 0, 1)) }}
                            </span>
                            <span class="text-sm font-medium text-gray-900 capitalize">
                                {{ $contribution->household_name ?? '—' }} {{ 'Block:'. $contribution->block . 'Lot:' . $contribution->lot }}
                            </span>
                        </div>
                    </td>

                    <td class="td-cell text-center justify-content-center">
                        @if($contribution->photo)
                            <a href="{{ $contribution->photo_url }}" target="_blank"
                               class="inline-flex items-center justify-center w-9 h-9 rounded-lg overflow-hidden border border-gray-200 hover:ring-2 hover:ring-indigo-500 transition">
                                <img src="{{ $contribution->photo_url }}" alt="Receipt" class="w-full h-full object-cover">
                            </a>
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>

                    {{-- Amount --}}
                    <td class="td-cell text-center font-semibold text-gray-900">
                        &#8369;{{ number_format($contribution->total_amount, 2) }}
                    </td>

                    {{-- Actions --}}
                    <td class="td-cell items-center">
                        <div class="flex items-center justify-center gap-2">
                            {{-- Verify --}}
                            <form action="{{ route('contributions.verify.store', [$organization, $contribution->reference_number]) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="verification_status" value="verified">
                                <button type="submit"
                                        onclick="return confirm('Mark this contribution as verified?')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-green-100 text-green-800 hover:bg-green-200 border border-green-200 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Verify
                                </button>
                            </form>

                            {{-- Disprove --}}
                            <form action="{{ route('contributions.verify.store', [$organization, $contribution->reference_number]) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="verification_status" value="rejected">
                                <input type="hidden" name="rejection_reason" value="Rejected by treasurer">
                                <button type="submit"
                                        onclick="return confirm('Mark this contribution as disproved?')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-100 text-red-700 hover:bg-red-200 border border-red-200 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    Disprove
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($contributions->hasPages())
    <div class="px-5 py-4 border-t border-gray-100">
        {{ $contributions->appends(['itemsPerPage' => $itemsPerPage])->links() }}
    </div>
    @endif

    @endif
</div>

<style>
    .th-cell { @apply px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider; }
    .td-cell { @apply px-5 py-3.5 text-sm text-gray-700 }
</style>

<script>
    function updateItemsPerPage(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('itemsPerPage', value);
        window.location.href = url.toString();
    }
</script>

@endsection
