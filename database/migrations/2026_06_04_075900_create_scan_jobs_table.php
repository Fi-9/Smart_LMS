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
        Schema::create('scan_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_session_id')->constrained('scan_sessions')->cascadeOnDelete();
            $table->string('front_cover_path', 1000);
            $table->string('back_cover_path', 1000)->nullable();
            $table->string('front_cover_hash', 40)->nullable()->index();
            $table->string('back_cover_hash', 40)->nullable()->index();
            $table->enum('priority', ['normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['waiting', 'processing', 'completed', 'failed'])->default('waiting');
            $table->integer('attempts')->default(0);
            $table->integer('confidence_score')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_jobs');
    }
};
