<?php

declare(strict_types=1);

namespace App\AI\Console;

use App\AI\AIManager;
use App\AI\Events\FounderBriefGenerated;
use Illuminate\Console\Command;

class AiRunAgentCommand extends Command
{
    protected $signature = 'ai:run {agent : Agent slug} {--task= : Task key (default: ad-hoc)}';

    protected $description = 'Run a named AI agent synchronously. Useful for ad-hoc briefs and local debugging.';

    public function handle(AIManager $manager): int
    {
        $agent = (string) $this->argument('agent');
        $task = (string) ($this->option('task') ?? 'manual-run');

        $this->info("Running agent [{$agent}] task [{$task}]");

        $response = $manager->agent($agent)
            ->task($task)
            ->run();

        $this->line('Provider: '.($response->provider ?? 'n/a'));
        $this->line('Model: '.($response->model ?? 'n/a'));
        $this->line('Tokens: '.$response->promptTokens.' prompt / '.$response->completionTokens.' completion');
        $this->line('Latency: '.$response->latencyMs.' ms');
        $this->line('---');
        $this->line($response->text ?: '(empty)');

        if ($response->isError()) {
            $this->error('Agent failed: '.$response->error);

            return self::FAILURE;
        }

        if ($agent === 'founder') {
            $payload = json_decode($response->text, true) ?: ['raw' => $response->text];
            FounderBriefGenerated::dispatch(
                'cmd-'.uniqid(),
                $payload,
            );
        }

        return self::SUCCESS;
    }
}
