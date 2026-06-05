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
        Schema::create('ai_scan_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->json('ocr_result');
            $table->decimal('confidence', 5, 2)->nullable();
            $table->string('model_name', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('book_id', 'idx_ai_scan_book_id');
            $table->index('created_at', 'idx_ai_scan_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_scan_results');
    }
};
