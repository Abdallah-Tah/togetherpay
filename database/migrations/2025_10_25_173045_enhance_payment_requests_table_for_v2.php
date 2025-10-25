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
            // Add group_id for direct group access
            $table->foreignId('group_id')->nullable()->after('id')->constrained('groups')->onDelete('cascade');

            // Add PR Code for deterministic reconciliation
            $table->string('pr_code', 64)->nullable()->unique()->after('member_id');

            // Add note field (max 160 chars for wallet compatibility)
            $table->string('note', 160)->nullable()->after('pr_code');

            // Rename channel to method_hint
            $table->renameColumn('channel', 'method_hint');

            // Add new status values to existing enum
            // Note: For PostgreSQL, we need to handle this differently
            // This will work for MySQL/SQLite

            // Add evidence tracking
            $table->text('evidence_url')->nullable()->after('paid_at');
            $table->string('evidence_type', 20)->nullable()->after('evidence_url');

            // Add state transition timestamps
            $table->timestamp('initiated_at')->nullable()->after('sent_at');
            $table->timestamp('marked_at')->nullable()->after('sent_at');
            $table->timestamp('confirmed_at')->nullable()->after('paid_at');

            // Rename link to deep_link and add web_fallback_link
            $table->renameColumn('link', 'deep_link');
            $table->text('web_fallback_link')->nullable()->after('deep_link');

            // Add indexes for new columns
            $table->index('pr_code');
            $table->index(['group_id', 'cycle_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn([
                'group_id',
                'pr_code',
                'note',
                'evidence_url',
                'evidence_type',
                'initiated_at',
                'marked_at',
                'confirmed_at',
                'web_fallback_link',
            ]);

            $table->renameColumn('method_hint', 'channel');
            $table->renameColumn('deep_link', 'link');

            $table->dropIndex(['pr_code']);
            $table->dropIndex(['group_id', 'cycle_id', 'status']);
        });
    }
};
