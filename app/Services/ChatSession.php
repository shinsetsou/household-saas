<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\SalesConversation;
use App\Models\SalesConversationMessage;
use App\Models\SupportConversation;
use App\Models\SupportConversationMessage;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Single persistence layer for chat widget conversations.
 *
 * Sales and Support histories are kept in isolated tables — the service
 * exposes only separate methods so accidental cross-contamination is
 * impossible at the data layer (per locked decision: separate histories).
 */
class ChatSession
{
    public const MAX_HISTORY_TURNS = 10;

    /**
     * Sales: per anonymous visitor session_id (uuid v4 string from localStorage).
     */
    public function sales(string $sessionId): SalesConversation
    {
        return SalesConversation::firstOrCreate(
            ['session_id' => $sessionId],
            ['last_message_at' => null],
        );
    }

    /**
     * Support: one row per (organization, user). Viewers never reach this
     * endpoint (route gating handles that) but if a user loses admin role
     * mid-session their past messages still exist.
     */
    public function support(Organization $organization, User $user): SupportConversation
    {
        return SupportConversation::firstOrCreate([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Append a message and refresh conversation aggregate counters.
     *
     * @param  array<string, mixed>|null  $toolCalls
     */
    public function appendSalesMessage(
        SalesConversation $conversation,
        string $role,
        string $content,
        ?array $toolCalls = null,
        ?string $dispatchId = null,
    ): SalesConversationMessage {
        $now = now();
        $message = SalesConversationMessage::create([
            'sales_conversation_id' => $conversation->id,
            'role' => $role,
            'content' => $content,
            'tool_calls' => $toolCalls,
            'dispatch_id' => $dispatchId,
            'created_at' => $now,
        ]);

        $conversation->update([
            'last_message_role' => $role,
            'last_message_at' => $now,
            'message_count' => $conversation->messages()->count(),
            'lead_email' => $toolCalls['email'] ?? $conversation->lead_email,
        ]);

        return $message;
    }

    /**
     * @param  array<string, mixed>|null  $toolCalls
     */
    public function appendSupportMessage(
        SupportConversation $conversation,
        string $role,
        string $content,
        ?array $toolCalls = null,
        ?string $dispatchId = null,
    ): SupportConversationMessage {
        $now = now();
        $message = SupportConversationMessage::create([
            'support_conversation_id' => $conversation->id,
            'role' => $role,
            'content' => $content,
            'tool_calls' => $toolCalls,
            'dispatch_id' => $dispatchId,
            'created_at' => $now,
        ]);

        $conversation->update([
            'last_message_role' => $role,
            'last_message_at' => $now,
            'message_count' => $conversation->messages()->count(),
        ]);

        return $message;
    }

    /**
     * Returns the last N turns formatted for LLM context. Always returns
     * user/assistant pairs in chronological order.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function salesHistory(SalesConversation $conversation): array
    {
        return $conversation->messages()
            ->orderByDesc('id')
            ->limit(self::MAX_HISTORY_TURNS * 2)
            ->get()
            ->reverse()
            ->map(fn (SalesConversationMessage $m) => [
                'role' => $m->role,
                'content' => $m->content,
            ])
            ->all();
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    public function supportHistory(SupportConversation $conversation): array
    {
        return $conversation->messages()
            ->orderByDesc('id')
            ->limit(self::MAX_HISTORY_TURNS * 2)
            ->get()
            ->reverse()
            ->map(fn (SupportConversationMessage $m) => [
                'role' => $m->role,
                'content' => $m->content,
            ])
            ->all();
    }

    /**
     * Generate a new sales session id (uuid v4) for first-time visitors.
     */
    public static function newSalesSessionId(): string
    {
        return (string) Str::uuid();
    }
}
