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
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('billing_cycles')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('group_members')->onDelete('cascade');
            $table->integer('amount_cents');
            $table->string('channel'); // VENMO, CASH_APP, CARD
            $table->text('link')->nullable(); // Deep-link or payment URL
            $table->string('external_id')->nullable(); // External payment system ID
            $table->string('status')->default('pending'); // pending, sent, paid, failed, expired
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('fail_reason')->nullable();
            $table->json('metadata')->nullable(); // Store additional data (recipient handle, etc.)
            $table->timestamps();

            $table->index(['member_id', 'status']);
            $table->index(['cycle_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
