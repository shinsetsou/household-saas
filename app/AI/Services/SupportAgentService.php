<?php

declare(strict_types=1);

namespace App\AI\Services;

use App\AI\AIManager;
use App\AI\MemoryQuery;
use App\Models\Organization;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Str;

class SupportAgentService
{
    public function __construct(protected readonly AIManager $manager) {}

    /**
     * Resolve a support question through the RAG-first protocol.
     *
     * @return array{reply: string, confidence: float, ticket: ?SupportTicket, kb_citations: array<int, array<string, mixed>>}
     */
    public function ask(
        string $question,
        ?Organization $org = null,
        ?User $user = null,
    ): array {
        $threshold = (float) config('ai.agents.support.rag_confidence_threshold', 0.55);
        $embedder = $this->manager->resolveEmbedder();
        $store = $this->manager->resolveMemoryStore();

        $results = $store->search(new MemoryQuery(
            namespace: 'kb',
            query: $question,
            queryEmbedding: $embedder->embed($question),
            limit: 3,
        ));

        $bestScore = (float) ($results[0]['score'] ?? 0.0);
        $citations = array_map(fn ($r) => [
            'score' => round($r['score'], 4),
            'content' => $r['item']->content,
            'url' => $r['item']->metadata['url'] ?? null,
            'title' => $r['item']->metadata['title'] ?? null,
        ], $results);

        if ($bestScore >= $threshold) {
            $reply = $this->synthesizeAnswer($question, $citations);

            return [
                'reply' => $reply,
                'confidence' => $bestScore,
                'ticket' => null,
                'kb_citations' => $citations,
            ];
        }

        $ticket = SupportTicket::create([
            'uuid' => (string) Str::uuid(),
            'subject' => 'Support request: '.Str::limit($question, 80),
            'body' => $question,
            'category' => 'other',
            'severity' => 'normal',
            'source' => 'support_agent',
            'organization_id' => $org?->id,
            'user_id' => $user?->id,
            'kb_citations' => array_column($citations, 'url'),
            'confidence' => $bestScore,
            'status' => 'open',
        ]);

        return [
            'reply' => 'I could not find a confident answer. I have opened a support ticket and a team member will follow up shortly.',
            'confidence' => $bestScore,
            'ticket' => $ticket,
            'kb_citations' => $citations,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $citations
     */
    protected function synthesizeAnswer(string $question, array $citations): string
    {
        if (empty($citations)) {
            return 'No matching knowledge found.';
        }

        $top = $citations[0];

        $url = $top['url'] ? " (source: {$top['url']})" : '';

        return trim($top['content']).$url;
    }
}
