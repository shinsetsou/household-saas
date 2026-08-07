<?php

declare(strict_types=1);

namespace App\AI;

use App\AI\Contracts\Tool;

class GenericAgent extends Agent
{
    public function __construct(string $name)
    {
        $config = config("ai.agents.{$name}", []);

        if (! isset($config['system_prompt'])) {
            throw new \RuntimeException("AI agent [{$name}] has no system_prompt in config/ai.php.");
        }

        parent::__construct($name, (string) $config['system_prompt']);

        foreach ((array) ($config['tools'] ?? []) as $toolClass) {
            $tool = app($toolClass);

            if (! $tool instanceof Tool) {
                throw new \RuntimeException("Configured tool [{$toolClass}] for agent [{$name}] is not a Tool contract.");
            }

            $this->addTool($tool);
        }

        if (isset($config['model'])) {
            $this->model((string) $config['model']);
        }

        if (isset($config['temperature'])) {
            $this->temperature((float) $config['temperature']);
        }

        if (isset($config['max_tokens'])) {
            $this->maxTokens((int) $config['max_tokens']);
        }

        if (isset($config['cost_cap_cents'])) {
            $this->costCapCents((int) $config['cost_cap_cents']);
        }
    }
}
