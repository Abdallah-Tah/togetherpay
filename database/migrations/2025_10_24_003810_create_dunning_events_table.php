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
        Schema::create('dunning_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_request_id')->constrained()->onDelete('cascade');
            $table->integer('attempt_no')->default(1);
            $table->string('type'); // reminder, retry, escalation
            $table->json('meta')->nullable(); // Metadata about the dunning event
            $table->timestamp('created_at');

            $table->index(['payment_request_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dunning_events');
    }
};
