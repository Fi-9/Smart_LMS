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
            $table->text('processing_notes')->nullable();
            $table->jsonb('source_chain')->nullable();
            $table->timestamp('stage_completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('book_inbox', function (Blueprint $table) {
            $table->dropColumn([
                'processing_notes',
                'source_chain',
                'stage_completed_at',
            ]);
        });
    }
};
