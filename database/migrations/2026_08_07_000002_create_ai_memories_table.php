<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_memories', function (Blueprint $table) {
            $table->id();
            $table->string('namespace', 64)->index();
            $table->string('source', 32)->default('kb');
            $table->string('source_id', 128)->nullable();
            $table->string('embedding_model', 64)->nullable();
            $table->json('embedding');
            $table->mediumText('content');
            $table->json('metadata')->nullable();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['namespace', 'source']);
            $table->index(['organization_id', 'namespace']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_memories');
    }
};