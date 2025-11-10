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
            $table->string('square_merchant_id')->nullable()->after('remember_token');
            $table->text('square_access_token')->nullable()->after('square_merchant_id');
            $table->string('square_location_id')->nullable()->after('square_access_token');
            $table->timestamp('square_connected_at')->nullable()->after('square_location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['square_merchant_id', 'square_access_token', 'square_location_id', 'square_connected_at']);
        });
    }
};
