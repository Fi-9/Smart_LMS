<?php

namespace App\Repositories\Eloquent;

use App\Models\Book;
use App\Repositories\Contracts\BookRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BookRepository implements BookRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Book::query()
            ->with(['category', 'rack'])
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%");
                });
            })
            ->when($filters['category_id'] ?? null, fn ($query, int $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['rack_id'] ?? null, fn ($query, int $rackId) => $query->where('rack_id', $rackId))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $attributes): Book
    {
        return Book::query()->create($attributes);
    }

    public function findOrFail(int $bookId): Book
    {
        return Book::query()->with(['category', 'rack'])->findOrFail($bookId);
    }

    public function update(Book $book, array $attributes): Book
    {
        $book->update($attributes);

        return $book->refresh();
    }

    public function delete(Book $book): void
    {
        $book->delete();
    }
}
