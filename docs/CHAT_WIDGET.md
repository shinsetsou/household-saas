# Auth-Aware Chat Widget (Sales → Support)

> PRD + implementation blueprint for the shared chat widget used by the
> public Sales agent and the authenticated Support agent.

---

## 1. Background

BarangayBooks already has the four Phase 1 AI agents wired through
[`docs/AI_SETUP.md`](./AI_SETUP.md) (`founder`, `sales`, `support`,
`customer_success`). The wiring exists; the user-facing surface does not.

Today visitors and authenticated users both interact with the product
through the same static marketing site + Blade app — there is no chat
entry point. This document covers adding a single floating chat widget
that:

- Shows on **anonymous** visitors on the marketing site and routes them to
  the **Sales agent** for conversion (recommend plans, capture emails,
  book demos, automatic follow-up).
- Shows on **logged-in admins and treasurers** inside the tenant app and
  routes them to the **Support agent** (RAG-first L1 support; auto-tickets
  below the similarity threshold).
- Does **not** show to `viewer` roles.
- Maintains **strictly separate histories** for Sales and Support — no
  cross-contamination of conversation context.

Sales and Support were chosen as the first two agents because they
already have declared endpoints (`/api/ai/sales/inquire` and
`/api/ai/support/message`) in `docs/AI_SETUP.md`. The Founder and Customer
Success agents are cron-driven and do not need user-facing surfaces.

## 2. Goals & Non-goals

### Goals

- One shared widget component, two routes, two agents.
- Public Sales endpoint is throttled, cost-capped, and audit-logged.
- Authed Support endpoint respects `EnsureOrganizationRole:admin,treasurer`.
- Sales follow-up emails are dispatched on lead capture and run via
  queued jobs (no blocking the request).
- Every conversation is persisted for analytics and re-engagement.
- Cost per visit stays under `AI_AGENT_SALES_COST_CAP_CENTS=5`.

### Non-goals (deferred)

- Streaming replies (OpenAI SSE). MVP is request/response + UI polling
  for any long-running reply.
- Slack/Discord notifications. `AI_FOUNDER_EMAIL` is the only recipient
  channel for v1 per `docs/AI_SETUP.md:97`.
- A new Conversations History page in the admin UI. Conversations live
  in the DB and `ai_call_logs`; no in-app UI for them.
- Voice input, attachments, or multi-modal. Text only.

## 3. Decisions (locked from prior conversation)

1. **Continuity.** Separate Sales and Support histories. Sales thread is
   private. Support never sees prior Sales context.
2. **Access.** Support widget is gated to `admin` + `treasurer` only via
   `EnsureOrganizationRole:admin,treasurer`. Viewers do not see the
   widget at all (not even an "AI is unavailable" stub).
3. **Provider.** `AI_PROVIDER=openai` in production, `ollama` for local
   dev, `fake` for tests. Switching is config-only.
4. **Streaming.** Off for MVP. Long replies over ~3s surface a "still
   thinking" indicator; widget re-polls after response.
5. **Email follow-up cadence.** Day 0 thank-you, Day 3 nudge, Day 7 demo
   offer. Tracked via `leads.followup_stage`.

## 4. Architecture

### 4.1 Routing

| Auth state | Endpoint | Agent | Middleware stack |
|---|---|---|---|
| Anonymous (marketing pages) | `POST /api/ai/sales/inquire` | `sales` | `throttle:30,1`, `EnforceDailyCostCap` |
| Logged-in `viewer` | _(no widget)_ | — | — |
| Logged-in `admin` or `treasurer` | `POST /api/ai/support/message` | `support` | `web`, `auth`, `verified`, `EnsureOrganizationMember`, `EnsureOrganizationRole:admin,treasurer`, `EnforceDailyCostCap` |

The widget never knows the agent name — it only sees the endpoint URL.
Endpoint selection happens at Blade render time via a `data-auth-state`
marker on `<body>`.

### 4.2 Sales path

```
Widget (Alpine + Axios, marketing layout)
  └─ axios.post('/api/ai/sales/inquire', {
        session_id,        // uuid stored in localStorage
        message,
        page_url,
        prev_messages[]    // last 10 turns from sales_conversations
       })
       └─ SalesAgentController@inquire
            ├─ throttle:30,1
            ├─ EnforceDailyCostCap
            ├─ SalesConversation::firstOrCreate(session_id)
            ├─ append user message
            ├─ AIManager::agent('sales')
            │     ->task('reply_to_customer')
            │     ->context($ctx)
            │     ->run()
            │      ├─ tools: find_knowledge, recommend_plan,
            │      │          capture_email, schedule_followup,
            │      │          handoff_to_human
            │      └─ returns AssistantReply DTO
            ├─ append assistant reply
            ├─ if tool called capture_email → upsert leads row,
            │    dispatch SendSalesFollowupEmail chain
            ├─ if tool called handoff_to_human → fire
            │    SalesHandoffRequested event (mail AI_FOUNDER_EMAIL)
            └─ JSON reply
```

