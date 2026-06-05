<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign(['rack_id']);
            $table->dropUnique(['rack_id', 'position_code']);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->unsignedBigInteger('rack_id')->nullable()->change();
            $table->string('position_code')->nullable()->change();
            
            $table->unique(['rack_id', 'position_code']);
            $table->foreign('rack_id')->references('id')->on('racks')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::table('books')
            ->whereNull('rack_id')
            ->orWhereNull('position_code')
            ->delete();

        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign(['rack_id']);
            $table->dropUnique(['rack_id', 'position_code']);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->unsignedBigInteger('rack_id')->nullable(false)->change();
            $table->string('position_code')->nullable(false)->change();

            $table->unique(['rack_id', 'position_code']);
            $table->foreign('rack_id')->references('id')->on('racks')->cascadeOnDelete();
        });
    }
};
