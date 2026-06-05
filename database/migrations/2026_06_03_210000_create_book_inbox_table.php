<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_inbox', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_session_id')->nullable()->constrained('scan_sessions')->nullOnDelete();
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rack_id')->nullable()->constrained('racks')->nullOnDelete();

            // Book metadata
            $table->string('title', 500)->nullable();
            $table->string('author', 255)->nullable();
            $table->string('isbn', 20)->nullable();
            $table->string('publisher', 255)->nullable();
            $table->integer('published_year')->nullable();
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('language', 50)->nullable();

            // Image paths
            $table->string('cover_front_path', 1000)->nullable();
            $table->string('cover_back_path', 1000)->nullable();

            // Source tracking
            $table->string('source', 50)->nullable()->comment('google_books, openlibrary, gemini, manual');
            $table->string('source_url', 1000)->nullable();
            $table->float('confidence')->default(0)->comment('0.0 - 1.0');

            // Processing status
            $table->enum('status', ['pending', 'approved', 'rejected', 'routed'])
                ->default('pending');
            $table->text('rejection_reason')->nullable();

            // Raw scan data for debugging/reprocessing
            $table->jsonb('scan_data')->nullable();

            // Routing info
            $table->string('position_code', 50)->nullable();
            $table->foreignId('routed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('routed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('isbn', 'book_inbox_isbn_idx');
            $table->index('status', 'book_inbox_status_idx');
            $table->index(['scanned_by', 'status'], 'book_inbox_scanner_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_inbox');
    }
};
