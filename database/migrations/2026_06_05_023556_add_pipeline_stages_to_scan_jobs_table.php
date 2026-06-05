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
            $table->string('current_stage')->default('identification');
            $table->string('stage_status')->default('waiting');
            $table->text('stage_message')->nullable();
            $table->json('pipeline_metrics')->nullable();
            $table->json('identification_result')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scan_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'current_stage',
                'stage_status',
                'stage_message',
                'pipeline_metrics',
                'identification_result',
            ]);
        });
    }
};
