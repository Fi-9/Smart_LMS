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
        $robotics = Category::query()->firstOrCreate(['name' => 'Robotics']);

        $rackA = Rack::query()->firstOrCreate(
            ['name' => 'Rack A'],
            ['rows' => 3, 'columns' => 3]
        );

        $rackB = Rack::query()->firstOrCreate(
            ['name' => 'Rack B'],
            ['rows' => 2, 'columns' => 4]
        );

        $rackC = Rack::query()->firstOrCreate(
            ['name' => 'Rack C'],
            ['rows' => 3, 'columns' => 4]
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
                'rack_id' => $rackB->id,
                'position_code' => 'B1',
            ],
            [
                'title' => 'Python for Automation',
                'author' => 'S. Utami',
                'isbn' => '9781000000007',
                'category_id' => $programming->id,
                'rack_id' => $rackA->id,
                'position_code' => 'C1',
            ],
            [
                'title' => 'Java OOP Patterns',
                'author' => 'B. Hidayat',
                'isbn' => '9781000000008',
                'category_id' => $programming->id,
                'rack_id' => null,
                'position_code' => null,
            ],
            [
                'title' => 'CCNA Lab Workbook',
                'author' => 'Y. Rahma',
                'isbn' => '9781000000009',
                'category_id' => $networking->id,
                'rack_id' => $rackB->id,
                'position_code' => 'A3',
            ],
            [
                'title' => 'Network Security Essentials',
                'author' => 'T. Firmansyah',
                'isbn' => '9781000000010',
                'category_id' => $networking->id,
                'rack_id' => null,
                'position_code' => null,
            ],
            [
                'title' => 'SQL Query Mastery',
                'author' => 'L. Anindita',
                'isbn' => '9781000000011',
                'category_id' => $database->id,
                'rack_id' => $rackC->id,
                'position_code' => 'A1',
            ],
            [
                'title' => 'NoSQL Concepts',
                'author' => 'F. Prasetyo',
                'isbn' => '9781000000012',
                'category_id' => $database->id,
                'rack_id' => null,
                'position_code' => null,
            ],
            [
                'title' => 'Arduino Starter Kit',
                'author' => 'N. Sari',
                'isbn' => '9781000000013',
                'category_id' => $robotics->id,
                'rack_id' => $rackC->id,
                'position_code' => 'A2',
            ],
            [
                'title' => 'IoT with ESP32',
                'author' => 'H. Pradana',
                'isbn' => '9781000000014',
                'category_id' => $robotics->id,
                'rack_id' => null,
                'position_code' => null,
            ],
        ];

        $borrowedIsbns = [
            '9781000000004',
            '9781000000009',
            '9781000000013',
        ];

        foreach ($books as $book) {
            Book::query()->updateOrCreate(
                ['isbn' => $book['isbn']],
                array_merge($book, [
                    'status' => in_array($book['isbn'], $borrowedIsbns, true)
                        ? BookStatus::BORROWED->value
                        : BookStatus::AVAILABLE->value,
                    'cover_url' => null,
                    'qr_code_path' => null,
                ])
            );
        }
    }
}
