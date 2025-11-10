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
            // Square OAuth credentials for owners running auto_square groups
            $table->string('square_merchant_id')->nullable()->after('email')->comment('Square merchant ID from OAuth');
            $table->text('square_access_token')->nullable()->after('square_merchant_id')->comment('Encrypted Square access token');
            $table->string('square_location_id')->nullable()->after('square_access_token')->comment('Primary Square location ID');

            // Track Square OAuth connection timestamp
            $table->timestamp('square_connected_at')->nullable()->after('square_location_id')->comment('When Square account was connected');

            // Index for fast lookups
            $table->index('square_merchant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['square_merchant_id']);
            $table->dropColumn(['square_merchant_id', 'square_access_token', 'square_location_id', 'square_connected_at']);
        });
    }
};