### 4.3 Support path

```
Widget (Alpine + Axios, app layout, admin/treasurer only)
  └─ axios.post('/api/ai/support/message', {
        message,
        page_url,
        org_slug,          // from data-org-slug on <body>
        prev_messages[]    // per-org, per-user, NOT shared with Sales
       })
       └─ SupportAgentController@message
            ├─ auth + verified + EnsureOrganizationMember
            ├─ EnsureOrganizationRole:admin,treasurer (viewer → 403)
            ├─ EnforceDailyCostCap
            ├─ SupportConversation::firstOrCreate(org_id, user_id)
            ├─ AIManager::agent('support')->task('answer')->run()
            │    ├─ find_knowledge (RAG over ai_memories,
            │    │                  threshold 0.55)
            │    └─ if low confidence → create_support_ticket →
            │         return templated fallback reply
            └─ JSON reply
```

### 4.4 Shared chat-history service

A single `ChatSession` service handles Sales/Support loading and
appending. The two implementations are isolated by table, never mix
data: `_sales_conversations` and `_support_conversations` are completely
separate. This enforces the locked decision in §3 even at the data
layer.

```
app/Services/ChatSession.php
  ::sales():       loads/creates SalesConversation by session_id
  ::salesAppend(): appends message, returns last N turns
  ::support():     loads/creates SupportConversation by org+user
  ::supportAppend(): appends message, returns last N turns
```

### 4.5 Tool layer (Sales)

All tools extend `App\AI\Tools\BaseTool` per `docs/AI_SETUP.md:104-117`:

| Tool | Wraps | Effect |
|---|---|---|
| `find_knowledge` | `MemoryStore::search` over `ai_memories` (category filtered) | Returns top-k articles |
| `recommend_plan` | `BillingService::listPlans()` | Returns plan cards (no pricing math in prompt) |
| `capture_email` | `LeadCaptureService::capture()` | Upserts `leads`, queues follow-up chain |
| `schedule_followup` | `LeadCaptureService::scheduleFollowup()` | Sets follow-up stage manually |
| `handoff_to_human` | Event dispatcher | Fires `SalesHandoffRequested` for human email |

### 4.6 Email follow-up chain

Captured by `LeadCaptureService::capture()`. Each step is a separate
queued job; cadence is tracked on `leads.followup_stage`.

```
leads.followup_stage:
  null            ──► SendSalesFollowupEmail(day0)
  day0_sent       ──► SendSalesFollowupEmail(day3)
  day3_sent       ──► SendSalesFollowupEmail(day7)
  day7_sent       ──► (stop)
```

Each step updates `last_followup_at`. If a stage is already past, the
service is a no-op (idempotent).

## 5. Data model

Five tables; idempotent migrations.

### 5.1 `leads`

| column | type | notes |
|---|---|---|
| `id` | bigint pk | |
| `email` | string unique | primary key for upsert |
| `name` | string nullable | |
| `org_size` | string nullable | 'small' \| 'medium' \| 'large' \| null |
| `role` | string nullable | 'president' \| 'treasurer' \| 'secretary' \| 'board' \| null |
| `source` | string | default 'sales_chat' |
| `followup_stage` | string nullable | null \| day0_sent \| day3_sent \| day7_sent |
| `last_followup_at` | timestamp nullable | |
| `metadata` | json nullable | free-form tool-call context |
| `created_at`, `updated_at` | timestamps | |

### 5.2 `sales_conversations`

| column | type | notes |
|---|---|---|
| `id` | bigint pk | |
| `session_id` | string unique | client-generated uuid in localStorage |
| `lead_email` | string nullable | set after capture_email tool runs |
| `last_message_at` | timestamp | for analytics |
| `created_at`, `updated_at` | timestamps | |

### 5.3 `sales_conversation_messages`

| column | type | notes |
|---|---|---|
| `id` | bigint pk | |
| `sales_conversation_id` | foreignId | |
| `role` | string | 'user' \| 'assistant' \| 'tool' |
| `content` | text | |
| `tool_calls` | json nullable | |
| `created_at` | timestamp | |

Index on `(sales_conversation_id, created_at)`.

### 5.4 `support_conversations`

| column | type | notes |
|---|---|---|
| `id` | bigint pk | |
| `organization_id` | foreignId | |
| `user_id` | foreignId | |
| `last_message_at` | timestamp | |
| `created_at`, `updated_at` | timestamps | |

Unique on `(organization_id, user_id)`.

### 5.5 `support_conversation_messages`

