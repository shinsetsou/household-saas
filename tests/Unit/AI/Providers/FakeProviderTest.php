<?php

declare(strict_types=1);

namespace Tests\Unit\AI\Providers;

use App\AI\AgentRequest;
use App\AI\Providers\FakeProvider;
use Tests\TestCase;

class FakeProviderTest extends TestCase
{
    public function test_default_reply_used_when_no_script_matches(): void
    {
        $provider = new FakeProvider(['default_reply' => 'hello']);

        $response = $provider->complete(new AgentRequest(
            agent: 'founder',
            task: 'brief',
            messages: [['role' => 'user', 'content' => 'hi']],
        ));

        $this->assertSame('hello', $response->text);
        $this->assertSame('fake', $response->provider);
    }

    public function test_scripted_reply_is_consumed_in_fifo_order(): void
    {
        $provider = new FakeProvider(['default_reply' => 'fallback']);
        $provider->scriptReply(['agent' => 'founder'], 'first');
        $provider->scriptReply(['agent' => 'founder'], 'second');

        $r1 = $provider->complete($this->makeRequest('founder'));
        $r2 = $provider->complete($this->makeRequest('founder'));
        $r3 = $provider->complete($this->makeRequest('founder'));

        $this->assertSame('first', $r1->text);
        $this->assertSame('second', $r2->text);
        $this->assertSame('fallback', $r3->text);
    }

    public function test_embed_returns_deterministic_vector(): void
    {
        $provider = new FakeProvider;

        $a = $provider->embed('hello world');
        $b = $provider->embed('hello world');
        $c = $provider->embed('different');

        $this->assertSame($a, $b);
        $this->assertNotSame($a, $c);
    }

    public function test_contains_match_finds_substring_in_messages(): void
    {
        $provider = new FakeProvider(['default_reply' => 'fallback']);
        $provider->scriptReply(['contains' => 'refund'], 'refund-help');

        $response = $provider->complete(new AgentRequest(
            agent: 'support',
            task: 'ask',
            messages: [['role' => 'user', 'content' => 'how do refunds work?']],
        ));

        $this->assertSame('refund-help', $response->text);
    }

    private function makeRequest(string $agent): AgentRequest
    {
        return new AgentRequest(agent: $agent, task: 'x', messages: []);
    }
}
