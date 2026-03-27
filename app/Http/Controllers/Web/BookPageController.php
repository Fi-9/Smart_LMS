<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Rack;
use App\Services\BookService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BookPageController extends Controller
{
    public function __construct(
        private readonly BookService $bookService
    ) {
    }

    public function index(Request $request): View
    {
        return $this->indexView($request);
    }

    public function indexView(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'category_id' => $request->integer('category_id') ?: null,
            'rack_id' => $request->integer('rack_id') ?: null,
            'status' => $request->string('status')->toString() ?: null,
        ];

        return view('books.index', [
            'books' => $this->bookService->paginate($filters, (int) $request->integer('per_page', 15)),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'racks' => Rack::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }
}
