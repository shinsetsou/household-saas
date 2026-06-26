<x-guest-layout
    :title="'Confirm password — BarangayBooks'"
    panel-title="Confirm your password."
    panel-subtitle="This is a secure area. Please confirm your password to continue.">

    <div>
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-pink-500 text-white shadow-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <h2 class="mt-5 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Secure area</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            Please confirm your password to continue.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-8 space-y-5">
        @csrf

        <x-password-input name="password" label="Password" autocomplete="current-password" />

        <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-violet-600 px-4 py-3 text-sm font-semibold text-white shadow-brand-glow hover:from-brand-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition">
            Confirm
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>
    </form>
</x-guest-layout>