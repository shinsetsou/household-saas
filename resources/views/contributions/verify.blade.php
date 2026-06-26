@extends('layouts.layout')

@section('title', 'Verify Contributions')

@section('content')

{{-- Breadcrumb --}}
<nav class="flex items-center gap-2 text-xs text-slate-500 mb-4">
    <a href="{{ route('organizations.index', $organization->slug) }}" class="hover:text-brand-600 transition-colors">Dashboard</a>
    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 20 20"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg>
    <span class="text-slate-700 font-medium">Verify Contributions</span>
</nav>

<x-page-header
    eyebrow="Treasurer"
    title="Pending verifications"
    :subtitle="'Review and verify contribution submissions for '.$organization->name"
    tone="amber"
    icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">
    <x-slot:actions>
        <x-brand-button variant="secondary" :href="route('contributions.index', $organization->slug)" icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">View all</x-brand-button>
    </x-slot:actions>
</x-page-header>

<div class="mt-6 rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden"
     x-data="{
        selected: {},
        get selectedRefs() { return Object.keys(this.selected).filter(k => this.selected[k]); },
        get count() { return this.selectedRefs.length; },
        toggleAll(checked) {
            const refs = $refs.bulkCheckboxes || [];
            refs.forEach(cb => { this.selected[cb.value] = checked; cb.checked = checked; });
        },
        toggleOne(ref, checked) { this.selected[ref] = checked; }
     }"
     @bulk-confirm-verify.window="if (window.confirm(`Verify ${$event.detail.count} contribution(s)?`)) { $refs.bulkForm.action = $refs.bulkForm.dataset.verifyUrl; $refs.bulkForm.querySelector('[name=verification_status]').value = 'verified'; $refs.bulkForm.querySelector('[name=rejection_reason]').value = ''; $refs.bulkForm.submit(); }"
     @bulk-confirm-reject.window="if (window.confirm(`Reject ${$event.detail.count} contribution(s)?`)) { $refs.bulkForm.action = $refs.bulkForm.dataset.rejectUrl; $refs.bulkForm.querySelector('[name=verification_status]').value = 'rejected'; $refs.bulkForm.submit(); }">

    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-amber-100 text-amber-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Awaiting Review</h2>
                <p class="text-xs text-slate-500">{{ $contributions->total() }} pending</p>
            </div>
        </div>
        <x-status-chip tone="amber" icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">{{ $contributions->total() }} pending</x-status-chip>
    </div>

    @if($contributions->isEmpty())
        <x-empty-state
            tone="emerald"
            icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
            title="All caught up!"
            subtitle="No contributions are pending verification right now." />
    @else

    <div class="flex justify-end items-center gap-2 px-5 py-3 border-b border-slate-100 bg-slate-50">
        <label for="itemsPerPage" class="text-xs font-medium text-slate-500">Rows per page:</label>
        <select id="itemsPerPage" onchange="updateItemsPerPage(this.value)" class="text-xs text-slate-700 bg-white border border-slate-200 rounded-md px-2 py-1 focus:outline-none focus:ring-1 focus:ring-brand-500">
            <option value="10" {{ $itemsPerPage == 10 ? 'selected' : '' }}>10</option>
            <option value="25" {{ $itemsPerPage == 25 ? 'selected' : '' }}>25</option>
            <option value="50" {{ $itemsPerPage == 50 ? 'selected' : '' }}>50</option>
        </select>
    </div>

    <form x-ref="bulkForm" method="POST" class="hidden" data-verify-url="{{ route('contributions.verify.bulk', $organization) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="verification_status" value="verified">
        <input type="hidden" name="rejection_reason" value="">
        <template x-for="ref in selectedRefs" :key="ref">
            <input type="hidden" name="reference_numbers[]" :value="ref">
        </template>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="th-cell w-10">
                        <input type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" @change="toggleAll($event.target.checked)">
                    </th>
                    <th class="th-cell">Reference</th>
                    <th class="th-cell">Household</th>
                    <th class="th-cell">Receipt</th>
                    <th class="th-cell">Total Amount</th>
                    <th class="th-cell text-center">Billing Month</th>
                    <th class="th-cell text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($contributions as $contribution)
                <tr class="hover:bg-slate-50 transition-colors"
                    :class="selected['{{ $contribution->reference_number }}'] ? 'bg-brand-50/40' : ''">

                    <td class="td-cell text-center">
                        <input type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                               x-ref="bulkCheckboxes"
                               value="{{ $contribution->reference_number }}"
                               @change="toggleOne('{{ $contribution->reference_number }}', $event.target.checked)">
                    </td>

                    <td class="td-cell text-center">
                        <span class="font-mono text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded">{{ substr($contribution->reference_number, 0, 8) }}…</span>
                    </td>

                    <td class="td-cell">
                        @php $history = $paymentHistory[$contribution->household_id] ?? collect(); @endphp
                        <div class="relative inline-flex items-center gap-2.5">
                            <span class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-violet-600 text-white text-xs font-bold uppercase">
                                {{ strtoupper(substr($contribution->household_name ?? '?', 0, 1)) }}
                            </span>

                            @if($history->isNotEmpty())
                                <span class="history-trigger text-sm font-medium text-slate-900 capitalize cursor-help border-b border-dotted border-slate-400"
                                      data-history='@json($history->all())'>
                                    {{ $contribution->household_name ?? '—' }} {{ 'Block:'.$contribution->block . 'Lot:' . $contribution->lot }}
                                </span>
                            @else
                                <span class="text-sm font-medium text-slate-900 capitalize">
                                    {{ $contribution->household_name ?? '—' }} {{ 'Block:'.$contribution->block . 'Lot:' . $contribution->lot }}
                                </span>
                            @endif
                        </div>
                    </td>

                    <td class="td-cell text-center">
                        @if($contribution->photo)
                            <a href="{{ $contribution->photo_url }}" target="_blank"
                               class="inline-flex items-center justify-center w-10 h-10 rounded-xl overflow-hidden ring-1 ring-slate-200 hover:ring-2 hover:ring-brand-500 transition">
                                <img src="{{ $contribution->photo_url }}" alt="Receipt" class="w-full h-full object-cover">
                            </a>
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>

                    <td class="td-cell text-center font-bold text-slate-900 tabular-nums">
                        ₱{{ number_format($contribution->total_amount, 2) }}
                    </td>

                    <td class="td-cell text-center">
                        @php
                            $monthForValue = $contribution->month_for
                                ? \Illuminate\Support\Carbon::parse($contribution->month_for)->format('Y-m')
                                : now()->format('Y-m');
                        @endphp
                        <input type="month" name="month_for" form="verify-form-{{ $loop->index }}"
                               value="{{ $monthForValue }}"
                               class="text-xs text-slate-700 bg-white border border-slate-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <p class="mt-1 text-[10px] text-slate-400">Month this payment covers</p>
                    </td>

                    <td class="td-cell">
                        <div class="flex items-center justify-center gap-2">
                            <form id="verify-form-{{ $loop->index }}" action="{{ route('contributions.verify.store', [$organization, $contribution->reference_number]) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="verification_status" value="verified">
                                <button type="submit" onclick="return confirm('Mark this contribution as verified?')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-100 text-emerald-800 hover:bg-emerald-200 ring-1 ring-emerald-200 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Verify
                                </button>
                            </form>

                            <form action="{{ route('contributions.verify.store', [$organization, $contribution->reference_number]) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="verification_status" value="rejected">
                                <input type="hidden" name="rejection_reason" value="Rejected by treasurer">
                                <input type="hidden" name="month_for" value="{{ $monthForValue }}">
                                <button type="submit" onclick="return confirm('Mark this contribution as disproved?')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-rose-100 text-rose-800 hover:bg-rose-200 ring-1 ring-rose-200 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
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

    {{-- Bulk action toolbar --}}
    <div x-show="count > 0" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="sticky bottom-0 inset-x-0 z-30 border-t border-slate-200 bg-white/95 backdrop-blur shadow-[0_-8px_24px_-12px_rgba(0,0,0,0.15)] px-5 py-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-violet-600 text-white text-sm font-bold" x-text="count"></span>
                <p class="text-sm text-slate-700">
                    <span class="font-bold" x-text="count"></span>
                    contribution<span x-show="count !== 1">s</span> selected
                </p>
                <button type="button"
                        @click="toggleAll(false); $refs.bulkCheckboxes.forEach(cb => cb.checked = false)"
                        class="text-xs font-medium text-slate-500 hover:text-slate-700 underline underline-offset-2">
                    Clear
                </button>
            </div>

            <div class="flex items-center gap-2">
                <input type="text" x-ref="bulkRejectionReason" name="rejection_reason_bulk"
                       placeholder="Reason for rejection (required for reject)"
                       class="text-sm w-64 rounded-lg border-slate-200 bg-white px-3 py-2 focus:border-brand-500 focus:ring-1 focus:ring-brand-500">

                <button type="button"
                        @click="$dispatch('bulk-confirm-verify', { count: count })"
                        :disabled="count === 0"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg bg-gradient-to-r from-emerald-500 to-green-600 text-white hover:from-emerald-600 hover:to-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    Verify selected
                </button>

                <button type="button"
                        @click="
                            const reason = ($refs.bulkRejectionReason?.value || '').trim();
                            if (!reason) { alert('Please enter a rejection reason.'); return; }
                            $refs.bulkForm.querySelector('[name=rejection_reason]').value = reason;
                            $dispatch('bulk-confirm-reject', { count: count });
                        "
                        :disabled="count === 0"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg bg-gradient-to-r from-rose-500 to-red-600 text-white hover:from-rose-600 hover:to-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Reject selected
                </button>
            </div>
        </div>
    </div>

    @if($contributions->hasPages())
    <div class="px-5 py-4 border-t border-slate-100">
        {{ $contributions->appends(['itemsPerPage' => $itemsPerPage])->links() }}
    </div>
    @endif

    @endif
