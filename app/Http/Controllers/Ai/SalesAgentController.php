<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ai;

use App\AI\AIManager;
use App\Http\Controllers\Controller;
use App\Services\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesAgentController extends Controller
{
    public function __construct(
        protected readonly ChatSession $chat,
        protected readonly AIManager $manager,
    ) {}

    public function inquire(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id' => ['nullable', 'string', 'max:64'],
            'message' => ['required', 'string', 'max:2000'],
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email'],
            'page_url' => ['nullable', 'url'],
            'prev_messages' => ['nullable', 'array', 'max:20'],
            'prev_messages.*.role' => ['required_with:prev_messages', 'string', 'in:user,assistant'],
            'prev_messages.*.content' => ['required_with:prev_messages', 'string', 'max:4000'],
        ]);

        $sessionId = $data['session_id'] ?? null;

        if ($sessionId === null) {
            return response()->json([
                'reply' => '',
                'session_id' => ChatSession::newSalesSessionId(),
                'note' => 'session_id required for continuity.',
            ], 422);
        }

        $conversation = $this->chat->sales($sessionId);

        $this->chat->appendSalesMessage(
            $conversation,
            'user',
            $data['message'],
        );

        $context = array_filter([
            'visitor_name' => $data['name'] ?? null,
            'visitor_email' => $data['email'] ?? null,
            'page_url' => $data['page_url'] ?? null,
            'conversation_id' => $conversation->id,
            'session_id' => $sessionId,
        ]);

        $history = $this->chat->salesHistory($conversation);

        $agent = $this->manager->agent('sales')
            ->task($data['message'])
            ->context($context)
            ->costCapCents((int) config('ai.agents.sales.cost_cap_cents', 5));

        foreach (array_slice($history, -ChatSession::MAX_HISTORY_TURNS) as $turn) {
            if ($turn['role'] === 'user' || $turn['role'] === 'assistant') {
                $agent->say($turn['role'], $turn['content']);
            }
        }

        $dispatchId = $agent->dispatchId();

        try {
            $response = $agent->run();
        } catch (\Throwable $e) {
            $this->chat->appendSalesMessage(
                $conversation,
                'assistant',
                '[Error: '.substr($e->getMessage(), 0, 200).']',
                null,
                $dispatchId,
            );

            return response()->json([
                'reply' => 'Our sales assistant is busy right now. Please try again in a moment.',
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ], 502);
        }

        $this->chat->appendSalesMessage(
            $conversation,
            'assistant',
            $response->text,
            null,
            $dispatchId,
        );

        if (! empty($data['email'])) {
            $conversation->update(['lead_email' => $data['email']]);
        }

        return response()->json([
            'reply' => $response->text,
            'session_id' => $sessionId,
            'dispatch_id' => $dispatchId,
            'provider' => $response->provider,
            'model' => $response->model,
            'error' => $response->error,
        ], $response->isError() ? 502 : 200);
    }
}
