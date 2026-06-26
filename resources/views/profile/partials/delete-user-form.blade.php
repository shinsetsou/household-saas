<section class="space-y-6">
    <header class="flex items-start gap-4">
        <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 text-white shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
        </span>
        <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Delete account</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data you wish to retain.
            </p>
        </div>
    </header>

    <button type="button"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-50 dark:bg-red-900/20 px-4 py-2.5 text-sm font-semibold text-red-700 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800 hover:bg-red-100 dark:hover:bg-red-900/40 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
        Delete account
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-red-100 text-red-600 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>

            <h2 class="text-lg font-bold text-slate-900">Are you sure?</h2>
            <p class="mt-2 text-sm text-slate-600">
                Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Password" class="sr-only" />
                <x-password-input name="password" label="Password" autocomplete="current-password" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                <x-danger-button>Delete account</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>