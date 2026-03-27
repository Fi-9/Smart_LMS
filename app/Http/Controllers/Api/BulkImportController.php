<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkImportCommitRequest;
use App\Http\Requests\BulkImportPreviewRequest;
use App\Services\BulkImportService;
use Illuminate\Http\JsonResponse;

class BulkImportController extends Controller
{
    public function __construct(
        private readonly BulkImportService $bulkImportService
    ) {
    }

    public function preview(BulkImportPreviewRequest $request): JsonResponse
    {
        $result = $this->bulkImportService->preview($request->file('file'));

        return response()->json($result);
    }

    public function commit(BulkImportCommitRequest $request): JsonResponse
    {
        $result = $this->bulkImportService->commit($request->validated('preview_token'));

        if ($result['message'] === 'Preview token is invalid or expired.') {
            return response()->json($result, JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json($result);
    }
}
