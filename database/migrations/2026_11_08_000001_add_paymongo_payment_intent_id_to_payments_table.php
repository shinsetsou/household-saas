<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Stored when the checkout session is created (the session payload
            // carries payment_intent_id). `payment.paid` webhook events identify
            // the payment by intent id — a different namespace from the
            // checkout session id (cs_ vs pi_) — so this is the deterministic
            // 1:1 join key between the local pending row and the event.
            $table->string('paymongo_payment_intent_id')
                ->nullable()
                ->index()
                ->after('paymongo_checkout_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['paymongo_payment_intent_id']);
            $table->dropColumn('paymongo_payment_intent_id');
        });
    }
};
