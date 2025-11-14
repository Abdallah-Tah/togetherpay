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
        Schema::table('group_members', function (Blueprint $table) {
            // Square customer ID for this member (created when added to auto_square group)
            $table->string('square_customer_id')->nullable()->after('cash_email')->comment('Square customer ID for this member');

            // Square subscription ID for recurring billing
            $table->string('square_subscription_id')->nullable()->after('square_customer_id')->comment('Square subscription ID for recurring payments');

            // Track when subscription was created
            $table->timestamp('square_subscription_created_at')->nullable()->after('square_subscription_id')->comment('When Square subscription was created');

            // Indexes for fast lookups
            $table->index('square_customer_id');
            $table->index('square_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_members', function (Blueprint $table) {
            $table->dropIndex(['square_customer_id']);
            $table->dropIndex(['square_subscription_id']);
            $table->dropColumn([
                'square_customer_id',
                'square_subscription_id',
                'square_subscription_created_at',
            ]);
        });
    }
};