| column | type | notes |
|---|---|---|
| `id` | bigint pk | |
| `support_conversation_id` | foreignId | |
| `role` | string | 'user' \| 'assistant' \| 'tool' |
| `content` | text | |
| `tool_calls` | json nullable | |
| `created_at` | timestamp | |

Index on `(support_conversation_id, created_at)`.

### 5.6 `support_tickets` (verify or create)

Referenced in `docs/AI_SETUP.md:60`. We will verify it exists with the
columns:

| column | type | notes |
|---|---|---|
| `id` | bigint pk | |
| `organization_id` | foreignId | nullable (guest tickets have no org) |
| `user_id` | foreignId | nullable |
| `subject` | string | |
| `body` | text | |
| `status` | string | 'open' \| 'closed' |
| `source` | string | 'support_chat' \| 'email' \| etc. |
| `created_at`, `closed_at` | timestamps | |

If the table or these columns are missing, a new migration adds them.

## 6. API contracts

### 6.1 Sales inquiry

`POST /api/ai/sales/inquire`

**Request:**

```json
{
  "session_id": "uuid-v4-string",
  "message": "How much is the Standard plan?",
  "page_url": "https://barangaybooks.com/pricing",
  "prev_messages": [
    {"role": "user", "content": "Hi"},
    {"role": "assistant", "content": "Hello! How can I help?"}
  ]
}
```

Validation:
- `session_id` — required, string, max 64
- `message` — required, string, max 2000
- `page_url` — nullable, URL
- `prev_messages` — nullable, array, max 20 items

**Response 200:**

```json
{
  "reply": "The Standard plan is designed for mid-sized HOAs...",
  "suggested_actions": [
    {"type": "capture_email", "label": "Get pricing by email"},
    {"type": "book_demo", "label": "Book a 15-min demo"}
  ],
  "tool_calls": [
    {"name": "recommend_plan", "arguments": {...}}
  ],
  "session_id": "uuid-v4-string"
}
```

**Errors:**
- `429 Too Many Requests` — throttle exceeded
- `402 Payment Required` — daily cost cap reached (mapped from `AgentFailed`)
- `422 Unprocessable Entity` — validation failed
- `500 Internal Server Error` — provider failure

### 6.2 Support message

`POST /api/ai/support/message`

**Request:**

```json
{
  "message": "How do I import households from CSV?",
  "page_url": "https://barangaybooks.app/sunset-heights/households",
  "prev_messages": []
}
```

Validation:
- `message` — required, string, max 2000
- `page_url` — nullable, URL
- `prev_messages` — nullable, array

**Response 200:**

```json
{
  "reply": "To import households, go to Settings → Households → Import CSV...",
  "ticket_created": false,
  "sources": [
    {"article": "docs/KB/household-import.md", "score": 0.84}
  ]
}
```

If `ticket_created: true`, the `reply` becomes a templated fallback and a
notification is dispatched.

**Errors:**
- `401` — not logged in
- `403` — not a member of the org, or not admin/treasurer
- `429` — throttle
- `402` — daily cost cap

## 7. UI design

Floating bubble bottom-right. Alpine-driven UI (no framework). Single
component renders identically for Sales and Support; only the header
text and accent color differ.

| State | Bubble | Header | Accent |
|---|---|---|---|
| Closed | Indigo circle, "Chat" | hidden | indigo-600 |
| Open (Sales) | hidden | "Ask us about pricing & plans" | indigo-600 |
| Open (Support) | hidden | "How can we help?" | emerald-600 |

Header copy is set via a Blade `<x-chat-widget :state="...">` prop.

Component takes:
- `state` — 'sales' | 'support'
- `endpoint` — string
- `csrf_token` — optional (Sales public, Support uses session)
- `greeting` — optional string, defaults to canned
- `placeholder` — optional

`data-auth-state` and `data-org-slug` are set in `<body>` for downstream
scripts; widget itself only needs the endpoint prop.

## 8. File plan

