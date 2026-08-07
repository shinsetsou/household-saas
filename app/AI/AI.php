<?php

declare(strict_types=1);

namespace App\AI;

use App\AI\Contracts\AIProvider;
use App\AI\Contracts\Embedder;
use App\AI\Contracts\MemoryStore;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Agent agent(string $name)
 * @method static Agent adhoc(string $name, string $systemPrompt)
 * @method static AgentResponse execute(AgentRequest $request)
 * @method static AIProvider resolveProvider(string $agent = null)
 * @method static Embedder resolveEmbedder()
 * @method static MemoryStore resolveMemoryStore()
 * @method static AIManager fake(?AIProvider $provider = null)
 * @method static AIManager flushFake()
 *
 * @see AIManager
 */
class AI extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AIManager::class;
    }
}
