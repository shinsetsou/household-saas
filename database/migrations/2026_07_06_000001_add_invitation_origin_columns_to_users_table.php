<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('invitation_origin_org_slug')->nullable()->after('force_change_password');
            $table->string('invitation_origin_role')->nullable()->after('invitation_origin_org_slug');
            $table->string('invitation_origin_inviter_email')->nullable()->after('invitation_origin_role');
            $table->timestamp('invitation_origin_revoked_at')->nullable()->after('invitation_origin_inviter_email');

            $table->index('invitation_origin_org_slug');
            $table->index('invitation_origin_revoked_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['invitation_origin_org_slug']);
            $table->dropIndex(['invitation_origin_revoked_at']);

            $table->dropColumn([
                'invitation_origin_org_slug',
                'invitation_origin_role',
                'invitation_origin_inviter_email',
                'invitation_origin_revoked_at',
            ]);
        });
    }
};
