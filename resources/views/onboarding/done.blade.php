<x-onboarding.layout :step="3" title="You're all set">
    <div class="text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 text-emerald-600">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="mt-5 text-2xl font-bold tracking-tight">You're all set</h1>
        <p class="mt-2 text-slate-600">{{ $organization->name }} is ready to collect contributions.</p>
    </div>

    <div class="mt-8 rounded-lg bg-slate-50 border border-slate-200 p-5">
        <dl class="grid grid-cols-2 gap-y-3 text-sm">
            <dt class="text-slate-600">Organization</dt>
            <dd class="font-semibold text-right">{{ $organization->name }}</dd>
            <dt class="text-slate-600">Monthly due</dt>
            <dd class="font-semibold text-right">&#8369;{{ number_format((float) $organization->monthly_due_amount, 2) }}</dd>
            <dt class="text-slate-600">Households</dt>
            <dd class="font-semibold text-right">{{ $householdCount }}</dd>
        </dl>
    </div>

    <a href="{{ route('organizations.index', $organization) }}"
       class="mt-8 block text-center px-6 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold shadow-sm transition">
        Go to your dashboard
    </a>

    @if ($householdCount === 0)
        <a href="{{ route('households.import.create', $organization) }}"
           class="mt-3 block text-center text-sm font-medium text-slate-600 hover:text-slate-900 underline underline-offset-2">
            Add households later from settings &rarr;
        </a>
    @endif
</x-onboarding.layout>
