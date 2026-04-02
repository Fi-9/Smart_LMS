<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use App\Models\Rack;
use App\Services\BookService;
use App\Services\RackService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BookPageController extends Controller
{
    public function __construct(
        private readonly BookService $bookService,
        private readonly RackService $rackService
    ) {
    }

    public function index(Request $request): View
    {
        return $this->indexView($request);
    }

    public function indexView(Request $request): View
    {
        $selectedBookId = $request->integer('selected_book_id') ?: null;
        $filters = [
            'search' => $request->string('search')->toString(),
            'category_id' => $request->integer('category_id') ?: null,
            'rack_id' => $request->integer('rack_id') ?: null,
            'status' => $request->string('status')->toString() ?: null,
        ];

        $books = $this->bookService->paginate($filters, (int) $request->integer('per_page', 15));
        $selectedBook = $books->getCollection()->firstWhere('id', $selectedBookId)
            ?? $books->getCollection()->first();

        return view('books.index', [
            'books' => $books,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'racks' => Rack::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'selected_book' => $selectedBook,
            'selected_book_rack_mini_map' => $this->buildRackMiniMap($selectedBook),
        ]);
    }

    public function show(Book $book): View
    {
        $book = $this->bookService->findOrFail($book->id);

        return view('books.show', [
            'book' => $book,
            'rack_mini_map' => $this->buildRackMiniMap($book),
        ]);
    }

    public function panel(Book $book): View
    {
        $book = $this->bookService->findOrFail($book->id);

        return view('books.partials.detail_panel', [
            'book' => $book,
            'rack_mini_map' => $this->buildRackMiniMap($book),
        ]);
    }

    private function buildRackMiniMap(?Book $book): ?array
    {
        if (! $book || ! $book->rack_id) {
            return null;
        }

        $rack = Rack::query()
            ->with(['books:id,title,rack_id,position_code'])
            ->find($book->rack_id);

        if (! $rack) {
            return null;
        }

        return $this->rackService->buildMiniMap($rack, $book->id);
    }
}
