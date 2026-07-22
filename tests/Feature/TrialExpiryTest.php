<?php

namespace Tests\Feature;

use App\Enums\OrganizationRole;
use App\Mail\TrialEndingMail;
use App\Mail\TrialExpiredMail;
use App\Models\Household;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TrialExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Organization $organization;

    protected Plan $community;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->organization = Organization::create([
            'name' => 'Test HOA',
            'monthly_due_amount' => 200,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
        ]);
        $this->organization->users()->attach($this->admin->id, ['role' => OrganizationRole::Admin->value]);

        $this->community = Plan::create(['slug' => 'community', 'name' => 'Community', 'price_cents' => 19900, 'max_households' => 50, 'sort_order' => 1]);
    }

    public function test_command_expires_overdue_trials(): void
    {
        Mail::fake();

        $this->organization->update([
            'trial_ends_at' => Carbon::now()->subDay(),
            'plan_id' => $this->community->id,
        ]);

        $this->artisan('trials:expire')->assertSuccessful();

        $this->assertSame(Organization::SUBSCRIPTION_PAST_DUE, $this->organization->fresh()->subscription_status);

        Mail::assertQueued(TrialExpiredMail::class, fn ($mail) => $mail->hasTo('admin@example.com'));

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $this->organization->id,
            'action' => 'organization.billing.trial_expired',
        ]);
    }

    public function test_command_sends_t7_warning_once(): void
    {
        Mail::fake();

        $this->organization->update([
            'trial_ends_at' => Carbon::now()->addDays(7),
            'plan_id' => $this->community->id,
        ]);

        $this->artisan('trials:expire')->assertSuccessful();

        Mail::assertQueuedCount(1);
        Mail::assertQueued(TrialEndingMail::class, fn ($mail) => $mail->daysRemaining === 7);

        $this->assertTrue($this->organization->fresh()->hasWarningBeenSent(7));

        $this->artisan('trials:expire')->assertSuccessful();
        Mail::assertQueuedCount(1);
    }

    public function test_command_sends_t1_warning_once(): void
    {
        Mail::fake();

        $this->organization->update([
            'trial_ends_at' => Carbon::now()->addDay(),
            'plan_id' => $this->community->id,
        ]);

        $this->artisan('trials:expire')->assertSuccessful();

        Mail::assertQueuedCount(1);
        Mail::assertQueued(TrialEndingMail::class, fn ($mail) => $mail->daysRemaining === 1);
    }

    public function test_command_skips_orgs_outside_warning_window(): void
    {
        Mail::fake();

        $this->organization->update([
            'trial_ends_at' => Carbon::now()->addDays(5),
            'plan_id' => $this->community->id,
        ]);

        $this->artisan('trials:expire')->assertSuccessful();

        Mail::assertQueuedCount(0);
    }

    public function test_command_skips_active_subscriptions(): void
    {
        Mail::fake();

        $this->organization->update([
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
            'trial_ends_at' => Carbon::now()->subDay(),
            'plan_id' => $this->community->id,
        ]);

        $this->artisan('trials:expire')->assertSuccessful();

        $this->assertSame(Organization::SUBSCRIPTION_ACTIVE, $this->organization->fresh()->subscription_status);
        Mail::assertQueuedCount(0);
    }

    public function test_dry_run_does_not_mutate(): void
    {
        Mail::fake();

        $this->organization->update([
            'trial_ends_at' => Carbon::now()->subDay(),
            'plan_id' => $this->community->id,
        ]);

        $this->artisan('trials:expire --dry-run')->assertSuccessful();

        $this->assertSame(Organization::SUBSCRIPTION_TRIALING, $this->organization->fresh()->subscription_status);
        Mail::assertQueuedCount(0);
    }

    public function test_expired_org_blocks_writes(): void
    {
        $household = Household::create([
            'organization_id' => $this->organization->id,
            'block' => 1,
            'lot' => 1,
            'name' => 'House 1',
        ]);

        $this->organization->update([
            'subscription_status' => Organization::SUBSCRIPTION_PAST_DUE,
        ]);

        $this->actingAs($this->admin)
            ->post(route('households.store', $this->organization->slug), [
                'name' => 'New House',
                'block' => 1,
                'lot' => 2,
            ])
            ->assertRedirect(route('organizations.settings.billing.show', $this->organization->slug))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('households', [
            'organization_id' => $this->organization->id,
            'name' => 'New House',
        ]);
    }

    public function test_expired_org_allows_gets(): void
    {
        $this->organization->update([
            'subscription_status' => Organization::SUBSCRIPTION_PAST_DUE,
        ]);

        $this->actingAs($this->admin)
            ->get(route('organizations.index', $this->organization->slug))
            ->assertOk();
    }

    public function test_expired_org_allows_billing_writes(): void
    {
        Http::fake([
            'api.paymongo.com/v1/checkout_sessions' => Http::response([
                'data' => ['id' => 'cs_x', 'attributes' => ['checkout_url' => 'https://x.test']],
            ]),
        ]);

        $this->organization->update([
            'subscription_status' => Organization::SUBSCRIPTION_PAST_DUE,
            'plan_id' => $this->community->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.billing.checkout', [$this->organization->slug, $this->community->slug]))
            ->assertRedirect('https://x.test');
    }

    public function test_dashboard_shows_trial_banner(): void
    {
        $this->organization->update([
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
            'trial_ends_at' => Carbon::now()->addDays(3),
            'plan_id' => $this->community->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('organizations.index', $this->organization->slug))
            ->assertOk()
            ->assertSee('Your trial ends in 3 days');
    }

    public function test_dashboard_shows_expired_banner(): void
    {
        $this->organization->update([
            'subscription_status' => Organization::SUBSCRIPTION_PAST_DUE,
        ]);

        $this->actingAs($this->admin)
            ->get(route('organizations.index', $this->organization->slug))
            ->assertOk()
            ->assertSee('Your trial has ended');
    }
}
