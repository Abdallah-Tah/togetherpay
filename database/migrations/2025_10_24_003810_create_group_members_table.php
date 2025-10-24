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
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('preferred_channel')->default('VENMO'); // VENMO, CASH_APP, CARD, UNKNOWN
            $table->string('venmo_username')->nullable();
            $table->string('cash_tag')->nullable();
            $table->string('cash_phone')->nullable();
            $table->string('cash_email')->nullable();
            $table->string('share_type')->default('AMOUNT'); // AMOUNT, PERCENT
            $table->decimal('share_value', 10, 2)->default(0); // Amount in dollars or percentage
            $table->string('status')->default('active'); // active, paused, left
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['group_id', 'user_id']);
            $table->index(['group_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_members');
    }
};
