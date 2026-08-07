<?php

use App\AI\Tools\CustomerSuccess\EscalateHighRiskTool;
use App\AI\Tools\CustomerSuccess\GetOnboardingStatusTool;
use App\AI\Tools\Founder\GetDailyMetricsTool;
use App\AI\Tools\Founder\GetMrrTool;
use App\AI\Tools\Sales\CaptureEmailTool;
use App\AI\Tools\Sales\FindKnowledgeTool as SalesFindKnowledgeTool;
use App\AI\Tools\Sales\HandoffToHumanTool;
use App\AI\Tools\Sales\RecommendPlanTool;
use App\AI\Tools\Sales\ScheduleFollowupTool;
use App\AI\Tools\Support\CreateSupportTicketTool;
use App\AI\Tools\Support\FindKnowledgeTool;

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | The provider that powers AIManager calls. Supported: "openai", "ollama",
    | "fake". Switching providers requires zero changes to agents or tools.
    |
    */

    'provider' => env('AI_PROVIDER', 'fake'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI
    |--------------------------------------------------------------------------
    */

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'request_timeout' => (int) env('OPENAI_REQUEST_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ollama (local development only — never use in production)
    |--------------------------------------------------------------------------
    |
    | Ollama runs as a local process. It exposes an OpenAI-compatible REST
    | endpoint at /v1. Configure the URL with the port Ollama is bound to.
    |
    */

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.1'),
        'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
        'request_timeout' => (int) env('OLLAMA_REQUEST_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fake provider (tests + local dev without LLM keys)
    |--------------------------------------------------------------------------
    |
    | The fake provider returns scripted replies; tests can swap in a fresh
    | FakeProvider via AIManager::fake()->reply(...) to drive deterministic
    | assertions.
    |
    */

    'fake' => [
        'default_reply' => env('AI_FAKE_DEFAULT_REPLY', 'fake-acknowledged'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cost guards
    |--------------------------------------------------------------------------
    |
    | AIManager refuses to dispatch any agent once today's spend exceeds the
    | cap. Cost is computed in USD cents from logged ai_call_logs rows.
    |
    */

    'daily_cost_cap_cents' => (int) env('AI_DAILY_COST_CAP_CENTS', 50),
    'cost_per_1k_prompt_tokens_cents' => (float) env('AI_COST_PROMPT_CENTS', 0.015),
    'cost_per_1k_completion_tokens_cents' => (float) env('AI_COST_COMPLETION_CENTS', 0.06),

    /*
    |--------------------------------------------------------------------------
    | Per-agent overrides
    |--------------------------------------------------------------------------
    |
    | Each agent may override the default provider, model, or per-run cost cap.
    | Generic agents also pick up their system prompt + tool list from this
    | config; concrete Agent classes (App\AI\Agents\FounderAgent) can ignore
    | these and inject their own.
    |
    */

    'agents' => [
        'founder' => [
            'provider' => env('AI_AGENT_FOUNDER_PROVIDER'),
            'model' => env('AI_AGENT_FOUNDER_MODEL'),
            'cost_cap_cents' => (int) env('AI_AGENT_FOUNDER_COST_CAP_CENTS', 10),
            'temperature' => 0.2,
            'system_prompt' => "You are the BarangayBooks Founder Agent. Every morning you generate an executive briefing for the founder.\n\n".'Use the provided tools to gather metrics. Never invent numbers. Reply in JSON with keys: revenue_usd_cents, mrr_usd_cents, new_users, new_trials, new_paying, churn_count, failed_payments, support_tickets, product_issues, top_3_risks, top_3_wins, suggested_priorities.',
            'tools' => [
                GetMrrTool::class,
                GetDailyMetricsTool::class,
            ],
        ],
        'sales' => [
            'provider' => env('AI_AGENT_SALES_PROVIDER'),
            'model' => env('AI_AGENT_SALES_MODEL'),
            'cost_cap_cents' => (int) env('AI_AGENT_SALES_COST_CAP_CENTS', 5),
            'temperature' => 0.4,
            'system_prompt' => "You are the BarangayBooks Sales Agent. You speak with prospective HOA officers visiting the website.\n\n".'Workflow: (1) understand what they need, (2) call recommend_plan to get current prices (never invent prices), (3) call find_knowledge for feature / FAQ questions, (4) capture their email via capture_email, (5) hand off to a human if they ask or intent is high. Be concise, warm, never technical. Plans: Community (free 14-day trial, up to 20 households), Standard, Premium — current prices live in the plan catalog.',
            'tools' => [
                RecommendPlanTool::class,
                SalesFindKnowledgeTool::class,
                CaptureEmailTool::class,
                ScheduleFollowupTool::class,
                HandoffToHumanTool::class,
            ],
        ],
        'support' => [
            'provider' => env('AI_AGENT_SUPPORT_PROVIDER'),
            'model' => env('AI_AGENT_SUPPORT_MODEL'),
            'cost_cap_cents' => (int) env('AI_AGENT_SUPPORT_COST_CAP_CENTS', 15),
            'temperature' => 0.1,
            'system_prompt' => "You are the BarangayBooks Support Agent — Level 1 only.\n\n".'Before answering ANY question you MUST call find_knowledge with the user question. If the top result has similarity >= 0.55, answer using only that knowledge and cite the source URL from metadata. If confidence is below 0.55, create a support ticket with create_support_ticket and tell the user a human will follow up.',
            'tools' => [
                FindKnowledgeTool::class,
                CreateSupportTicketTool::class,
            ],
            'rag_confidence_threshold' => (float) env('AI_AGENT_SUPPORT_RAG_THRESHOLD', 0.55),
        ],
        'customer_success' => [
            'provider' => env('AI_AGENT_CS_PROVIDER'),
            'model' => env('AI_AGENT_CS_MODEL'),
            'cost_cap_cents' => (int) env('AI_AGENT_CS_COST_CAP_CENTS', 10),
            'temperature' => 0.2,
            'system_prompt' => "You are the BarangayBooks Customer Success Agent. Every night you scan all organizations for churn risk.\n\n".'For each at-risk organization, decide whether to: (a) send a friendly onboarding email, (b) suggest a help article, or (c) escalate to the founder. Always use the provided tools — never fabricate data. Score thresholds are configured by the platform.',
            'tools' => [
                GetOnboardingStatusTool::class,
                EscalateHighRiskTool::class,
            ],
            'escalation_score' => (int) env('AI_AGENT_CS_ESCALATION_SCORE', 70),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Agent map (advanced)
    |--------------------------------------------------------------------------
    |
    | Map agent slug => concrete Agent class. Unmapped agents fall back to
    | GenericAgent which reads system_prompt/tools from this file.
    |
    */

    'agent_map' => [
        // 'founder' => \App\AI\Agents\FounderAgent::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory
    |--------------------------------------------------------------------------
    */

    'memory' => [
        'driver' => env('AI_MEMORY_DRIVER', 'eloquent'),
        'default_namespace' => 'general',
        'embedding_dimensions' => (int) env('AI_EMBEDDING_DIMENSIONS', 1536),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Email address that receives daily Founder briefs and agent failure
    | alerts. Slack/Discord delivery will be added in a later phase.
    |
    */

    'founder_email' => env('AI_FOUNDER_EMAIL', env('MAIL_FROM_ADDRESS')),
    'failure_alert_email' => env('AI_FAILURE_ALERT_EMAIL', env('MAIL_FROM_ADDRESS')),
    'timezone' => env('AI_TIMEZONE', 'Asia/Manila'),

];
