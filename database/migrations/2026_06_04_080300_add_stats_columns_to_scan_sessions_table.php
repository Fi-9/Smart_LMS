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
        Schema::table('scan_sessions', function (Blueprint $table) {
            $table->integer('total_books')->default(0);
            $table->integer('waiting_count')->default(0);
            $table->integer('processing_count')->default(0);
            $table->integer('completed_count')->default(0);
            $table->integer('failed_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scan_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'total_books',
                'waiting_count',
                'processing_count',
                'completed_count',
                'failed_count',
            ]);
        });
    }
};
