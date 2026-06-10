<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_inbox', function (Blueprint $table) {
            $table->unsignedTinyInteger('metadata_completeness')->default(0)->after('confidence_score');
            $table->json('metadata_missing')->nullable()->after('metadata_completeness');
        });
    }

    public function down(): void
    {
        Schema::table('book_inbox', function (Blueprint $table) {
            $table->dropColumn(['metadata_completeness', 'metadata_missing']);
        });
    }
};
