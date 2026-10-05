@extends('organizations.settings.layout', ['activeTab' => 'general'])

@section('tab-content')

<div class="max-w-2xl bg-white border border-gray-200 rounded-xl shadow-sm p-6">
    <form method="POST" action="{{ route('organizations.settings.update-general', $organization->slug) }}" class="space-y-5">
        @csrf
        @method('PATCH')

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Organization name</label>
            <input id="name" name="name" type="text" required
                   value="{{ old('name', $organization->name) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="monthly_due_amount" class="block text-sm font-medium text-gray-700">Monthly due (&#8369;)</label>
            <input id="monthly_due_amount" name="monthly_due_amount" type="number" step="0.01" min="0" required
                   value="{{ old('monthly_due_amount', $organization->monthly_due_amount) }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            <p class="mt-1 text-xs text-gray-500">Each household contributes this amount per month.</p>
            @error('monthly_due_amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="manual_payment_instructions" class="block text-sm font-medium text-gray-700">Manual payment instructions</label>
            <textarea id="manual_payment_instructions" name="manual_payment_instructions" rows="6"
                      class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('manual_payment_instructions', $organization->manual_payment_instructions) }}</textarea>
            <p class="mt-1 text-xs text-gray-500">Shown on the public lookup page and payment form when filled in. Leave blank to hide.</p>
            @error('manual_payment_instructions')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="pt-2">
            <button type="submit" class="inline-flex items-center px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition-colors shadow-sm">
                Save changes
            </button>
        </div>
    </form>
</div>

@endsection
