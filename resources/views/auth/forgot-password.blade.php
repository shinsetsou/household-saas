<x-guest-layout
    :title="'Reset password — BarangayBooks'"
    panel-title="Forgot your password?"
    panel-subtitle="No problem. Enter your email and we'll send you a secure link to choose a new one.">

    <div>
        <h2 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Reset your password</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            Remembered it?
            <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Back to sign in</a>
        </p>
    </div>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email address" />
            <x-text-input id="email"
                          type="email"
                          name="email"
                          :value="old('email')"
                          required
                          autofocus
                          placeholder="you@yourhoa.ph"
                          class="mt-2 block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-violet-600 px-4 py-3 text-sm font-semibold text-white shadow-brand-glow hover:from-brand-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition">
            Email me a reset link
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </button>
    </form>
</x-guest-layout>