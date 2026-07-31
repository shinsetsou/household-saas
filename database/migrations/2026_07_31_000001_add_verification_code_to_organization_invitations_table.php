<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_invitations', function (Blueprint $table) {
            $table->string('verification_code', 6)->nullable()->after('role');
            $table->index(['organization_id', 'verification_code']);
        });
    }

    public function down(): void
    {
        Schema::table('organization_invitations', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'verification_code']);
            $table->dropColumn('verification_code');
        });
    }
};
