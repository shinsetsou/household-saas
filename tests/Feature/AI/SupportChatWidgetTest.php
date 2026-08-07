<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\AI\AIManager;
use App\AI\Providers\FakeProvider;
use App\Enums\OrganizationRole;
use App\Models\AiCallLog;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\SupportConversation;
use App\Models\SupportConversationMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportChatWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai.provider', 'fake');

        if (! Plan::where('slug', 'community')->exists()) {
            Plan::create([
                'slug' => 'community',
                'name' => 'Community',
                'description' => 'Free trial.',
                'price_cents' => 0,
                'currency' => 'PHP',
                'interval' => 'month',
                'max_households' => 20,
                'csv_import' => false,
                'api_access' => false,
                'sort_order' => 1,
                'is_active' => true,
            ]);
        }
    }

    public function test_support_endpoint_requires_authentication(): void
    {
        $org = Organization::create(['name' => 'T', 'slug' => 't', 'monthly_due_amount' => 100]);

        $this->postJson('/api/ai/support/message', [
            'message' => 'help',
            'organization_slug' => $org->slug,
        ])->assertStatus(401);
    }

    public function test_viewer_role_is_rejected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $org = Organization::create(['name' => 'T', 'slug' => 't', 'monthly_due_amount' => 100]);
        $org->users()->attach($user, ['role' => OrganizationRole::Viewer->value]);

        $response = $this->actingAs($user)
            ->postJson('/api/ai/support/message', [
                'message' => 'help',
                'organization_slug' => $org->slug,
            ]);

        $response->assertStatus(403);
        $this->assertSame(0, AiCallLog::where('agent', 'support')->count());
    }

    public function test_admin_role_can_chat(): void
    {
        $manager = $this->app->make(AIManager::class);
        $manager->fake(new FakeProvider(['default_reply' => 'support-reply']));

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $org = Organization::create(['name' => 'T', 'slug' => 't', 'monthly_due_amount' => 100]);
        $org->users()->attach($admin, ['role' => OrganizationRole::Admin->value]);

        $response = $this->actingAs($admin)
            ->postJson('/api/ai/support/message', [
                'message' => 'How do I import households?',
                'organization_slug' => $org->slug,
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['reply' => 'support-reply']);
        $this->assertSame(1, AiCallLog::where('agent', 'support')->count());
        $this->assertSame(2, SupportConversationMessage::count());
    }

    public function test_treasurer_role_can_chat(): void
    {
        $manager = $this->app->make(AIManager::class);
        $manager->fake(new FakeProvider(['default_reply' => 'treasurer-can-talk']));

        $treasurer = User::factory()->create(['email_verified_at' => now()]);
        $org = Organization::create(['name' => 'T', 'slug' => 't', 'monthly_due_amount' => 100]);
        $org->users()->attach($treasurer, ['role' => OrganizationRole::Treasurer->value]);

        $response = $this->actingAs($treasurer)
            ->postJson('/api/ai/support/message', [
                'message' => 'How do I verify contributions?',
                'organization_slug' => $org->slug,
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['reply' => 'treasurer-can-talk']);
    }

    public function test_non_member_is_rejected(): void
    {
        $stranger = User::factory()->create(['email_verified_at' => now()]);
        $org = Organization::create(['name' => 'T', 'slug' => 't', 'monthly_due_amount' => 100]);

        $this->actingAs($stranger)
            ->postJson('/api/ai/support/message', [
                'message' => 'help',
                'organization_slug' => $org->slug,
            ])->assertStatus(403);
    }

    public function test_support_conversation_persists_across_messages(): void
    {
        $manager = $this->app->make(AIManager::class);
        $manager->fake(new FakeProvider(['default_reply' => 'r1']));

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $org = Organization::create(['name' => 'T', 'slug' => 't', 'monthly_due_amount' => 100]);
        $org->users()->attach($admin, ['role' => OrganizationRole::Admin->value]);

        $this->actingAs($admin)
            ->postJson('/api/ai/support/message', [
                'message' => 'first',
                'organization_slug' => $org->slug,
            ])->assertOk();

        $manager->fake(new FakeProvider(['default_reply' => 'r2']));

        $this->actingAs($admin)
            ->postJson('/api/ai/support/message', [
                'message' => 'second',
                'organization_slug' => $org->slug,
            ])->assertOk();

        $this->assertSame(1, SupportConversation::count());
        $this->assertSame(4, SupportConversationMessage::count());
        $this->assertSame(2, AiCallLog::where('agent', 'support')->count());
    }

    public function test_support_validation_requires_message(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $org = Organization::create(['name' => 'T', 'slug' => 't', 'monthly_due_amount' => 100]);
        $org->users()->attach($admin, ['role' => OrganizationRole::Admin->value]);

        $this->actingAs($admin)
            ->postJson('/api/ai/support/message', [
                'organization_slug' => $org->slug,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }
}
