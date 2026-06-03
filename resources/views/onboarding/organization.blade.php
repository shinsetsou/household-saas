<x-onboarding.layout :step="1" title="Create your organization">
    <h1 class="text-2xl font-bold tracking-tight">Create your organization</h1>
    <p class="mt-2 text-slate-600">Name your HOA and set the monthly due. You can change these later in settings.</p>

    <form method="POST" action="{{ route('onboarding.organization.store') }}" class="mt-8 space-y-5" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">Organization name</label>
            <input id="name" name="name" type="text" required autofocus value="{{ old('name') }}"
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="monthly_due_amount" class="block text-sm font-medium text-slate-700">Monthly due (&#8369;)</label>
            <input id="monthly_due_amount" name="monthly_due_amount" type="number" step="0.01" min="0" required value="{{ old('monthly_due_amount', 200) }}"
                   class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
            <p class="mt-1 text-xs text-slate-500">The amount each household contributes per month.</p>
            @error('monthly_due_amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="pt-2">
            <button type="submit" :disabled="submitting"
                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold shadow-sm transition disabled:opacity-60">
                <span x-show="!submitting">Continue to households</span>
                <span x-show="submitting" x-cloak>Creating&hellip;</span>
            </button>
        </div>
    </form>
</x-onboarding.layout>
