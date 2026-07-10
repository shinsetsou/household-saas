@extends('layouts.layout')

@section('title', $expense->description . ' — Expense')

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-xs text-slate-500 mb-4">
    <a href="{{ route('organizations.index', $organization->slug) }}" class="hover:text-brand-600 transition-colors">Dashboard</a>
    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 20 20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg>
    <a href="{{ route('expenses.index', $organization->slug) }}" class="hover:text-brand-600 transition-colors">Expenses</a>
    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 20 20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg>
    <span class="text-slate-700 font-medium truncate max-w-[200px]">{{ \Illuminate\Support\Str::limit($expense->description, 40) }}</span>
</nav>

<x-page-header
    eyebrow="Expense detail"
    :title="$expense->description"
    :subtitle="$expense->created_at->format('M d, Y · g:i A')"
    tone="rose"
    icon="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
    <x-slot:actions>
        <a href="{{ route('expenses.index', $organization->slug) }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back
        </a>
    </x-slot:actions>
</x-page-header>

<div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- Main --}}
    <div class="lg:col-span-2 space-y-5">
        {{-- Amount + status --}}
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Amount</div>
                    <div class="text-3xl font-bold text-rose-700 tabular-nums">-₱{{ number_format($expense->amount, 2) }}</div>
                </div>
                <div class="text-right">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Status</div>
                    @if($expense->status === 'paid')
                        <x-status-chip tone="green" icon="M5 13l4 4L19 7">Paid</x-status-chip>
                    @elseif($expense->status === 'cancelled')
                        <x-status-chip tone="slate" icon="M6 18L18 6M6 6l12 12">Cancelled</x-status-chip>
                    @else
                        <x-status-chip tone="amber" icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">Pending</x-status-chip>
                    @endif
                </div>
            </div>

            <dl class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Recorded</dt>
                    <dd class="mt-1 text-slate-800">{{ $expense->created_at->format('M d, Y · g:i A') }}</dd>
                </div>
                @if($expense->paid_at)
                    <div>
                        <dt class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Paid on</dt>
                        <dd class="mt-1 text-slate-800">
                            {{ $expense->paid_at->format('M d, Y · g:i A') }}
                            @if($expense->paidByUser)
                                <span class="text-slate-500">by {{ $expense->paidByUser->name }}</span>
                            @endif
                        </dd>
                    </div>
                @endif
                @if($expense->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Notes</dt>
                        <dd class="mt-1 text-slate-800 whitespace-pre-line">{{ $expense->notes }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Receipt --}}
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-slate-800">Receipt</h2>
                @if($expense->receipt)
                    <a href="{{ $expense->receipt_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        Open in new tab
                    </a>
                @endif
            </div>
            @if($expense->receipt)
                @php
                    $isImage = \Illuminate\Support\Str::endsWith(strtolower($expense->receipt), ['.jpg','.jpeg','.png','.gif','.webp']);
                @endphp
                @if($isImage)
                    <a href="{{ $expense->receipt_url }}" target="_blank" rel="noopener">
                        <img src="{{ $expense->receipt_url }}" alt="Receipt for {{ $expense->description }}" class="rounded-lg border border-slate-200 max-h-96 w-auto">
                    </a>
                @else
                    <a href="{{ $expense->receipt_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-4 py-3 text-sm font-medium rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        View receipt
                    </a>
                @endif
            @else
                <div class="text-sm text-slate-500 py-8 text-center border border-dashed border-slate-200 rounded-lg">
                    No receipt attached.
                </div>
            @endif
        </div>
    </div>

    {{-- Sidebar actions --}}
    <div class="space-y-3">
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm p-4">
            <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-3">Actions</h3>
            <div class="space-y-2">
                @if($expense->status === 'pending')
                    <form action="{{ route('expenses.mark-paid', [$organization, $expense]) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-semibold rounded-lg text-white bg-green-600 hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Mark as paid
                        </button>
                    </form>
                    <form action="{{ route('expenses.cancel', [$organization, $expense]) }}" method="POST" onsubmit="return confirm('Cancel this expense? You can restore it later.')">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors">
                            Cancel expense
                        </button>
                    </form>
                @elseif($expense->status === 'cancelled')
                    <form action="{{ route('expenses.restore', [$organization, $expense]) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors">
                            Restore to pending
                        </button>
                    </form>
                @else
                    <div class="text-sm text-slate-500 px-2 py-1">No further actions — expense is paid.</div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
