<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignRackPositionRequest;
use App\Services\BookService;
use App\Services\RackPlacementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookAssignmentController extends Controller
{
    public function __construct(
        private readonly RackPlacementService $rackPlacementService,
        private readonly BookService $bookService
    ) {
    }

    public function store(AssignRackPositionRequest $request): JsonResponse
    {
        try {
            $book = $this->rackPlacementService->assignBookToPosition(
                (int) $request->validated('book_id'),
                (int) $request->validated('rack_id'),
                $request->validated('position_code')
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'success' => true,
            'book' => [
                'id' => $book->id,
                'title' => $book->title,
                'rack_id' => $book->rack_id,
                'position_code' => $book->position_code,
            ],
        ]);
    }

    public function autoAssign(Request $request): JsonResponse
    {
        $assigned = $this->bookService->autoAssignUnassignedBooks(
            (int) $request->integer('limit', 50)
        );

        return response()->json([
            'success' => true,
            'assigned_count' => $assigned,
        ]);
    }
}