</div>

<style>
    .th-cell { @apply px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider; }
    .td-cell { @apply px-5 py-3.5 text-sm; }
</style>

<script>
    function updateItemsPerPage(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('itemsPerPage', value);
        window.location.href = url.toString();
    }

    // Floating hover card for "last paid months". Detached from the table so it
    // escapes any overflow:hidden / overflow-x-auto / transform ancestor.
    (function () {
        const portal = document.createElement('div');
        portal.id = 'history-portal';
        portal.style.cssText = 'position:fixed;top:0;left:0;z-index:9999;pointer-events:none;opacity:0;transition:opacity .12s;';
        document.body.appendChild(portal);

        function render(history) {
            portal.innerHTML = `
                <div style="min-width:14rem;max-width:18rem;padding:.75rem;border-radius:.5rem;background:#fff;border:1px solid #e5e7eb;box-shadow:0 10px 15px -3px rgba(0,0,0,.1);text-align:left;">
                    <p style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin:0 0 .5rem;">
                        Last ${history.length} paid month${history.length === 1 ? '' : 's'}
                    </p>
                    <div style="display:flex;flex-wrap:wrap;gap:.375rem;">
                        ${history.map((label, i) => `
                            <span style="display:inline-flex;align-items:center;padding:.125rem .5rem;border-radius:9999px;font-size:11px;font-weight:500;
                                         background:${i === 0 ? '#ecfdf5' : '#f9fafb'};
                                         color:${i === 0 ? '#047857' : '#374151'};
                                         border:1px solid ${i === 0 ? '#a7f3d0' : '#e5e7eb'};">
                                ${label}
                            </span>
                        `).join('')}
                    </div>
                    <p style="margin-top:.5rem;font-size:10px;color:#9ca3af;">Most recent first</p>
                </div>`;
        }

        function position(trigger) {
            const rect = trigger.getBoundingClientRect();
            const card = portal.firstElementChild;
            const margin = 8;
            const cardHeight = card ? card.offsetHeight : 0;
            const cardWidth = card ? card.offsetWidth : 0;

            let top = rect.bottom + margin;
            if (top + cardHeight > window.innerHeight - margin) {
                top = rect.top - cardHeight - margin;
            }
            let left = rect.left + rect.width / 2 - cardWidth / 2;
            left = Math.max(margin, Math.min(left, window.innerWidth - cardWidth - margin));

            portal.style.transform = `translate(${left}px, ${top}px)`;
        }

        document.addEventListener('mouseover', (e) => {
            const trigger = e.target.closest('.history-trigger');
            if (!trigger) return;
            render(JSON.parse(trigger.dataset.history || '[]'));
            position(trigger);
            portal.style.opacity = '1';
        });
        document.addEventListener('mouseout', (e) => {
            const trigger = e.target.closest('.history-trigger');
            if (!trigger) return;
            const related = e.relatedTarget;
            if (related && trigger.contains(related)) return;
            portal.style.opacity = '0';
        });
        window.addEventListener('scroll', () => {
            const visible = portal.style.opacity === '1';
            if (!visible) return;
            const trigger = document.querySelector('.history-trigger:hover');
            if (trigger) position(trigger);
        }, true);
    })();
</script>

@endsection