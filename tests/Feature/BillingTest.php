<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
                            'metadata' => [
                                'organization_id' => (string) $this->organization->id,
                                'plan_id' => (string) $this->barangay->id,
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
    }

    public function test_payment_paid_webhook_ignores_unrelated_payload(): void
    {
        $payload = ['data' => ['attributes' => ['type' => 'source.chargeable']]];

        $this->postWebhook($payload)->assertOk();

        $this->assertDatabaseMissing('payments', ['organization_id' => $this->organization->id]);
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

    public function test_admin_can_cancel_subscription(): void
    {
        $this->organization->update([
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
            'plan_id' => $this->barangay->id,
        ]);
        Subscription::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.billing.cancel', $this->organization->slug))
            ->assertRedirect(route('organizations.settings.billing.show', $this->organization->slug));

        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id,
            'subscription_status' => Organization::SUBSCRIPTION_CANCELED,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'organization_id' => $this->organization->id,
            'status' => Subscription::STATUS_CANCELED,
        ]);
    }

    public function test_household_limit_middleware_blocks_creation_when_exceeded(): void
    {
        $this->organization->update([
            'plan_id' => $this->community->id,
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
        ]);

        \App\Models\Household::create(['organization_id' => $this->organization->id, 'block' => 1, 'lot' => 1, 'name' => 'House 1']);
        for ($i = 2; $i <= 50; $i++) {
            \App\Models\Household::create(['organization_id' => $this->organization->id, 'block' => 1, 'lot' => $i, 'name' => 'House '.$i]);
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
            \App\Models\Household::create(['organization_id' => $this->organization->id, 'block' => 1, 'lot' => $i, 'name' => 'House '.$i]);
        }

        $this->assertFalse($this->organization->fresh()->hasReachedHouseholdLimit());
    }

    public function test_return_success_redirects_to_billing_with_message(): void
    {
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