<?php

namespace App\Services;

use App\Enums\BookStatus;
use App\Models\Book;
use App\Models\Category;
use App\Models\Rack;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function stats(): array
    {
        return [
            'total_books' => Book::query()->count(),
            'total_categories' => Category::query()->count(),
            'total_racks' => Rack::query()->count(),
            'available_books' => Book::query()->where('status', BookStatus::AVAILABLE->value)->count(),
            'borrowed_books' => Book::query()->where('status', BookStatus::BORROWED->value)->count(),
            'total_borrowed_active' => \App\Models\Borrowing::query()->active()->count(),
            'total_late' => \App\Models\Borrowing::query()->late()->count(),
            'books_per_category' => DB::table('books')
                ->join('categories', 'categories.id', '=', 'books.category_id')
                ->selectRaw('categories.name as category, count(*) as total')
                ->groupBy('categories.name')
                ->orderByDesc('total')
                ->get(),
            'books_per_rack' => DB::table('books')
                ->join('racks', 'racks.id', '=', 'books.rack_id')
                ->selectRaw('racks.name as rack, count(*) as total')
                ->groupBy('racks.name')
                ->orderByDesc('total')
                ->get(),
        ];
    }
}

