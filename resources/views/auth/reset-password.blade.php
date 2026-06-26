<x-guest-layout
    :title="'Choose a new password — BarangayBooks'"
    panel-title="Choose a new password."
    panel-subtitle="Pick something long and memorable. At least 8 characters, ideally with a number and symbol.">

    <div>
        <h2 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Set a new password</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            Reset link expired?
            <a href="{{ route('password.request') }}" class="font-semibold text-brand-600 hover:text-brand-700">Request a new one</a>
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" value="Email address" />
            <x-text-input id="email"
                          type="email"
                          name="email"
                          :value="old('email', $request->email)"
                          required
                          autofocus
                          autocomplete="username"
                          class="mt-2 block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <x-password-input name="password" label="New password" autocomplete="new-password" />

        <x-password-input name="password_confirmation" label="Confirm new password" autocomplete="new-password" />

        <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-violet-600 px-4 py-3 text-sm font-semibold text-white shadow-brand-glow hover:from-brand-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition">
            Reset password
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>
    </form>
</x-guest-layout>