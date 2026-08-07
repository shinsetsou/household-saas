<?php

declare(strict_types=1);

namespace App\Providers;

use App\AI\AIManager;
use App\AI\Console\AiEmbedDocsCommand;
use App\AI\Console\AiRunAgentCommand;
use App\AI\Contracts\AIProvider;
use App\AI\Contracts\Embedder;
use App\AI\Contracts\MemoryStore;
use App\AI\Logging\CallLogger;
use App\AI\Memory\EloquentMemoryStore;
use App\AI\ProviderEmbedder;
use App\AI\Providers\FakeProvider;
use App\AI\Providers\OllamaProvider;
use App\AI\Providers\OpenAIProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AIManager::class, function (Application $app) {
            return new AIManager(
                container: $app,
                config: (array) config('ai'),
                logger: $app->make(CallLogger::class),
            );
        });

        $this->app->singleton(CallLogger::class);

        // AIProvider is a tagged container alias keyed by `name` argument.
        $this->app->bind(AIProvider::class, function (Application $app, array $params) {
            $name = $params['name'] ?? config('ai.provider');

            return match ($name) {
                'openai' => new OpenAIProvider(
                    config: (array) config('ai.openai'),
                    model: (string) config('ai.openai.model'),
                    embeddingModel: (string) config('ai.openai.embedding_model'),
                ),
                'ollama' => new OllamaProvider(
                    config: (array) config('ai.ollama'),
                    model: (string) config('ai.ollama.model'),
                    embeddingModel: (string) config('ai.ollama.embedding_model'),
                ),
                'fake' => new FakeProvider((array) config('ai.fake')),
                default => throw new \RuntimeException("Unknown AI provider [{$name}]"),
            };
        });

        $this->app->bind(Embedder::class, function (Application $app) {
            $provider = $app->make(AIProvider::class);

            return new ProviderEmbedder(
                provider: $provider,
                modelName: $provider->name() === 'openai'
                    ? (string) config('ai.openai.embedding_model')
                    : (string) config('ai.ollama.embedding_model'),
                dimensions: (int) config('ai.memory.embedding_dimensions'),
            );
        });

        $this->app->bind(MemoryStore::class, function (Application $app) {
            $embedder = $app->make(Embedder::class);

            return new EloquentMemoryStore($embedder);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AiRunAgentCommand::class,
                AiEmbedDocsCommand::class,
            ]);
        }
    }
}
