<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('racks', function (Blueprint $table) {
            $table->dropColumn('column_category');
        });

        Schema::table('racks', function (Blueprint $table) {
            $table->json('column_categories')->nullable()->after('capacity_per_slot');
        });
    }

    public function down(): void
    {
        Schema::table('racks', function (Blueprint $table) {
            $table->dropColumn('column_categories');
        });

        Schema::table('racks', function (Blueprint $table) {
            $table->string('column_category')->nullable()->after('capacity_per_slot');
        });
    }
};
