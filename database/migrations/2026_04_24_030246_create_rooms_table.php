<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique()->comment('e.g. RM-01, RM-02');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'preview', 'inactive'])->default('active');
            $table->string('accent', 20)->default('emerald')->comment('Color accent: emerald, sky, amber, rose, violet');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('sort_order');
        });

        // Add room_id FK to racks table
        Schema::table('racks', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->after('id')->constrained('rooms')->nullOnDelete();
            $table->index('room_id');
        });
    }

    public function down(): void
    {
        Schema::table('racks', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropIndex(['room_id']);
            $table->dropColumn('room_id');
        });

        Schema::dropIfExists('rooms');
    }
};
