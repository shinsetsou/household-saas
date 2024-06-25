<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contributions', function (Blueprint $table) {
            //
            $table->string('photo')->nullable()->after('amount'); // Add this line
            $table->string('status')->default('paid')->after('photo'); // Adding status field
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contributions', function (Blueprint $table) {
            //
            $table->dropColumn(['photo', 'status']);
        });
    }
};
