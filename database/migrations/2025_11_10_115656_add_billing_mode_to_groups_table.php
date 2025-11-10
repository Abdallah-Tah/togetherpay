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
        Schema::table('groups', function (Blueprint $table) {
            // Billing mode: 'manual' (default, existing deep-link flow) or 'auto_square' (Square recurring)
            $table->enum('billing_mode', ['manual', 'auto_square'])
                ->default('manual')
                ->after('currency')
                ->comment('Payment collection mode: manual (deep-link requests) or auto_square (Square + Cash App Pay)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('billing_mode');
        });
    }
};
