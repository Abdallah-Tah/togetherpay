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
        Schema::table('group_invitations', function (Blueprint $table) {
            $table->unsignedInteger('resend_count')->default(0)->after('status');
            $table->timestamp('last_resent_at')->nullable()->after('accepted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_invitations', function (Blueprint $table) {
            $table->dropColumn(['resend_count', 'last_resent_at']);
        });
    }
};
