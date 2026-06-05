@extends('layouts.layout')

@section('title', 'Contributions')

@section('content')

{{-- Page header --}}
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">All Contributions</h1>
            <p class="mt-1 text-sm text-gray-500">Complete record of household payments</p>
        </div>
    </div>
</div>

{{-- Verification status tabs --}}
@php
    $tabs = [
        null        => ['label' => 'All',      'count' => $totalCount],
        'verified'  => ['label' => 'Verified', 'count' => (int) ($counts['verified'] ?? 0)],
        'pending'   => ['label' => 'Pending',  'count' => (int) ($counts['pending'] ?? 0)],
        'rejected'  => ['label' => 'Rejected', 'count' => (int) ($counts['rejected'] ?? 0)],
    ];
@endphp
<div class="mb-6 flex flex-wrap gap-1 border-b border-gray-200">
    @foreach($tabs as $value => $tab)
        @php $active = ($value === '' || $value === null) ? is_null($status) : $status === $value; @endphp
        <a href="{{ route('contributions.index', array_filter([$organization->slug, 'status' => $value])) }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium border-b-2 -mb-px transition
                  {{ $active ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            {{ $tab['label'] }}
            @if($tab['count'] > 0)
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-xs font-semibold
                             {{ $active ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $tab['count'] }}
                </span>
            @endif
        </a>
    @endforeach
</div>

{{-- Table / empty state --}}
@if($contributions->isEmpty())
<div class="flex flex-col items-center justify-center py-20 bg-white border border-dashed border-gray-300 rounded-2xl">
    <span class="flex items-center justify-center w-14 h-14 rounded-2xl bg-gray-100 text-gray-400 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor">
            <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
            <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9z" clip-rule="evenodd"/>
        </svg>
    </span>
    <p class="text-sm font-semibold text-gray-900 mb-1">
        {{ $status ? 'No '.$status.' contributions' : 'No contributions recorded' }}
    </p>
    <p class="text-sm text-gray-500">
        {{ $status ? 'Try a different tab.' : 'Contributions will appear here after households submit payments.' }}
    </p>
</div>
@else
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 bg-gray-50">
        <span class="text-xs text-gray-500">{{ $contributions->count() }} record{{ $contributions->count() !== 1 ? 's' : '' }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr class="h-12">
                    <th class="th-cell">Household</th>
                    <th class="th-cell">Amount</th>
                    <th class="th-cell">Status</th>
                    <th class="th-cell">Verification</th>
                    <th class="th-cell">Date</th>
                    <th class="th-cell">Receipt</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($contributions as $contribution)
                <tr class="h-12 hover:bg-gray-50 transition-colors">
                    <td class="td-cell">
                        <div class="flex items-center justify-center gap-2.5">
                            <span class="flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold uppercase">
                                {{ strtoupper(substr($contribution->household->name ?? '?', 0, 1)) }}
                            </span>
                            <span class="text-sm font-medium text-gray-900 capitalize">
                                {{ $contribution->household->name ?? '—' }}
                            </span>
                        </div>
                    </td>
                    <td class="td-cell text-center font-semibold text-gray-900">
                        &#8369;{{ number_format($contribution->amount, 2) }}
                    </td>
                    <td class="td-cell text-center">
                        @if($contribution->status === 'paid')
                            <span class="status-badge-green">Paid</span>
                        @elseif($contribution->status === 'lacking')
                            <span class="status-badge-yellow">Lacking</span>
                        @else
                            <span class="status-badge-gray">{{ ucfirst($contribution->status ?? '—') }}</span>
                        @endif
                    </td>
                    <td class="td-cell text-center">
                        @if($contribution->verification_status === 'verified')
                            <span class="status-badge-green">Verified</span>
                        @elseif($contribution->verification_status === 'rejected')
                            <span class="status-badge-red">Rejected</span>
                        @else
                            <span class="status-badge-gray">Pending</span>
                        @endif
                    </td>
                    <td class="td-cell text-center text-gray-600">
                        {{ $contribution->created_at->format('M d, Y') }}
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
    .status-badge-green  { @apply inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 }
    .status-badge-yellow { @apply inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 }
    .status-badge-gray   { @apply inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 }
    .status-badge-red    { @apply inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 }
</style>

@endsection
