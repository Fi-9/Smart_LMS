<?php

namespace App\Http\Controllers;

use App\Services\BookService;
use Illuminate\Contracts\View\View;

class BookPublicController extends Controller
{
    public function __construct(
        private readonly BookService $bookService
    ) {
    }

    public function show(int $bookId): View
    {
        $book = $this->bookService->findOrFail($bookId);

        return view('books.public_show', compact('book'));
    }
}
