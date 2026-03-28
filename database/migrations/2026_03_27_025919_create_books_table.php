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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author');
            $table->string('isbn')->nullable()->unique();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rack_id')->nullable()->constrained()->nullOnDelete();
            $table->string('position_code')->nullable();
            $table->string('cover_url')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->enum('status', ['available', 'borrowed'])->default('available');
            $table->timestamps();

            $table->unique(['rack_id', 'position_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
