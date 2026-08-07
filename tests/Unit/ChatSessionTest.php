<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\SalesConversation;
use App\Models\SalesConversationMessage;
use App\Models\SupportConversation;
use App\Models\SupportConversationMessage;
use App\Models\User;
use App\Services\ChatSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_returns_same_conversation_for_same_session_id(): void
    {
        $service = app(ChatSession::class);

        $a = $service->sales('visitor-session-A');
        $b = $service->sales('visitor-session-A');

        $this->assertTrue($a->is($b));
        $this->assertSame(1, SalesConversation::count());
    }

    public function test_sales_creates_separate_rows_per_session(): void
    {
        $service = app(ChatSession::class);

        $service->sales('visitor-A');
        $service->sales('visitor-B');

        $this->assertSame(2, SalesConversation::count());
    }

    public function test_append_sales_message_updates_aggregates(): void
    {
        $service = app(ChatSession::class);

        $conv = $service->sales('visitor-A');
        $service->appendSalesMessage($conv, 'user', 'hi');
        $service->appendSalesMessage($conv, 'assistant', 'hello');

        $this->assertSame(2, SalesConversationMessage::count());
        $this->assertSame(2, $conv->fresh()->message_count);
        $this->assertSame('assistant', $conv->fresh()->last_message_role);
        $this->assertNotNull($conv->fresh()->last_message_at);
    }

    public function test_sales_history_is_chronological_and_capped(): void
    {
        $service = app(ChatSession::class);

        $conv = $service->sales('visitor-A');

        for ($i = 0; $i < 30; $i++) {
            $service->appendSalesMessage($conv, 'user', "q$i");
        }

        $history = $service->salesHistory($conv);

        $this->assertLessThanOrEqual(ChatSession::MAX_HISTORY_TURNS * 2, count($history));

        $contents = array_column($history, 'content');

        // Cap respected: oldest dropped, newest retained
        $this->assertNotContains('q0', $contents, 'oldest messages should be trimmed');
        $this->assertContains('q29', $contents, 'newest messages should be retained');

        // Chronological order: index of an earlier content is < later content
        foreach ($contents as $idx => $content) {
            if ($idx === 0) {
                continue;
            }
            $prev = (int) ltrim($contents[$idx - 1], 'q');
            $cur = (int) ltrim($content, 'q');
            $this->assertGreaterThan($prev, $cur, 'history should be oldest-first');
        }
    }

    public function test_support_returns_same_conversation_per_org_user(): void
    {
        $service = app(ChatSession::class);

        $org = Organization::create(['name' => 'T', 'slug' => 't', 'monthly_due_amount' => 100]);
        $user = User::factory()->create();

        $a = $service->support($org, $user);
        $b = $service->support($org, $user);

        $this->assertTrue($a->is($b));
        $this->assertSame(1, SupportConversation::count());
    }

    public function test_support_separates_histories_by_user(): void
    {
        $service = app(ChatSession::class);

        $org = Organization::create(['name' => 'T', 'slug' => 't', 'monthly_due_amount' => 100]);
        $admin = User::factory()->create();
        $treasurer = User::factory()->create();

        $service->support($org, $admin);
        $service->support($org, $treasurer);

        $this->assertSame(2, SupportConversation::count());
    }

    public function test_sales_and_support_tables_are_strictly_separated(): void
    {
        $service = app(ChatSession::class);

        $service->sales('visitor-session');

        $this->assertSame(1, SalesConversation::count());
        $this->assertSame(0, SupportConversation::count());

        $org = Organization::create(['name' => 'T', 'slug' => 't', 'monthly_due_amount' => 100]);
        $user = User::factory()->create();
        $service->support($org, $user);

        $this->assertSame(1, SalesConversation::count());
        $this->assertSame(1, SupportConversation::count());
        $this->assertSame(0, SupportConversationMessage::count());
        $this->assertSame(0, SalesConversationMessage::count());
    }
}
