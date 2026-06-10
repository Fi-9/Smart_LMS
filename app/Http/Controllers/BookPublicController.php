<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Contracts\View\View;

class BookPublicController extends Controller
{
    public function show(int $bookId): View
    {
        $book = Book::with(['rack', 'category'])->findOrFail($bookId);

        return view('books.public_show', compact('book'));
    }
}
