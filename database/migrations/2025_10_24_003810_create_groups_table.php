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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('timezone')->default('America/New_York');
            $table->string('currency', 3)->default('USD');
            $table->string('cadence')->default('MONTHLY'); // MONTHLY, EVERY_4_WEEKS, CUSTOM
            $table->integer('due_day')->default(1); // Day of month (1-28) for MONTHLY
            $table->integer('grace_days')->default(7);
            $table->integer('total_amount_cents')->nullable(); // Total bill amount in cents
            $table->string('status')->default('active'); // active, paused, archived
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
