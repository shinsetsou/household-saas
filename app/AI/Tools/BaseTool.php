<?php

declare(strict_types=1);

namespace App\AI\Tools;

use App\AI\Contracts\Tool;
use App\AI\Contracts\ToolResult;
use App\AI\ToolResultPayload;

abstract class BaseTool implements Tool
{
    abstract public function name(): string;

    abstract public function description(): string;

    abstract public function schema(): array;

    abstract protected function handle(array $arguments): ToolResult;

    final public function execute(array $arguments): ToolResult
    {
        try {
            return $this->handle($arguments);
        } catch (\Throwable $e) {
            return ToolResultPayload::failure($e->getMessage(), [
                'tool' => $this->name(),
                'class' => static::class,
            ]);
        }
    }

    /**
     * Helper to build a JSON-Schema object descriptor.
     *
     * `properties` is normalized to an empty object (associative cast) when
     * empty so json_encode emits `{}` instead of `[]`. OpenAI's strict
     * tool-schema validator rejects `parameters.properties = []`; it must
     * be a JSON object.
     *
     * @param  array<string, mixed>  $properties
     * @param  array<int, string>  $required
     */
    protected function objectSchema(array $properties, array $required = []): array
    {
        return [
            'type' => 'object',
            'properties' => $properties === [] ? (object) [] : $properties,
            'required' => $required,
        ];
    }
}
