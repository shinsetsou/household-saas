<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject', 191);
            $table->text('body');
            $table->string('category', 64)->nullable();
            $table->string('severity', 16)->default('normal');
            $table->string('status', 16)->default('open');
            $table->string('source', 32)->default('support_agent');
            $table->uuid('dispatch_id')->nullable();
            $table->json('kb_citations')->nullable();
            $table->float('confidence')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index(['organization_id', 'status']);
            $table->index('dispatch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};