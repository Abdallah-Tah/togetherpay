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
            $table->foreignId('payment_request_id')->constrained('payment_requests')->onDelete('cascade');
            $table->string('type'); // initial, reminder, escalation, final
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->text('message')->nullable();
            $table->timestamp('created_at')->nullable();

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
