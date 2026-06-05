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
        Schema::create('book_lookup_cache', function (Blueprint $table) {
            $table->id();
            $table->string('isbn', 20)->nullable()->index();
            $table->string('title_author_hash', 40)->nullable()->index();
            $table->string('title', 500);
            $table->string('author', 255)->nullable();
            $table->string('publisher', 255)->nullable();
            $table->integer('published_year')->nullable();
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('cover_url', 1000)->nullable();
            $table->string('language', 50)->nullable();
            $table->jsonb('metadata_json')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_lookup_cache');
    }
};
