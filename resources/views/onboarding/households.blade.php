<x-onboarding.layout :step="2" title="Add households — BarangayBooks">
    <div class="flex items-center gap-3">
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 text-white shadow-brand-glow">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        </span>
        <p class="text-xs font-semibold uppercase tracking-wider text-brand-600">Step 2 of 3</p>
    </div>

    <h1 class="mt-5 text-3xl font-extrabold tracking-tight">Add your households</h1>
    <p class="mt-2 text-slate-600 dark:text-slate-400">
        Upload a CSV of your households. You can skip this and add them later.
    </p>

    @if ($errors->any())
        <div class="mt-6 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
            <p class="text-sm font-semibold text-red-800 dark:text-red-200">We couldn't import your CSV:</p>
            <ul class="mt-2 list-disc list-inside text-sm text-red-700 dark:text-red-300 space-y-0.5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-8 rounded-xl bg-slate-50 dark:bg-slate-800/50 ring-1 ring-slate-200 dark:ring-slate-700 p-5">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">CSV format</p>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                    Header row must be exactly <code class="px-1.5 py-0.5 bg-white dark:bg-slate-900 rounded font-mono text-[11px]">name,block,lot</code>. Block and lot must be whole numbers.
                </p>
            </div>
            <a href="/samples/households-sample.csv" download
               class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:text-brand-700 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M4 6h16"/></svg>
                Sample
            </a>
        </div>

        <div class="mt-4 overflow-x-auto rounded-lg ring-1 ring-slate-200 dark:ring-slate-700">
            <table class="min-w-full text-xs">
                <thead class="bg-slate-100 dark:bg-slate-800">
                    <tr class="text-slate-600 dark:text-slate-300">
                        <th class="text-left px-3 py-2 font-semibold">name</th>
                        <th class="text-left px-3 py-2 font-semibold">block</th>
                        <th class="text-left px-3 py-2 font-semibold">lot</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200">
                    <tr class="border-t border-slate-200 dark:border-slate-700"><td class="px-3 py-2">Cruz Family</td><td class="px-3 py-2">7</td><td class="px-3 py-2">12</td></tr>
                    <tr class="border-t border-slate-200 dark:border-slate-700"><td class="px-3 py-2">Reyes Family</td><td class="px-3 py-2">3</td><td class="px-3 py-2">4</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <form method="POST"
          action="{{ route('onboarding.households.store') }}"
          enctype="multipart/form-data"
          class="mt-6"
          x-data="{ filename: '', dragOver: false }">
        @csrf

        <label
            @dragover.prevent="dragOver = true"
            @dragleave="dragOver = false"
            @drop.prevent="dragOver = false; $event.target.querySelector('input[type=file]').files = $event.dataTransfer.files; $event.target.querySelector('input[type=file]').dispatchEvent(new Event('change'))"
            :class="dragOver ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20' : 'border-slate-300 dark:border-slate-700'"
            class="relative flex flex-col items-center justify-center w-full rounded-2xl border-2 border-dashed bg-white dark:bg-slate-900 px-6 py-10 text-center transition cursor-pointer">
            <svg class="w-10 h-10 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                <span class="text-brand-600">Click to upload</span> or drag and drop
            </p>
            <p class="mt-1 text-xs text-slate-500">CSV files only</p>
            <p x-show="filename" x-cloak class="mt-3 text-xs font-mono text-slate-600 dark:text-slate-300">Selected: <span x-text="filename"></span></p>
            <input type="file"
                   name="file"
                   accept=".csv,text/csv"
                   required
                   @change="filename = $event.target.files[0]?.name || ''"
                   class="absolute inset-0 opacity-0 cursor-pointer" />
        </label>

        <div class="mt-6 flex items-center justify-between">
            <button type="button"
                    onclick="document.getElementById('skip-form').submit()"
                    class="text-sm font-semibold text-slate-500 hover:text-slate-700 underline underline-offset-2">
                Skip for now →
            </button>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-violet-600 px-6 py-3 text-sm font-semibold text-white shadow-brand-glow hover:from-brand-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition">
                Upload and continue
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </div>
    </form>

    <form id="skip-form" method="POST" action="{{ route('onboarding.households.skip') }}" class="hidden">
        @csrf
    </form>
</x-onboarding.layout>