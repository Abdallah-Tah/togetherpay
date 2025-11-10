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
            // User's preferred default billing mode for new groups
            // Options: 'manual' (deep-link requests), 'auto_square' (Square recurring)
            $table->enum('preferred_billing_mode', ['manual', 'auto_square'])
                ->default('manual')
                ->after('square_connected_at')
                ->comment('Preferred payment collection mode for new groups');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferred_billing_mode');
        });
    }
};
