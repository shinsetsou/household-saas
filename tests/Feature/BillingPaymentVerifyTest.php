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

class BillingPaymentVerifyTest extends TestCase
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

        $this->admin = User::factory()->create([
            'email_verified_at' => now(),
            'force_change_password' => false,
        ]);
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
        config()->set('paymongo.base_url', 'https://api.paymongo.com/v1');
    }

    public function test_admin_can_verify_payment_by_paymongo_id(): void
    {
        Http::fake([
            'https://api.paymongo.com/v1/payments/pay_live_123' => Http::response([
                'data' => $this->paidPaymentPayload('pay_live_123', 49900, 'PHP', 'Barangay'),
            ]),
        ]);

        $this->organization->update(['plan_id' => $this->barangay->id]);

        $response = $this->actingAs($this->admin)
            ->followingRedirects()
            ->post(route('organizations.settings.billing.verify-payment', $this->organization->slug), [
                'org_name' => 'Test HOA',
                'reference' => 'pay_live_123',
            ]);

        $response->assertOk();
        $response->assertSee('Payment verified!');

        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id,
            'subscription_status' => Organization::SUBSCRIPTION_ACTIVE,
            'plan_id' => $this->barangay->id,
        ]);

        $this->assertDatabaseHas('payments', [
            'organization_id' => $this->organization->id,
            'paymongo_payment_id' => 'pay_live_123',
            'status' => Payment::STATUS_PAID,
            'plan_id' => $this->barangay->id,
            'verified_by_user_id' => $this->admin->id,
        ]);
    }

    public function test_verification_by_reference_number_falls_back_to_list_scan(): void
    {
        Http::fake([
            'https://api.paymongo.com/v1/payments?reference_number=SBuAjs2' => Http::response(['data' => []]),
            'https://api.paymongo.com/v1/payments?limit=100' => Http::response([
                'data' => [
                    $this->paidPaymentPayload('pay_ref_1', 19900, 'PHP', 'Community', 'SBuAjs2'),
                ],
            ]),
        ]);

        $this->organization->update(['plan_id' => $this->community->id]);

        $response = $this->actingAs($this->admin)
            ->followingRedirects()
            ->post(route('organizations.settings.billing.verify-payment', $this->organization->slug), [
                'org_name' => 'Test HOA',
                'reference' => 'SBuAjs2',
            ]);

        $response->assertOk();
        $response->assertSee('Payment verified!');

        $this->assertDatabaseHas('payments', [
            'paymongo_payment_id' => 'pay_ref_1',
            'plan_id' => $this->community->id,
        ]);
    }

    public function test_not_found_shows_user_friendly_error(): void
    {
        Http::fake([
            'https://api.paymongo.com/v1/payments/pay_missing' => Http::response(['errors' => [['detail' => 'Not found']]], 404),
        ]);

        $this->organization->update(['plan_id' => $this->community->id]);

        $response = $this->actingAs($this->admin)
            ->followingRedirects()
            ->post(route('organizations.settings.billing.verify-payment', $this->organization->slug), [
                'org_name' => 'Test HOA',
                'reference' => 'pay_missing',
            ]);

        $response->assertOk();
        $response->assertSee('We could not find a PayMongo payment');

        $this->assertDatabaseMissing('payments', [
            'organization_id' => $this->organization->id,
            'paymongo_payment_id' => 'pay_missing',
        ]);
    }

    public function test_not_paid_payment_rejects_verification(): void
    {
        Http::fake([
            'https://api.paymongo.com/v1/payments/pay_unpaid' => Http::response([
                'data' => [
                    'id' => 'pay_unpaid',
                    'attributes' => [
                        'status' => 'unpaid',
                        'livemode' => true,
                        'amount' => 49900,
                        'currency' => 'PHP',
                    ],
                ],
            ]),
        ]);

        $this->organization->update(['plan_id' => $this->barangay->id]);

        $response = $this->actingAs($this->admin)
            ->followingRedirects()
            ->post(route('organizations.settings.billing.verify-payment', $this->organization->slug), [
                'org_name' => 'Test HOA',
                'reference' => 'pay_unpaid',
            ]);

        $response->assertOk();
        $response->assertSee('not confirmed as paid');
        $this->assertDatabaseMissing('payments', ['paymongo_payment_id' => 'pay_unpaid']);
    }

    public function test_amount_mismatch_rejects_verification(): void
    {
        Http::fake([
            'https://api.paymongo.com/v1/payments/pay_overpay' => Http::response([
                'data' => $this->paidPaymentPayload('pay_overpay', 99900, 'PHP', 'City'),
            ]),
        ]);

        $this->organization->update(['plan_id' => $this->community->id]);

        $response = $this->actingAs($this->admin)
            ->followingRedirects()
            ->post(route('organizations.settings.billing.verify-payment', $this->organization->slug), [
                'org_name' => 'Test HOA',
                'reference' => 'pay_overpay',
                'plan_slug' => 'community',
            ]);

        $response->assertOk();
        $response->assertSee('amount does not match');

        $this->assertDatabaseMissing('payments', ['paymongo_payment_id' => 'pay_overpay']);
    }

    public function test_double_verify_is_idempotent(): void
    {
        Http::fake([
            'https://api.paymongo.com/v1/payments/pay_twice' => Http::response([
                'data' => $this->paidPaymentPayload('pay_twice', 49900, 'PHP', 'Barangay'),
            ]),
        ]);

        $this->organization->update(['plan_id' => $this->barangay->id]);

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.billing.verify-payment', $this->organization->slug), [
                'org_name' => 'Test HOA',
                'reference' => 'pay_twice',
            ])
            ->assertRedirect();

        $first = $this->organization->payments()->first();
        $this->assertNotNull($first);

        $response = $this->actingAs($this->admin)
            ->followingRedirects()
            ->post(route('organizations.settings.billing.verify-payment', $this->organization->slug), [
                'org_name' => 'Test HOA',
                'reference' => 'pay_twice',
            ]);

        $response->assertOk();
        $response->assertSee('already verified');

        $this->assertSame(1, Payment::where('paymongo_payment_id', 'pay_twice')->count());
        $this->assertDatabaseHas('subscriptions', [
            'organization_id' => $this->organization->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);
    }

    public function test_payment_used_by_another_org_rejects(): void
    {
        Http::fake([
            'https://api.paymongo.com/v1/payments/pay_claimed' => Http::response([
                'data' => $this->paidPaymentPayload('pay_claimed', 49900, 'PHP', 'Barangay'),
            ]),
        ]);

        $otherOrg = Organization::factory()->create();
        Payment::create([
            'organization_id' => $otherOrg->id,
            'plan_id' => $this->barangay->id,
            'paymongo_payment_id' => 'pay_claimed',
            'amount_cents' => 49900,
            'currency' => 'PHP',
            'status' => Payment::STATUS_PAID,
        ]);

        $this->organization->update(['plan_id' => $this->barangay->id]);

        $response = $this->actingAs($this->admin)
            ->followingRedirects()
            ->post(route('organizations.settings.billing.verify-payment', $this->organization->slug), [
                'org_name' => 'Test HOA',
                'reference' => 'pay_claimed',
            ]);

        $response->assertOk();
        $response->assertSee('already been used by another organization');
    }

    public function test_wrong_org_name_rejects(): void
    {
        Http::fake();

        $this->organization->update(['plan_id' => $this->community->id]);

        $response = $this->actingAs($this->admin)
            ->followingRedirects()
            ->post(route('organizations.settings.billing.verify-payment', $this->organization->slug), [
                'org_name' => 'Wrong Name',
                'reference' => 'pay_any',
            ]);

        $response->assertOk();
        $response->assertSee('Organization name does not match');
    }

    public function test_non_admin_member_cannot_verify(): void
    {
        Http::fake();

        $member = User::factory()->create([
            'email_verified_at' => now(),
            'force_change_password' => false,
        ]);
        $this->organization->users()->attach($member->id, ['role' => 'member']);

        $this->actingAs($member)
            ->post(route('organizations.settings.billing.verify-payment', $this->organization->slug), [
                'org_name' => 'Test HOA',
                'reference' => 'pay_any',
            ])
            ->assertForbidden();
    }

    public function test_guest_cannot_verify(): void
    {
        Http::fake();

        $this->post(route('organizations.settings.billing.verify-payment', $this->organization->slug), [
            'org_name' => 'Test HOA',
            'reference' => 'pay_any',
        ])
            ->assertRedirect(route('login'));
    }

    public function test_rate_limit_blocks_excess_requests(): void
    {
        Http::fake([
            'https://api.paymongo.com/v1/payments/pay_ratelimit' => Http::response([
                'data' => $this->paidPaymentPayload('pay_ratelimit', 49900, 'PHP', 'Barangay'),
            ]),
        ]);

        $this->organization->update(['plan_id' => $this->barangay->id]);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->admin)
                ->post(route('organizations.settings.billing.verify-payment', $this->organization->slug), [
                    'org_name' => 'Test HOA',
                    'reference' => 'pay_ratelimit',
                ]);
        }

        $this->actingAs($this->admin)
            ->post(route('organizations.settings.billing.verify-payment', $this->organization->slug), [
                'org_name' => 'Test HOA',
                'reference' => 'pay_ratelimit',
            ])
            ->assertStatus(429);
    }

    protected function paidPaymentPayload(string $id, int $amount, string $currency, string $descriptor, ?string $reference = null): array
    {
        $metadata = [];

        if ($reference) {
            $metadata['pm_reference_number'] = $reference;
        }

        return [
            'id' => $id,
            'attributes' => [
                'status' => 'paid',
                'livemode' => true,
                'amount' => $amount,
                'currency' => $currency,
                'statement_descriptor' => $descriptor,
                'description' => $descriptor.' (monthly)',
                'metadata' => $metadata,
            ],
        ];
    }
}
