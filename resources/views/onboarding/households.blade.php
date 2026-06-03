<x-onboarding.layout :step="2" title="Add your households">
    <h1 class="text-2xl font-bold tracking-tight">Add your households</h1>
    <p class="mt-2 text-slate-600">Upload a CSV with your households. You can skip this and add them later.</p>

    @if ($errors->any())
        <div class="mt-4 rounded-lg bg-red-50 border border-red-200 p-4">
            <p class="text-sm font-semibold text-red-800">We couldn't import your CSV:</p>
            <ul class="mt-2 list-disc list-inside text-sm text-red-700 space-y-0.5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-6 rounded-lg bg-slate-50 border border-slate-200 p-4">
        <p class="text-sm font-semibold text-slate-800">CSV format</p>
        <p class="mt-1 text-xs text-slate-600">Header row must be exactly <code class="px-1 bg-white rounded">name,block,lot</code>. Block and lot must be whole numbers.</p>

        <div class="mt-3 overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead>
                    <tr class="text-slate-600">
                        <th class="text-left px-3 py-1.5 bg-white border border-slate-200">name</th>
                        <th class="text-left px-3 py-1.5 bg-white border border-slate-200">block</th>
                        <th class="text-left px-3 py-1.5 bg-white border border-slate-200">lot</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
                    <tr><td class="px-3 py-1.5 bg-white border border-slate-200">Cruz Family</td><td class="px-3 py-1.5 bg-white border border-slate-200">7</td><td class="px-3 py-1.5 bg-white border border-slate-200">12</td></tr>
                    <tr><td class="px-3 py-1.5 bg-white border border-slate-200">Reyes Family</td><td class="px-3 py-1.5 bg-white border border-slate-200">3</td><td class="px-3 py-1.5 bg-white border border-slate-200">4</td></tr>
                </tbody>
            </table>
        </div>

        <a href="/samples/households-sample.csv" download
           class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 hover:text-brand-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M4 6h16"/></svg>
            Download sample households-sample.csv
        </a>
    </div>

    <form method="POST" action="{{ route('onboarding.households.store') }}" enctype="multipart/form-data" class="mt-6" x-data="{ filename: '' }">
        @csrf
        <label class="block">
            <span class="block text-sm font-medium text-slate-700">CSV file</span>
            <input type="file" name="file" accept=".csv,text/csv" required
                   @change="filename = $event.target.files[0]?.name || ''"
                   class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100" />
            <p x-show="filename" x-cloak class="mt-1 text-xs text-slate-500">Selected: <span class="font-mono" x-text="filename"></span></p>
        </label>

        <div class="mt-6">
            <button type="submit" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold shadow-sm transition">
                Upload and continue
            </button>
        </div>
    </form>

    <form method="POST" action="{{ route('onboarding.households.skip') }}" class="mt-4">
        @csrf
        <button type="submit" class="text-sm font-medium text-slate-600 hover:text-slate-900 underline underline-offset-2">
            Skip for now &rarr;
        </button>
    </form>
</x-onboarding.layout>
