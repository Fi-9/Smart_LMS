<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE books DROP FOREIGN KEY books_rack_id_foreign');
        DB::statement('ALTER TABLE books DROP INDEX books_rack_id_position_code_unique');
        DB::statement('ALTER TABLE books MODIFY rack_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE books MODIFY position_code VARCHAR(255) NULL');
        DB::statement('ALTER TABLE books ADD UNIQUE books_rack_id_position_code_unique (rack_id, position_code)');
        DB::statement('ALTER TABLE books ADD CONSTRAINT books_rack_id_foreign FOREIGN KEY (rack_id) REFERENCES racks(id) ON DELETE SET NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('books')
            ->whereNull('rack_id')
            ->orWhereNull('position_code')
            ->delete();

        DB::statement('ALTER TABLE books DROP FOREIGN KEY books_rack_id_foreign');
        DB::statement('ALTER TABLE books DROP INDEX books_rack_id_position_code_unique');
        DB::statement('ALTER TABLE books MODIFY rack_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE books MODIFY position_code VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE books ADD UNIQUE books_rack_id_position_code_unique (rack_id, position_code)');
        DB::statement('ALTER TABLE books ADD CONSTRAINT books_rack_id_foreign FOREIGN KEY (rack_id) REFERENCES racks(id) ON DELETE CASCADE');
    }
};
