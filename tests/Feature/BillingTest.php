<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Organization $organization;

    protected Plan $community;

    protected Plan $barangay;

    protected Plan $city;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->organization = Organization::create([
            'name' => 'Test HOA',
            'monthly_due_amount' => 200,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
        ]);
        $this->organization->users()->attach($this->admin->id, ['role' => 'admin']);

        $this->community = Plan::create(['slug' => 'community', 'name' => 'Community', 'price_cents' => 19900, 'max_households' => 50, 'sort_order' => 1]);
        $this->barangay = Plan::create(['slug' => 'barangay', 'name' => 'Barangay', 'price_cents' => 49900, 'max_households' => 250, 'sort_order' => 2]);
        $this->city = Plan::create(['slug' => 'city', 'name' => 'City', 'price_cents' => 99900, 'max_households' => null, 'sort_order' => 3]);

        config()->set('paymongo.secret_key', 'sk_test_fake');
        config()->set('paymongo.success_url', 'http://localhost/billing/return/success');
        config()->set('paymongo.cancel_url', 'http://localhost/billing/return/cancel');
        config()->set('paymongo.webhook_secret', 'whsec_test_fake');
    }

    public function test_billing_settings_page_lists_plans(): void
    {
        $this->actingAs($this->admin)
            ->get(route('organizations.settings.billing.show', $this->organization->slug))
            ->assertOk()
            ->assertSee('Community')
            ->assertSee('Barangay')
            ->assertSee('City');
    }

    public function test_billing_page_shows_subscribe_button_for_current_plan_while_trialing(): void
    {
        // Trialing on the free Community plan — must offer a way to pay for
        // Community and convert the trial to a paid subscription.
        $this->organization->update([
            'plan_id' => $this->community->id,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
            'trial_ends_at' => now()->addDays(3),
        ]);

        $this->actingAs($this->admin)
            ->get(route('organizations.settings.billing.show', $this->organization->slug))
            ->assertOk()
            ->assertSee('Subscribe to Community')
            ->assertSee('Current · Trial');
    }

    public function test_billing_page_shows_reactivate_button_for_current_plan_when_past_due(): void
    {
        $this->organization->update([
            'plan_id' => $this->community->id,
            'subscription_status' => Organization::SUBSCRIPTION_PAST_DUE,
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('organizations.settings.billing.show', $this->organization->slug))
            ->assertOk()
            ->assertSee('Reactivate Community')
            ->assertSee('Current · Past due');
    }

    public function test_billing_page_hides_button_on_current_plan_when_already_active(): void
    {
        $this->organization->update([
            'plan_id' => $this->community->id,
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('organizations.settings.billing.show', $this->organization->slug))
            ->assertOk();

        // No "Subscribe", "Reactivate", or "Switch to Community" — the user
        // has already paid. The other plans still show "Switch to <plan>".
        $response->assertDontSee('Subscribe to Community');
        $response->assertDontSee('Reactivate Community');
        $response->assertDontSee('Switch to Community');
        $response->assertSee('Switch to Barangay');
        $response->assertSee('Switch to City');
    }

    public function test_billing_page_lists_all_three_plans_even_if_db_started_empty(): void
    {
        // Simulate a stale dev DB: delete all plan rows. AppServiceProvider should
        // auto-seed on the next boot, so the billing page must still render all
        // three plan cards with working Switch buttons.
        Plan::query()->delete();

        $this->assertSame(0, Plan::count());

        $response = $this->actingAs($this->admin)
            ->get(route('organizations.settings.billing.show', $this->organization->slug));

        $response->assertOk();
        $response->assertSee('Community');
        $response->assertSee('Barangay');
        $response->assertSee('City');

        $this->assertSame(3, Plan::count());
    }

    public function test_billing_page_shows_switch_button_for_non_current_plans(): void
    {
        // Put the org on the cheapest plan so both bigger plans show a button.
        $this->organization->update(['plan_id' => $this->community->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('organizations.settings.billing.show', $this->organization->slug));

        $response->assertOk();
        // Both bigger plans should show their switch CTA.
        $response->assertSee('Switch to Barangay');
        $response->assertSee('Switch to City');
        // The current plan should NOT show a switch button.
        $this->assertStringNotContainsString(
            'Switch to Community',
            $response->getContent()
        );
    }

    public function test_guest_cannot_view_billing_page(): void
    {
        $this->get(route('organizations.settings.billing.show', $this->organization->slug))
            ->assertRedirect(route('login'));
    }

    public function test_non_member_cannot_view_billing_page(): void
    {
        $other = User::factory()->create();

        $this->actingAs($other)
            ->get(route('organizations.settings.billing.show', $this->organization->slug))
            ->assertForbidden();
    }

    public function test_non_admin_cannot_checkout(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member->id, ['role' => 'member']);

        Http::fake([
            'api.paymongo.com/*' => Http::response(['data' => ['id' => 'cs_test', 'attributes' => ['checkout_url' => 'https://paymongo.test/cs']]]),
        ]);

        $this->actingAs($member)
            ->post(route('organizations.settings.billing.checkout', [$this->organization->slug, $this->barangay->slug]))
            ->assertForbidden();
    }

    public function test_admin_can_start_checkout_and_redirects(): void
    {
        Http::fake([
            'api.paymongo.com/v1/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => ['checkout_url' => 'https://paymongo.test/checkout/cs_test_123'],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('organizations.settings.billing.checkout', [$this->organization->slug, $this->barangay->slug]));

        $response->assertRedirect('https://paymongo.test/checkout/cs_test_123');

        $this->assertDatabaseHas('payments', [
            'organization_id' => $this->organization->id,
            'paymongo_checkout_session_id' => 'cs_test_123',
            'amount_cents' => 49900,
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    public function test_payment_paid_webhook_activates_subscription(): void
    {
        // Pre-create the pending payment row that startCheckout() would have
        // written. The real PayMongo payment.paid event does NOT carry our
        // `organization_id` / `plan_id` in its metadata — those are in the
        // checkout session we created earlier, and we persist them locally
        // on the `payments` row. The webhook only signals "this payment
        // succeeded, please activate it".
        $payment = Payment::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'paymongo_payment_id' => 'pay_test_1',
            'amount_cents' => 49900,
            'currency' => 'PHP',
            'status' => Payment::STATUS_PENDING,
            'description' => 'Checkout for Barangay',
        ]);

        // Real PayMongo `payment.paid` payload shape (no organization/plan metadata).
        $payload = [
            'data' => [
                'id' => 'pay_test_1',
                'attributes' => [
                    'type' => 'payment.paid',
                    'amount' => 49900,
                    'currency' => 'PHP',
                    'data' => [
                        'id' => 'pay_test_1',
                        'attributes' => [
                            'amount' => 49900,
                            'currency' => 'PHP',
                            'status' => 'paid',
                            'metadata' => [
                                'pm_reference_number' => 'SBuAjs2',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->postWebhook($payload)->assertOk();

        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id,
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
            'plan_id' => $this->barangay->id,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('payments', [
            'organization_id' => $this->organization->id,
            'paymongo_payment_id' => 'pay_test_1',
            'status' => Payment::STATUS_PAID,
            'amount_cents' => 49900,
        ]);

        $payment->refresh();
        $this->assertNotNull($payment->subscription_id);
    }

    public function test_payment_paid_webhook_ignores_unrelated_payload(): void
    {
        $payload = ['data' => ['attributes' => ['type' => 'source.chargeable']]];

        $this->postWebhook($payload)->assertOk();

        $this->assertDatabaseMissing('payments', ['organization_id' => $this->organization->id]);
    }

    public function test_payment_paid_webhook_with_no_local_payment_row_is_logged_not_silently_ignored(): void
    {
        // A real PayMongo payment.paid arrives but our local `payments` row
        // is missing (DB lost the row, race with refund, etc.). The handler
        // must NOT activate any org — and must log loudly so we can spot it.
        Log::spy();

        $payload = [
            'data' => [
                'id' => 'pay_orphan_1',
                'attributes' => [
                    'type' => 'payment.paid',
                    'amount' => 49900,
                    'currency' => 'PHP',
                    'data' => [
                        'id' => 'pay_orphan_1',
                        'attributes' => [
                            'amount' => 49900,
                            'currency' => 'PHP',
                            'status' => 'paid',
                            'metadata' => [
                                'pm_reference_number' => 'SBuAjs2',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->postWebhook($payload)->assertOk();

        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
        ]);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message, $context) => $message === 'payment.paid webhook: no matching local payment row'
                && ($context['paymongo_payment_id'] ?? null) === 'pay_orphan_1');
    }

    public function test_payment_paid_webhook_is_idempotent_on_retry(): void
    {
        $payment = Payment::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'paymongo_payment_id' => 'pay_test_retry',
            'amount_cents' => 49900,
            'currency' => 'PHP',
            'status' => Payment::STATUS_PENDING,
            'description' => 'Checkout for Barangay',
        ]);

        $payload = [
            'data' => [
                'id' => 'pay_test_retry',
                'attributes' => [
                    'type' => 'payment.paid',
                    'amount' => 49900,
                    'currency' => 'PHP',
                    'data' => [
                        'id' => 'pay_test_retry',
                        'attributes' => ['amount' => 49900, 'currency' => 'PHP', 'status' => 'paid'],
                    ],
                ],
            ],
        ];

        $this->postWebhook($payload)->assertOk();
        $this->postWebhook($payload)->assertOk();
        $this->postWebhook($payload)->assertOk();

        // Exactly one paid payment row exists for this org + payment id.
        $this->assertSame(1, Payment::where('paymongo_payment_id', 'pay_test_retry')->count());
        $this->assertSame(1, Payment::where('organization_id', $this->organization->id)->where('status', Payment::STATUS_PAID)->count());

        $payment->refresh();
        $this->assertSame(Payment::STATUS_PAID, $payment->status);
    }

    public function test_webhook_rejects_missing_signature(): void
    {
        $payload = ['data' => ['attributes' => ['type' => 'payment.paid']]];

        $this->postJson(route('billing.webhook'), $payload)
            ->assertStatus(401);

        $this->assertDatabaseMissing('payments', ['organization_id' => $this->organization->id]);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $payload = ['data' => ['attributes' => ['type' => 'payment.paid']]];

        $this->postJson(route('billing.webhook'), $payload, [
            'Paymongo-Signature' => 'deadbeef',
        ])->assertStatus(401);

        $this->assertDatabaseMissing('payments', ['organization_id' => $this->organization->id]);
    }

    public function test_webhook_rejects_payload_tampering_after_signing(): void
    {
        $signedBody = json_encode(['data' => ['attributes' => ['type' => 'payment.paid']]]);
        $signature = hash_hmac('sha256', $signedBody, 'whsec_test_fake');

        $tamperedBody = json_encode(['data' => ['attributes' => ['type' => 'source.chargeable']]]);

        $this->call('POST', route('billing.webhook'), [], [], [], [
            'HTTP_PAYMONGO_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $tamperedBody)->assertStatus(401);
    }

    public function test_webhook_returns_500_when_secret_unset(): void
    {
        config()->set('paymongo.webhook_secret', '');

        $payload = ['data' => ['attributes' => ['type' => 'payment.paid']]];
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, 'whsec_test_fake');

        $this->call('POST', route('billing.webhook'), [], [], [], [
            'HTTP_PAYMONGO_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertStatus(500);
    }

    protected function postWebhook(array $payload)
    {
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, 'whsec_test_fake');

        return $this->call('POST', route('billing.webhook'), [], [], [], [
            'HTTP_PAYMONGO_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }

    public function test_admin_can_cancel_subscription_at_period_end(): void
    {
        $this->organization->update([
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
            'plan_id' => $this->barangay->id,
        ]);
        $subscription = Subscription::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(14),
        ]);

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.billing.cancel', $this->organization->slug))
            ->assertRedirect(route('organizations.settings.billing.show', $this->organization->slug))
            ->assertSessionHas('info');

        $subscription = $subscription->fresh();
        $this->assertNotNull($subscription->canceled_at);
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status, 'Subscription should remain active until period end');
        $this->assertSame(Organization::SUBSCRIPTION_ACTIVE, $this->organization->fresh()->subscription_status);
        $this->assertNotNull($this->organization->fresh()->subscription_ends_at);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $this->organization->id,
            'action' => 'organization.billing.cancel_scheduled',
        ]);
    }

    public function test_admin_can_resume_subscription_before_period_end(): void
    {
        $this->organization->update([
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
            'plan_id' => $this->barangay->id,
        ]);
        $subscription = Subscription::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(14),
            'canceled_at' => now()->addDays(14),
        ]);

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.billing.resume', $this->organization->slug))
            ->assertRedirect(route('organizations.settings.billing.show', $this->organization->slug))
            ->assertSessionHas('success');

        $this->assertNull($subscription->fresh()->canceled_at);
        $this->assertNull($this->organization->fresh()->subscription_ends_at);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $this->organization->id,
            'action' => 'organization.billing.resumed',
        ]);
    }

    public function test_subscriptions_expire_command_finalizes_due_cancellations(): void
    {
        $this->organization->update([
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
            'plan_id' => $this->barangay->id,
        ]);
        $subscription = Subscription::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now()->subDays(30),
            'current_period_end' => now()->subDays(1),
            'canceled_at' => now()->subDay(), // scheduled yesterday
        ]);

        $this->artisan('subscriptions:expire')->assertSuccessful();

        $subscription = $subscription->fresh();
        $this->assertSame(Subscription::STATUS_CANCELED, $subscription->status);
        $this->assertSame(Organization::SUBSCRIPTION_CANCELED, $this->organization->fresh()->subscription_status);
    }

    public function test_subscriptions_expire_command_skips_cancellations_not_yet_due(): void
    {
        $this->organization->update([
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
            'plan_id' => $this->barangay->id,
        ]);
        $subscription = Subscription::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(14),
            'canceled_at' => now()->addDays(14),
        ]);

        $this->artisan('subscriptions:expire')->assertSuccessful();

        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->fresh()->status);
    }

    public function test_downgrade_checkout_schedules_current_sub_cancellation(): void
    {
        $this->organization->update([
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
            'plan_id' => $this->barangay->id,
        ]);
        Subscription::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(14),
        ]);

        Http::fake([
            'api.paymongo.com/v1/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_downgrade',
                    'attributes' => ['checkout_url' => 'https://paymongo.test/checkout/cs_downgrade'],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('organizations.settings.billing.checkout', [$this->organization->slug, $this->community->slug]));

        $response->assertRedirect('https://paymongo.test/checkout/cs_downgrade');

        // Current subscription should be scheduled to cancel at period end (not immediately).
        $this->assertDatabaseHas('subscriptions', [
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);
        $subscription = Subscription::where('organization_id', $this->organization->id)->first();
        $this->assertNotNull($subscription->canceled_at);

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $this->organization->id,
            'action' => 'organization.billing.downgrade_scheduled',
        ]);
    }

    public function test_upgrade_checkout_does_not_cancel_existing_sub(): void
    {
        $this->organization->update([
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
            'plan_id' => $this->community->id,
        ]);
        $subscription = Subscription::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->community->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(14),
        ]);

        Http::fake([
            'api.paymongo.com/v1/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_upgrade',
                    'attributes' => ['checkout_url' => 'https://paymongo.test/checkout/cs_upgrade'],
                ],
            ]),
        ]);

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.billing.checkout', [$this->organization->slug, $this->barangay->slug]))
            ->assertRedirect('https://paymongo.test/checkout/cs_upgrade');

        // Existing subscription should be untouched.
        $this->assertNull($subscription->fresh()->canceled_at);
        $this->assertDatabaseMissing('audit_logs', [
            'organization_id' => $this->organization->id,
            'action' => 'organization.billing.downgrade_scheduled',
        ]);
    }

    public function test_cancel_does_not_change_subscription_status_immediately(): void
    {
        $this->organization->update([
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
            'plan_id' => $this->barangay->id,
        ]);
        Subscription::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(14),
        ]);

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.billing.cancel', $this->organization->slug));

        // Status remains active — only canceled_at is set.
        $this->assertSame(Organization::SUBSCRIPTION_ACTIVE, $this->organization->fresh()->subscription_status);
        $this->assertTrue($this->organization->fresh()->isCancelingAtPeriodEnd());
    }

    public function test_household_limit_middleware_blocks_creation_when_exceeded(): void
    {
        $this->organization->update([
            'plan_id' => $this->community->id,
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
        ]);

        Household::create(['organization_id' => $this->organization->id, 'block' => 1, 'lot' => 1, 'name' => 'House 1']);
        for ($i = 2; $i <= 50; $i++) {
            Household::create(['organization_id' => $this->organization->id, 'block' => 1, 'lot' => $i, 'name' => 'House '.$i]);
        }

        $this->actingAs($this->admin)
            ->get(route('households.create', $this->organization->slug))
            ->assertRedirect(route('organizations.settings.billing.show', $this->organization->slug))
            ->assertSessionHas('error');
    }

    public function test_unlimited_plan_never_blocks(): void
    {
        $this->organization->update([
            'plan_id' => $this->city->id,
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
        ]);

        for ($i = 1; $i <= 60; $i++) {
            Household::create(['organization_id' => $this->organization->id, 'block' => 1, 'lot' => $i, 'name' => 'House '.$i]);
        }

        $this->assertFalse($this->organization->fresh()->hasReachedHouseholdLimit());
    }

    public function test_return_success_shows_processing_flash_when_org_not_yet_active(): void
    {
        // The org is still trialing in setUp() — the webhook may not have
        // arrived yet. The success URL must NOT claim the plan is active.
        $this->get(route('billing.return.success', ['organization' => $this->organization->slug]))
            ->assertRedirect(route('organizations.settings.billing.show', $this->organization->slug))
            ->assertSessionHas('info');
    }

    public function test_return_success_shows_success_flash_when_org_is_active(): void
    {
        $this->organization->update([
            'plan_id' => $this->barangay->id,
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
        ]);

        $this->get(route('billing.return.success', ['organization' => $this->organization->slug]))
            ->assertRedirect(route('organizations.settings.billing.show', $this->organization->slug))
            ->assertSessionHas('success');
    }

    public function test_return_cancel_redirects_to_billing_with_info(): void
    {
        $this->get(route('billing.return.cancel', ['organization' => $this->organization->slug]))
            ->assertRedirect(route('organizations.settings.billing.show', $this->organization->slug))
            ->assertSessionHas('info');
    }
}
