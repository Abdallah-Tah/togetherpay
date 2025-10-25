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
        Schema::table('users', function (Blueprint $table) {
            $table->string('cashapp_tag')->nullable()->after('phone'); // e.g., $JohnDoe
            $table->string('cashapp_customer_id')->nullable()->after('cashapp_tag'); // OAuth customer ID
            $table->string('venmo_username')->nullable()->after('cashapp_customer_id'); // e.g., @JohnDoe
            $table->string('venmo_customer_id')->nullable()->after('venmo_username'); // OAuth customer ID
            $table->string('preferred_payment_method')->default('CASH_APP')->after('venmo_customer_id'); // CASH_APP, VENMO, CARD
            $table->json('payment_metadata')->nullable()->after('preferred_payment_method'); // Additional payment data
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'cashapp_tag',
                'cashapp_customer_id',
                'venmo_username',
                'venmo_customer_id',
                'preferred_payment_method',
                'payment_metadata',
            ]);
        });
    }
};
