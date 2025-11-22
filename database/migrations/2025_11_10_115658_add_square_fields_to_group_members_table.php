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
            if (! Schema::hasColumn('group_members', 'square_customer_id')) {
                $table->string('square_customer_id')->nullable()->after('cash_email')->comment('Square customer ID for this member');
                $table->index('square_customer_id');
            }

            // Square subscription ID for recurring billing
            if (! Schema::hasColumn('group_members', 'square_subscription_id')) {
                $table->string('square_subscription_id')->nullable()->after('square_customer_id')->comment('Square subscription ID for recurring payments');
                $table->index('square_subscription_id');
            }

            // Track when subscription was created
            if (! Schema::hasColumn('group_members', 'square_subscription_created_at')) {
                $table->timestamp('square_subscription_created_at')->nullable()->after('square_subscription_id')->comment('When Square subscription was created');
            }
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
