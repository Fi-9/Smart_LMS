<?php

namespace App\Repositories\Contracts;

use App\Models\Book;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BookRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function findOrFail(int $bookId): Book;

    public function create(array $attributes): Book;

    public function update(Book $book, array $attributes): Book;

    public function delete(Book $book): void;
}
