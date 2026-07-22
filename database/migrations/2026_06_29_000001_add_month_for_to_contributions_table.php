<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contributions', function (Blueprint $table) {
            $table->date('month_for')->nullable()->after('amount');
            $table->index(['month_for', 'verification_status'], 'contributions_month_for_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('contributions', function (Blueprint $table) {
            $table->dropIndex('contributions_month_for_status_idx');
            $table->dropColumn('month_for');
        });
    }
};
