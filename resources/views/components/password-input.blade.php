@props(['name' => 'password', 'label' => 'Password', 'autocomplete' => 'current-password', 'value' => null])

<div x-data="{
        show: false,
        value: @js($value ?? old($name, '')),
        strength: 0,
        label: 'Too short',
        score() {
            const v = this.value;
            if (!v || v.length < 8) { this.strength = 0; this.label = 'At least 8 characters'; return; }
            let s = 0;
            if (v.length >= 8) s++;
            if (v.length >= 12) s++;
            if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
            if (/\d/.test(v)) s++;
            if (/[^A-Za-z0-9]/.test(v)) s++;
            // Map to 0..4 buckets
            const bucket = s <= 1 ? 1 : s === 2 ? 2 : s === 3 ? 3 : 4;
            this.strength = bucket;
            this.label = ['Too short', 'Weak', 'Fair', 'Good', 'Strong'][bucket];
        },
    }"
     x-init="score()"
     class="space-y-2">

    <x-input-label :for="$name" :value="$label" />

    <div class="relative auth-input rounded-xl">
        <input :id="@js($name)"
               :type="show ? 'text' : 'password'"
               name="{{ $name }}"
               x-model="value"
               @input="score()"
               {{ $attributes->merge(['class' => 'block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 pr-12 px-4 py-3 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition']) }}
               autocomplete="{{ $autocomplete }}" />
        <button type="button"
                @click="show = !show"
                :aria-label="show ? 'Hide password' : 'Show password'"
                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition">
            <svg x-show="!show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
            </svg>
        </button>
    </div>

    {{-- Strength meter (only when password is being set/reset) --}}
    @if($autocomplete === 'new-password')
        <div class="flex items-center gap-1.5" aria-live="polite">
            <span :class="strength >= 1 ? 'pw-bar on-' + strength : 'pw-bar'"></span>
            <span :class="strength >= 2 ? 'pw-bar on-' + strength : 'pw-bar'"></span>
            <span :class="strength >= 3 ? 'pw-bar on-' + strength : 'pw-bar'"></span>
            <span :class="strength >= 4 ? 'pw-bar on-' + strength : 'pw-bar'"></span>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400" x-text="label"></p>
    @endif

    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>