| Path | Action |
|---|---|
| `routes/web.php` | modify — register public Sales route inside throttle group |
| `app/Http/Controllers/AI/SalesAgentController.php` | create |
| `app/Http/Controllers/AI/SupportAgentController.php` | verify / create |
| `app/AI/Agents/SalesAgent.php` | create — extends BaseAgent |
| `app/AI/Tools/Sales/FindKnowledge.php` | create |
| `app/AI/Tools/Sales/RecommendPlan.php` | create |
| `app/AI/Tools/Sales/CaptureEmail.php` | create |
| `app/AI/Tools/Sales/ScheduleFollowup.php` | create |
| `app/AI/Tools/Sales/HandoffToHuman.php` | create |
| `app/Services/LeadCaptureService.php` | create |
| `app/Services/ChatSession.php` | create |
| `app/Jobs/SendSalesFollowupEmail.php` | create |
| `app/Mail/SalesFollowupMail.php` | create |
| `app/Events/SalesHandoffRequested.php` | create |
| `app/Listeners/SendSalesHandoffNotification.php` | create |
| `app/Providers/EventServiceProvider.php` | modify — bind listener |
| `config/ai.php` | modify — register sales agent tools + prompt |
| `database/migrations/<ts>_create_leads_table.php` | create |
| `database/migrations/<ts>_create_sales_conversations_tables.php` | create (two tables) |
| `database/migrations/<ts>_create_support_conversations_tables.php` | create (two tables) |
| `database/migrations/<ts>_verify_support_tickets_table.php` | create only if missing |
| `resources/views/components/chat-widget.blade.php` | create |
| `resources/views/layouts/layout.blade.php` | modify — mount widget for admin/treasurer only |
| `resources/views/marketing/layout.blade.php` | create — mount Sales widget |
| `resources/css/app.css` | minor — widget styles |
| `resources/js/app.js` | minor — register Alpine widget data |
| `tests/Unit/Services/LeadCaptureServiceTest.php` | create |
| `tests/Feature/AI/SalesAgentTest.php` | create |
| `tests/Feature/AI/SupportAgentTest.php` | create |
| `docs/AI_SETUP.md` | append — Chat widget section |
| `AGENTS.md` | minor — note widget component + Sales/Support gating |

## 9. Build sequence

1. **Migrations**: `leads`, `sales_conversations` + messages,
   `support_conversations` + messages, verify or create
   `support_tickets`. Run `php artisan migrate` against the test DB.
2. **Services**: `LeadCaptureService` + `ChatSession`. Unit test
   `LeadCaptureService` (idempotency, follow-up chain stage transitions).
3. **Sales agent**: `SalesAgent` + 5 tools. Configure via
   `config/ai.php`. Tools registered per `docs/AI_SETUP.md:104-117`.
4. **Sales endpoint**: `SalesAgentController` + route. Feature test:
   throttle, validation, lead capture, follow-up job dispatched.
5. **Support endpoint**: verify existing controller, ensure role
   middleware applied. Feature test: viewer 403, admin/treasurer 200,
   RAG hit, RAG miss → ticket.
6. **Follow-up job + mailable**: `SendSalesFollowupEmail`,
   `SalesFollowupMail`. Test: each stage transitions correctly, stage
   past `day7_sent` is no-op.
7. **Event + listener**: `SalesHandoffRequested` →
   `SendSalesHandoffNotification` mails `AI_FOUNDER_EMAIL`.
8. **Blade component**: `chat-widget.blade.php`. Mount in
   `marketing/layout.blade.php` with `state="sales"`. Mount in
   `layouts/layout.blade.php` with `state="support"` gated on role.
9. **Docs**: append "Chat widget" section to `docs/AI_SETUP.md`. Note
   the new component in `AGENTS.md` (Layouts section).
10. **Verify**: `./vendor/bin/pint --test && php artisan test
    --filter='AI|ChatSession|LeadCapture'`.

## 10. Failure modes & mitigations

| Failure | Detection | Mitigation |
|---|---|---|
| Sales endpoint hammered | `throttle:30,1` | 429 + backoff in widget |
| OpenAI cost spike | `EnforceDailyCostCap` blocks pre-LLM | Returns 402; widget shows "try later" |
| RAG KB empty | `find_knowledge` returns 0 hits | Always falls through to ticket creation |
| Email provider down | `SalesFollowupMail` queue fails | Job retry per Laravel default exponential backoff |
| LLM loop / repeated tool call | Tool budget cap (5 calls / turn) | `BaseTool` enforces max calls |
| Viewer discovers endpoint URL | `EnsureOrganizationRole` returns 403 | Frontend never mounts widget for viewer; URL is opaque |
| Lead captured, follow-up spam | Idempotent stages in `leads.followup_stage` | Service refuses to re-send past stages |
| Conversation grows unbounded | Trim to last 20 turns in `ChatSession` | Older messages stay in DB for analytics only |

## 11. Cost & performance budget

Per `docs/AI_SETUP.md`:
- Sales: 5¢/run cap, daily 50¢ cap.
- Support: 15¢/run cap, daily 50¢ cap (shared).

Target latency: p95 < 2.5s for short replies, < 5s for tool-using
replies. Polling-cap in the widget if the agent exceeds 5s server-side.

## 12. Open assumptions

1. `app/AI/Tools/Sales/` does not exist yet — we create it clean.
2. `support_tickets` may need a fresh migration; we verify first.
3. `EnforceOrganizationRole` already accepts role lists — verified in
   AGENTS.md.
4. Marketing layout (`resources/views/marketing/layout.blade.php`) does
   not exist; we create it with auth-state body marker.
5. `config/ai.php` already has an `agents` config section; we extend it
   with a `sales` block, not replace.

---

End of plan. Implementation may begin once the operator has reviewed
this document.
