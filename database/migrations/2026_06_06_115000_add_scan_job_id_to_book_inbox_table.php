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
        Schema::table('book_inbox', function (Blueprint $table) {
            $table->foreignId('scan_job_id')
                ->nullable()
                ->after('scan_session_id')
                ->constrained('scan_jobs')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('book_inbox', function (Blueprint $table) {
            $table->dropForeign(['scan_job_id']);
            $table->dropColumn('scan_job_id');
        });
    }
};
