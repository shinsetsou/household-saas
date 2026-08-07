<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_call_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('dispatch_id')->unique();
            $table->string('agent', 64)->index();
            $table->string('task', 128)->nullable();
            $table->string('model', 64)->nullable();
            $table->string('provider', 32)->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->unsignedBigInteger('cost_usd_cents')->default(0);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->string('status', 16)->default('pending');
            $table->text('error')->nullable();
            $table->json('context')->nullable();
            $table->longText('prompt')->nullable();
            $table->longText('response')->nullable();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['agent', 'created_at']);
            $table->index(['organization_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_call_logs');
    }
};