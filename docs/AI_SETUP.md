# BarangayBooks AI Layer

> Operator-facing setup for `app/AI/`, AIManager, and the four Phase 1 agents
> (Founder, Sales, Support, Customer Success). For the broader vision see
> [`docs/AI_VISION.md`](./AI_VISION.md).

## Providers

| Provider | Use case | Required env |
|---|---|---|
| `openai` | Production default | `OPENAI_API_KEY` |
| `ollama` | Local development | `OLLAMA_BASE_URL=http://127.0.0.1:11434` |
| `fake` | Tests + dev without keys | none |

Switch with `AI_PROVIDER` (or `AI_AGENT_<NAME>_PROVIDER` for per-agent override).

Ollama is **dev-only**. There is no managed-Ollama production target on
Railway/Render; running Ollama in the cloud adds ~4 GB RAM and slow cold-starts.
The provider interface makes the swap a one-line config change.

## Quick start

```bash
# Production
cp .env.example .env
# Set AI_PROVIDER=openai, OPENAI_API_KEY=sk-...

# Local dev (no keys)
AI_PROVIDER=fake

# Local dev with Ollama (must be running on host)
AI_PROVIDER=ollama
ollama pull llama3.1
ollama pull nomic-embed-text
```

## The four Phase 1 agents

| Agent | Trigger | Cost cap | Key tool |
|---|---|---|---|
| `founder` | Cron @ 07:00 Asia/Manila + on-demand | 10¢/run | `get_daily_metrics`, `get_mrr` |
| `sales` | Public `POST /api/ai/sales/inquire` (rate-limited) | 5¢/run | `recommend_plan`, `capture_email`, `handoff_to_human` |
| `support` | Authenticated `POST /api/ai/support/message` (admin + treasurer only) | 15¢/run | `find_knowledge`, `create_support_ticket` |
| `customer_success` | Cron @ 02:00 Asia/Manila | 10¢/run | `get_onboarding_status`, `escalate_high_risk` |

All agents log to `ai_call_logs`. View at `GET /api/ai/call-logs` (admin only).

## Chat widget

Single Blade component `resources/views/components/chat-widget.blade.php` ships in two flavors:

| Mount | Layout | Audience | Endpoint | Notes |
|---|---|---|---|---|
| Sales | `resources/views/welcome.blade.php` | Anonymous marketing visitors | `/api/ai/sales/inquire` | Persists via `session_id` (uuid in localStorage) |
| Support | `resources/views/layouts/layout.blade.php` | Admin + treasurer only (gated by `navContext->canVerify`) | `/api/ai/support/message` | Persists per (org, user); route also enforces role |

Sales and Support conversation histories live in isolated tables — `sales_conversations` + `sales_conversation_messages` and `support_conversations` + `support_conversation_messages` — and are never mixed. The `ChatSession` service in `app/Services/ChatSession.php` enforces the separation.

### Lead capture & follow-up

`App\AI\Tools\Sales\CaptureEmailTool` writes to the `leads` table (separate from `users`; we don't want a User row for a visitor who never signed up). On first capture the `LeadCaptureService::capture()` dispatches `App\AI\Jobs\SendSalesFollowupEmail` on the `ai` queue. Each successful delivery advances `leads.followup_stage` to `day0_sent → day3_sent → day7_sent`. Idempotency is enforced both in the service (no re-dispatch on duplicate email) and in the job (skips if requested stage is past the stored stage).

The `HandoffToHumanTool` flags the lead for human follow-up and fires the `sales.handoff_requested` event. The founder can listen for that event to push a Slack/Discord/email notification when channel support is added.

### Verification rules

- Sales endpoint is throttled `throttle:30,1` (60 calls/min per IP burst protection); pre-LLM daily cost cap.
- Support endpoint is `auth + verified`; the controller additionally enforces `OrganizationRole::Admin | Treasurer`. `EnsureOrganizationRole` middleware isn't used on the chat route because the middleware expects `route('organization')` route-binding (used for tenant URLs like `/{org}/foo`). The chat route is `/api/ai/support/message` so the role check happens inside the controller after resolving `organization_slug` from the body.

## RAG (knowledge base)

Knowledge lives in `ai_memories`. Seed it with:

```bash
mkdir -p docs/KB
# Drop .md articles into docs/KB
php artisan ai:embed-docs
```

The Support agent uses `find_knowledge` before every answer. If similarity is
below `AI_AGENT_SUPPORT_RAG_THRESHOLD` (default 0.55), a `support_tickets` row
is created automatically and a templated "we'll follow up" reply is returned.

## Cost guards

```env
AI_DAILY_COST_CAP_CENTS=50            # Hard cap per 24h, summed across agents
AI_AGENT_FOUNDER_COST_CAP_CENTS=10
AI_AGENT_SALES_COST_CAP_CENTS=5
AI_AGENT_SUPPORT_COST_CAP_CENTS=15
AI_AGENT_CS_COST_CAP_CENTS=10
```

`EnforceDailyCostCap` blocks new dispatches once the cap is reached and emits
an `AgentFailed` event. The cap is checked *before* the LLM call so we never
bill for refused work.

## Cron schedule

Already wired in `routes/console.php`:

```php
Schedule::call(fn () => app(AIManager::class)->agent('founder')->task('daily-brief')->queue())
    ->dailyAt('07:00')->timezone('Asia/Manila')->onOneServer();

Schedule::call(fn () => CustomerSuccessScanJob::dispatch())
    ->dailyAt('02:00')->timezone('Asia/Manila')->onOneServer();
```

Wire a real scheduler on your host: Railway Cron, Render Cron, or
`* * * * * php artisan schedule:run` system cron.

## Notifications

| Channel | Recipient | Env var |
|---|---|---|
| Daily Founder brief | email | `AI_FOUNDER_EMAIL` |
| Agent failure alerts | email | `AI_FAILURE_ALERT_EMAIL` |
| CS escalation | email | `AI_FOUNDER_EMAIL` |

Slack / Discord delivery is a Phase 2 item — emit events today, wire delivery
later.

## Adding a new tool

```php
namespace App\AI\Tools\Support;

class MyNewTool extends \App\AI\Tools\BaseTool
{
    public function name(): string { return 'my_new_tool'; }
    public function description(): string { return 'What it does for the model.'; }
    public function schema(): array { return $this->objectSchema([...]); }
    protected function handle(array $arguments): \App\AI\Contracts\ToolResult
    {
        return \App\AI\ToolResultPayload::success([...]);
    }
}
```

Register in `config/ai.php` under `agents.<name>.tools`.

## Testing

All AI tests use the `fake` provider. Use `AIManager::fake()` to swap in
scripted replies:

```php
$provider = new \App\AI\Providers\FakeProvider;
$provider->scriptReply(['agent' => 'founder'], 'json payload');
app(AIManager::class)->fake($provider);

$response = app(AIManager::class)->agent('founder')->task('brief')->run();
```

Tests live in `tests/Unit/AI/` and `tests/Feature/AI/`.

## Architecture rules

- **Agents don't access the DB directly** — they call tools.
- **Tools wrap existing Laravel services** — no duplicated business logic.
- **Every AI call goes through AIManager** — never call OpenAI directly.
- **Every action is queued** — Jobs for async, but AIManager::execute() itself
  is synchronous so callers can chain it.
- **Provider switching is config-only** — agents never see the provider.
- **Prompts orchestrate, Laravel executes** — keep domain logic out of prompts.