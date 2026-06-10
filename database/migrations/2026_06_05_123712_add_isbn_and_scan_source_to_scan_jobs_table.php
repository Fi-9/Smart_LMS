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
        Schema::table('scan_jobs', function (Blueprint $table) {
            $table->string('isbn')->nullable()->after('id');
            $table->string('scan_source', 20)->default('camera')->after('isbn');
            
            $table->index('isbn');
            $table->index('scan_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scan_jobs', function (Blueprint $table) {
            $table->dropIndex(['isbn']);
            $table->dropIndex(['scan_source']);
            $table->dropColumn(['isbn', 'scan_source']);
        });
    }
};
