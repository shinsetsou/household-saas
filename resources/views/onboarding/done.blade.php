<x-onboarding.layout :step="3" title="All set — BarangayBooks">
    <div class="text-center">
        <div class="mx-auto inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-brand-glow">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="mt-6 text-3xl font-extrabold tracking-tight">You're all set!</h1>
        <p class="mt-2 text-slate-600 dark:text-slate-400">
            <span class="font-semibold text-slate-900 dark:text-white">{{ $organization->name }}</span> is ready to collect contributions.
        </p>
    </div>

    <div class="mt-8 rounded-2xl bg-gradient-to-br from-brand-50 to-violet-50 dark:from-brand-900/30 dark:to-violet-900/30 ring-1 ring-brand-100 dark:ring-brand-800 p-6">
        <dl class="grid grid-cols-2 gap-y-4 text-sm">
            <dt class="text-slate-600 dark:text-slate-300">Organization</dt>
            <dd class="font-bold text-right text-slate-900 dark:text-white">{{ $organization->name }}</dd>

            <dt class="text-slate-600 dark:text-slate-300">Monthly due</dt>
            <dd class="font-bold text-right text-slate-900 dark:text-white">₱{{ number_format((float) $organization->monthly_due_amount, 2) }}</dd>

            <dt class="text-slate-600 dark:text-slate-300">Households</dt>
            <dd class="font-bold text-right text-slate-900 dark:text-white">{{ $householdCount }}</dd>
        </dl>
    </div>

    <a href="{{ route('organizations.index', $organization) }}"
       class="mt-8 block w-full text-center rounded-xl bg-gradient-to-r from-brand-600 to-violet-600 px-6 py-3.5 text-sm font-semibold text-white shadow-brand-glow hover:from-brand-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition">
        Go to your dashboard →
    </a>

    @if ($householdCount === 0)
        <a href="{{ route('households.import.create', $organization) }}"
           class="mt-3 block text-center text-sm font-semibold text-slate-500 hover:text-slate-700 underline underline-offset-2">
            Add households later from settings
        </a>
    @endif
</x-onboarding.layout>