<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents');
            $table->string('currency', 3)->default('PHP');
            $table->string('interval')->default('month');
            $table->unsignedInteger('max_households')->nullable();
            $table->boolean('csv_import')->default(true);
            $table->boolean('api_access')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('subscription_status')->default('trialing')->after('monthly_due_amount');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
            $table->foreignId('plan_id')->nullable()->after('subscription_ends_at')->constrained('plans')->nullOnDelete();
            $table->string('paymongo_customer_id')->nullable()->after('plan_id');
            $table->index('subscription_status');
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('paymongo_subscription_id')->nullable()->unique();
            $table->string('status');
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('paymongo_payment_id')->nullable()->unique();
            $table->string('paymongo_checkout_session_id')->nullable()->index();
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('PHP');
            $table->string('status');
            $table->string('description')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropIndex(['subscription_status']);
            $table->dropColumn([
                'subscription_status',
                'trial_ends_at',
                'subscription_ends_at',
                'plan_id',
                'paymongo_customer_id',
            ]);
        });

        Schema::dropIfExists('plans');
    }
};