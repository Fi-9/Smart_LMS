<?php

namespace App\Services;

use App\Models\ScanJob;
use Illuminate\Support\Facades\Log;
use Throwable;

class BookIdentificationService
{
    public function __construct(
        private readonly GeminiService $geminiService,
        private readonly OcrService $ocrService
    ) {}

    /**
     * Identify book signals from cover images using Gemini Vision + OCR.
     *
     * @param ScanJob $scanJob
     * @param array $images
     * @return array
     */
    public function identify(ScanJob $scanJob, array $images): array
    {
        // 1. Check if we already have identification result stored (Resume cache)
        if (!empty($scanJob->identification_result)) {
            Log::channel('ai_scan')->info("Skipping Stage 1: Identification result retrieved from ScanJob database", ['scan_job_id' => $scanJob->id]);
            return $scanJob->identification_result;
        }

        Log::channel('ai_scan')->info("Running Stage 1: Book Identification (Gemini + OCR)", ['scan_job_id' => $scanJob->id]);

        $ocrResults = null;
        if ($this->ocrService->isAvailable()) {
            try {
                Log::channel('ai_scan')->info("OCR is available. Running Tesseract OCR on images...");
                $ocrRaw = $this->ocrService->extract($images);
                $ocrResults = $ocrRaw['best'] ?? null;
                Log::channel('ai_scan')->info("OCR completed successfully", ['ocr_results' => $ocrResults]);
            } catch (Throwable $e) {
                Log::channel('ai_scan')->warning("OCR execution failed: " . $e->getMessage() . ". Continuing with Gemini Vision only.");
            }
        } else {
            Log::channel('ai_scan')->info("OCR is not available on this system. Running Gemini Vision only.");
        }

        // Call Gemini Vision with OCR results (if available)
        $visionResult = $this->geminiService->extractBookSignals($images, $ocrResults);
        $best = is_array($visionResult['best'] ?? null) ? $visionResult['best'] : [];

        // Merge OCR signals to fill gaps in Gemini Vision output (e.g. ISBN, Publisher, Title)
        $merged = [
            'title' => $best['title'] ?? $ocrResults['title'] ?? null,
            'author' => $best['author'] ?? $ocrResults['author'] ?? null,
            'isbn' => $best['isbn'] ?? $ocrResults['isbn'] ?? null,
            'publisher_hint' => $best['publisher'] ?? $ocrResults['publisher'] ?? null,
            'category' => $best['category'] ?? $ocrResults['category'] ?? null,
            'description_back_cover' => $best['description'] ?? null,
        ];

        // Clean empty fields
        $merged = array_map(function ($val) {
            if (is_string($val)) {
                $t = trim($val);
                return $t !== '' ? $t : null;
            }
            return $val;
        }, $merged);

        // Save result to ScanJob table for caching/resume
        $scanJob->update([
            'identification_result' => $merged,
        ]);

        return $merged;
    }
}
