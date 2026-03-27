<?php

namespace Database\Seeders;

use App\Enums\BookStatus;
use App\Models\Book;
use App\Models\Category;
use App\Models\Rack;
use Illuminate\Database\Seeder;

class LibraryDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programming = Category::query()->firstOrCreate(['name' => 'Programming']);
        $networking = Category::query()->firstOrCreate(['name' => 'Networking']);
        $database = Category::query()->firstOrCreate(['name' => 'Database']);

        $rackA = Rack::query()->firstOrCreate(
            ['name' => 'Rack A'],
            ['rows' => 3, 'columns' => 3]
        );

        $rackB = Rack::query()->firstOrCreate(
            ['name' => 'Rack B'],
            ['rows' => 2, 'columns' => 4]
        );

        $books = [
            [
                'title' => 'Laravel Basics',
                'author' => 'R. Santoso',
                'isbn' => '9781000000001',
                'category_id' => $programming->id,
                'rack_id' => $rackA->id,
                'position_code' => 'A1',
            ],
            [
                'title' => 'PHP Advanced',
                'author' => 'N. Prabowo',
                'isbn' => '9781000000002',
                'category_id' => $programming->id,
                'rack_id' => null,
                'position_code' => null,
            ],
            [
                'title' => 'Mikrotik Guide',
                'author' => 'D. Kurniawan',
                'isbn' => '9781000000003',
                'category_id' => $networking->id,
                'rack_id' => $rackA->id,
                'position_code' => 'B2',
            ],
            [
                'title' => 'Cisco Fundamentals',
                'author' => 'A. Wirawan',
                'isbn' => '9781000000004',
                'category_id' => $networking->id,
                'rack_id' => null,
                'position_code' => null,
            ],
            [
                'title' => 'MySQL Beginner',
                'author' => 'M. Nugraha',
                'isbn' => '9781000000005',
                'category_id' => $database->id,
                'rack_id' => $rackB->id,
                'position_code' => 'A2',
            ],
            [
                'title' => 'PostgreSQL Deep Dive',
                'author' => 'I. Mahendra',
                'isbn' => '9781000000006',
                'category_id' => $database->id,
                'rack_id' => null,
                'position_code' => null,
            ],
        ];

        foreach ($books as $book) {
            Book::query()->updateOrCreate(
                ['isbn' => $book['isbn']],
                array_merge($book, [
                    'status' => BookStatus::AVAILABLE->value,
                    'cover_url' => null,
                    'qr_code_path' => null,
                ])
            );
        }
    }
}
