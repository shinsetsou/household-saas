@extends('layouts.layout')

@section('title', 'Submit Payment — ' . $household->name)

@section('content')

<nav class="flex items-center gap-2 text-xs text-slate-500 mb-4">
    <a href="{{ route('home', $organization->slug) }}" class="hover:text-brand-600 transition-colors">Household lookup</a>
    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg>
    <span class="text-slate-700 font-medium">{{ $household->name }}</span>
    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg>
    <span class="text-slate-700 font-medium">Submit payment</span>
</nav>

<x-page-header
    :eyebrow="$household->name . ' · Block '.$household->block.' · Lot '.$household->lot"
    title="Submit payment"
    subtitle="Upload a photo of your receipt so the treasurer can verify your payment."
    tone="emerald"
    icon="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
    <x-slot:actions>
        <x-brand-button variant="secondary" :href="route('home', $organization->slug)" icon="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z">Find another</x-brand-button>
    </x-slot:actions>
</x-page-header>

<div class="mt-8 max-w-2xl mx-auto">
    <div class="rounded-2xl bg-white dark:bg-gray-800 ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm overflow-hidden">

        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex items-center gap-3">
            <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 text-white shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </span>
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Payment details</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">All fields except billing month are required.</p>
            </div>
        </div>

        <form action="{{ URL::signedRoute('contributions.store', [$organization, $household]) }}" method="POST"
              enctype="multipart/form-data"
              id="contribution-form"
              x-data="{ submitting: false }"
              @submit="submitting = true"
              class="px-6 py-6 space-y-5">
            @csrf

            {{-- Amount --}}
            <div>
                <label for="amount" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    Amount <span class="text-rose-500">*</span>
                </label>
                <div class="relative mt-2">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 font-semibold text-sm pointer-events-none">₱</span>
                    <input type="number" name="amount" id="amount"
                           value="{{ old('amount') }}"
                           placeholder="0.00"
                           step="0.01" min="0"
                           class="block w-full pl-9 pr-4 py-3 text-base font-semibold rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:bg-white dark:focus:bg-slate-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none"
                           required>
                </div>
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                    Monthly due is <span class="font-semibold text-slate-700 dark:text-slate-200">₱{{ number_format($organization->monthly_due_amount, 2) }}</span>.
                    Overpayments are automatically split into future months.
                </p>
                @error('amount')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            {{-- Billing month --}}
            <div>
                <label for="month_for" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Billing month</label>
                <input type="month" name="month_for" id="month_for"
                       value="{{ old('month_for', now()->format('Y-m')) }}"
                       max="{{ now()->format('Y-m') }}"
                       class="mt-2 block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-4 py-3 text-sm focus:bg-white dark:focus:bg-slate-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                    Defaults to the current month. Pick an earlier month if you're paying arrears — the treasurer can correct this during verification.
                </p>
                @error('month_for')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            {{-- Photo upload --}}
            <div>
                <label for="photo" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    Proof of payment <span class="text-rose-500">*</span>
                </label>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    JPG, PNG, or GIF up to 2 MB. A clear photo of the receipt speeds up verification.
                </p>

                <label for="photo" id="upload-area"
                       class="relative mt-2 flex flex-col items-center justify-center w-full h-44 border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl cursor-pointer hover:border-brand-400 bg-slate-50 dark:bg-slate-900 hover:bg-brand-50/40 dark:hover:bg-brand-900/20 transition-colors">
                    <div id="upload-placeholder" class="flex flex-col items-center gap-2 pointer-events-none">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-white dark:bg-slate-800 ring-1 ring-slate-200 dark:ring-slate-700 text-brand-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </span>
                        <p class="text-sm text-slate-700 dark:text-slate-200">
                            <span class="font-semibold text-brand-600">Click to upload</span> or drag and drop
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">JPEG, PNG, GIF up to 2MB</p>
                    </div>
                    <img id="imagePreview" class="absolute inset-0 w-full h-full object-contain rounded-2xl hidden p-2" alt="Preview">
                    <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/gif"
                           class="sr-only" required onchange="previewImage(event)">
                </label>

                @error('photo')<p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 pt-2">
                <a href="{{ route('home', $organization->slug) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancel
                </a>
                <button type="submit" id="submit-btn" :disabled="submitting"
                        class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-green-600 px-4 py-3 text-sm font-semibold text-white shadow-brand-glow hover:from-emerald-600 hover:to-green-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <span x-show="!submitting">Submit contribution</span>
                    <span x-show="submitting" x-cloak>Submitting…</span>
                    <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </div>
        </form>
    </div>

    <p class="mt-4 text-center text-xs text-slate-400 dark:text-slate-500">
        Your treasurer will review your payment and mark it as verified.
        You'll receive a confirmation email once approved.
    </p>
</div>

<script>
    function previewImage(event) {
        const input = event.target;
        const reader = new FileReader();
        const preview = document.getElementById('imagePreview');
        const placeholder = document.getElementById('upload-placeholder');

        reader.onload = function () {
            preview.src = reader.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        };

        if (input.files && input.files[0]) {
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

@endsection