<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class BookPublicController extends Controller
{
    public function show(int $bookId): RedirectResponse
    {
        // Backward compatibility: old QR payloads still target /book/{id}.
        // Redirect them to the richer admin detail page.
        return redirect()->route('books.web.show', $bookId);
    }
}
