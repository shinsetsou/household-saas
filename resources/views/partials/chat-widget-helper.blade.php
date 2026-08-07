<style>[x-cloak] { display: none !important; }</style>
<span class="hidden bg-brand-600 bg-brand-700 hover:bg-brand-600 hover:bg-brand-700 ring-brand-500 focus:ring-brand-500 shadow-brand-900/30 ring-brand-700/10 bg-emerald-600 bg-emerald-700 hover:bg-emerald-600 hover:bg-emerald-700 ring-emerald-500 focus:ring-emerald-500 shadow-emerald-900/30 ring-emerald-700/10"></span>
<script>
window.bbChatWidget = function({ endpoint, state, sessionKey, organizationSlug, greeting, csrfToken, accent }) {
    return {
        open: false,
        pending: false,
        input: '',
        error: '',
        sessionId: localStorage.getItem(sessionKey) || null,
        messages: [],
        greeting: greeting,

        init() {
            if (this.greeting) {
                this.messages.push({ id: 'g', role: 'assistant', content: this.greeting });
            }
            if (!this.sessionId && state === 'sales') {
                this.sessionId = (crypto.randomUUID ? crypto.randomUUID() : 'sales-' + Math.random().toString(36).slice(2) + '-' + Date.now());
                localStorage.setItem(sessionKey, this.sessionId);
            }
            this.$watch('open', (v) => {
                if (v) {
                    this.$nextTick(() => {
                        if (this.$refs.input) this.$refs.input.focus();
                        this.scrollToBottom();
                    });
                }
            });
        },

        scrollToBottom() {
            const el = this.$refs.transcript;
            if (el) el.scrollTop = el.scrollHeight;
        },

        async send() {
            const text = (this.input || '').trim();
            if (!text || this.pending) return;
            this.error = '';
            this.input = '';
            this.messages.push({ id: 'u-' + Date.now(), role: 'user', content: text });
            this.pending = true;
            this.$nextTick(() => this.scrollToBottom());

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
                this.$nextTick(() => this.scrollToBottom());
            }
        },
    };
};
</script>
