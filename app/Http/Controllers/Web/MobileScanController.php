<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BookInbox;
use App\Models\ScanSession;
use App\Models\ScanJob;
use App\Jobs\ProcessBookScanJob;
use App\Services\AiBookScanPipelineService;
use App\Services\GeminiService;
use App\Services\IsbnLookupService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MobileScanController extends Controller
{
    public function __construct(
        private readonly AiBookScanPipelineService $pipelineService,
        private readonly IsbnLookupService $isbnLookupService,
        private readonly GeminiService $geminiService,
    ) {
    }

    // ────────────────────────────────────────────
    //  VIEW
    // ────────────────────────────────────────────

    public function index(Request $request): View
    {
        $activeSession = null;
        $todayCount = 0;

        try {
            $activeSession = ScanSession::query()
                ->where('user_id', auth()->id())
                ->whereDate('started_at', today())
                ->whereNull('ended_at')
                ->latest()
                ->first();

            $todayCount = (int) ScanSession::query()
                ->whereDate('started_at', today())
                ->sum('book_count');
        } catch (\Throwable $e) {
            Log::error('[MobileScan] index query failed', ['error' => $e->getMessage()]);
        }

        return view('scanner.mobile-scan', [
            'activeSession' => $activeSession,
            'todayCount' => $todayCount,
        ]);
    }

    // ────────────────────────────────────────────
    //  SESSION
    // ────────────────────────────────────────────

    public function startSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'operator_name' => ['required', 'string', 'max:255'],
        ]);

        ScanSession::query()
            ->where('user_id', auth()->id())
            ->whereDate('started_at', today())
            ->whereNull('ended_at')
            ->update(['ended_at' => now()]);

        $session = ScanSession::query()->create([
            'user_id' => auth()->id(),
            'operator_name' => $validated['operator_name'],
            'book_count' => 0,
            'started_at' => now(),
        ]);

        return response()->json([
            'session_id' => $session->id,
            'operator_name' => $session->operator_name,
        ]);
    }

    public function endSession(Request $request): JsonResponse
    {
        $session = ScanSession::query()
            ->where('user_id', auth()->id())
            ->whereDate('started_at', today())
            ->whereNull('ended_at')
            ->latest()
            ->first();

        if ($session) {
            $session->update(['ended_at' => now()]);
        }

        return response()->json(['message' => 'Session ended']);
    }

    // ────────────────────────────────────────────
    //  ISBN LOOKUP (Google Books → OpenLibrary)
    // ────────────────────────────────────────────

    public function lookupIsbn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'isbn' => ['required', 'string', 'max:20'],
            'priority' => ['nullable', 'string', 'in:normal,high,urgent'],
            'force' => ['nullable', 'string'],
        ]);

        $sessionId = $this->currentSessionId();
        if (!$sessionId) {
            return response()->json([
                'success' => false,
                'error' => 'Belum ada sesi scan aktif hari ini.',
            ], 400);
        }

        $isbn = preg_replace('/[^0-9Xx]/', '', $validated['isbn']);

        // Check for duplicate scan jobs
        $force = filter_var($request->input('force', false), FILTER_VALIDATE_BOOLEAN);
        if (!$force) {
            $duplicate = ScanJob::query()
                ->where('scan_session_id', $sessionId)
                ->where('status', '!=', 'failed')
                ->where(function ($q) use ($isbn) {
                    $q->where('identification_result->isbn', $isbn);
                })
                ->first();

            if ($duplicate) {
                return response()->json([
                    'warning' => 'duplicate_detected',
                    'message' => 'Buku dengan ISBN ini kemungkinan sudah pernah dipindai.',
                    'duplicate_job_id' => $duplicate->id,
                ]);
            }
        }

        // Create ScanJob record for staged processing pipeline
        // Bypasses Stage 1 (Identification) because identification_result is pre-populated.
        $scanJob = ScanJob::query()->create([
            'scan_session_id' => $sessionId,
            'isbn' => $isbn,
            'scan_source' => 'isbn',
            'front_cover_path' => '',
            'back_cover_path' => null,
            'front_cover_hash' => null,
            'back_cover_hash' => null,
            'priority' => $request->input('priority', 'normal'),
            'status' => 'waiting',
            'attempts' => 0,
            'current_stage' => 'lookup',
            'stage_status' => 'waiting',
            'identification_result' => [
                'isbn' => $isbn,
                'title' => null,
                'author' => null,
                'publisher_hint' => null,
                'category' => null,
                'description_back_cover' => null,
            ]
        ]);

        // Calculate dynamic queue number
        $queueNumber = ScanJob::query()
            ->where('status', 'waiting')
            ->where('id', '<=', $scanJob->id)
            ->count();

        // Increment stats on the session table
        \Illuminate\Support\Facades\DB::transaction(function () use ($sessionId) {
            $session = ScanSession::find($sessionId);
            if ($session) {
                $session->increment('total_books');
                $session->increment('waiting_count');
            }
        });

        // Dispatch background worker ProcessBookScanJob
        ProcessBookScanJob::dispatch($scanJob->id);

        return response()->json([
            'found' => true,
            'queued' => true,
            'scan_job_id' => $scanJob->id,
            'queue_number' => $queueNumber,
            'message' => 'ISBN masuk antrean.',
        ], 202);
    }

    // ────────────────────────────────────────────
    //  TITLE LOOKUP (Google Books → OpenLibrary)
    // ────────────────────────────────────────────

    public function lookupTitle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'author' => ['nullable', 'string', 'max:255'],
        ]);

        $title = $validated['title'];
        $author = $validated['author'] ?? null;

        // Try Google Books by title
        $results = $this->lookupGoogleByTitle($title, $author);
        if (!empty($results)) {
            return response()->json([
                'found' => true,
                'source' => 'google',
                'book' => $results[0],
            ]);
        }

        // Try OpenLibrary by title
        $ol = $this->lookupOpenLibraryByTitle($title, $author);
        if ($ol) {
            return response()->json([
                'found' => true,
                'source' => 'openlibrary',
                'book' => $ol,
            ]);
        }

        return response()->json(['found' => false]);
    }

    // ────────────────────────────────────────────
    //  COVER SCAN → Gemini Vision
    // ────────────────────────────────────────────

    public function scanCover(Request $request): JsonResponse
    {
        $request->validate([
            'front' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'back' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $mode = 'gemini';
        $ocrService = null;

        try {
            $images = [$request->file('front')];
            if ($request->hasFile('back')) {
                $images[] = $request->file('back');
            }

            // Save images
            $frontPath = $this->saveScanImage($request->file('front'), 'front');
            $backPath = $request->hasFile('back')
                ? $this->saveScanImage($request->file('back'), 'back')
                : null;

            // Step 1: Extract text from covers
            $ocrResults = null;
            if ($mode === 'ocr' && $ocrService) {
                if (!$ocrService->isAvailable()) {
                    return response()->json([
                        'found' => false,
                        'error' => 'Tesseract OCR belum terinstall. Gunakan mode Gemini Vision.',
                    ]);
                }
                $ocrRaw = $ocrService->extract($images);
                $ocrResults = $ocrRaw['best'] ?? null;
            }

            // Always call extractBookSignals, which will pass OCR results to n8n if available
            $visionResult = $this->geminiService->extractBookSignals($images, $ocrResults);

            $best = is_array($visionResult['best'] ?? null) ? $visionResult['best'] : [];
            $title = $best['title'] ?? null;
            $author = $best['author'] ?? null;
            $isbn = $best['isbn'] ?? null;

            // Step 2: Smart enrichment — search by title+author, cross-reference, Tavily fallback
            // If n8n already returned fully enriched metadata, use it directly. Otherwise, do local fallback search.
            if (isset($visionResult['_n8n_enriched'])) {
                $enriched = $visionResult['_n8n_enriched'];
            } else {
                $enriched = $this->smartEnrich(
                    title: $title ?: '',
                    author: $author ?: '',
                    isbn: $isbn ?: '',
                    category: $best['category'] ?? '',
                );
            }

            // Step 3: Merge — prefer enriched data, fill gaps from vision
            $merged = $this->mergeMetadata($best, $enriched, $frontPath, $backPath);

            $cleanIsbn = preg_replace('/[^0-9Xx]/', '', $merged['isbn'] ?? '');
            $hasExistingBook = false;
            $existingBookTitle = null;
            if (!empty($cleanIsbn)) {
                $existingBook = \App\Models\Book::where('isbn', $cleanIsbn)->first();
                if ($existingBook) {
                    $hasExistingBook = true;
                    $existingBookTitle = $existingBook->title;
                }
            }

            $sourceChain = [
                'identification' => $best,
                'google' => $enriched['google'] ?? null,
                'openlibrary' => $enriched['openlibrary'] ?? null,
                'cache_hit' => false,
                'final_source' => $merged['source'] ?? 'gemini_vision',
                'has_existing_book' => $hasExistingBook,
                'existing_book_title' => $existingBookTitle,
            ];

            // Save to inbox
            $inbox = BookInbox::query()->create([
                'scan_session_id' => $this->currentSessionId(),
                'scanned_by' => auth()->id(),
                'title' => $merged['title'],
                'author' => $merged['author'],
                'isbn' => $merged['isbn'],
                'publisher' => $merged['publisher'],
                'published_year' => $merged['published_year'],
                'description' => $merged['description'],
                'category' => $merged['category'],
                'language' => $merged['language'],
                'cover_front_path' => $merged['cover_front_path'],
                'cover_back_path' => $merged['cover_back_path'],
                'source' => ($merged['source'] ?? 'unknown') . '_' . $mode,
                'source_url' => $merged['source_url'],
                'confidence' => $merged['confidence'],
                'status' => 'pending',
                'scan_data' => ['vision_raw' => $visionResult, 'enriched' => $enriched],
                'source_chain' => $sourceChain,
            ]);

            $this->incrementSessionCount();

            return response()->json([
                'found' => true,
                'inbox_id' => $inbox->id,
                'source' => $inbox->source,
                'book' => [
                    'title' => $inbox->title,
                    'author' => $inbox->author,
                    'isbn' => $inbox->isbn,
                    'publisher' => $inbox->publisher,
                    'published_year' => $inbox->published_year,
                    'description' => $inbox->description,
                    'category' => $inbox->category,
                    'cover_url' => $merged['source_url'] ?: ($merged['cover_front_path'] ? Storage::url($merged['cover_front_path']) : null),
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('[MobileScan] Cover scan failed', [
                'error' => $e->getMessage(),
                'mode' => $mode,
            ]);

            return response()->json([
                'found' => false,
                'error' => 'Scan gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ────────────────────────────────────────────
    //  ASYNC SCAN QUEUE (Sprint 1)
    // ────────────────────────────────────────────

    public function enqueueScan(Request $request): JsonResponse
    {
        $request->validate([
            'front' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'back' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'priority' => ['nullable', 'string', 'in:normal,high,urgent'],
            'force' => ['nullable', 'string'], // receive boolean-like parameter as string
        ]);

        $sessionId = $this->currentSessionId();
        if (!$sessionId) {
            return response()->json([
                'success' => false,
                'error' => 'Belum ada sesi scan aktif hari ini.',
            ], 400);
        }

        $frontFile = $request->file('front');
        $backFile = $request->file('back');

        // SHA1 Cover hashes for duplicate detection
        $frontHash = sha1_file($frontFile->getRealPath());
        $backHash = $backFile ? sha1_file($backFile->getRealPath()) : null;

        // Check for duplicates if force is not true
        $force = filter_var($request->input('force', false), FILTER_VALIDATE_BOOLEAN);
        if (!$force) {
            $duplicate = ScanJob::query()
                ->where('scan_session_id', $sessionId)
                ->where('front_cover_hash', $frontHash)
                ->where('status', '!=', 'failed')
                ->first();

            if ($duplicate) {
                return response()->json([
                    'warning' => 'duplicate_detected',
                    'message' => 'Buku ini kemungkinan sudah pernah dipindai.',
                    'duplicate_job_id' => $duplicate->id,
                ]);
            }
        }

        // Save cover files
        $frontPath = $this->saveScanImage($frontFile, 'front');
        $backPath = $backFile ? $this->saveScanImage($backFile, 'back') : null;

        // Create ScanJob record (Fase 2)
        $scanJob = ScanJob::query()->create([
            'scan_session_id' => $sessionId,
            'isbn' => null,
            'scan_source' => 'camera',
            'front_cover_path' => $frontPath,
            'back_cover_path' => $backPath,
            'front_cover_hash' => $frontHash,
            'back_cover_hash' => $backHash,
            'priority' => $request->input('priority', 'normal'),
            'status' => 'waiting',
            'attempts' => 0,
        ]);

        // Calculate dynamic queue number (Catatan 1)
        $queueNumber = ScanJob::query()
            ->where('status', 'waiting')
            ->where('id', '<=', $scanJob->id)
            ->count();

        // Increment stats on the session table
        \Illuminate\Support\Facades\DB::transaction(function () use ($sessionId) {
            $session = ScanSession::find($sessionId);
            if ($session) {
                $session->increment('total_books');
                $session->increment('waiting_count');
            }
        });

        // Dispatch background worker ProcessBookScanJob (Sprint 1)
        ProcessBookScanJob::dispatch($scanJob->id);

        return response()->json([
            'found' => true,
            'queued' => true,
            'scan_job_id' => $scanJob->id,
            'queue_number' => $queueNumber,
            'message' => 'Buku masuk antrean.',
        ], 202);
    }

    public function queueStatus(Request $request): JsonResponse
    {
        $sessionId = $this->currentSessionId();
        if (!$sessionId) {
            return response()->json([
                'success' => false,
                'error' => 'Belum ada sesi scan aktif hari ini.',
            ], 400);
        }

        $session = ScanSession::find($sessionId);
        $jobs = ScanJob::query()
            ->where('scan_session_id', $sessionId)
            ->orderBy('id', 'desc')
            ->get();

        // Get completed job cover basenames to map them to BookInbox records in one query
        $completedJobs = $jobs->where('status', 'completed');
        $inboxMap = [];
        if ($completedJobs->isNotEmpty()) {
            $inboxes = BookInbox::query()
                ->where('scan_session_id', $sessionId)
                ->whereIn('scan_job_id', $completedJobs->pluck('id'))
                ->get();
            
            foreach ($completedJobs as $job) {
                $match = $inboxes->firstWhere('scan_job_id', $job->id);
                if ($match) {
                    $inboxMap[$job->id] = [
                        'inbox_id' => $match->id,
                        'title' => $match->title,
                        'author' => $match->author,
                        'isbn' => $match->isbn,
                        'publisher' => $match->publisher,
                        'published_year' => $match->published_year,
                        'category' => $match->category,
                        'description' => $match->description,
                        'status' => $match->status, // 'approved', 'pending'
                        'confidence_score' => $match->confidence_score,
                        'source' => $match->source,
                        'source_chain' => $match->source_chain,
                        'processing_notes' => $match->processing_notes,
                        'cover_front_path' => $match->cover_front_path,
                        'metadata_completeness' => $match->metadata_completeness,
                        'metadata_missing' => $match->metadata_missing,
                    ];
                }
            }
        }

        // Dynamically compute queue_number for waiting items (Catatan 1)
        $waitingJobs = $jobs->where('status', 'waiting')->sortBy('id');
        $waitingCountBefore = 0;
        $jobsWithQueueNumber = $jobs->map(function ($job) use (&$waitingCountBefore, $inboxMap) {
            $data = $job->toArray();
            if ($job->status === 'waiting') {
                $waitingCountBefore++;
                $data['queue_number'] = $waitingCountBefore;
            } else {
                $data['queue_number'] = null;
            }

            if (isset($inboxMap[$job->id])) {
                $data['inbox_id'] = $inboxMap[$job->id]['inbox_id'];
                $data['book_title'] = $inboxMap[$job->id]['title'];
                $data['book_author'] = $inboxMap[$job->id]['author'];
                $data['book_isbn'] = $inboxMap[$job->id]['isbn'];
                $data['book_publisher'] = $inboxMap[$job->id]['publisher'];
                $data['book_year'] = $inboxMap[$job->id]['published_year'];
                $data['book_category'] = $inboxMap[$job->id]['category'];
                $data['book_description'] = $inboxMap[$job->id]['description'];
                $data['inbox_status'] = $inboxMap[$job->id]['status'];
                $data['confidence_score'] = $inboxMap[$job->id]['confidence_score'];
                $data['source'] = $inboxMap[$job->id]['source'];
                $data['source_chain'] = $inboxMap[$job->id]['source_chain'] ?? null;
                $data['processing_notes'] = $inboxMap[$job->id]['processing_notes'] ?? null;
                $data['book_cover_front'] = $inboxMap[$job->id]['cover_front_path'] ?? null;
                $data['metadata_completeness'] = $inboxMap[$job->id]['metadata_completeness'] ?? null;
                $data['metadata_missing'] = $inboxMap[$job->id]['metadata_missing'] ?? null;
            }

            return $data;
        });

        return response()->json([
            'session' => [
                'total_books' => $session->total_books,
                'waiting_count' => $session->waiting_count,
                'processing_count' => $session->processing_count,
                'completed_count' => $session->completed_count,
                'failed_count' => $session->failed_count,
            ],
            'jobs' => $jobsWithQueueNumber,
        ]);
    }

    public function retryJob(ScanJob $job): JsonResponse
    {
        if ($job->scanSession->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized. Pekerjaan ini bukan milik sesi Anda.',
            ], 403);
        }

        if ($job->status !== 'failed') {
            return response()->json([
                'success' => false,
                'error' => 'Pekerjaan ini tidak dalam status gagal.',
            ], 400);
        }

        // Reset status to waiting
        $job->update([
            'status' => 'waiting',
            'attempts' => 0,
            'error_message' => null,
            'started_at' => null,
            'finished_at' => null,
        ]);

        // Sync statistics (Derived Stats)
        \Illuminate\Support\Facades\DB::transaction(function () use ($job) {
            $session = ScanSession::find($job->scan_session_id);
            if ($session) {
                $counts = \Illuminate\Support\Facades\DB::table('scan_jobs')
                    ->where('scan_session_id', $job->scan_session_id)
                    ->select(
                        \Illuminate\Support\Facades\DB::raw('count(*) as total'),
                        \Illuminate\Support\Facades\DB::raw("sum(case when status = 'waiting' then 1 else 0 end) as waiting"),
                        \Illuminate\Support\Facades\DB::raw("sum(case when status = 'processing' then 1 else 0 end) as processing"),
                        \Illuminate\Support\Facades\DB::raw("sum(case when status = 'completed' then 1 else 0 end) as completed"),
                        \Illuminate\Support\Facades\DB::raw("sum(case when status = 'failed' then 1 else 0 end) as failed")
                    )->first();

                if ($counts) {
                    $session->update([
                        'total_books' => (int) $counts->total,
                        'waiting_count' => (int) $counts->waiting,
                        'processing_count' => (int) $counts->processing,
                        'completed_count' => (int) $counts->completed,
                        'failed_count' => (int) $counts->failed,
                    ]);
                }
            }
        });

        // Re-dispatch job
        ProcessBookScanJob::dispatch($job->id);

        return response()->json([
            'success' => true,
            'message' => 'Pekerjaan berhasil dikirim ulang ke antrean.',
        ]);
    }

    // ────────────────────────────────────────────
    //  SAVE TO INBOX (manual entry fallback)
    // ────────────────────────────────────────────

    public function saveToInbox(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inbox_id' => ['nullable', 'integer', 'exists:book_inbox,id'],
            'title' => ['required', 'string', 'max:500'],
            'author' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:20'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'published_year' => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:50'],
            'cover_url' => ['nullable', 'string', 'max:1000'],
            'action_type' => ['nullable', 'string', 'in:add_copy,save_new'],
        ]);

        try {
            $isbn = $validated['isbn'] ?? null;
            $actionType = $validated['action_type'] ?? null;

            if ($actionType === 'save_new') {
                $isbn = null;
            } elseif ($actionType === 'add_copy' && !empty($isbn)) {
                $cleanIsbn = preg_replace('/[^0-9Xx]/', '', $isbn);
                $count = \App\Models\Book::where('isbn', 'like', $cleanIsbn . '%')->count();
                $isbn = $cleanIsbn . '-C' . ($count + 1);
            }

            $inboxId = $validated['inbox_id'] ?? null;
            if ($inboxId) {
                $inbox = BookInbox::findOrFail($inboxId);
                if ($inbox->scanned_by !== auth()->id()) {
                    return response()->json([
                        'saved' => false,
                        'error' => 'Unauthorized. Data inbox ini bukan milik Anda.',
                    ], 403);
                }
                $inbox->update([
                    'title' => $validated['title'],
                    'author' => $validated['author'],
                    'isbn' => $isbn,
                    'publisher' => $validated['publisher'],
                    'published_year' => $validated['published_year'],
                    'description' => $validated['description'],
                    'category' => $validated['category'],
                    'source' => $validated['source'] ?? $inbox->source ?? 'manual',
                    'source_url' => $validated['cover_url'] ?? $inbox->source_url,
                    'status' => 'approved',
                ]);
            } else {
                $sessionId = $this->currentSessionId();
                if (!$sessionId) {
                    return response()->json([
                        'saved' => false,
                        'error' => 'Belum ada sesi scan aktif hari ini.',
                    ], 400);
                }
                $inbox = BookInbox::query()->create([
                    'scan_session_id' => $sessionId,
                    'scanned_by' => auth()->id(),
                    'title' => $validated['title'],
                    'author' => $validated['author'],
                    'isbn' => $isbn,
                    'publisher' => $validated['publisher'],
                    'published_year' => $validated['published_year'],
                    'description' => $validated['description'],
                    'category' => $validated['category'],
                    'source' => $validated['source'] ?? 'manual',
                    'source_url' => $validated['cover_url'] ?? null,
                    'confidence' => 1.0,
                    'status' => 'approved',
                ]);

                $this->incrementSessionCount();
            }

            return response()->json([
                'saved' => true,
                'inbox_id' => $inbox->id,
                'message' => "{$inbox->title} masuk ke inbox review.",
            ]);

        } catch (\Throwable $e) {
            Log::error('[MobileScan] Save to inbox failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'saved' => false,
                'error' => 'Gagal menyimpan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteInbox(BookInbox $inbox): JsonResponse
    {
        if ($inbox->scanned_by !== auth()->id()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized. Data inbox ini bukan milik Anda.',
            ], 403);
        }

        try {
            $inbox->delete();
            return response()->json([
                'success' => true,
                'message' => 'Inbox item deleted.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Gagal menghapus: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ────────────────────────────────────────────
    //  STATS
    // ────────────────────────────────────────────

    public function todayStats(): JsonResponse
    {
        $sessions = ScanSession::query()
            ->whereDate('started_at', today())
            ->get();

        $inboxCount = BookInbox::query()
            ->whereDate('created_at', today())
            ->where('scanned_by', auth()->id())
            ->count();

        return response()->json([
            'total_books' => $sessions->sum('book_count'),
            'inbox_today' => $inboxCount,
            'operators' => $sessions->pluck('operator_name')->unique()->values(),
        ]);
    }

    // ────────────────────────────────────────────
    //  HELPERS
    // ────────────────────────────────────────────

    private function currentSessionId(): ?int
    {
        $session = ScanSession::query()
            ->where('user_id', auth()->id())
            ->whereDate('started_at', today())
            ->whereNull('ended_at')
            ->latest()
            ->first();

        return $session?->id;
    }

    private function incrementSessionCount(): void
    {
        ScanSession::query()
            ->where('user_id', auth()->id())
            ->whereDate('started_at', today())
            ->whereNull('ended_at')
            ->latest()
            ->increment('book_count');
    }

    private function saveScanImage(\Illuminate\Http\UploadedFile $file, string $prefix): string
    {
        $filename = $prefix . '_' . Str::uuid() . '.jpg';
        return $file->storeAs('book-scans', $filename, 'public');
    }

    private function normalizeLookup(array $data, string $isbn): array
    {
        return [
            'title' => $data['title'] ?? null,
            'author' => $data['author'] ?? null,
            'isbn' => $data['isbn'] ?? $isbn,
            'publisher' => $data['publisher'] ?? null,
            'published_year' => $data['published_year'] ?? null,
            'description' => $data['description'] ?? null,
            'cover_url' => $data['cover_url'] ?? null,
        ];
    }

    private function enrichFromIsbn(string $isbn): ?array
    {
        $metadata = $this->isbnLookupService->lookupByIsbn($isbn);

        return is_array($metadata)
            ? array_merge($metadata, ['source' => $metadata['source'] ?? 'catalog'])
            : null;
    }

    private function enrichFromTitle(string $title, ?string $author): ?array
    {
        foreach ($this->buildTitleLookupCandidates($title) as $candidate) {
            $metadata = $this->isbnLookupService->searchByTitleAuthor($candidate, $author);
            if (is_array($metadata)) {
                return array_merge($metadata, ['source' => $metadata['source'] ?? 'catalog']);
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function buildTitleLookupCandidates(string $title): array
    {
        $clean = $this->cleanTitleCandidate($title);
        if ($clean === null) {
            return [];
        }

        $candidates = [$clean];
        $normalized = preg_replace('/[^a-z0-9\s]+/iu', ' ', $clean) ?? $clean;
        $normalized = trim((string) preg_replace('/\s+/', ' ', $normalized));
        if ($normalized !== '' && ! $this->containsCandidate($candidates, $normalized)) {
            $candidates[] = $normalized;
        }

        $segments = preg_split('/\s*[:\-]\s*/', $clean) ?: [];
        foreach ($segments as $segment) {
            $segment = $this->cleanTitleCandidate($segment);
            if ($segment !== null && mb_strlen($segment) >= 6 && ! $this->containsCandidate($candidates, $segment)) {
                $candidates[] = $segment;
            }
        }

        $tokens = preg_split('/\s+/', strtolower($normalized)) ?: [];
        $stopwords = ['the', 'a', 'an', 'of', 'and', 'for', 'to', 'in', 'on', 'with', 'from', 'by', 'edition', 'edisi', 'book', 'volume', 'vol'];
        $keywords = [];

        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '' || mb_strlen($token) < 3 || in_array($token, $stopwords, true)) {
                continue;
            }

            if (! in_array($token, $keywords, true)) {
                $keywords[] = $token;
            }

            if (count($keywords) >= 6) {
                break;
            }
        }

        if ($keywords !== []) {
            $keywordTitle = implode(' ', $keywords);
            if (! $this->containsCandidate($candidates, $keywordTitle)) {
                $candidates[] = $keywordTitle;
            }
        }

        return $candidates;
    }

    private function cleanTitleCandidate(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value, " \t\n\r\0\x0B{}[]()<>\"'`|_.,;:!?");
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /**
     * @param array<int, string> $candidates
     */
    private function containsCandidate(array $candidates, string $candidate): bool
    {
        $candidate = strtolower(trim($candidate));

        foreach ($candidates as $existing) {
            if (strtolower(trim($existing)) === $candidate) {
                return true;
            }
        }

        return false;
    }

    private function lookupGoogleByTitle(string $title, ?string $author): array
    {
        $query = 'intitle:' . urlencode($title);
        if ($author) {
            $query .= '+inauthor:' . urlencode($author);
        }

        $url = "https://www.googleapis.com/books/v1/volumes?q={$query}&maxResults=3&langRestrict=id";
        $key = config('services.google_books.api_key');

        try {
            $http = \Illuminate\Support\Facades\Http::timeout(10);
            if (!config('services.ai_scan.tls_verify', true)) {
                $http = $http->withoutVerifying();
            }
            $response = $http->get($key ? "{$url}&key={$key}" : $url)
                ->throw();

            $items = $response->json('items', []);
            $results = [];

            foreach ($items as $item) {
                $info = $item['volumeInfo'] ?? [];
                $isbn = null;
                foreach ($info['industryIdentifiers'] ?? [] as $id) {
                    if (in_array($id['type'] ?? '', ['ISBN_13', 'ISBN_10'])) {
                        $isbn = $id['identifier'];
                        break;
                    }
                }

                $results[] = [
                    'title' => $info['title'] ?? null,
                    'author' => isset($info['authors']) ? implode(', ', $info['authors']) : null,
                    'isbn' => $isbn,
                    'publisher' => $info['publisher'] ?? null,
                    'published_year' => isset($info['publishedDate']) ? (int) substr($info['publishedDate'], 0, 4) : null,
                    'description' => $info['description'] ?? null,
                    'category' => $info['categories'][0] ?? null,
                    'language' => $info['language'] ?? null,
                    'cover_url' => $info['imageLinks']['thumbnail'] ?? null,
                ];
            }

            return $results;
        } catch (\Throwable $e) {
            Log::warning('[MobileScan] Google title lookup failed: ' . $e->getMessage());
            return [];
        }
    }

    private function lookupOpenLibraryByTitle(string $title, ?string $author): ?array
    {
        $query = ['title' => $title];
        if ($author) {
            $query['author'] = $author;
        }
        $query['limit'] = 1;

        try {
            $http = \Illuminate\Support\Facades\Http::timeout(10)->acceptJson();
            if (!config('services.ai_scan.tls_verify', true)) {
                $http = $http->withoutVerifying();
            }
            $response = $http->get("https://openlibrary.org/search.json", $query)
                ->throw();

            $doc = $response->json('docs.0');
            if (!$doc) return null;

            return [
                'title' => $doc['title'] ?? null,
                'author' => isset($doc['author_name']) ? implode(', ', $doc['author_name']) : null,
                'isbn' => $doc['isbn'][0] ?? null,
                'publisher' => $doc['publisher'][0] ?? null,
                'published_year' => $doc['first_publish_year'] ?? null,
                'language' => $doc['language'][0] ?? null,
                'cover_url' => isset($doc['cover_i']) ? "https://covers.openlibrary.org/b/id/{$doc['cover_i']}-M.jpg" : null,
                'description' => null,
                'category' => $doc['subject'][0] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('[MobileScan] OpenLibrary title lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Smart enrichment pipeline:
     * 1. Google Books by title+author → best match
     * 2. OpenLibrary by title+author → best match
     * 3. Cross-reference → merge best data
     * 4. Check completeness → Tavily web search if needed
     * 5. Gemini text enrichment → fill remaining gaps
     */
    private function smartEnrich(string $title, string $author, string $isbn, string $category): ?array
    {
        $results = ['google' => null, 'openlibrary' => null];

        // Step 1: Search Google Books by title+author
        $googleResults = $this->lookupGoogleByTitle($title, $author ?: null);
        if (!empty($googleResults)) {
            $results['google'] = $this->pickBestMatch($googleResults, $title, 'google');
        }

        // Step 2: Search OpenLibrary by title+author
        $olResult = $this->lookupOpenLibraryByTitle($title, $author ?: null);
        if ($olResult) {
            $results['openlibrary'] = $this->scoreMatch($olResult, $title) >= 0.3 ? $olResult : null;
        }

        // Also try ISBN lookup if available
        if ($isbn && !$results['google'] && !$results['openlibrary']) {
            $isbnResult = $this->enrichFromIsbn($isbn);
            if ($isbnResult) $results['isbn'] = $isbnResult;
        }

        // Step 3: Merge Google + OpenLibrary (prefer more complete data)
        $merged = $this->crossReferenceMerge($results, $title);

        // Step 4: Check completeness
        $completeness = $this->assessCompleteness($merged);
        Log::info('[SmartEnrich] Completeness check', [
            'title' => $title,
            'score' => $completeness['score'],
            'missing' => $completeness['missing'],
        ]);

        // Step 5: If incomplete → Tavily web search
        if ($completeness['score'] < 80) {
            $webData = $this->searchWebForBook($title, $author);
            if ($webData) {
                $merged = $this->fillGaps($merged, $webData);
                $completeness = $this->assessCompleteness($merged);
            }
        }

        // Step 6: If still missing description/category → Gemini text
        if ($completeness['score'] < 90 || empty($merged['description'])) {
            try {
                $geminiFill = $this->geminiService->enrichMetadata([
                    'title' => $merged['title'] ?? $title,
                    'author' => $merged['author'] ?? $author,
                    'category' => $merged['category'] ?? $category,
                ]);
                if (!empty($geminiFill['description'])) {
                    $merged['description'] = $geminiFill['description'];
                }
                if (!empty($geminiFill['category']) && empty($merged['category'])) {
                    $merged['category'] = $geminiFill['category'];
                }
            } catch (\Throwable $e) {
                Log::warning('[SmartEnrich] Gemini fill failed: ' . $e->getMessage());
            }
        }

        // Ensure Indonesian language preference
        if (!empty($merged['description']) && !$this->isIndonesian($merged['description'])) {
            try {
                $translated = $this->geminiService->translateToEnglish($merged['description']);
                // Actually translate TO Indonesian
                $merged['description'] = $this->translateToIndonesian($merged['description']);
            } catch (\Throwable $e) {
                // Keep original if translation fails
            }
        }

        $merged['source'] = $this->buildSourceString($results);

        return $merged;
    }

    /**
     * Pick best match from API results based on title similarity.
     */
    private function pickBestMatch(array $results, string $queryTitle, string $source): ?array
    {
        $best = null;
        $bestScore = 0.4; // minimum threshold

        foreach ($results as $item) {
            $score = $this->scoreMatch($item, $queryTitle);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $item;
            }
        }

        if ($best) {
            $best['match_score'] = round($bestScore, 2);
            $best['source'] = $source;
        }

        return $best;
    }

    /**
     * Score how well an API result matches the queried title (0-1).
     */
    private function scoreMatch(array $item, string $queryTitle): float
    {
        $itemTitle = strtolower(trim($item['title'] ?? ''));
        $query = strtolower(trim($queryTitle));

        if ($itemTitle === '' || $query === '') return 0;

        // Exact match
        if ($itemTitle === $query) return 1.0;

        // Contains
        if (str_contains($itemTitle, $query) || str_contains($query, $itemTitle)) return 0.85;

        // Word overlap
        $queryWords = array_filter(explode(' ', $query), fn($w) => strlen($w) > 2);
        $itemWords = array_filter(explode(' ', $itemTitle), fn($w) => strlen($w) > 2);

        if (empty($queryWords)) return 0;

        $matches = count(array_intersect($queryWords, $itemWords));
        $overlap = $matches / count($queryWords);

        // Levenshtein bonus
        $maxLen = max(strlen($query), strlen($itemTitle));
        if ($maxLen > 0) {
            $lev = 1 - (levenshtein($query, $itemTitle) / $maxLen);
            return ($overlap * 0.7) + ($lev * 0.3);
        }

        return $overlap * 0.7;
    }

    /**
     * Cross-reference: merge Google Books + OpenLibrary data.
     */
    private function crossReferenceMerge(array $sources, string $title): array
    {
        $google = $sources['google'] ?? null;
        $ol = $sources['openlibrary'] ?? null;
        $isbn = $sources['isbn'] ?? null;

        $merged = [
            'title' => $title,
            'author' => null,
            'isbn' => null,
            'publisher' => null,
            'published_year' => null,
            'description' => null,
            'category' => null,
            'cover_url' => null,
            'language' => null,
            'page_count' => null,
        ];

        // Prefer Google Books (more metadata), fill gaps from OpenLibrary
        foreach (['google', 'openlibrary', 'isbn'] as $sourceKey) {
            $src = $sources[$sourceKey] ?? null;
            if (!$src) continue;

            foreach (['title', 'author', 'isbn', 'publisher', 'published_year', 'description', 'category', 'cover_url', 'language'] as $field) {
                if (empty($merged[$field]) && !empty($src[$field])) {
                    $merged[$field] = $src[$field];
                }
            }
        }

        // If Google has description but OpenLibrary has different/better data, keep Google
        if ($google && $ol) {
            // Prefer longer description
            $gDesc = $google['description'] ?? '';
            $oDesc = $ol['description'] ?? '';
            if (strlen((string)$oDesc) > strlen((string)$gDesc) && strlen((string)$oDesc) > 100) {
                $merged['description'] = $oDesc;
            }
            // Prefer Indonesian language
            if (($ol['language'] ?? '') === 'id' || ($ol['language'] ?? '') === 'ind') {
                $merged['language'] = 'id';
            }
        }

        return $merged;
    }

    /**
     * Assess data completeness (0-100).
     */
    private function assessCompleteness(array $data): array
    {
        $weights = [
            'title' => 25,
            'author' => 20,
            'cover_url' => 15,
            'description' => 15,
            'publisher' => 8,
            'published_year' => 7,
            'category' => 5,
            'isbn' => 5,
        ];

        $score = 0;
        $missing = [];

        foreach ($weights as $field => $weight) {
            if (!empty($data[$field])) {
                $score += $weight;
            } else {
                $missing[] = $field;
            }
        }

        return ['score' => $score, 'missing' => $missing];
    }

    /**
     * Search the web for book info via Tavily API.
     */
    private function searchWebForBook(string $title, ?string $author): ?array
    {
        $searchQuery = "buku \"{$title}\"";
        if ($author) $searchQuery .= " {$author}";
        $searchQuery .= ' site:gramedia.com OR site:perpusnas.go.id OR site:goodreads.com';

        try {
            $tavilyKey = config('services.tavily.api_key');
            if (!$tavilyKey || $tavilyKey === 'tvly-secret') {
                // Try Google Books broader search instead
                return $this->broadGoogleSearch($title, $author);
            }

            $response = \Illuminate\Support\Facades\Http::timeout(15)
                ->acceptJson()
                ->asJson()
                ->post('https://api.tavily.com/search', [
                    'api_key' => $tavilyKey,
                    'query' => $searchQuery,
                    'search_depth' => 'basic',
                    'max_results' => 3,
                    'include_domains' => ['gramedia.com', 'perpusnas.go.id', 'goodreads.com', 'books.google.com'],
                ])
                ->throw();

            $results = $response->json('results', []);
            if (empty($results)) return null;

            // Extract description from web results
            $descriptions = [];
            foreach ($results as $r) {
                if (!empty($r['content'])) {
                    $descriptions[] = $r['content'];
                }
            }

            return [
                'description' => !empty($descriptions) ? implode(' ', array_slice($descriptions, 0, 2)) : null,
                'source' => 'tavily',
            ];

        } catch (\Throwable $e) {
            Log::warning('[SmartEnrich] Tavily search failed: ' . $e->getMessage());
            return $this->broadGoogleSearch($title, $author);
        }
    }

    /**
     * Broader Google Books search as fallback for web search.
     */
    private function broadGoogleSearch(string $title, ?string $author): ?array
    {
        $query = urlencode($title);
        if ($author) $query .= '+' . urlencode($author);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->acceptJson()
                ->get("https://www.googleapis.com/books/v1/volumes?q={$query}&maxResults=5&printType=books")
                ->throw();

            $items = $response->json('items', []);
            foreach ($items as $item) {
                $info = $item['volumeInfo'] ?? [];
                if (!empty($info['description']) && strlen((string)$info['description']) > 80) {
                    return [
                        'description' => $info['description'],
                        'source' => 'google_books_broad',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[SmartEnrich] Broad Google search failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Fill gaps in merged data using web search results.
     */
    private function fillGaps(array $current, array $webData): array
    {
        foreach (['description', 'cover_url', 'publisher', 'category'] as $field) {
            if (empty($current[$field]) && !empty($webData[$field])) {
                $current[$field] = $webData[$field];
            }
        }
        return $current;
    }

    /**
     * Check if text is in Indonesian.
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

    /**
     * Translate text to Indonesian using Gemini.
     */
    private function translateToIndonesian(string $text): string
    {
        try {
            $response = $this->geminiService->callGeminiDirect(
                "Translate this text to Indonesian. Return ONLY the translated text, no explanation:\n\n{$text}",
                800
            );
            $translated = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
            return $translated ?: $text;
        } catch (\Throwable $e) {
            return $text;
        }
    }

    /**
     * Build human-readable source string.
     */
    private function buildSourceString(array $sources): string
    {
        $parts = [];
        if ($sources['google'] ?? null) $parts[] = 'google_books';
        if ($sources['openlibrary'] ?? null) $parts[] = 'openlibrary';
        if ($sources['isbn'] ?? null) $parts[] = 'isbn_catalog';
        if (empty($parts)) $parts[] = 'gemini_vision';
        return implode('+', $parts);
    }

    private function mergeMetadata(array $vision, ?array $external, string $frontPath, ?string $backPath): array
    {
        $merge = fn(?string $a, ?string $b) => $a ?: $b;

        return [
            'title' => $merge($external['title'] ?? null, $vision['title'] ?? null),
            'author' => $merge($external['author'] ?? null, $vision['author'] ?? null),
            'isbn' => $merge($external['isbn'] ?? null, $vision['isbn'] ?? null),
            'publisher' => $merge($external['publisher'] ?? null, $vision['publisher'] ?? null),
            'published_year' => $external['published_year'] ?? $vision['published_year'] ?? null,
            'description' => $merge($external['description'] ?? null, $vision['description'] ?? null),
            'category' => $merge($external['category'] ?? null, $vision['category'] ?? null),
            'language' => $merge($external['language'] ?? null, $vision['language'] ?? null),
            'cover_front_path' => $frontPath,
            'cover_back_path' => $backPath,
            'source' => $external['source'] ?? 'gemini',
            'source_url' => $external['cover_url'] ?? null,
            'confidence' => $external ? 0.85 : 0.6,
        ];
    }
}
