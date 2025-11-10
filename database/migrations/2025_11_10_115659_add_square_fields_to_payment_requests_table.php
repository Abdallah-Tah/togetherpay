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
        Schema::table('payment_requests', function (Blueprint $table) {
            // Square invoice ID (if this request came from Square subscription)
            $table->string('square_invoice_id')->nullable()->after('status')->comment('Square invoice ID for auto-square payments');

            // Square payment ID (when Square processes the payment)
            $table->string('square_payment_id')->nullable()->after('square_invoice_id')->comment('Square payment ID when payment is processed');

            // Timestamp when Square webhook confirmed payment
            $table->timestamp('square_confirmed_at')->nullable()->after('square_payment_id')->comment('When Square webhook confirmed the payment');

            // Indexes for fast lookups
            $table->index('square_invoice_id');
            $table->index('square_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropIndex(['square_invoice_id']);
            $table->dropIndex(['square_payment_id']);
            $table->dropColumn([
                'square_invoice_id',
                'square_payment_id',
                'square_confirmed_at',
            ]);
        });
    }
};
