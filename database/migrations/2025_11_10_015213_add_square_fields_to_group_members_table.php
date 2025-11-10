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
            $table->string('square_customer_id')->nullable()->after('status');
            $table->string('square_subscription_id')->nullable()->after('square_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_members', function (Blueprint $table) {
            $table->dropColumn(['square_customer_id', 'square_subscription_id']);
        });
    }
};
