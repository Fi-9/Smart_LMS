<?php

namespace App\Jobs;

use App\Models\BookInbox;
use App\Models\BookLookupCache;
use App\Models\ScanJob;
use App\Models\ScanPipelineLog;
use App\Models\ScanSession;
use App\Services\CoverImageService;
use App\Services\GeminiService;
use App\Services\IsbnLookupService;
use App\Services\BookIdentificationService;
use App\Services\CatalogLookupService;
use App\Services\MetadataEnrichmentService;
use App\Services\FallbackEngineService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessBookScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $attempts = 0;
    public int $tries = 3;
    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $scanJobId
    ) {
        $this->onQueue('ai-scan');
    }

    /**
     * Execute the job.
     */
    public function handle(
        GeminiService $geminiService,
        IsbnLookupService $isbnLookupService,
        CoverImageService $coverImageService,
        BookIdentificationService $identificationService,
        CatalogLookupService $catalogLookupService,
        MetadataEnrichmentService $enrichmentService,
        FallbackEngineService $fallbackService
    ): void {
        $scanJob = ScanJob::find($this->scanJobId);
        if (!$scanJob || in_array($scanJob->status, ['completed', 'failed'])) {
            return;
        }

        // Detect ISBN‑only flow based on the scan_source
        $isIsbnFlow = $scanJob->scan_source !== 'camera';
        if ($isIsbnFlow) {
            // Jump straight to lookup stage
            $scanJob->update([
                'current_stage' => 'lookup',
                'stage_status' => 'processing',
                'stage_message' => 'Lookup based on ISBN only',
            ]);
        }

        // Initialize metrics array or load existing metrics
        $metrics = is_array($scanJob->pipeline_metrics) ? $scanJob->pipeline_metrics : [];

        // Update ScanJob to processing
        $scanJob->update([
            'status' => 'processing',
            'started_at' => now(),
            'attempts' => $scanJob->attempts + 1,
        ]);

        // Update Session Counters
        $this->updateSessionStats($scanJob->scan_session_id, 'processing');

        $images = [];
        $startTotal = microtime(true);

        try {
            // If this is an ISBN‑only flow, skip image hydration and identification
            if ($isIsbnFlow) {
                $identificationResult = ['isbn' => $scanJob->isbn];
            } else {
                $images = $this->hydrateImages($scanJob);
                if (empty($images) && empty($scanJob->identification_result)) {
                    throw new \RuntimeException('Cover images not found or deleted on the server.');
                }

                // ==========================================
                // STAGE 1: Book Identification
                // ==========================================
                $identificationResult = $scanJob->identification_result;
                if (empty($identificationResult)) {
                    $stage1Start = microtime(true);
                    $scanJob->update([
                        'current_stage' => 'identification',
                        'stage_status' => 'processing',
                        'stage_message' => 'Identifying book details from covers...',
                    ]);

                    $identificationResult = $this->retryWithBackoff(3, [5000, 15000, 30000], function () use ($identificationService, $scanJob, $images) {
                        return $identificationService->identify($scanJob, $images);
                    });
                    
                    $stage1Duration = (int) round((microtime(true) - $stage1Start) * 1000);
                    $metrics['identification'] = $stage1Duration;
                    $this->logProvider('Gemini', $stage1Duration, 'success', $scanJob->id);

                } else {
                    Log::channel('ai_scan')->info("Skipping Stage 1 (Resume): Identification result already exists");
                }

                $scanJob->update([
                    'stage_status' => 'completed',
                    'pipeline_metrics' => $metrics,
                ]);
            }

            // Extract basic fields for logging and fallback
            $title = $this->cleanString($identificationResult['title'] ?? null);
            $author = $this->cleanString($identificationResult['author'] ?? null);
            $isbn = $this->cleanIsbn($identificationResult['isbn'] ?? null);

            // ==========================================
            // STAGE 2: Catalog Lookup
            // ==========================================
            $stage2Start = microtime(true);
            $scanJob->update([
                'current_stage' => 'lookup',
                'stage_status' => 'processing',
                'stage_message' => 'Searching book catalogs...',
            ]);

            // Check Cache first
            $cachedMetadata = $this->checkCache($isbn, $title, $author);
            $googleData = null;
            $olData = null;
            $finalSource = null;
            $merged = null;

            if ($cachedMetadata) {
                Log::channel('ai_scan')->info('Cache hit in ProcessBookScanJob', ['isbn' => $isbn, 'title' => $title]);
                $this->logProvider('Cache', 0, 'success', $scanJob->id);
                $merged = $cachedMetadata;
                $finalSource = 'cache';
            } else {
                // Perform parallel catalog lookup
                $lookupResult = $catalogLookupService->lookup($isbn, $title, $author);
                $googleData = $lookupResult['google'] ?? null;
                $olData = $lookupResult['openlibrary'] ?? null;
                
                $lookupDuration = (int) round((microtime(true) - $stage2Start) * 1000);
                if ($googleData) {
                    $this->logProvider('GoogleBooks', $lookupDuration, 'success', $scanJob->id);
                } else {
                    $this->logProvider('GoogleBooks', $lookupDuration, 'failed', $scanJob->id, 'No catalog match or lookup failed');
                }
                if ($olData) {
                    $this->logProvider('OpenLibrary', $lookupDuration, 'success', $scanJob->id);
                } else {
                    $this->logProvider('OpenLibrary', $lookupDuration, 'failed', $scanJob->id, 'No catalog match or lookup failed');
                }
            }

            $stage2Duration = (int) round((microtime(true) - $stage2Start) * 1000);
            $metrics['lookup'] = $stage2Duration;

            $scanJob->update([
                'stage_status' => 'completed',
                'pipeline_metrics' => $metrics,
            ]);

            // ==========================================
            // STAGE 3: Enrichment & Confidence
            // ==========================================
            $stage3Start = microtime(true);
            $scanJob->update([
                'current_stage' => 'enrichment',
                'stage_status' => 'processing',
                'stage_message' => 'Enriching book metadata and calculating confidence...',
            ]);

            if ($finalSource !== 'cache') {
                // Enrich metadata (e.g., translation, confidence calculations)
                $merged = $enrichmentService->enrich($identificationResult, $googleData, $olData);
                // Apply source priority as defined by product owner
                $merged = $this->applySourcePriority($identificationResult, $googleData, $olData, $merged);
                $finalSource = $this->determineFinalSource($googleData, $olData);
            }

            // Always prioritize Indonesian description
            if (!empty($merged['description']) && !$this->isIndonesian($merged['description'])) {
                try {
                    $merged['description'] = $geminiService->translateToIndonesian($merged['description']);
                } catch (Throwable $e) {
                    // Keep original if translation fails
                }
            }

            // Calculate confidence score (weighted similarity)
            $confidenceScore = $enrichmentService->calculateConfidence($identificationResult, $merged, $finalSource);

            $stage3Duration = (int) round((microtime(true) - $stage3Start) * 1000);
            $metrics['enrichment'] = $stage3Duration;

            $scanJob->update([
                'stage_status' => 'completed',
                'pipeline_metrics' => $metrics,
            ]);

            // ==========================================
            // STAGE 4: Fallback Engine
            // ==========================================
            $stage4Start = microtime(true);
            $scanJob->update([
                'current_stage' => 'fallback',
                'stage_status' => 'processing',
                'stage_message' => 'Applying smart fallbacks for missing fields...',
            ]);

            $merged = $fallbackService->fallback($merged, $identificationResult, $scanJob);

            $stage4Duration = (int) round((microtime(true) - $stage4Start) * 1000);
            $metrics['fallback'] = $stage4Duration;

            $scanJob->update([
                'stage_status' => 'completed',
                'pipeline_metrics' => $metrics,
            ]);

            // Save to cache (if it wasn't a cache hit)
            if ($finalSource !== 'cache') {
                $this->saveToCache($merged, $isbn, $title, $author);
            }


            // ==========================================
            // STAGE 5: Admin Inbox
            // ==========================================
            $stage5Start = microtime(true);
            $scanJob->update([
                'current_stage' => 'inbox',
                'stage_status' => 'processing',
                'stage_message' => 'Saving processed book to review inbox...',
            ]);

            // Normalize covers using the correct web path format
            $frontCoverNormalized = null;
            if ($scanJob->front_cover_path) {
                $frontWebPath = '/storage/' . $scanJob->front_cover_path;
                $frontCoverNormalized = $coverImageService->normalizeCoverFromUpload($frontWebPath);
            }
            $backCoverNormalized = null;
            if ($scanJob->back_cover_path) {
                $backWebPath = '/storage/' . $scanJob->back_cover_path;
                $backCoverNormalized = $coverImageService->normalizeCoverFromUpload($backWebPath);
            }

            // Determine status based on confidence score (>= 95 auto-approved, else pending)
            $status = $confidenceScore >= 95 ? 'approved' : 'pending';

            $cleanIsbn = $this->cleanIsbn($merged['isbn'] ?? null) ?: $isbn;
            $hasExistingBook = false;
            $existingBookTitle = null;
            if ($cleanIsbn) {
                $existingBook = \App\Models\Book::where('isbn', $cleanIsbn)->first();
                if ($existingBook) {
                    $hasExistingBook = true;
                    $existingBookTitle = $existingBook->title;
                }
            }

            // Determine description source
            $descriptionSource = null;
            if (!empty($merged['description'])) {
                if (($googleData['description_source'] ?? null) === 'tavily' || ($olData['description_source'] ?? null) === 'tavily') {
                    $descriptionSource = 'tavily';
                } elseif (!empty($googleData['description']) && $merged['description'] === $googleData['description']) {
                    $descriptionSource = 'google_books';
                } elseif (!empty($olData['description']) && $merged['description'] === $olData['description']) {
                    $descriptionSource = 'openlibrary';
                } elseif (!empty($identificationResult['description']) && $merged['description'] === $identificationResult['description']) {
                    $descriptionSource = 'gemini_vision';
                } else {
                    if (!empty($googleData['description'])) {
                        $descriptionSource = 'google_books';
                    } elseif (!empty($olData['description'])) {
                        $descriptionSource = 'openlibrary';
                    } else {
                        $descriptionSource = 'gemini_vision';
                    }
                }
            }

            // Create source chain info
            $sourceChain = [
                'identification' => $identificationResult,
                'google' => $googleData,
                'openlibrary' => $olData,
                'cache_hit' => ($finalSource === 'cache'),
                'final_source' => $finalSource,
                'has_existing_book' => $hasExistingBook,
                'existing_book_title' => $existingBookTitle,
                'description' => $descriptionSource,
            ];

            // Build processing notes
            $processingNotes = "Pipeline v5.0 processing completed. Final source: {$finalSource}. Confidence score: {$confidenceScore}%.";

            // Calculate metadata completeness
            $completenessData = $this->calculateCompleteness($merged, $frontCoverNormalized);
            $metadataCompleteness = $completenessData['score'];
            $metadataMissing = [
                'present' => $completenessData['present'],
                'missing' => $completenessData['missing']
            ];

            // Insert into book_inbox staging
            $inbox = BookInbox::query()->create([
                'scan_session_id' => $scanJob->scan_session_id,
                'scanned_by' => $scanJob->scanSession->user_id,
                'scan_job_id' => $scanJob->id,
                'title' => $this->cleanString($merged['title'] ?? null) ?: $title,
                'author' => $this->cleanString($merged['author'] ?? null) ?: $author,
                'isbn' => $this->cleanIsbn($merged['isbn'] ?? null) ?: $isbn,
                'publisher' => $this->cleanString($merged['publisher'] ?? null) ?: ($identificationResult['publisher_hint'] ?? $identificationResult['publisher'] ?? null),
                'published_year' => $merged['published_year'] ?: null,
                'description' => $merged['description'] ?: null,
                'category' => $merged['category'] ?: ($identificationResult['category'] ?? null),
                'language' => $merged['language'] ?: 'id',
                'cover_front_path' => $frontCoverNormalized ?: ($scanJob->front_cover_path ?: ($merged['cover_url'] ?? null)),
                'cover_back_path' => $backCoverNormalized ?: $scanJob->back_cover_path,
                'source' => $finalSource,
                'source_url' => $merged['source_url'] ?? null,
                'confidence' => $confidenceScore / 100, // keep existing 0.0-1.0 float in sync
                'confidence_score' => $confidenceScore,
                'metadata_completeness' => $metadataCompleteness,
                'metadata_missing' => $metadataMissing,
                'status' => $status,
                'scan_data' => [
                    'vision_raw' => ['best' => $identificationResult],
                    'google' => $googleData ?? null,
                    'openlibrary' => $olData ?? null
                ],
                'processing_notes' => $processingNotes,
                'source_chain' => $sourceChain,
                'stage_completed_at' => now(),
            ]);

            $stage5Duration = (int) round((microtime(true) - $stage5Start) * 1000);
            $metrics['inbox'] = $stage5Duration;

            // Dispatch asynchronous Tavily job post-completion
            EnrichBookWithTavilyJob::dispatch($inbox->id);

            // Complete ScanJob
            $scanJob->update([
                'status' => 'completed',
                'current_stage' => 'completed',
                'stage_status' => 'completed',
                'stage_message' => 'Process completed successfully.',
                'finished_at' => now(),
                'confidence_score' => $confidenceScore,
                'pipeline_metrics' => $metrics,
            ]);

            $this->updateSessionStats($scanJob->scan_session_id, 'completed');

        } catch (Throwable $e) {
            Log::channel('ai_scan')->error('ProcessBookScanJob failed', [
                'scan_job_id' => $this->scanJobId,
                'current_stage' => $scanJob->current_stage,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $msg = $e->getMessage();
            $isRateLimit = str_contains($msg, '429') || str_contains($msg, 'quota') || str_contains($msg, 'Rate Limit');
            $isUnavailable = str_contains($msg, '503') || str_contains($msg, 'experiencing high demand');

            if (($isRateLimit || $isUnavailable) && $scanJob->attempts < $this->tries) {
                // Rate limit / 503: kembalikan ke queue dengan delay 60s (bukan langsung failed)
                $cleanError = $isRateLimit
                    ? 'Gemini API Rate Limit — akan dicoba ulang dalam 60 detik'
                    : 'Gemini API Sementara Tidak Tersedia — akan dicoba ulang dalam 60 detik';

                $scanJob->update([
                    'status' => 'waiting',
                    'stage_status' => 'waiting',
                    'stage_message' => $cleanError,
                    'started_at' => null,
                    'pipeline_metrics' => $metrics,
                ]);

                $this->updateSessionStats($scanJob->scan_session_id, 'waiting');
                $this->release(60); // re-queue dengan delay 60 detik
                return;
            }

            if ($isRateLimit) {
                $cleanError = 'Gemini API Rate Limit / Quota Exceeded';
            } elseif ($isUnavailable) {
                $cleanError = 'Gemini API Temporary Unavailable (503)';
            } elseif (str_contains($msg, 'Connection timed out') || str_contains($msg, 'timeout')) {
                $cleanError = 'Connection Timeout';
            } else {
                $cleanError = $msg;
            }

            $scanJob->update([
                'status' => 'failed',
                'stage_status' => 'failed',
                'stage_message' => $cleanError,
                'finished_at' => now(),
                'error_message' => $cleanError,
                'pipeline_metrics' => $metrics,
            ]);

            $this->updateSessionStats($scanJob->scan_session_id, 'failed');

            throw $e;
        }
    }


    /**
     * Retry helper with progressive exponential backoff.
     */
    private function retryWithBackoff(int $times, array $backoffMs, callable $callback): mixed
    {
        $attempts = 0;
        while (true) {
            try {
                return $callback();
            } catch (Throwable $e) {
                $attempts++;
                if ($attempts >= $times) {
                    throw $e;
                }
                $sleepMs = $backoffMs[$attempts - 1] ?? end($backoffMs);
                usleep($sleepMs * 1000);
            }
        }
    }

    /**
     * Logs performace per provider in relational format.
     */
    private function logProvider(string $provider, int $durationMs, string $status, int $scanJobId, ?string $error = null): void
    {
        try {
            ScanPipelineLog::query()->create([
                'scan_id' => (string) $scanJobId,
                'provider' => $provider,
                'duration_ms' => $durationMs,
                'status' => $status,
                'error' => $error,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to log scan pipeline stats', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Check if similar item exists in lookup cache.
     */
    private function checkCache(?string $isbn, ?string $title, ?string $author): ?array
    {
        if ($isbn) {
            $cached = BookLookupCache::where('isbn', $isbn)->first();
            if ($cached) {
                return $cached->toArray();
            }
        }

        if ($title && $author) {
            $hash = sha1(strtolower(trim($title)) . '|' . strtolower(trim($author)));
            $cached = BookLookupCache::where('title_author_hash', $hash)->first();
            if ($cached) {
                return $cached->toArray();
            }
        }

        return null;
    }

    /**
     * Store item in cache.
     */
    private function saveToCache(array $data, ?string $isbn, ?string $title, ?string $author): void
    {
        $resolvedTitle = $data['title'] ?: $title;
        if (empty($resolvedTitle) || $resolvedTitle === 'Unknown') {
            return;
        }

        // Cache healing: calculate completeness and set TTL based on quality gate policy
        $completeness = $this->calculateCompleteness($data);
        $completenessScore = $completeness['score'];

        try {
            $hash = ($title && $author) ? sha1(strtolower(trim($title)) . '|' . strtolower(trim($author))) : null;

            $searchAttrs = [];
            if (!empty($isbn)) {
                $searchAttrs = ['isbn' => $isbn];
            } elseif (!empty($hash)) {
                $searchAttrs = ['title_author_hash' => $hash];
            } else {
                return;
            }

            // Cache healing: set expiry based on completeness
            // Completeness < 50: TTL 15 minutes.
            // Completeness 50-70: TTL 1 hour.
            // Completeness 70-90: TTL 6 hours.
            // Completeness > 90: TTL 24 hours.
            $expiresAt = match (true) {
                $completenessScore > 90 => now()->addHours(24),
                $completenessScore >= 70 => now()->addHours(6),
                $completenessScore >= 50 => now()->addHours(1),
                default => now()->addMinutes(15),
            };

            BookLookupCache::query()->updateOrCreate(
                $searchAttrs,
                [
                    'isbn' => !empty($isbn) ? $isbn : null,
                    'title_author_hash' => $hash,
                    'title' => $data['title'] ?: $title ?: 'Unknown',
                    'author' => $data['author'] ?: $author,
                    'publisher' => $data['publisher'] ?? null,
                    'published_year' => $data['published_year'] ?? null,
                    'description' => $data['description'] ?? null,
                    'category' => $data['category'] ?? null,
                    'cover_url' => $data['cover_url'] ?? null,
                    'language' => $data['language'] ?? 'id',
                    'metadata_json' => $data,
                    'expires_at' => $expiresAt,
                ]
            );
        } catch (Throwable $e) {
            Log::warning('Failed to save to BookLookupCache: ' . $e->getMessage());
        }
    }

    /**
     * Merge metadata using preference layers.
     */
    private function mergeMetadata(array $vision, ?array $google, ?array $ol): array
    {
        $merged = [
            'title' => $google['title'] ?? $ol['title'] ?? $vision['title'] ?? null,
            'author' => $google['author'] ?? $ol['author'] ?? $vision['author'] ?? null,
            'isbn' => $google['isbn'] ?? $ol['isbn'] ?? $vision['isbn'] ?? null,
            'publisher' => $google['publisher'] ?? $ol['publisher'] ?? $vision['publisher'] ?? null,
            'published_year' => $google['published_year'] ?? $ol['published_year'] ?? $vision['published_year'] ?? null,
            'description' => $vision['description'] ?? $google['description'] ?? $ol['description'] ?? null,
            'category' => $google['category'] ?? $ol['category'] ?? $vision['category'] ?? null,
            'cover_url' => $google['cover_url'] ?? $ol['cover_url'] ?? null,
            'language' => $google['language'] ?? $ol['language'] ?? 'id',
            'source_url' => $google['source_url'] ?? $ol['source_url'] ?? null,
        ];

        // Prefer longer descriptions
        $descGoogle = $google['description'] ?? '';
        $descOL = $ol['description'] ?? '';
        if (strlen((string) $descOL) > strlen((string) $descGoogle) && strlen((string) $descOL) > 100) {
            $merged['description'] = $descOL;
        }

        return $merged;
    }

    /**
     * Calculate dynamic weighted confidence score.
     */
    private function calculateConfidenceScore(array $vision, ?array $google, ?array $ol, array $merged): int
    {
        // If no external sources were returned, base confidence is 60%
        if (!$google && !$ol) {
            return 60;
        }

        $vTitle = $vision['title'] ?? '';
        $vAuthor = $vision['author'] ?? '';
        $vPublisher = $vision['publisher'] ?? '';
        $vIsbn = $vision['isbn'] ?? '';

        $titleSim = $this->computeSimilarity($vTitle, $merged['title'] ?? '');
        $authorSim = $this->computeSimilarity($vAuthor, $merged['author'] ?? '');
        
        $publisherSim = 0.0;
        $usePublisher = false;
        if (!empty($vPublisher) && !empty($merged['publisher'])) {
            $publisherSim = $this->computeSimilarity($vPublisher, $merged['publisher']);
            $usePublisher = true;
        }

        $isbnMatch = 0.0;
        $useIsbn = false;
        if (!empty($vIsbn) && !empty($merged['isbn'])) {
            $cleanV = preg_replace('/[^0-9Xx]/', '', $vIsbn);
            $cleanM = preg_replace('/[^0-9Xx]/', '', $merged['isbn']);
            if ($cleanV !== '' && $cleanM !== '') {
                $useIsbn = true;
                if ($cleanV === $cleanM) {
                    $isbnMatch = 1.0;
                }
            }
        }

        if ($useIsbn && $isbnMatch === 1.0) {
            $score = 0.95 + ($titleSim * 0.03) + ($authorSim * 0.02);
        } else {
            $weights = [
                'title' => 0.5,
                'author' => 0.4,
            ];
            $sum = ($titleSim * $weights['title']) + ($authorSim * $weights['author']);
            $totalWeight = $weights['title'] + $weights['author'];

            if ($usePublisher) {
                $sum += $publisherSim * 0.1;
                $totalWeight += 0.1;
            }

            $score = ($sum / $totalWeight) * 0.90;
        }

        $finalScore = (int) round($score * 100);

        // Cap at 89 if no real/exact ISBN match is present to prevent auto-approve
        $hasRealIsbnMatch = $useIsbn && $isbnMatch === 1.0;
        if (!$hasRealIsbnMatch && $finalScore > 89) {
            $finalScore = 89;
        }

        return max(50, min(100, $finalScore));
    }

    /**
     * Calculate dynamic confidence score on cache hit.
     */
    private function calculateCacheConfidenceScore(array $vision, array $cached): int
    {
        $vTitle = $vision['title'] ?? '';
        $vAuthor = $vision['author'] ?? '';
        $vPublisher = $vision['publisher'] ?? '';
        $vIsbn = $vision['isbn'] ?? '';

        $cachedIsbn = $cached['isbn'] ?? '';
        $hasIsbn = !empty($cachedIsbn) && !str_starts_with($cachedIsbn, 'cache_');

        $titleSim = $this->computeSimilarity($vTitle, $cached['title'] ?? '');
        $authorSim = $this->computeSimilarity($vAuthor, $cached['author'] ?? '');
        
        $publisherSim = 0.0;
        $usePublisher = false;
        if (!empty($vPublisher) && !empty($cached['publisher'])) {
            $publisherSim = $this->computeSimilarity($vPublisher, $cached['publisher']);
            $usePublisher = true;
        }

        $isbnMatch = 0.0;
        $useIsbn = false;
        if (!empty($vIsbn) && $hasIsbn) {
            $cleanV = preg_replace('/[^0-9Xx]/', '', $vIsbn);
            $cleanC = preg_replace('/[^0-9Xx]/', '', $cachedIsbn);
            if ($cleanV !== '' && $cleanC !== '') {
                $useIsbn = true;
                if ($cleanV === $cleanC) {
                    $isbnMatch = 1.0;
                }
            }
        }

        if ($useIsbn && $isbnMatch === 1.0) {
            $score = 0.95 + ($titleSim * 0.03) + ($authorSim * 0.02);
        } else {
            $weights = [
                'title' => 0.5,
                'author' => 0.4,
            ];
            $sum = ($titleSim * $weights['title']) + ($authorSim * $weights['author']);
            $totalWeight = $weights['title'] + $weights['author'];

            if ($usePublisher) {
                $sum += $publisherSim * 0.1;
                $totalWeight += 0.1;
            }

            $score = ($sum / $totalWeight) * 0.90;
        }

        $finalScore = (int) round($score * 100);

        // Cap at 89 if no real ISBN is present to prevent auto-approve
        if (!$hasIsbn && $finalScore > 89) {
            $finalScore = 89;
        }

        return max(50, min(100, $finalScore));
    }

    private function computeSimilarity(string $a, string $b): float
    {
        $a = strtolower(trim($a));
        $b = strtolower(trim($b));
        if ($a === '' || $b === '') return 0.0;
        if ($a === $b) return 1.0;

        $maxLen = max(strlen($a), strlen($b));
        if ($maxLen === 0) return 0.0;

        return 1.0 - (levenshtein($a, $b) / $maxLen);
    }

    private function determineFinalSource(?array $google, ?array $ol): string
    {
        if ($google && $ol) return 'google_books+openlibrary';
        if ($google) return 'google_books';
        if ($ol) return 'openlibrary';
        return 'gemini_vision';
    }
    /**
     * Apply source priority to merged metadata according to product owner rules.
     *
     * Priority (high to low):
     *   - Title, Author, ISBN, Publisher, Published Year, Category: Google Books > OpenLibrary > Vision
     *   - Description: prefer longer description (>100 chars) from OpenLibrary, else Google, else Vision
     *   - Cover URL: OpenLibrary > Google Books > Uploaded Cover (if any)
     *   - Language: Google Books > OpenLibrary > 'id'
     *   - Source URL: Google Books > OpenLibrary > Vision
     */
    private function applySourcePriority(array $vision, ?array $google, ?array $ol, array $merged): array
    {
        // Helper to pick first non-null, non-empty value
        $pick = function (...$values) {
            foreach ($values as $v) {
                if ($v !== null && $v !== '' && $v !== []) {
                    return $v;
                }
            }
            return null;
        };

        // Fields with Google > OpenLibrary > Vision priority
        $fields = ['title', 'author', 'isbn', 'publisher', 'published_year', 'category'];
        foreach ($fields as $field) {
            $merged[$field] = $pick($google[$field] ?? null, $ol[$field] ?? null, $vision[$field] ?? null);
        }

        // Description priority: longer description from OL (>100 chars) else GB else Vision
        $descOl = $ol['description'] ?? '';
        $descGb = $google['description'] ?? '';
        $descVision = $vision['description'] ?? '';
        if (strlen((string) $descOl) > 100) {
            $merged['description'] = $descOl;
        } elseif (strlen((string) $descGb) > 0) {
            $merged['description'] = $descGb;
        } else {
            $merged['description'] = $descVision;
        }

        // Cover URL priority: OL > GB > existing merged (uploaded)
        $merged['cover_url'] = $pick($ol['cover_url'] ?? null, $google['cover_url'] ?? null, $merged['cover_url'] ?? null);

        // Language priority: GB > OL > default 'id'
        $merged['language'] = $pick($google['language'] ?? null, $ol['language'] ?? null, 'id');

        // Source URL priority: GB > OL > Vision
        $merged['source_url'] = $pick($google['source_url'] ?? null, $ol['source_url'] ?? null, $vision['source_url'] ?? null);

        return $merged;
    }

    /**
     * Check if description is in Indonesian using stop-words.
     */
    private function isIndonesian(string $text): bool
    {
        $indonesianWords = ['dan', 'yang', 'dengan', 'untuk', 'pada', 'adalah', 'ini', 'itu', 'dari', 'akan', 'juga', 'sudah', 'bisa', 'ada', 'tidak', 'oleh', 'setelah', 'sangat', 'karena', 'buku'];
        $lower = strtolower($text);
        $count = 0;
        foreach ($indonesianWords as $word) {
            if (str_contains($lower, ' ' . $word . ' ') || str_starts_with($lower, $word . ' ') || str_ends_with($lower, ' ' . $word)) {
                $count++;
            }
        }
        return $count >= 2;
    }

    private function cleanString(?string $str): ?string
    {
        if ($str === null) return null;
        $t = trim($str);
        return $t !== '' ? $t : null;
    }

    private function cleanIsbn(?string $isbn): ?string
    {
        if ($isbn === null) return null;
        $clean = preg_replace('/[^0-9Xx]/', '', $isbn);
        return $clean !== '' ? $clean : null;
    }

    /**
     * Hydrate UploadedFile instances from local storage.
     */
    private function hydrateImages(ScanJob $scanJob): array
    {
        $images = [];

        if ($scanJob->front_cover_path && Storage::disk('public')->exists($scanJob->front_cover_path)) {
            $path = Storage::disk('public')->path($scanJob->front_cover_path);
            $images[] = new UploadedFile(
                $path,
                basename($path),
                'image/jpeg',
                null,
                true
            );
        }

        if ($scanJob->back_cover_path && Storage::disk('public')->exists($scanJob->back_cover_path)) {
            $path = Storage::disk('public')->path($scanJob->back_cover_path);
            $images[] = new UploadedFile(
                $path,
                basename($path),
                'image/jpeg',
                null,
                true
            );
        }

        return $images;
    }

    /**
     * Safely updates summary statistics for the active scan session.
     */
    private function updateSessionStats(int $sessionId, string $targetStatus): void
    {
        try {
            DB::transaction(function () use ($sessionId, $targetStatus) {
                $session = ScanSession::find($sessionId);
                if (!$session) return;

                // Sync all counts from the scan_jobs table directly to avoid race conditions (Derived Stats source of truth)
                $counts = DB::table('scan_jobs')
                    ->where('scan_session_id', $sessionId)
                    ->select(
                        DB::raw('count(*) as total'),
                        DB::raw("sum(case when status = 'waiting' then 1 else 0 end) as waiting"),
                        DB::raw("sum(case when status = 'processing' then 1 else 0 end) as processing"),
                        DB::raw("sum(case when status = 'completed' then 1 else 0 end) as completed"),
                        DB::raw("sum(case when status = 'failed' then 1 else 0 end) as failed")
                    )->first();

                if ($counts) {
                    $session->update([
                        'total_books' => (int) $counts->total,
                        'waiting_count' => (int) $counts->waiting,
                        'processing_count' => (int) $counts->processing,
                        'completed_count' => (int) $counts->completed,
                        'failed_count' => (int) $counts->failed,
                        'book_count' => (int) $counts->completed, // Keep book_count in sync with completed for backwards compatibility
                    ]);
                }
            });
        } catch (Throwable $e) {
            Log::error('Failed to update ScanSession stats: ' . $e->getMessage());
        }
    }

    /**
     * Calculate metadata completeness score and list missing fields.
     *
     * @param array $merged The merged metadata
     * @param string|null $coverPath Optional local cover path override
     * @return array{score: int, present: array<string>, missing: array<string>}
     */
    private function calculateCompleteness(array $merged, ?string $coverPath = null): array
    {
        $fields = [
            'title' => $merged['title'] ?? null,
            'author' => $merged['author'] ?? null,
            'isbn' => $merged['isbn'] ?? null,
            'cover' => $coverPath ?: ($merged['cover_url'] ?? null),
            'description' => $merged['description'] ?? null,
            'category' => $merged['category'] ?? null,
            'publisher' => $merged['publisher'] ?? null,
            'published_year' => $merged['published_year'] ?? null,
        ];

        $missing = [];
        $present = [];
        $filled = 0;

        foreach ($fields as $name => $value) {
            if (!empty($value) && $value !== 'Unknown') {
                $filled++;
                $present[] = $name;
            } else {
                $missing[] = $name;
            }
        }

        $score = (int) round(($filled / count($fields)) * 100);

        return [
            'score' => $score,
            'present' => $present,
            'missing' => $missing,
        ];
    }
}
