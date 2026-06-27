<x-guest-layout
    :title="'Set your password — BarangayBooks'"
    panel-title="Almost there."
    panel-subtitle="You were invited to join an HOA. Pick a password to finish setting up your account.">

    <div>
        <h2 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Set your password</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            Signing in as
            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $email }}</span>
        </p>
    </div>

    <form method="POST" action="{{ route('password.set.store') }}" class="mt-8 space-y-5">
        @csrf

        <x-password-input name="password" label="New password" autocomplete="new-password" />
        <x-password-input name="password_confirmation" label="Confirm new password" autocomplete="new-password" />

        <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-violet-600 px-4 py-3 text-sm font-semibold text-white shadow-brand-glow hover:from-brand-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition">
            Set password &amp; continue
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>
    </form>

    <p class="mt-6 text-center text-xs text-slate-500 dark:text-slate-400">
        Wrong invitation?
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="font-semibold text-brand-600 hover:text-brand-700">Sign out</button>
        </form>
        and use the original link instead.
    </p>
</x-guest-layout>
