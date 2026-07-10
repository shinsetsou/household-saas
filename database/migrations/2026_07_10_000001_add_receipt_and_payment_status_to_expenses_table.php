<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('receipt')->nullable()->after('description');
            $table->string('status')->default('pending')->after('receipt');
            $table->timestamp('paid_at')->nullable()->after('status');
            $table->foreignId('paid_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable()->after('paid_by');

            $table->index('status');
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['paid_by']);
            $table->dropIndex(['organization_id', 'status']);
            $table->dropIndex(['status']);
            $table->dropColumn(['receipt', 'status', 'paid_at', 'paid_by', 'notes']);
        });
    }
};
