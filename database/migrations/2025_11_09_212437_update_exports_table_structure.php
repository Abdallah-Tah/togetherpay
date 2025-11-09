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
        Schema::table('exports', function (Blueprint $table) {
            // Rename columns to match ExportService expectations
            $table->renameColumn('created_by', 'user_id');
            $table->renameColumn('type', 'export_type');
            $table->renameColumn('path', 'file_path');

            // Add new columns
            $table->string('format')->after('export_type'); // csv, pdf
            $table->json('params')->nullable()->after('file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exports', function (Blueprint $table) {
            $table->dropColumn(['format', 'params']);
            $table->renameColumn('user_id', 'created_by');
            $table->renameColumn('export_type', 'type');
            $table->renameColumn('file_path', 'path');
        });
    }
};
