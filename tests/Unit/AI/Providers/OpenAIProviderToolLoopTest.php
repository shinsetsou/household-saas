<?php

declare(strict_types=1);

namespace Tests\Unit\AI\Providers;

use App\AI\AgentRequest;
use App\AI\Contracts\ToolResult;
use App\AI\Providers\OpenAIProvider;
use App\AI\ToolResultPayload;
use App\AI\Tools\BaseTool;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The OpenAIProvider must support a multi-turn tool-calling loop: when the
 * model returns tool_calls, we execute them locally, append the results,
 * and re-issue the request. Plain text responses short-circuit the loop.
 *
 * These tests use Http::fake() so they don't need a network round-trip
 * or a valid OpenAI key.
 */
class OpenAIProviderToolLoopTest extends TestCase
{
    public function test_plain_text_response_short_circuits_loop(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'id' => 'cmpl-1',
                'model' => 'gpt-4o-mini-2024-07-18',
                'choices' => [[
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Hi there, how can I help?',
                    ],
                    'finish_reason' => 'stop',
                ]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 9],
            ], 200),
        ]);

        $provider = $this->makeProvider();
        $response = $provider->complete($this->makeRequest());

        $this->assertSame('Hi there, how can I help?', $response->text);
        $this->assertSame('stop', $response->finishReason);
        Http::assertSentCount(1);
    }

    public function test_tool_call_invokes_registered_tool_then_returns_text(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push([
                    'id' => 'cmpl-1',
                    'model' => 'gpt-4o-mini-2024-07-18',
                    'choices' => [[
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => [[
                                'id' => 'call_abc',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'plan_lookup',
                                    'arguments' => '{}',
                                ],
                            ]],
                        ],
                        'finish_reason' => 'tool_calls',
                    ]],
                    'usage' => ['prompt_tokens' => 50, 'completion_tokens' => 5],
                ])
                ->push([
                    'id' => 'cmpl-2',
                    'model' => 'gpt-4o-mini-2024-07-18',
                    'choices' => [[
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'The Standard plan is 990 PHP/mo. Want a demo?',
                        ],
                        'finish_reason' => 'stop',
                    ]],
                    'usage' => ['prompt_tokens' => 80, 'completion_tokens' => 20],
                ]),
        ]);

        $tool = $this->makePlanLookupTool();
        $provider = $this->makeProvider();
        $response = $provider->complete($this->makeRequest([$tool]));

        $this->assertSame('The Standard plan is 990 PHP/mo. Want a demo?', $response->text);
        $this->assertSame('stop', $response->finishReason);

        // Tool was hit exactly once.
        $this->assertSame(1, $tool->calls);
        // Two HTTP requests were sent (tool call -> tool result -> final).
        Http::assertSentCount(2);
    }

    public function test_loop_caps_at_max_iterations_and_force_summarizes(): void
    {
        // First MAX_TOOL_ITERATIONS calls all return tool_calls; the next one
        // (no tools) returns a summary.
        $toolCallResponse = [
            'id' => 'cmpl-'.uniqid(),
            'model' => 'gpt-4o-mini-2024-07-18',
            'choices' => [[
                'index' => 0,
                'message' => [
                    'role' => 'assistant',
                    'content' => 'still working',
                    'tool_calls' => [[
                        'id' => 'call_'.uniqid(),
                        'type' => 'function',
                        'function' => [
                            'name' => 'plan_lookup',
                            'arguments' => '{}',
                        ],
                    ]],
                ],
                'finish_reason' => 'tool_calls',
            ]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
        ];

        $summaryResponse = [
            'id' => 'cmpl-final',
            'model' => 'gpt-4o-mini-2024-07-18',
            'choices' => [[
                'index' => 0,
                'message' => [
                    'role' => 'assistant',
                    'content' => 'Sorry I could not get there in time. Here is what I know so far...',
                ],
                'finish_reason' => 'stop',
            ]],
            'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 30],
        ];

        $toolCallPush = [];
        for ($i = 0; $i < OpenAIProvider::MAX_TOOL_ITERATIONS; $i++) {
            $toolCallPush[] = $toolCallResponse;
        }

        $sequence = Http::sequence();
        foreach ($toolCallPush as $response) {
            $sequence->push($response);
        }
        $sequence->push($summaryResponse);

        Http::fake([
            'api.openai.com/*' => $sequence,
        ]);

        $tool = $this->makePlanLookupTool();
        $provider = $this->makeProvider();

        $response = $provider->complete($this->makeRequest([$tool]));

        $this->assertSame('Sorry I could not get there in time. Here is what I know so far...', $response->text);

        // Tool executed MAX_TOOL_ITERATIONS times; one final no-tool call made
        // to force the model to synthesize an answer.
        $this->assertSame(OpenAIProvider::MAX_TOOL_ITERATIONS, $tool->calls);
        Http::assertSentCount(OpenAIProvider::MAX_TOOL_ITERATIONS + 1);
    }

    public function test_unknown_tool_returns_error_payload_to_model(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push([
                    'id' => 'cmpl-1',
                    'model' => 'gpt-4o-mini-2024-07-18',
                    'choices' => [[
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => [[
                                'id' => 'call_xx',
                                'type' => 'function',
                                'function' => ['name' => 'unknown_tool', 'arguments' => '{}'],
                            ]],
                        ],
                        'finish_reason' => 'tool_calls',
                    ]],
                    'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 1],
                ])
                ->push([
                    'id' => 'cmpl-2',
                    'model' => 'gpt-4o-mini-2024-07-18',
                    'choices' => [[
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'I cannot help with that tool.',
                        ],
                        'finish_reason' => 'stop',
                    ]],
                    'usage' => ['prompt_tokens' => 30, 'completion_tokens' => 10],
                ]),
        ]);

        // Pass a real tool so the loop runs (otherwise empty($tools) short-
        // circuits before we ever reach the unknown_tool branch).
        $tool = $this->makePlanLookupTool();
        $provider = $this->makeProvider();
        $response = $provider->complete($this->makeRequest([$tool]));

        $this->assertSame('I cannot help with that tool.', $response->text);
        $this->assertSame('stop', $response->finishReason);
        Http::assertSentCount(2);
    }

    public function test_tool_failure_payload_propagates_to_model(): void
    {
        $tool = new class extends BaseTool
        {
            public int $calls = 0;

            public function name(): string
            {
                return 'broken_tool';
            }

            public function description(): string
            {
                return 'always fails';
            }

            public function schema(): array
            {
                return $this->objectSchema([]);
            }

            protected function handle(array $arguments): ToolResult
            {
                $this->calls++;

                return ToolResultPayload::failure('Tool execution failed.');
            }
        };

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::sequence()
                ->push([
                    'choices' => [[
                        'message' => [
                            'content' => null,
                            'tool_calls' => [[
                                'id' => 'call_yy',
                                'type' => 'function',
                                'function' => ['name' => 'broken_tool', 'arguments' => '{}'],
                            ]],
                        ],
                        'finish_reason' => 'tool_calls',
                    ]],
                    'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 1],
                ])
                ->push([
                    'choices' => [[
                        'message' => [
                            'content' => 'Apologies, the tool errored.',
                        ],
                        'finish_reason' => 'stop',
                    ]],
                    'usage' => ['prompt_tokens' => 30, 'completion_tokens' => 10],
                ]),
        ]);

        $provider = $this->makeProvider();
        $response = $provider->complete($this->makeRequest([$tool]));

        $this->assertSame(1, $tool->calls);
        $this->assertSame('Apologies, the tool errored.', $response->text);
    }

    protected function makeProvider(): OpenAIProvider
    {
        return new OpenAIProvider(
            [
                'api_key' => 'fake-key',
                'base_url' => 'https://api.openai.com/v1',
                'request_timeout' => 30,
            ],
            'gpt-4o-mini',
            'text-embedding-3-small',
        );
    }

    protected function makeRequest(array $tools = []): AgentRequest
    {
        return new AgentRequest(
            agent: 'sales',
            task: 'hi',
            messages: [
                ['role' => 'system', 'content' => 'You are a sales agent.'],
                ['role' => 'user', 'content' => 'What does this platform do?'],
            ],
            tools: $tools,
        );
    }

    protected function makePlanLookupTool(): BaseTool
    {
        return new class extends BaseTool
        {
            public int $calls = 0;

            public function name(): string
            {
                return 'plan_lookup';
            }

            public function description(): string
            {
                return 'returns plan info';
            }

            public function schema(): array
            {
                return $this->objectSchema([]);
            }

            protected function handle(array $arguments): ToolResult
            {
                $this->calls++;

                return ToolResultPayload::success([
                    'plans' => [
                        ['slug' => 'standard', 'name' => 'Standard', 'price_cents' => 99000],
                    ],
                ]);
            }
        };
    }
}
