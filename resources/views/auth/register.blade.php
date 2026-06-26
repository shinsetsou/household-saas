<x-guest-layout
    :title="'Create your account — BarangayBooks'"
    panel-title="Start collecting dues in minutes."
    panel-subtitle="Free 14-day trial. No credit card needed. Set up your HOA, invite your treasurer, and you're done.">

    <x-slot:panelFeatures>
        <li class="flex items-start gap-3">
            <span class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white/10 ring-1 ring-white/20">
                <span class="font-bold">1</span>
            </span>
            <span class="text-sm">Sign up — takes 30 seconds.</span>
        </li>
        <li class="flex items-start gap-3">
            <span class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white/10 ring-1 ring-white/20">
                <span class="font-bold">2</span>
            </span>
            <span class="text-sm">Name your HOA and set the monthly due.</span>
        </li>
        <li class="flex items-start gap-3">
            <span class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white/10 ring-1 ring-white/20">
                <span class="font-bold">3</span>
            </span>
            <span class="text-sm">Import households from a CSV — or add them later.</span>
        </li>
    </x-slot:panelFeatures>

    <div>
        <h2 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Create your account</h2>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            Already have one?
            <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Sign in</a>
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <x-input-label for="name" value="Your name" />
            <x-text-input id="name"
                          type="text"
                          name="name"
                          :value="old('name')"
                          required
                          autofocus
                          autocomplete="name"
                          placeholder="Maria Santos"
                          class="mt-2 block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30" />
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="email" value="Email address" />
            <x-text-input id="email"
                          type="email"
                          name="email"
                          :value="old('email')"
                          required
                          autocomplete="username"
                          placeholder="you@yourhoa.ph"
                          class="mt-2 block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-3 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <x-password-input name="password" label="Create a password" autocomplete="new-password" />

        <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-violet-600 px-4 py-3 text-sm font-semibold text-white shadow-brand-glow hover:from-brand-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition">
            Create account
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>

        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-slate-200 dark:border-slate-800"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="px-3 bg-slate-50 dark:bg-slate-950 text-xs uppercase tracking-wider text-slate-400">or sign up with</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#EA4335" d="M12 11v3.2h4.5c-.2 1.2-1.4 3.4-4.5 3.4-2.7 0-4.9-2.2-4.9-5s2.2-5 4.9-5c1.5 0 2.6.6 3.2 1.2l2.2-2.1C15.9 5.4 14.1 4.6 12 4.6 7.7 4.6 4.2 8.1 4.2 12.4S7.7 20.2 12 20.2c6.9 0 7.6-6 7.6-7.2v-.9H12z"/></svg>
                Google
            </button>
            <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 .5C5.7.5.7 5.5.7 11.8c0 5 3.3 9.3 7.8 10.8.6.1.8-.3.8-.6v-2c-3.2.7-3.9-1.5-3.9-1.5-.5-1.3-1.3-1.6-1.3-1.6-1-.7.1-.7.1-.7 1.2.1 1.8 1.2 1.8 1.2 1 1.7 2.7 1.2 3.4.9.1-.8.4-1.3.8-1.6-2.6-.3-5.3-1.3-5.3-5.8 0-1.3.5-2.3 1.2-3.1-.1-.3-.5-1.5.1-3.1 0 0 1-.3 3.2 1.2.9-.3 2-.4 3-.4s2 .1 3 .4c2.2-1.5 3.2-1.2 3.2-1.2.6 1.6.2 2.8.1 3.1.7.8 1.2 1.8 1.2 3.1 0 4.5-2.7 5.5-5.3 5.8.4.4.8 1.1.8 2.2v3.3c0 .3.2.7.8.6 4.5-1.5 7.8-5.8 7.8-10.8C23.3 5.5 18.3.5 12 .5z"/></svg>
                GitHub
            </button>
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">
            By creating an account you agree to our
            <a href="/terms" class="underline hover:text-slate-600">Terms</a>
            and
            <a href="/privacy" class="underline hover:text-slate-600">Privacy Policy</a>.
        </p>
    </form>
</x-guest-layout>