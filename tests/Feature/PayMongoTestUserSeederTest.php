<?php

namespace Tests\Feature;

use App\Services\Billing\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PayMongoTestUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_aborts_cleanly_when_plans_table_missing(): void
    {
        Schema::dropIfExists('plans');

        $this->artisan('db:seed', ['--class' => 'PayMongoTestUserSeeder'])
            ->expectsOutputToContain('Missing table `plans`')
            ->expectsOutputToContain('Aborting PayMongoTestUserSeeder')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['email' => 'paymongo-test@example.com']);
        $this->assertDatabaseMissing('organizations', ['slug' => 'paymongo-test-hoa']);
    }

    public function test_seeder_succeeds_when_schema_is_complete(): void
    {
        $this->artisan('db:seed', ['--class' => 'PayMongoTestUserSeeder'])
            ->expectsOutputToContain('PayMongo test user seeded:')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'paymongo-test@example.com']);
        $this->assertDatabaseHas('organizations', ['slug' => 'paymongo-test-hoa']);
    }

    public function test_billing_service_ensure_plans_exist_is_noop_when_table_missing(): void
    {
        Schema::dropIfExists('plans');

        $result = app(BillingService::class)->ensurePlansExist();

        $this->assertFalse($result);
        $this->assertFalse(Schema::hasTable('plans'));
    }
}
