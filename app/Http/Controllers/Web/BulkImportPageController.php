<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommitScannedBooksRequest;
use App\Http\Requests\BulkImportCommitRequest;
use App\Http\Requests\BulkImportPreviewRequest;
use App\Http\Requests\LookupIsbnRequest;
use App\Http\Requests\ScanBookBatchRequest;
use App\Http\Requests\ScanBookImagesRequest;
use App\Http\Requests\StoreManualBookRequest;
use App\Models\Category;
use App\Models\Rack;
use App\Services\AiScanObservabilityService;
use App\Services\AiBookScanPipelineService;
use App\Services\AiInfrastructureService;
use App\Services\BookService;
use App\Services\BulkImportService;
use App\Services\IsbnLookupService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BulkImportPageController extends Controller
{
    private const AI_SCAN_DRAFT_CACHE_PREFIX = 'bulk-import-ai-draft:';
    private const AI_SCAN_DRAFT_TTL_MINUTES = 180;

    public function __construct(
        private readonly BulkImportService $bulkImportService,
        private readonly BookService $bookService,
        private readonly IsbnLookupService $isbnLookupService,
        private readonly AiBookScanPipelineService $aiBookScanPipelineService,
        private readonly AiScanObservabilityService $observabilityService,
        private readonly AiInfrastructureService $aiInfrastructureService
    ) {
    }

    public function index(Request $request): View
    {
        return $this->view($request);
    }

    public function view(Request $request): View
    {
        $aiScanDraftToken = $request->session()->get('bulk_import_ai_scan_draft_token');
        $aiScanDraft = $this->resolveAiScanDraft($aiScanDraftToken);

        return view('books.import', [
            'preview' => $request->session()->get('bulk_import_preview'),
            'import_summary' => $request->session()->get('bulk_import_summary'),
            'ai_scan_draft' => $aiScanDraft,
            'ai_scan_draft_token' => $aiScanDraftToken,
            'ai_runtime' => $this->aiInfrastructureService->runtimeSummary(),
            'ai_diagnostics' => $this->aiInfrastructureService->diagnostics(),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'racks' => Rack::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function preview(BulkImportPreviewRequest $request)
    {
        $result = $this->bulkImportService->preview($request->file('file'));

        $request->session()->forget('bulk_import_summary');

        return redirect()
            ->route('books.import')
            ->with('bulk_import_preview', $result)
            ->with(
                'toast',
                ['type' => 'info', 'message' => 'Preview generated. Review rows before import.']
            );
    }

    public function commit(BulkImportCommitRequest $request)
    {
        $result = $this->bulkImportService->commit($request->validated('preview_token'));

        if ($result['message'] === 'Preview token is invalid or expired.') {
            return redirect()->route('books.import')->with(
                'toast',
                ['type' => 'error', 'message' => $result['message']]
            );
        }

        $request->session()->forget('bulk_import_preview');

        return redirect()
            ->route('books.import')
            ->with('bulk_import_summary', $result)
            ->with(
                'toast',
                ['type' => 'success', 'message' => 'Import finished successfully.']
            );
    }

    public function storeManual(StoreManualBookRequest $request)
    {
        $attributes = $request->validated();
        
        $category = Category::firstOrCreate(['name' => $attributes['category_name']]);
        $attributes['category_id'] = $category->id;
        unset($attributes['category_name']);

        $this->bookService->createManual($attributes);

        return redirect()->route('books.import')->with(
            'toast',
            ['type' => 'success', 'message' => 'Manual book input saved.']
        );
    }

    public function lookupIsbn(LookupIsbnRequest $request): JsonResponse
    {
        $result = $this->isbnLookupService->lookup($request->validated('isbn'));

        if (! $result) {
            return response()->json([
                'message' => 'No book metadata found for the given ISBN.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json($result);
    }

    public function scanWithAi(ScanBookImagesRequest $request): JsonResponse
    {
        $runtimeIssue = $this->aiInfrastructureService->ensureVisionRuntimeAvailable();
        if ($runtimeIssue) {
            return response()->json([
                'message' => $runtimeIssue,
            ], Response::HTTP_BAD_GATEWAY);
        }

        // AI pipeline can take longer than default PHP execution limit (60s),
        // especially in full mode (vision + multi-provider fallbacks).
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');
        $start = microtime(true);
        $mode = (string) $request->validated('mode', 'full');
        $imageCount = count($request->file('images', []));

        try {
            $result = $this->aiBookScanPipelineService->scan(
                $request->file('images', []),
                $mode
            );

            $durationMs = (int) round((microtime(true) - $start) * 1000);
            $this->observabilityService->recordSuccess(
                channel: 'web',
                mode: $mode,
                source: (string) ($result['source'] ?? 'ai'),
                durationMs: $durationMs,
                imageCount: $imageCount
            );
        } catch (\RuntimeException $e) {
            $durationMs = (int) round((microtime(true) - $start) * 1000);
            $this->observabilityService->recordFailure(
                channel: 'web',
                mode: $mode,
                durationMs: $durationMs,
                imageCount: $imageCount,
                error: $e->getMessage()
            );

            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        }

        return response()->json($result);
    }

    public function scanBatchWithAi(ScanBookBatchRequest $request)
    {
        $runtimeIssue = $this->aiInfrastructureService->ensureVisionRuntimeAvailable();
        if ($runtimeIssue) {
            return redirect()
                ->route('books.import')
                ->withInput()
                ->with('toast', [
                    'type' => 'error',
                    'message' => $runtimeIssue,
                ]);
        }

        @set_time_limit(900);
        @ini_set('max_execution_time', '900');

        $mode = (string) $request->validated('mode', 'full');
        $batch = $request->validated('books');
        $results = [];

        foreach ($batch as $index => $bookPayload) {
            $images = array_values(array_filter([
                $request->file("books.{$index}.front_image"),
                $request->file("books.{$index}.back_image"),
            ]));

            $start = microtime(true);
            $imageCount = count($images);

            try {
                $result = $this->aiBookScanPipelineService->scan($images, $mode);

                $durationMs = (int) round((microtime(true) - $start) * 1000);
                $this->observabilityService->recordSuccess(
                    channel: 'web',
                    mode: $mode,
                    source: (string) ($result['source'] ?? 'ai'),
                    durationMs: $durationMs,
                    imageCount: $imageCount
                );

                $results[] = [
                    'scan_id' => (string) Str::uuid(),
                    'title' => $result['title'] ?? null,
                    'author' => $result['author'] ?? null,
                    'category_name' => $result['category'] ?? 'Uncategorized',
                    'description' => $result['description'] ?? null,
                    'publisher' => $result['publisher'] ?? null,
                    'published_year' => $result['published_year'] ?? null,
                    'isbn' => $result['isbn'] ?? null,
                    'cover_url' => $result['cover_url'] ?? null,
                    'source' => $result['source'] ?? 'ai',
                    'source_url' => $result['source_url'] ?? null,
                    'field_sources' => $result['field_sources'] ?? [],
                    'notes' => $bookPayload['notes'] ?? null,
                    'scan_status' => 'success',
                    'error' => null,
                ];
            } catch (\RuntimeException $e) {
                $durationMs = (int) round((microtime(true) - $start) * 1000);
                $this->observabilityService->recordFailure(
                    channel: 'web',
                    mode: $mode,
                    durationMs: $durationMs,
                    imageCount: $imageCount,
                    error: $e->getMessage()
                );

                $results[] = [
                    'scan_id' => (string) Str::uuid(),
                    'title' => 'Buku #' . ($index + 1),
                    'author' => null,
                    'category_name' => 'Perlu Review',
                    'description' => null,
                    'publisher' => null,
                    'published_year' => null,
                    'isbn' => null,
                    'cover_url' => null,
                    'source' => 'ai',
                    'source_url' => null,
                    'field_sources' => [],
                    'notes' => $bookPayload['notes'] ?? null,
                    'scan_status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        $draftToken = (string) Str::uuid();
        Cache::put(
            $this->aiDraftCacheKey($draftToken),
            [
                'mode' => $mode,
                'books' => $results,
                'generated_at' => now()->toIso8601String(),
            ],
            now()->addMinutes(self::AI_SCAN_DRAFT_TTL_MINUTES)
        );

        $request->session()->put('bulk_import_ai_scan_draft_token', $draftToken);
        $request->session()->forget('bulk_import_summary');
        $request->session()->forget('bulk_import_preview');

        $successCount = collect($results)->where('scan_status', 'success')->count();
        $failedCount = collect($results)->where('scan_status', 'failed')->count();

        return redirect()
            ->route('books.import')
            ->with('toast', [
                'type' => $failedCount > 0 ? 'info' : 'success',
                'message' => "AI batch scan selesai. Berhasil: {$successCount}, perlu review: {$failedCount}.",
            ]);
    }

    public function commitScannedBooks(CommitScannedBooksRequest $request)
    {
        $draftToken = $request->validated('draft_token');
        $draft = $this->resolveAiScanDraft($draftToken);

        if (! $draft) {
            return redirect()
                ->route('books.import')
                ->with('toast', [
                    'type' => 'error',
                    'message' => 'Draft hasil scan tidak ditemukan atau sudah kedaluwarsa.',
                ]);
        }

        $imported = 0;
        $skipped = 0;
        $skippedReasons = [];

        foreach ($request->validated('books') as $bookPayload) {
            try {
                $title = $this->nullableTrimmedString($bookPayload['title'] ?? null);
                $author = $this->nullableTrimmedString($bookPayload['author'] ?? null);
                $categoryName = $this->nullableTrimmedString($bookPayload['category_name'] ?? null);

                if (! $title || ! $author || ! $categoryName) {
                    throw new \RuntimeException('Title, author, dan category wajib dilengkapi sebelum buku dimasukkan ke library.');
                }

                $category = Category::query()->firstOrCreate([
                    'name' => $categoryName,
                ]);

                $attributes = [
                    'title' => $title,
                    'author' => $author,
                    'isbn' => $this->nullableTrimmedString($bookPayload['isbn'] ?? null),
                    'description' => $this->nullableTrimmedString($bookPayload['description'] ?? null),
                    'cover_url' => $this->nullableTrimmedString($bookPayload['cover_url'] ?? null),
                    'rack_id' => $bookPayload['rack_id'] ?? null,
                    'category_id' => $category->id,
                ];

                $this->bookService->createManual($attributes);
                $imported++;
            } catch (Throwable $e) {
                $skipped++;
                $reason = $e->getMessage();
                $skippedReasons[$reason] = ($skippedReasons[$reason] ?? 0) + 1;
            }
        }

        Cache::forget($this->aiDraftCacheKey($draftToken));
        $request->session()->forget('bulk_import_ai_scan_draft_token');

        return redirect()
            ->route('books.import')
            ->with('bulk_import_summary', [
                'imported' => $imported,
                'skipped' => $skipped,
                'skipped_reasons' => $skippedReasons,
            ])
            ->with('toast', [
                'type' => $skipped > 0 ? 'info' : 'success',
                'message' => "Review selesai diproses. Imported: {$imported}, skipped: {$skipped}.",
            ]);
    }

    private function resolveAiScanDraft(?string $token): ?array
    {
        if (! is_string($token) || trim($token) === '') {
            return null;
        }

        $draft = Cache::get($this->aiDraftCacheKey($token));

        return is_array($draft) ? $draft : null;
    }

    private function aiDraftCacheKey(string $token): string
    {
        return self::AI_SCAN_DRAFT_CACHE_PREFIX . $token;
    }

    private function nullableTrimmedString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
