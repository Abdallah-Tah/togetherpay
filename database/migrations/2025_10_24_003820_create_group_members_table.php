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
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('preferred_channel')->default('CASH_APP'); // CASH_APP, VENMO
            $table->string('cash_tag')->nullable();
            $table->string('cash_phone')->nullable();
            $table->string('cash_email')->nullable();
            $table->string('venmo_username')->nullable();
            $table->enum('share_type', ['PERCENT', 'AMOUNT'])->default('PERCENT');
            $table->decimal('share_value', 10, 2)->default(0); // Percentage (0-100) or fixed amount
            $table->string('status')->default('active'); // active, inactive, removed
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'status']);
            $table->index('user_id');
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
