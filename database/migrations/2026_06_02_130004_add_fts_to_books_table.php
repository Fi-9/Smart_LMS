<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE books ADD COLUMN search_vector tsvector');
            DB::statement('CREATE INDEX books_search_idx ON books USING GIN(search_vector)');

            // Create Trigger Function
            DB::statement("
                CREATE OR REPLACE FUNCTION books_search_vector_update() RETURNS trigger AS $$
                begin
                    new.search_vector :=
                        setweight(to_tsvector('simple', coalesce(new.title, '')), 'A') ||
                        setweight(to_tsvector('simple', coalesce(new.author, '')), 'B');
                    return new;
                end
                $$ LANGUAGE plpgsql;
            ");

            // Create Trigger
            DB::statement('
                CREATE TRIGGER tsvectorupdate BEFORE INSERT OR UPDATE
                ON books FOR EACH ROW EXECUTE FUNCTION books_search_vector_update();
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS tsvectorupdate ON books');
            DB::statement('DROP FUNCTION IF EXISTS books_search_vector_update()');
            DB::statement('DROP INDEX IF EXISTS books_search_idx');
            DB::statement('ALTER TABLE books DROP COLUMN IF EXISTS search_vector');
        }
    }
};
