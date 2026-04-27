<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\BorrowBookRequest;
use App\Models\Borrowing;
use App\Services\BorrowingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BorrowingController extends Controller
{
    public function __construct(
        private readonly BorrowingService $borrowingService
    ) {
    }

    public function index(Request $request): View
    {
        $filters = [
            'status' => $request->input('status', ''),
            'search' => $request->input('search', ''),
        ];

        return view('borrowings.index', [
            'borrowings' => $this->borrowingService->paginate($filters),
            'filters' => $filters,
        ]);
    }

    public function store(BorrowBookRequest $request): JsonResponse
    {
        try {
            $borrowing = $this->borrowingService->borrowBook(
                bookId: $request->integer('book_id'),
                borrowerName: $request->string('borrower_name')->toString() ?: null,
                dueDate: $request->string('due_date')->toString(),
                memberId: $request->filled('member_id') ? $request->integer('member_id') : null,
            );

            return response()->json([
                'success' => true,
                'message' => "Buku '{$borrowing->book->title}' berhasil dipinjamkan.",
                'borrowing' => $borrowing,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function returnBook(Borrowing $borrowing): JsonResponse
    {
        try {
            $returned = $this->borrowingService->returnBook($borrowing->id);

            return response()->json([
                'success' => true,
                'message' => "Buku '{$returned->book->title}' berhasil dikembalikan.",
                'borrowing' => $returned,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
