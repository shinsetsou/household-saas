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
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden"
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

    {{-- Hidden bulk form. JS sets action + verification_status before submit. --}}
    <form x-ref="bulkForm"
          method="POST"
          class="hidden"
          data-verify-url="{{ route('contributions.verify.bulk', $organization) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="verification_status" value="verified">
        <input type="hidden" name="rejection_reason" value="">
        <template x-for="ref in selectedRefs" :key="ref">
            <input type="hidden" name="reference_numbers[]" :value="ref">
        </template>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th-cell w-10">
                        <input type="checkbox"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                               @change="toggleAll($event.target.checked)">
                    </th>
                    <th class="th-cell">Reference</th>
                    <th class="th-cell">Household</th>
                    <th class="th-cell">Receipt</th>
                    <th class="th-cell">Total Amount</th>
                    <th class="th-cell text-center">Billing Month</th>
                    <th class="th-cell text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($contributions as $contribution)
                <tr class="hover:bg-gray-50 transition-colors"
                    :class="selected['{{ $contribution->reference_number }}'] ? 'bg-indigo-50/40' : ''">

                    {{-- Selection checkbox --}}
                    <td class="td-cell text-center">
                        <input type="checkbox"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                               x-ref="bulkCheckboxes"
                               value="{{ $contribution->reference_number }}"
                               @change="toggleOne('{{ $contribution->reference_number }}', $event.target.checked)">
                    </td>

                    {{-- Reference number --}}
                    <td class="td-cell text-center">
                        <span class="font-mono text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                            {{ substr($contribution->reference_number, 0, 8) }}…
                        </span>
                    </td>

                    {{-- Household --}}
                    <td class="td-cell justify-content-center">
                        @php
                            $history = $paymentHistory[$contribution->household_id] ?? collect();
                        @endphp
                        <div class="relative inline-flex items-center justify-center gap-2.5">
                            <span class="flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold uppercase">
                                {{ strtoupper(substr($contribution->household_name ?? '?', 0, 1)) }}
                            </span>

                            @if($history->isNotEmpty())
                                <span class="history-trigger text-sm font-medium text-gray-900 capitalize cursor-help border-b border-dotted border-gray-300"
                                      data-history='@json($history->all())'>
                                    {{ $contribution->household_name ?? '—' }} {{ 'Block:'. $contribution->block . 'Lot:' . $contribution->lot }}
                                </span>
                            @else
                                <span class="text-sm font-medium text-gray-900 capitalize">
                                    {{ $contribution->household_name ?? '—' }} {{ 'Block:'. $contribution->block . 'Lot:' . $contribution->lot }}
                                </span>
                            @endif
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

                    {{-- Billing month (editable) --}}
                    <td class="td-cell text-center">
                        @php
                            $monthForValue = $contribution->month_for
                                ? \Illuminate\Support\Carbon::parse($contribution->month_for)->format('Y-m')
                                : now()->format('Y-m');
                        @endphp
                        <input type="month" name="month_for" form="verify-form-{{ $loop->index }}"
                               value="{{ $monthForValue }}"
                               class="text-xs text-gray-700 bg-white border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <p class="mt-1 text-[10px] text-gray-400">Month this payment covers</p>
                    </td>

                    {{-- Actions --}}
                    <td class="td-cell items-center">
                        <div class="flex items-center justify-center gap-2">
                            {{-- Verify --}}
                            <form id="verify-form-{{ $loop->index }}" action="{{ route('contributions.verify.store', [$organization, $contribution->reference_number]) }}" method="POST">
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
                                <input type="hidden" name="month_for" value="{{ $monthForValue }}">
                                <button type="submit"
                                        onclick="return confirm('Mark this contribution as disproved?')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-100 text-red-700 hover:bg-red-200 border border-red-200 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
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

    {{-- Bulk action toolbar (sticky bottom, slides in when ≥1 row selected) --}}
    <div x-show="count > 0"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="sticky bottom-0 inset-x-0 z-30 border-t border-gray-200 bg-white/95 backdrop-blur shadow-[0_-8px_24px_-12px_rgba(0,0,0,0.15)] px-5 py-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-600 text-white text-sm font-bold" x-text="count"></span>
                <p class="text-sm text-gray-700">
                    <span class="font-semibold" x-text="count"></span>
                    contribution<span x-show="count !== 1">s</span> selected
                </p>
                <button type="button"
                        @click="toggleAll(false); $refs.bulkCheckboxes.forEach(cb => cb.checked = false)"
                        class="text-xs font-medium text-gray-500 hover:text-gray-700 underline underline-offset-2">
                    Clear
                </button>
            </div>

            <div class="flex items-center gap-2">
                <input type="text"
                       x-ref="bulkRejectionReason"
                       name="rejection_reason_bulk"
                       placeholder="Reason for rejection (required for reject)"
                       class="text-sm w-64 rounded-lg border-gray-300 bg-white px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">

                <button type="button"
                        @click="$dispatch('bulk-confirm-verify', { count: count })"
                        :disabled="count === 0"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg bg-green-600 text-white hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Verify selected
                </button>

                <button type="button"
                        @click="
                            const reason = ($refs.bulkRejectionReason?.value || '').trim();
                            if (!reason) { alert('Please enter a rejection reason.'); return; }
                            // Stamp the rejection reason into the bulk form before submission.
                            $refs.bulkForm.querySelector('[name=rejection_reason]').value = reason;
                            $dispatch('bulk-confirm-reject', { count: count });
                        "
                        :disabled="count === 0"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                    Reject selected
                </button>
            </div>
        </div>
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
            // Flip above if it would overflow the viewport bottom.
            if (top + cardHeight > window.innerHeight - margin) {
                top = rect.top - cardHeight - margin;
            }
            let left = rect.left + rect.width / 2 - cardWidth / 2;
            // Clamp horizontally to the viewport.
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
