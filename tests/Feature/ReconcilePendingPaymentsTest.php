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

class ReconcilePendingPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Organization $organization;

    protected Plan $barangay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->organization = Organization::create([
            'name' => 'Test HOA',
            'slug' => 'test-hoa',
            'monthly_due_amount' => 200,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
        ]);
        $this->organization->users()->attach($this->admin->id, ['role' => 'admin']);

        $this->barangay = Plan::create([
            'slug' => 'barangay',
            'name' => 'Barangay',
            'price_cents' => 49900,
            'max_households' => 250,
            'sort_order' => 2,
        ]);

        config()->set('paymongo.secret_key', 'sk_test_fake');
        config()->set('paymongo.base_url', 'https://api.paymongo.com/v1');
    }

    public function test_recovers_payment_when_paymongo_session_is_paid(): void
    {
        $payment = Payment::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'paymongo_checkout_session_id' => 'cs_recover_1',
            'amount_cents' => 49900,
            'currency' => 'PHP',
            'status' => Payment::STATUS_PENDING,
            'description' => 'Checkout for Barangay',
        ]);

        Http::fake([
            'api.paymongo.com/v1/checkout_sessions/cs_recover_1' => Http::response([
                'data' => [
                    'id' => 'cs_recover_1',
                    'attributes' => [
                        'status' => 'active',
                        'payments' => [
                            [
                                'id' => 'pay_recover_1',
                                'attributes' => [
                                    'status' => 'paid',
                                    'amount' => 49900,
                                    'currency' => 'PHP',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('billing:reconcile-pending', ['--payment' => $payment->id])
            ->assertExitCode(0);

        $payment->refresh();
        $this->assertSame(Payment::STATUS_PAID, $payment->status);
        $this->assertSame('pay_recover_1', $payment->paymongo_payment_id);
        $this->assertNotNull($payment->paid_at);
        $this->assertNotNull($payment->subscription_id);

        $this->organization->refresh();
        $this->assertSame(Organization::SUBSCRIPTION_ACTIVE, $this->organization->subscription_status);
        $this->assertSame($this->barangay->id, $this->organization->plan_id);
        $this->assertNull($this->organization->trial_ends_at);

        $this->assertDatabaseHas('subscriptions', [
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);
    }

    public function test_marks_payment_failed_when_session_expired_with_no_payments(): void
    {
        $payment = Payment::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'paymongo_checkout_session_id' => 'cs_expired_1',
            'amount_cents' => 49900,
            'currency' => 'PHP',
            'status' => Payment::STATUS_PENDING,
            'description' => 'Checkout for Barangay',
        ]);

        Http::fake([
            'api.paymongo.com/v1/checkout_sessions/cs_expired_1' => Http::response([
                'data' => [
                    'id' => 'cs_expired_1',
                    'attributes' => [
                        'status' => 'expired',
                        'payments' => [],
                    ],
                ],
            ]),
        ]);

        $this->artisan('billing:reconcile-pending', ['--payment' => $payment->id])
            ->assertExitCode(0);

        $payment->refresh();
        $this->assertSame(Payment::STATUS_FAILED, $payment->status);

        $this->organization->refresh();
        $this->assertSame(Organization::SUBSCRIPTION_TRIALING, $this->organization->subscription_status);
    }

    public function test_skips_in_flight_checkout_with_pending_payments(): void
    {
        $payment = Payment::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'paymongo_checkout_session_id' => 'cs_inflight',
            'amount_cents' => 49900,
            'currency' => 'PHP',
            'status' => Payment::STATUS_PENDING,
            'description' => 'Checkout for Barangay',
        ]);

        Http::fake([
            'api.paymongo.com/v1/checkout_sessions/cs_inflight' => Http::response([
                'data' => [
                    'id' => 'cs_inflight',
                    'attributes' => [
                        'status' => 'active',
                        'payments' => [
                            [
                                'id' => 'pay_pending_1',
                                'attributes' => [
                                    'status' => 'awaiting_payment_method',
                                    'amount' => 49900,
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('billing:reconcile-pending', ['--payment' => $payment->id])
            ->assertExitCode(0);

        $payment->refresh();
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
    }

    public function test_dry_run_does_not_modify_state(): void
    {
        $payment = Payment::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'paymongo_checkout_session_id' => 'cs_dryrun',
            'amount_cents' => 49900,
            'currency' => 'PHP',
            'status' => Payment::STATUS_PENDING,
            'description' => 'Checkout for Barangay',
        ]);

        Http::fake([
            'api.paymongo.com/v1/checkout_sessions/cs_dryrun' => Http::response([
                'data' => [
                    'id' => 'cs_dryrun',
                    'attributes' => [
                        'status' => 'active',
                        'payments' => [
                            [
                                'id' => 'pay_dryrun',
                                'attributes' => [
                                    'status' => 'paid',
                                    'amount' => 49900,
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('billing:reconcile-pending', [
            '--payment' => $payment->id,
            '--dry-run' => true,
        ])->assertExitCode(0);

        $payment->refresh();
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertNull($payment->paymongo_payment_id);

        $this->organization->refresh();
        $this->assertSame(Organization::SUBSCRIPTION_TRIALING, $this->organization->subscription_status);
    }

    public function test_skips_payments_newer_than_older_than_minutes(): void
    {
        $recent = Payment::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'paymongo_checkout_session_id' => 'cs_recent',
            'amount_cents' => 49900,
            'currency' => 'PHP',
            'status' => Payment::STATUS_PENDING,
            'description' => 'Checkout for Barangay',
        ]);

        $old = new Payment([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'paymongo_checkout_session_id' => 'cs_old',
            'amount_cents' => 49900,
            'currency' => 'PHP',
            'status' => Payment::STATUS_PENDING,
            'description' => 'Checkout for Barangay',
        ]);
        $old->created_at = now()->subMinutes(30);
        $old->updated_at = now()->subMinutes(30);
        $old->save();

        Http::fake([
            'api.paymongo.com/v1/checkout_sessions/cs_recent' => Http::response(['data' => ['attributes' => ['status' => 'active', 'payments' => []]]]),
            'api.paymongo.com/v1/checkout_sessions/cs_old' => Http::response([
                'data' => [
                    'attributes' => [
                        'status' => 'active',
                        'payments' => [
                            ['id' => 'pay_old', 'attributes' => ['status' => 'paid', 'amount' => 49900]],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('billing:reconcile-pending', ['--older-than-minutes' => 5])
            ->assertExitCode(0);

        $recent->refresh();
        $old->refresh();

        $this->assertSame(Payment::STATUS_PENDING, $recent->status);
        $this->assertSame(Payment::STATUS_PAID, $old->status);
    }

    public function test_recovers_plan_from_amount_when_orphan_row_has_null_plan_id(): void
    {
        // Pre-fix code created pending rows without plan_id. The reconcile
        // command must still be able to recover them by matching the
        // PayMongo payment amount against the plan's price_cents.
        $payment = new Payment([
            'organization_id' => $this->organization->id,
            'plan_id' => null,
            'paymongo_checkout_session_id' => 'cs_orphan',
            'amount_cents' => 49900,
            'currency' => 'PHP',
            'status' => Payment::STATUS_PENDING,
            'description' => 'Checkout for Barangay (orphan)',
        ]);
        $payment->save();

        Http::fake([
            'api.paymongo.com/v1/checkout_sessions/cs_orphan' => Http::response([
                'data' => [
                    'attributes' => [
                        'status' => 'active',
                        'payments' => [
                            ['id' => 'pay_orphan', 'attributes' => ['status' => 'paid', 'amount' => 49900]],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('billing:reconcile-pending', ['--payment' => $payment->id])
            ->assertExitCode(0);

        $payment->refresh();
        $this->assertSame($this->barangay->id, $payment->plan_id);
        $this->assertSame(Payment::STATUS_PAID, $payment->status);

        $this->organization->refresh();
        $this->assertSame(Organization::SUBSCRIPTION_ACTIVE, $this->organization->subscription_status);
        $this->assertSame($this->barangay->id, $this->organization->plan_id);
    }

    public function test_filters_by_organization_slug(): void
    {
        $otherOrg = Organization::create([
            'name' => 'Other HOA',
            'slug' => 'other-hoa',
            'monthly_due_amount' => 200,
            'subscription_status' => Organization::SUBSCRIPTION_TRIALING,
        ]);

        Payment::create([
            'organization_id' => $this->organization->id,
            'plan_id' => $this->barangay->id,
            'paymongo_checkout_session_id' => 'cs_in_scope',
            'amount_cents' => 49900,
            'currency' => 'PHP',
            'status' => Payment::STATUS_PENDING,
            'description' => 'in scope',
        ]);

        Payment::create([
            'organization_id' => $otherOrg->id,
            'plan_id' => $this->barangay->id,
            'paymongo_checkout_session_id' => 'cs_out_of_scope',
            'amount_cents' => 49900,
            'currency' => 'PHP',
            'status' => Payment::STATUS_PENDING,
            'description' => 'out of scope',
        ]);

        Http::fake([
            'api.paymongo.com/v1/checkout_sessions/cs_in_scope' => Http::response([
                'data' => [
                    'attributes' => [
                        'status' => 'active',
                        'payments' => [
                            ['id' => 'pay_in', 'attributes' => ['status' => 'paid', 'amount' => 49900]],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('billing:reconcile-pending', ['--org' => 'test-hoa'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('payments', [
            'paymongo_checkout_session_id' => 'cs_in_scope',
            'status' => Payment::STATUS_PAID,
        ]);
        $this->assertDatabaseHas('payments', [
            'paymongo_checkout_session_id' => 'cs_out_of_scope',
            'status' => Payment::STATUS_PENDING,
        ]);
    }
}
