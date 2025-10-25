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
        Schema::table('dunning_events', function (Blueprint $table) {
            // Rename 'type' to 'kind' for consistency
            $table->renameColumn('type', 'kind');

            // Add channel field (push, sms, email)
            $table->string('channel', 20)->default('push')->after('kind');

            // Add sent_at (rename created_at to sent_at for clarity)
            $table->timestamp('sent_at')->nullable()->after('channel');

            // Add delivery tracking
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('opened_at')->nullable()->after('delivered_at');

            // Add updated_at for standard timestamps
            $table->timestamp('updated_at')->nullable();

            // Update indexes
            $table->dropIndex(['payment_request_id', 'created_at']);
            $table->index(['payment_request_id', 'kind']);
            $table->index(['sent_at', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dunning_events', function (Blueprint $table) {
            $table->renameColumn('kind', 'type');
            $table->dropColumn([
                'channel',
                'sent_at',
                'delivered_at',
                'opened_at',
                'updated_at',
            ]);

            $table->dropIndex(['payment_request_id', 'kind']);
            $table->dropIndex(['sent_at', 'channel']);
            $table->index(['payment_request_id', 'created_at']);
        });
    }
};
