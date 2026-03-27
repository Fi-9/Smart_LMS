<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LookupIsbnRequest;
use App\Services\IsbnLookupService;
use Illuminate\Http\JsonResponse;

class IsbnLookupController extends Controller
{
    public function __construct(
        private readonly IsbnLookupService $isbnLookupService
    ) {
    }

    public function __invoke(LookupIsbnRequest $request): JsonResponse
    {
        $result = $this->isbnLookupService->lookup($request->validated('isbn'));

        if (! $result) {
            return response()->json([
                'message' => 'No book metadata found for the given ISBN.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json($result);
    }
}

