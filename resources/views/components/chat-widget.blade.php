@props([
    'state' => 'sales',
    'endpoint' => '/api/ai/sales/inquire',
    'greeting' => null,
    'placeholder' => null,
    'sessionKey' => null,
    'organizationSlug' => null,
    'csrfToken' => null,
])

@php
    $sessionKey = $sessionKey ?: ($state === 'support' ? 'bb_chat_support_session' : 'bb_chat_sales_session');
    $headerText = $state === 'support' ? 'Support chat' : 'Chat with us';
    $accent = $state === 'support' ? 'emerald' : 'brand';
    $greeting = $greeting ?: ($state === 'support'
        ? 'Hi! Ask anything about using BarangayBooks.'
        : 'Hi! Curious about BarangayBooks for your HOA?');
    $placeholder = $placeholder ?: 'Type your message...';
    $buttonLabel = $state === 'support' ? 'Help' : 'Chat';
    $csrfToken = $csrfToken ?: csrf_token();

    // Escape for both HTML attribute (double-quoted) AND Alpine @js() (single-quoted JS string).
    // @js() is HTML-safe, but apostrophes in the greeting reach Alpine's eval() as "\u0027"
    // which JS decodes correctly.
    $greetingJson = json_encode($greeting, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp

<div
    x-data="window.bbChatWidget ? window.bbChatWidget({ endpoint: @js($endpoint), state: @js($state), sessionKey: @js($sessionKey), organizationSlug: @js($organizationSlug), greeting: {{ $greetingJson }}, csrfToken: @js($csrfToken), accent: @js($accent) }) : {}"
    x-cloak
    class="fixed bottom-4 right-4 z-50 flex flex-col items-end"
>
    <div
        x-show="open"
        x-transition.opacity.duration.200ms
        @click.outside="open = false"
        role="dialog"
        aria-label="Chat with us"
        class="mb-3 w-[min(360px,calc(100vw-2rem))] h-[520px] max-h-[calc(100vh-7rem)] rounded-2xl bg-white dark:bg-slate-900 shadow-2xl shadow-slate-900/20 ring-1 ring-slate-200 dark:ring-slate-800 flex flex-col overflow-hidden"
    >
        <header class="flex items-center justify-between px-4 py-3 bg-{{ $accent }}-600 dark:bg-{{ $accent }}-700 text-white">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-white/15 backdrop-blur flex items-center justify-center text-sm font-bold">
                    <span x-text="state === 'support' ? 'S' : 'B'"></span>
                </div>
                <div>
                    <p class="text-sm font-semibold leading-tight">{{ $headerText }}</p>
                    <p class="text-[11px] opacity-80">Powered by BarangayBooks AI</p>
                </div>
            </div>
            <button type="button" @click="open = false" class="text-white/80 hover:text-white" aria-label="Close chat">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </header>

        <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3 bg-slate-50 dark:bg-slate-950" x-ref="transcript">
            <template x-if="messages && messages.length === 0">
                <div class="rounded-lg bg-white dark:bg-slate-800 ring-1 ring-slate-200 dark:ring-slate-700 px-3 py-2 text-sm text-slate-700 dark:text-slate-200 max-w-[85%]"
                     x-text="greeting || '{{ $greeting }}'"></div>
            </template>
            <template x-for="m in messages" :key="m.id">
                <div :class="m.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div
                        :class="m.role === 'user'
                            ? 'bg-{{ $accent }}-600 text-white rounded-2xl rounded-br-md max-w-[85%] px-3 py-2 text-sm'
                            : 'bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 ring-1 ring-slate-200 dark:ring-slate-700 rounded-2xl rounded-bl-md max-w-[85%] px-3 py-2 text-sm'"
                        x-text="m.content"
                    ></div>
                </div>
            </template>
            <template x-if="pending">
                <div class="flex justify-start">
                    <div class="bg-white dark:bg-slate-800 ring-1 ring-slate-200 dark:ring-slate-700 rounded-2xl rounded-bl-md px-3 py-2 text-sm text-slate-500">
                        <span class="inline-flex gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay:0ms"></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay:120ms"></span>
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay:240ms"></span>
                        </span>
                    </div>
                </div>
            </template>
            <template x-if="error">
                <div class="rounded-lg bg-rose-50 dark:bg-rose-950/40 ring-1 ring-rose-200 dark:ring-rose-800 px-3 py-2 text-xs text-rose-700 dark:text-rose-300" x-text="error"></div>
            </template>
        </div>

        <form @submit.prevent="send()" class="border-t border-slate-200 dark:border-slate-800 px-3 py-3 bg-white dark:bg-slate-900">
            <div class="flex items-end gap-2">
                <textarea
                    x-ref="input"
                    x-model="input"
                    @keydown.enter.prevent.exact="send()"
                    rows="1"
                    placeholder="{{ $placeholder }}"
                    class="flex-1 resize-none rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 focus:ring-2 focus:ring-{{ $accent }}-500 focus:border-transparent outline-none"
                ></textarea>
                <button
                    type="submit"
                    :disabled="pending || !input || !input.trim()"
                    class="px-3 py-2 rounded-xl bg-{{ $accent }}-600 hover:bg-{{ $accent }}-700 disabled:opacity-50 text-white text-sm font-semibold transition"
                >
                    Send
                </button>
            </div>
            <p class="mt-1.5 text-[10px] text-slate-500 dark:text-slate-400">
                AI replies may be inaccurate. For billing or account changes, contact your admin.
            </p>
        </form>
    </div>

    <button
        type="button"
        x-show="!open"
        @click="open = true"
        class="inline-flex items-center gap-2 rounded-full bg-{{ $accent }}-600 hover:bg-{{ $accent }}-700 text-white px-4 py-3 shadow-lg shadow-{{ $accent }}-900/30 ring-1 ring-{{ $accent }}-700/10 font-semibold text-sm transition"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
        <span>{{ $buttonLabel }}</span>
    </button>
</div>

@once
    {{-- The bbChatWidget helper + Tailwind prescan lives in
         resources/views/partials/chat-widget-helper.blade.php
         and is included by every layout that mounts this widget.
         Do NOT add @push directives here — they execute AFTER @stack and
         would land in <body>, racing Alpine for the function definition. --}}
@endonce
