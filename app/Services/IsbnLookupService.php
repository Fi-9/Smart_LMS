<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class IsbnLookupService
{
    public function lookup(string $isbn): ?array
    {
        return $this->lookupGoogleBooks($isbn) ?? $this->lookupOpenLibrary($isbn);
    }

    private function lookupGoogleBooks(string $isbn): ?array
    {
        try {
            $response = Http::timeout(10)
                ->get('https://www.googleapis.com/books/v1/volumes', ['q' => "isbn:{$isbn}"]);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $payload = $response->json();
        $first = $payload['items'][0]['volumeInfo'] ?? null;

        if (! $first) {
            return null;
        }

        return [
            'title' => $first['title'] ?? null,
            'author' => $first['authors'][0] ?? null,
            'cover_url' => $first['imageLinks']['thumbnail'] ?? null,
            'source' => 'google_books',
        ];
    }

    private function lookupOpenLibrary(string $isbn): ?array
    {
        try {
            $response = Http::timeout(10)->get(
                'https://openlibrary.org/api/books',
                [
                    'bibkeys' => "ISBN:{$isbn}",
                    'format' => 'json',
                    'jscmd' => 'data',
                ]
            );
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $book = $response->json("ISBN:{$isbn}");

        if (! $book) {
            return null;
        }

        return [
            'title' => $book['title'] ?? null,
            'author' => $book['authors'][0]['name'] ?? null,
            'cover_url' => $book['cover']['medium'] ?? null,
            'source' => 'open_library',
        ];
    }
}

