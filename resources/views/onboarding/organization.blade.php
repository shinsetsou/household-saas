<x-onboarding.layout :step="1" title="Welcome — BarangayBooks">
    <div class="flex items-center gap-3">
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 text-white shadow-brand-glow">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        </span>
        <p class="text-xs font-semibold uppercase tracking-wider text-brand-600">Step 1 of 3</p>
    </div>

    <h1 class="mt-5 text-3xl font-extrabold tracking-tight">Let's set up your HOA</h1>
    <p class="mt-2 text-slate-600 dark:text-slate-400">
        Tell us your organization's name and how much each household owes per month. You can change these later in settings.
    </p>

    <form method="POST" action="{{ route('onboarding.organization.store') }}"
          class="mt-8 space-y-5"
          x-data="{ submitting: false }"
          @submit="submitting = true">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Organization name</label>
            <input id="name"
                   name="name"
                   type="text"
                   required
                   autofocus
                   value="{{ old('name') }}"
                   placeholder="Sampaguita Village HOA"
                   class="mt-2 block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none" />
            @error('name')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="monthly_due_amount" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Monthly due (₱)</label>
            <div class="relative mt-2">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 font-medium pointer-events-none">₱</span>
                <input id="monthly_due_amount"
                       name="monthly_due_amount"
                       type="number"
                       step="0.01"
                       min="0"
                       required
                       value="{{ old('monthly_due_amount', 200) }}"
                       class="block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 pl-9 pr-4 py-3 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none" />
            </div>
            <p class="mt-1.5 text-xs text-slate-500">The amount each household contributes per month.</p>
            @error('monthly_due_amount')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="pt-2 flex items-center justify-between">
            <p class="text-xs text-slate-400">Takes about 30 seconds.</p>
            <button type="submit" :disabled="submitting"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-violet-600 px-6 py-3 text-sm font-semibold text-white shadow-brand-glow hover:from-brand-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition disabled:opacity-60 disabled:cursor-not-allowed">
                <span x-show="!submitting">Continue</span>
                <span x-show="submitting" x-cloak>Creating…</span>
                <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </div>
    </form>
</x-onboarding.layout>