@extends('layouts.layout')

@section('title', $household->name)

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-xs text-gray-500 mb-6">
    <a href="@auth{{ route('organizations.index', $organization->slug) }} @endauth" class="hover:text-indigo-600 transition-colors">Dashboard</a>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
    <a href="{{ route('home', $organization->slug) }}" class="hover:text-indigo-600 transition-colors">Households</a>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
    <span class="text-gray-700 font-medium">{{ $household->name }}</span>
</nav>

{{-- Page header --}}
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <span class="flex-shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 text-lg font-bold uppercase">
                {{ strtoupper(substr($household->name, 0, 1)) }}
            </span>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 capitalize">{{ $household->name }}</h1>
                <p class="text-sm text-gray-500">Household contribution record</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @auth
            <a href="{{ route('home', $organization->slug) }}"
               class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg text-gray-600 border border-gray-300 hover:bg-gray-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
                </svg>
                Back
            </a>
            @endauth
            <a href="{{ route('contributions.create', [$organization, $household]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Pay Contribution
            </a>
        </div>
    </div>
</div>

{{-- Stats --}}
@php
    $balance = $totalContributions - $totalExpenses;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-2">Total Paid</p>
        <p class="text-2xl font-bold text-green-600">&#8369;{{ number_format($totalContributions, 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">All-time contributions</p>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-2">Org. Expenses</p>
        <p class="text-2xl font-bold text-red-600">&#8369;{{ number_format($totalExpenses, 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">
            <a href="{{ route('expenses.index', $organization) }}" class="hover:text-indigo-600 transition-colors underline underline-offset-2">View all expenses</a>
        </p>
    </div>
    <div class="bg-white border border-{{ $balance >= 0 ? 'indigo' : 'red' }}-200 $balance >= 0 ? 'indigo' : 'red' }}-800 rounded-xl p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-2">Balance</p>
        <p class="text-2xl font-bold {{ $balance >= 0 ? 'text-indigo-600' : 'text-red-600' }}">
            &#8369;{{ number_format(abs($balance), 2) }}
        </p>
        <p class="text-xs text-gray-500 mt-1">{{ $balance >= 0 ? 'Surplus' : 'Deficit' }}</p>
    </div>
</div>

{{-- Contribution history --}}
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
        <h2 class="text-sm font-semibold text-gray-900">Contribution History</h2>
        <span class="text-xs text-gray-500">{{ $contributions->total() }} record{{ $contributions->total() !== 1 ? 's' : '' }}</span>
    </div>

    @if($contributions->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 px-5">
        <span class="flex items-center justify-center w-12 h-12 rounded-xl bg-gray-100 text-gray-400 mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9z" clip-rule="evenodd"/>
            </svg>
        </span>
        <p class="text-sm font-medium text-gray-900 mb-1">No contributions yet</p>
        <p class="text-xs text-gray-500 mb-4">Record the first payment for this household.</p>
        <a href="{{ route('contributions.create', [$organization, $household]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
            Pay Contribution
        </a>
    </div>
    @else

    {{-- Items per page --}}
    <div class="flex justify-end items-center gap-2 px-5 py-3 border-b border-gray-100 bg-gray-50">
        <label for="itemsPerPage" class="text-xs font-medium text-gray-500">Rows per page:</label>
        <select id="itemsPerPage" name="itemsPerPage" onchange="updateItemsPerPage(this.value)"
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
                    <th class="th-cell">Date</th>
                    <th class="th-cell">Amount</th>
                    <th class="th-cell">Status</th>
                    <th class="th-cell">Verification</th>
                    <th class="th-cell">Receipt</th>
                    @auth
                    <th class="th-cell">Actions</th>
                    @endauth
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($contributions as $contribution)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="td-cell text-gray-600 text-center">
                        {{ $contribution->created_at->format('M d, Y') }}
                    </td>
                    <td class="td-cell font-semibold text-gray-900 text-center">
                        &#8369;{{ number_format($contribution->amount, 2) }}
                    </td>
                    <td class="td-cell text-center">
                        @if($contribution->status === 'paid')
                            <span class="status-badge-green">Paid</span>
                        @elseif($contribution->status === 'lacking')
                            <span class="status-badge-yellow">Lacking</span>
                        @else
                            <span class="status-badge-gray">{{ ucfirst($contribution->status) }}</span>
                        @endif
                    </td>
                    <td class="td-cell text-center">
                        @if($contribution->admin_verified == 1)
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Verified
                            </span>
                        @elseif($contribution->admin_verified == 3)
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-red-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                Disproved
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                Pending
                            </span>
                        @endif
                    </td>
                    <td class="td-cell text-center">
                        @if($contribution->photo)
                            <a href="{{ $contribution->photo_url }}" target="_blank"
                               class="inline-flex items-center justify-center w-9 h-9 rounded-lg overflow-hidden border border-gray-200 hover:ring-2 hover:ring-indigo-500 transition">
                                <img src="{{ $contribution->photo_url }}" alt="Receipt" class="w-full h-full object-cover">
                            </a>
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    @auth
                    <td class="td-cell text-center">
                        <form action="{{ route('contributions.destroy', [$organization, $household, $contribution]) }}" method="POST"
                              onsubmit="return confirm('Delete this contribution? This cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-md bg-red-50 text-red-700 hover:bg-red-100 transition-colors border border-red-200">
                                Delete
                            </button>
                        </form>
                    </td>
                    @endauth
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
    .td-cell { @apply px-5 py-3.5 text-sm; }
    .status-badge-green  { @apply inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 }
    .status-badge-yellow { @apply inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 }
    .status-badge-gray   { @apply inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 }
</style>

<script>
    function updateItemsPerPage(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('itemsPerPage', value);
        window.location.href = url.toString();
    }
</script>

@endsection
