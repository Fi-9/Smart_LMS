<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Services\BookService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function __construct(
        private readonly BookService $bookService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $books = $this->bookService->paginate(
            filters: [
                'search' => $request->string('search')->toString(),
                'category_id' => $request->integer('category_id') ?: null,
                'rack_id' => $request->integer('rack_id') ?: null,
            ],
            perPage: (int) $request->integer('per_page', 15)
        );

        return response()->json($books);
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        try {
            $book = $this->bookService->create($request->validated());
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return response()->json([
                    'message' => 'Rack position is already occupied for this rack.',
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            throw $exception;
        }

        return response()->json($book->load(['category', 'rack']), JsonResponse::HTTP_CREATED);
    }

    public function show(Book $book): JsonResponse
    {
        return response()->json($book->load(['category', 'rack']));
    }

    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        try {
            $book = $this->bookService->update($book, $request->validated());
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return response()->json([
                    'message' => 'Rack position is already occupied for this rack.',
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            throw $exception;
        }

        return response()->json($book->load(['category', 'rack']));
    }

    public function destroy(Book $book): JsonResponse
    {
        $this->bookService->delete($book);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}

