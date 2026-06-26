<section class="space-y-6">
    <header class="flex items-start gap-4">
        <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 text-white shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </span>
        <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Profile information</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Update your name and email address.</p>
        </div>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

    <form method="post"
          action="{{ route('profile.update', $organization ? ['organization' => $organization->slug] : []) }}"
          class="space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Name" />
            <x-text-input id="name"
                          name="name"
                          type="text"
                          class="mt-2 block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30"
                          :value="old('name', $user->name)"
                          required
                          autofocus
                          autocomplete="name" />
            <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email"
                          name="email"
                          type="email"
                          class="mt-2 block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30"
                          :value="old('email', $user->email)"
                          required
                          autocomplete="username" />
            <x-input-error class="mt-1.5" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 ring-1 ring-amber-200 dark:ring-amber-800 p-3">
                    <p class="text-sm text-amber-800 dark:text-amber-200">
                        Your email address is unverified.
                        <button form="send-verification" class="font-semibold underline underline-offset-2 hover:text-amber-900">
                            Click here to re-send the verification email.
                        </button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-emerald-700">A new verification link has been sent.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-brand-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition">
                Save changes
            </button>
            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                   class="inline-flex items-center gap-1.5 text-sm text-emerald-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Saved.
                </p>
            @endif
        </div>
    </form>
</section>