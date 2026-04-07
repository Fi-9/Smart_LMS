<?php

namespace App\Jobs;

use App\Services\AiBatchScanDraftService;
use App\Services\AiBookScanPipelineService;
use App\Services\AiScanObservabilityService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessAiBatchScanBook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    /**
     * @param array<int, string> $imagePaths
     */
    public function __construct(
        private readonly string $draftToken,
        private readonly string $scanId,
        private readonly array $imagePaths,
        private readonly string $mode = 'full',
    ) {
        $this->onQueue('ai-scan');
    }

    public function handle(
        AiBatchScanDraftService $draftService,
        AiBookScanPipelineService $pipelineService,
        AiScanObservabilityService $observabilityService
    ): void {
        $draft = $draftService->get($this->draftToken);
        if (! is_array($draft) || ($draft['status'] ?? null) === 'cancelled') {
            $this->cleanupFiles();
            return;
        }

        $draftService->markProcessing($this->draftToken, $this->scanId);

        $start = microtime(true);
        $imageCount = count($this->imagePaths);

        try {
            $images = $this->hydrateImages();
            $result = $pipelineService->scan($images, $this->mode);

            $draftService->markSuccess($this->draftToken, $this->scanId, $result);

            $observabilityService->recordSuccess(
                channel: 'queue',
                mode: $this->mode,
                source: (string) ($result['source'] ?? 'ai'),
                durationMs: (int) round((microtime(true) - $start) * 1000),
                imageCount: $imageCount
            );
        } catch (Throwable $e) {
            $draftService->markFailed($this->draftToken, $this->scanId, $e->getMessage());

            $observabilityService->recordFailure(
                channel: 'queue',
                mode: $this->mode,
                durationMs: (int) round((microtime(true) - $start) * 1000),
                imageCount: $imageCount,
                error: $e->getMessage()
            );

            throw $e;
        } finally {
            $this->cleanupFiles();
        }
    }

    public function failed(Throwable $exception): void
    {
        app(AiBatchScanDraftService::class)->markFailed(
            $this->draftToken,
            $this->scanId,
            $exception->getMessage()
        );

        $this->cleanupFiles();
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function hydrateImages(): array
    {
        $images = [];

        foreach ($this->imagePaths as $path) {
            if (! Storage::disk('local')->exists($path)) {
                throw new RuntimeException("File batch scan tidak ditemukan: {$path}");
            }

            $absolutePath = Storage::disk('local')->path($path);
            $images[] = new UploadedFile(
                $absolutePath,
                basename($absolutePath),
                null,
                null,
                true
            );
        }

        return $images;
    }

    private function cleanupFiles(): void
    {
        foreach ($this->imagePaths as $path) {
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        }
    }
}
