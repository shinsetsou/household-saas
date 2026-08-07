@props([
    'state' => 'sales',           // 'sales' | 'support'
    'endpoint' => '/api/ai/sales/inquire',
    'greeting' => null,
    'placeholder' => null,
    'sessionKey' => null,
    'organizationSlug' => null,
    'csrfToken' => null,
])

@php
    $sessionKey = $sessionKey ?: ($state === 'support' ? 'bb_chat_support_session' : 'bb_chat_sales_session');
    $headerText = $state === 'support'
        ? 'Support chat'
        : 'Chat with us';
    $accent = $state === 'support' ? 'emerald' : 'brand';
    $greeting = $greeting ?: ($state === 'support'
        ? 'Hi! Ask anything about using BarangayBooks.'
        : 'Hi! Curious about BarangayBooks for your HOA?');
    $placeholder = $placeholder ?: 'Type your message...';
    $buttonLabel = $state === 'support' ? 'Help' : 'Chat';
    $csrfToken = $csrfToken ?: csrf_token();
@endphp

<div
    x-data="bbChatWidget({ endpoint: @js($endpoint), state: @js($state), sessionKey: @js($sessionKey), organizationSlug: @js($organizationSlug), greeting: @js($greeting), csrfToken: @js($csrfToken), accent: @js($accent) })"
    x-init="init()"
    x-cloak
    class="fixed bottom-4 right-4 z-50 flex flex-col items-end"
>
    <div
        x-show="open"
        x-transition.opacity
        @click.outside="open = false"
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
                    :disabled="pending || !input.trim()"
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
    @push('head')
        <style>[x-cloak] { display: none !important; }</style>
        {{-- Pre-scan: tell Tailwind CDN we use these dynamic accent classes --}}
        <span class="hidden bg-brand-600 bg-brand-700 hover:bg-brand-600 hover:bg-brand-700 ring-brand-500 focus:ring-brand-500 shadow-brand-900/30 ring-brand-700/10 bg-emerald-600 bg-emerald-700 hover:bg-emerald-600 hover:bg-emerald-700 ring-emerald-500 focus:ring-emerald-500 shadow-emerald-900/30 ring-emerald-700/10"></span>
    @endpush

    @push('scripts')
        <script>
            window.bbChatWidget = function({ endpoint, state, sessionKey, organizationSlug, greeting, csrfToken, accent }) {
                return {
                    open: false,
                    pending: false,
                    input: '',
                    error: '',
                    sessionId: localStorage.getItem(sessionKey) || null,
                    messages: [],

                    init() {
                        if (greeting) {
                            this.messages.push({ id: 'g', role: 'assistant', content: greeting });
                        }
                        if (!this.sessionId && state === 'sales') {
                            this.sessionId = (crypto.randomUUID ? crypto.randomUUID() : 'sales-' + Math.random().toString(36).slice(2) + '-' + Date.now());
                            localStorage.setItem(sessionKey, this.sessionId);
                        }
                        this.$watch('open', (v) => {
                            if (v) {
                                this.$nextTick(() => this.$refs.input?.focus());
                                this.scrollToBottom();
                            }
                        });
                    },

                    scrollToBottom() {
                        const el = this.$refs.transcript;
                        if (el) el.scrollTop = el.scrollHeight;
                    },

                    async send() {
                        const text = this.input.trim();
                        if (!text || this.pending) return;
                        this.error = '';
                        this.input = '';
                        this.messages.push({ id: 'u-' + Date.now(), role: 'user', content: text });
                        this.pending = true;
                        this.$nextTick(this.scrollToBottom);

                        const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
                        if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;
                        if (state === 'sales') headers['X-Session-Id'] = this.sessionId;

                        const body = { message: text };
                        if (state === 'support') {
                            body.organization_slug = organizationSlug;
                        } else {
                            body.session_id = this.sessionId;
                        }

                        try {
                            const res = await fetch(endpoint, {
                                method: 'POST',
                                headers,
                                body: JSON.stringify(body),
                                credentials: 'same-origin',
                            });

                            if (res.status === 429) {
                                throw new Error('Too many requests. Please slow down and try again in a minute.');
                            }
                            if (res.status === 402) {
                                throw new Error('Our AI assistant has reached its daily limit. Please try again tomorrow.');
                            }
                            if (!res.ok) {
                                const data = await res.json().catch(() => ({}));
                                throw new Error(data.message || data.error || 'Sorry, something went wrong.');
                            }

                            const data = await res.json();
                            const reply = data.reply || '';
                            this.messages.push({ id: 'a-' + Date.now(), role: 'assistant', content: reply });
                            if (data.session_id) {
                                this.sessionId = data.session_id;
                                localStorage.setItem(sessionKey, data.session_id);
                            }
                        } catch (e) {
                            this.error = e.message;
                            this.messages.push({ id: 'e-' + Date.now(), role: 'assistant', content: 'Sorry, I could not reach our assistant right now. Please try again.' });
                        } finally {
                            this.pending = false;
                            this.$nextTick(this.scrollToBottom);
                        }
                    },
                };
            };
        </script>
    @endpush
@endonce
