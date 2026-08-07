<?php

declare(strict_types=1);

namespace Tests\Unit\AI;

use App\AI\Contracts\ToolResult;
use App\AI\ToolResultPayload;
use App\AI\Tools\BaseTool;
use Tests\TestCase;

/**
 * Locks down the JSON-Schema fix that prevents empty tool-parameter
 * objects from serializing as `[]` (rejected by OpenAI's strict
 * tools validator) instead of `{}`.
 */
class BaseToolEmptySchemaTest extends TestCase
{
    public function test_object_schema_with_empty_properties_emits_object_not_array_in_json(): void
    {
        $tool = $this->makeTool([]);
        $schema = $tool->schema();

        $encoded = json_encode($schema);
        $this->assertStringContainsString('"properties":{}', $encoded);
        $this->assertStringNotContainsString('"properties":[]', $encoded);
    }

    public function test_object_schema_with_populated_properties_preserves_them(): void
    {
        $properties = ['email' => ['type' => 'string']];
        $tool = $this->makeTool($properties, ['email']);

        $schema = $tool->schema();
        $this->assertSame('object', $schema['type']);
        $this->assertSame($properties, (array) $schema['properties']);
        $this->assertSame(['email'], (array) $schema['required']);
    }

    public function test_empty_required_list_is_preserved(): void
    {
        $tool = $this->makeTool(['x' => ['type' => 'integer']]);

        $schema = $tool->schema();
        $this->assertSame([], (array) $schema['required']);
        $this->assertSame('integer', $schema['properties']['x']['type']);
    }

    protected function makeTool(array $properties, array $required = []): BaseTool
    {
        return new class($properties, $required) extends BaseTool
        {
            public function __construct(
                private readonly array $properties,
                private readonly array $required,
            ) {}

            public function name(): string
            {
                return 't';
            }

            public function description(): string
            {
                return 'd';
            }

            public function schema(): array
            {
                return $this->objectSchema($this->properties, $this->required);
            }

            protected function handle(array $arguments): ToolResult
            {
                return ToolResultPayload::success([]);
            }
        };
    }
}
