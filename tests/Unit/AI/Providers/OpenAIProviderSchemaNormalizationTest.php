<?php

declare(strict_types=1);

namespace Tests\Unit\AI\Providers;

use App\AI\AgentRequest;
use App\AI\Contracts\ToolResult;
use App\AI\Providers\OpenAIProvider;
use App\AI\ToolResultPayload;
use App\AI\Tools\BaseTool;
use Tests\TestCase;

class OpenAIProviderSchemaNormalizationTest extends TestCase
{
    public function test_empty_properties_serializes_as_json_object_not_array(): void
    {
        $provider = $this->makeProvider();

        $payload = $this->buildPayload($provider, [
            $this->makeEmptyTool('recommend_plan'),
        ]);

        $this->assertSame('function', $payload['tools'][0]['type']);
        $params = $payload['tools'][0]['function']['parameters'];
        $encoded = json_encode($params);
        $this->assertStringContainsString('"properties":{}', $encoded, 'properties must serialize as {} not []');
        $this->assertStringNotContainsString('"properties":[]', $encoded);
    }

    public function test_tool_with_required_list_keeps_required(): void
    {
        $provider = $this->makeProvider();

        $tool = new class extends BaseTool
        {
            public function name(): string
            {
                return 'do_thing';
            }

            public function description(): string
            {
                return 'does the thing';
            }

            public function schema(): array
            {
                return $this->objectSchema(
                    ['arg' => ['type' => 'string']],
                    ['arg'],
                );
            }

            protected function handle(array $arguments): ToolResult
            {
                return ToolResultPayload::success(['ok' => true]);
            }
        };

        $payload = $this->buildPayload($provider, [$tool]);

        $params = $payload['tools'][0]['function']['parameters'];
        $this->assertSame('object', $params['type']);
        $this->assertSame('string', $params['properties']['arg']['type']);
        $this->assertSame(['arg'], $params['required']);
    }

    public function test_missing_type_is_defaulted_to_object(): void
    {
        $provider = $this->makeProvider();

        $tool = new class extends BaseTool
        {
            public function name(): string
            {
                return 'no_type';
            }

            public function description(): string
            {
                return 'no type';
            }

            public function schema(): array
            {
                return ['properties' => ['x' => ['type' => 'integer']]];
            }

            protected function handle(array $arguments): ToolResult
            {
                return ToolResultPayload::success([]);
            }
        };

        $payload = $this->buildPayload($provider, [$tool]);
        $this->assertSame('object', $payload['tools'][0]['function']['parameters']['type']);
    }

    protected function makeProvider(): OpenAIProvider
    {
        $config = [
            'api_key' => 'fake-key',
            'base_url' => 'https://api.openai.com/v1',
            'request_timeout' => 30,
        ];

        return new OpenAIProvider($config, 'gpt-4o-mini', 'text-embedding-3-small');
    }

    protected function buildPayload(OpenAIProvider $provider, array $tools): array
    {
        $request = new AgentRequest(
            agent: 'sales',
            task: 'hi',
            messages: [['role' => 'user', 'content' => 'hi']],
            tools: $tools,
        );

        $reflection = new \ReflectionMethod($provider, 'buildPayload');
        $reflection->setAccessible(true);

        return $reflection->invoke($provider, $request);
    }

    protected function makeEmptyTool(string $name): BaseTool
    {
        return new class($name) extends BaseTool
        {
            public function __construct(private readonly string $toolName) {}

            public function name(): string
            {
                return $this->toolName;
            }

            public function description(): string
            {
                return 'no-arg tool';
            }

            public function schema(): array
            {
                return $this->objectSchema([]);
            }

            protected function handle(array $arguments): ToolResult
            {
                return ToolResultPayload::success([]);
            }
        };
    }
}
