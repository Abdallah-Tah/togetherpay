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
            $table->string('channel')->default('CASH_APP'); // CASH_APP, VENMO
            $table->text('link')->nullable(); // Deep link or web link
            $table->string('external_id')->nullable(); // ID from payment provider
            $table->string('status')->default('pending'); // pending, sent, paid, failed, expired
            $table->string('fail_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['cycle_id', 'status']);
            $table->index('member_id');
            $table->index('external_id');
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
