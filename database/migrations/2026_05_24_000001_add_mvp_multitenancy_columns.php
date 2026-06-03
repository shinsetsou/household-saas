<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->decimal('monthly_due_amount', 10, 2)->default(200)->after('status');
        });

        Schema::create('organization_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('viewer');
            $table->timestamps();
            $table->unique(['organization_id', 'user_id']);
        });

        Schema::table('households', function (Blueprint $table) {
            $table->unique(['organization_id', 'block', 'lot']);
        });

        Schema::table('contributions', function (Blueprint $table) {
            $table->string('verification_status')->default('pending')->after('admin_verified');
            $table->string('rejection_reason')->nullable()->after('verification_status');
            $table->foreignId('verified_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->index('reference_number');
            $table->index('verification_status');
        });

        DB::table('contributions')->where('admin_verified', 1)->update(['verification_status' => 'verified']);
        DB::table('contributions')->where('admin_verified', 3)->update(['verification_status' => 'rejected']);
        DB::table('contributions')->where('admin_verified', 0)->update(['verification_status' => 'pending']);
    }

    public function down(): void
    {
        Schema::table('contributions', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropIndex(['reference_number']);
            $table->dropIndex(['verification_status']);
            $table->dropColumn(['verification_status', 'rejection_reason', 'verified_by', 'verified_at']);
        });

        Schema::table('households', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'block', 'lot']);
        });

        Schema::dropIfExists('organization_user');

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('monthly_due_amount');
        });
    }
};
