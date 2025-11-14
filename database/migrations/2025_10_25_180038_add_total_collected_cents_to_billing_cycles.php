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
        Schema::table('billing_cycles', function (Blueprint $table) {
            // Add total_collected_cents if it doesn't exist
            // Note: The original table already has 'collected_amount_cents', so we skip if that exists
            if (! Schema::hasColumn('billing_cycles', 'total_collected_cents') && ! Schema::hasColumn('billing_cycles', 'collected_amount_cents')) {
                $table->integer('total_collected_cents')->default(0)->after('total_amount_cents');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_cycles', function (Blueprint $table) {
            $table->dropColumn('total_collected_cents');
        });
    }
};
