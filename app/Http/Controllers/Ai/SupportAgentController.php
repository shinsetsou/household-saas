<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ai;

use App\AI\AIManager;
use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportAgentController extends Controller
{
    public function __construct(
        protected readonly ChatSession $chat,
        protected readonly AIManager $manager,
    ) {}

    public function message(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'organization_slug' => ['required', 'string'],
            'page_url' => ['nullable', 'url'],
            'prev_messages' => ['nullable', 'array', 'max:20'],
        ]);

        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $org = Organization::query()->where('slug', $data['organization_slug'])->first();

        if (! $org || ! $user->belongsToOrganization($org)) {
            abort(403, 'Not a member of this organization.');
        }

        $role = $org->roleFor($user);

        if (! in_array($role, [OrganizationRole::Admin, OrganizationRole::Treasurer], true)) {
            abort(403, 'Support chat is limited to admins and treasurers.');
        }

        $conversation = $this->chat->support($org, $user);

        $this->chat->appendSupportMessage($conversation, 'user', $data['message']);

        $context = [
            'organization_slug' => $org->slug,
            'organization_name' => $org->name,
            'user_role' => $role?->value,
            'page_url' => $data['page_url'] ?? null,
            'conversation_id' => $conversation->id,
        ];

        $history = $this->chat->supportHistory($conversation);

        $agent = $this->manager->agent('support')
            ->task($data['message'])
            ->context($context)
            ->forUser($user)
            ->forOrganization($org->id)
            ->costCapCents((int) config('ai.agents.support.cost_cap_cents', 15));

        foreach (array_slice($history, -ChatSession::MAX_HISTORY_TURNS) as $turn) {
            if ($turn['role'] === 'user' || $turn['role'] === 'assistant') {
                $agent->say($turn['role'], $turn['content']);
            }
        }

        $dispatchId = $agent->dispatchId();

        try {
            $response = $agent->run();
        } catch (\Throwable $e) {
            $this->chat->appendSupportMessage(
                $conversation,
                'assistant',
                '[Error: '.substr($e->getMessage(), 0, 200).']',
                null,
                $dispatchId,
            );

            return response()->json([
                'reply' => 'I could not reach support right now. Please try again in a moment.',
                'ticket_created' => false,
                'error' => $e->getMessage(),
            ], 502);
        }

        $this->chat->appendSupportMessage(
            $conversation,
            'assistant',
            $response->text,
            null,
            $dispatchId,
        );

        return response()->json([
            'reply' => $response->text,
            'ticket_created' => false,
            'conversation_id' => $conversation->id,
            'dispatch_id' => $dispatchId,
            'provider' => $response->provider,
            'model' => $response->model,
            'error' => $response->error,
        ], $response->isError() ? 502 : 200);
    }
}
