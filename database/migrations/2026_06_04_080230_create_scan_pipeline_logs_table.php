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
        Schema::create('scan_pipeline_logs', function (Blueprint $table) {
            $table->id();
            $table->string('scan_id')->nullable()->index();
            $table->string('provider'); // Gemini, GoogleBooks, OpenLibrary, Tavily
            $table->integer('duration_ms');
            $table->string('status'); // success, failed
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_pipeline_logs');
    }
};
