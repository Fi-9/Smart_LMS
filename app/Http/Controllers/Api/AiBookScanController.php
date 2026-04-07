<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScanBookImagesRequest;
use App\Services\AiScanObservabilityService;
use App\Services\AiBookScanPipelineService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class AiBookScanController extends Controller
{
    public function __construct(
        private readonly AiBookScanPipelineService $pipelineService,
        private readonly AiScanObservabilityService $observabilityService
    ) {
    }

    public function __invoke(ScanBookImagesRequest $request): JsonResponse
    {
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');
        $start = microtime(true);
        $mode = (string) $request->validated('mode', 'full');
        $imageCount = count($request->file('images', []));

        try {
            $result = $this->pipelineService->scan(
                $request->file('images', []),
                $mode
            );

            $durationMs = (int) round((microtime(true) - $start) * 1000);
            $this->observabilityService->recordSuccess(
                channel: 'api',
                mode: $mode,
                source: (string) ($result['source'] ?? 'ai'),
                durationMs: $durationMs,
                imageCount: $imageCount
            );
        } catch (RuntimeException $e) {
            $durationMs = (int) round((microtime(true) - $start) * 1000);
            $this->observabilityService->recordFailure(
                channel: 'api',
                mode: $mode,
                durationMs: $durationMs,
                imageCount: $imageCount,
                error: $e->getMessage()
            );

            return response()->json([
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_GATEWAY);
        }

        return response()->json($result);
    }
}
