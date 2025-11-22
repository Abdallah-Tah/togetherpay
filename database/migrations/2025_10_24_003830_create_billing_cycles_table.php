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
        Schema::create('billing_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamp('due_at');
            $table->string('status')->default('draft'); // draft, collecting, closed, cancelled
            $table->integer('total_amount_cents')->default(0);
            $table->integer('collected_amount_cents')->default(0);
            $table->timestamps();

            $table->index(['group_id', 'status']);
            $table->index('due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_cycles');
    }
};
