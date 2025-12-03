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
        Schema::table('payment_requests', function (Blueprint $table) {
            // Auto-confirmation grace period
            $table->timestamp('auto_confirm_at')->nullable()->after('confirmed_at')
                ->comment('Timestamp when payment will auto-confirm if no issues reported');

            // Dispute tracking
            $table->string('dispute_reason')->nullable()->after('fail_reason')
                ->comment('Reason if owner reported an issue with this payment');
            $table->timestamp('disputed_at')->nullable()->after('dispute_reason')
                ->comment('When owner reported issue with this payment');

            // Undo window tracking
            $table->timestamp('undo_available_until')->nullable()->after('confirmed_at')
                ->comment('Owner can undo confirmation until this time');

            // Add indexes for scheduled jobs
            $table->index(['status', 'auto_confirm_at']);
            $table->index('undo_available_until');
        });

        // Add trust score to group members for reputation tracking
        Schema::table('group_members', function (Blueprint $table) {
            if (! Schema::hasColumn('group_members', 'trust_score')) {
                $table->decimal('trust_score', 5, 2)->default(100.00)->after('share_cents')
                    ->comment('Hidden reputation score (0-100) based on payment history');
                $table->integer('on_time_payments')->default(0)->after('trust_score')
                    ->comment('Count of on-time payments');
                $table->integer('late_payments')->default(0)->after('on_time_payments')
                    ->comment('Count of late payments');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropIndex(['status', 'auto_confirm_at']);
            $table->dropIndex(['undo_available_until']);
            $table->dropColumn([
                'auto_confirm_at',
                'dispute_reason',
                'disputed_at',
                'undo_available_until',
            ]);
        });

        Schema::table('group_members', function (Blueprint $table) {
            $table->dropColumn([
                'trust_score',
                'on_time_payments',
                'late_payments',
            ]);
        });
    }
};
