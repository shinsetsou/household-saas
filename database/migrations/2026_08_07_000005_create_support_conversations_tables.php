<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('last_message_role', 16)->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedInteger('message_count')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
            $table->index('last_message_at');
        });

        Schema::create('support_conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_conversation_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('role', 16);
            $table->text('content');
            $table->json('tool_calls')->nullable();
            $table->string('dispatch_id', 64)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['support_conversation_id', 'created_at']);
            $table->index('dispatch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_conversation_messages');
        Schema::dropIfExists('support_conversations');
    }
};
