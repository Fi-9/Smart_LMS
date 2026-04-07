<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AiBatchScanDraftService
{
    private const CACHE_PREFIX = 'bulk-import-ai-draft:';
    private const TTL_MINUTES = 180;
    private const STALE_QUEUE_AFTER_SECONDS = 20;

    /**
     * @param array<int, array<string, mixed>> $books
     */
    public function create(string $mode, array $books): string
    {
        $token = (string) Str::uuid();

        $normalizedBooks = array_values(array_map(function (array $book): array {
            return [
                'scan_id' => (string) ($book['scan_id'] ?? Str::uuid()),
                'title' => $book['title'] ?? null,
                'author' => $book['author'] ?? null,
                'category_name' => $book['category_name'] ?? 'Perlu Review',
                'description' => $book['description'] ?? null,
                'publisher' => $book['publisher'] ?? null,
                'published_year' => $book['published_year'] ?? null,
                'isbn' => $book['isbn'] ?? null,
                'cover_url' => $book['cover_url'] ?? null,
                'source' => $book['source'] ?? 'ai',
                'source_url' => $book['source_url'] ?? null,
                'field_sources' => is_array($book['field_sources'] ?? null) ? $book['field_sources'] : [],
                'notes' => $book['notes'] ?? null,
                'scan_status' => $book['scan_status'] ?? 'pending',
                'error' => $book['error'] ?? null,
                'queued_at' => $book['queued_at'] ?? now()->toIso8601String(),
                'started_at' => $book['started_at'] ?? null,
                'finished_at' => $book['finished_at'] ?? null,
            ];
        }, $books));

        $draft = [
            'token' => $token,
            'mode' => $mode,
            'status' => 'queued',
            'generated_at' => now()->toIso8601String(),
            'books' => $normalizedBooks,
        ];

        $this->put($token, $this->withSummary($draft));

        return $token;
    }

    public function get(?string $token): ?array
    {
        if (! is_string($token) || trim($token) === '') {
            return null;
        }

        $draft = Cache::get($this->cacheKey($token));

        return is_array($draft) ? $this->withSummary($draft) : null;
    }

    public function forget(string $token): void
    {
        Cache::forget($this->cacheKey($token));
    }

    public function cancel(string $token): ?array
    {
        $draft = $this->get($token);
        if (! $draft) {
            return null;
        }

        $draft['cancelled_at'] = now()->toIso8601String();
        $draft['books'] = array_map(function (array $book): array {
            if (in_array($book['scan_status'] ?? null, ['success', 'failed'], true)) {
                return $book;
            }

            $book['scan_status'] = 'cancelled';
            $book['finished_at'] = now()->toIso8601String();
            $book['error'] = 'Scan dibatalkan oleh user.';

            return $book;
        }, $draft['books'] ?? []);

        $draft = $this->withSummary($draft);
        $this->put($token, $draft);

        return $draft;
    }

    public function markProcessing(string $token, string $scanId): ?array
    {
        return $this->updateBook($token, $scanId, function (array $book): array {
            $book['scan_status'] = 'processing';
            $book['started_at'] = now()->toIso8601String();
            $book['error'] = null;

            return $book;
        });
    }

    /**
     * @param array<string, mixed> $result
     */
    public function markSuccess(string $token, string $scanId, array $result): ?array
    {
        return $this->updateBook($token, $scanId, function (array $book) use ($result): array {
            $book['title'] = $result['title'] ?? $book['title'];
            $book['author'] = $result['author'] ?? $book['author'];
            $book['category_name'] = $result['category'] ?? $book['category_name'];
            $book['description'] = $result['description'] ?? $book['description'];
            $book['publisher'] = $result['publisher'] ?? $book['publisher'];
            $book['published_year'] = $result['published_year'] ?? $book['published_year'];
            $book['isbn'] = $result['isbn'] ?? $book['isbn'];
            $book['cover_url'] = $result['cover_url'] ?? $book['cover_url'];
            $book['source'] = $result['source'] ?? $book['source'];
            $book['source_url'] = $result['source_url'] ?? $book['source_url'];
            $book['field_sources'] = is_array($result['field_sources'] ?? null) ? $result['field_sources'] : ($book['field_sources'] ?? []);
            $book['scan_status'] = 'success';
            $book['finished_at'] = now()->toIso8601String();
            $book['error'] = null;

            return $book;
        });
    }

    public function markFailed(string $token, string $scanId, string $message): ?array
    {
        return $this->updateBook($token, $scanId, function (array $book) use ($message): array {
            $book['scan_status'] = 'failed';
            $book['category_name'] = $book['category_name'] ?: 'Perlu Review';
            $book['finished_at'] = now()->toIso8601String();
            $book['error'] = $message;

            return $book;
        });
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $mutator
     */
    private function updateBook(string $token, string $scanId, callable $mutator): ?array
    {
        $draft = $this->get($token);
        if (! $draft) {
            return null;
        }

        $draft['books'] = array_map(function (array $book) use ($scanId, $mutator): array {
            if (($book['scan_id'] ?? null) !== $scanId) {
                return $book;
            }

            return $mutator($book);
        }, $draft['books'] ?? []);

        $draft = $this->withSummary($draft);
        $this->put($token, $draft);

        return $draft;
    }

    /**
     * @param array<string, mixed> $draft
     * @return array<string, mixed>
     */
    private function withSummary(array $draft): array
    {
        $books = collect($draft['books'] ?? []);

        $pending = $books->where('scan_status', 'pending')->count();
        $processing = $books->where('scan_status', 'processing')->count();
        $success = $books->where('scan_status', 'success')->count();
        $failed = $books->where('scan_status', 'failed')->count();
        $total = $books->count();

        $cancelled = $books->where('scan_status', 'cancelled')->count();

        $draft['summary'] = [
            'total' => $total,
            'pending' => $pending,
            'processing' => $processing,
            'success' => $success,
            'failed' => $failed,
            'cancelled' => $cancelled,
            'completed' => $success + $failed + $cancelled,
            'is_finished' => $total > 0 && ($pending + $processing) === 0,
            'is_stale_queue' => $this->isStaleQueue($books->all(), $processing),
            'queue_wait_seconds' => $this->queueWaitSeconds($books->all()),
        ];

        $draft['status'] = match (true) {
            $total === 0 => 'empty',
            isset($draft['cancelled_at']) => 'cancelled',
            $draft['summary']['is_finished'] && $failed > 0 => 'completed_with_errors',
            $draft['summary']['is_finished'] => 'completed',
            ($draft['summary']['is_stale_queue'] ?? false) === true => 'stale_queue',
            $processing > 0 => 'processing',
            default => 'queued',
        };

        return $draft;
    }

    /**
     * @param array<string, mixed> $draft
     */
    private function put(string $token, array $draft): void
    {
        Cache::put(
            $this->cacheKey($token),
            $draft,
            now()->addMinutes(self::TTL_MINUTES)
        );
    }

    private function cacheKey(string $token): string
    {
        return self::CACHE_PREFIX . $token;
    }

    /**
     * @param array<int, array<string, mixed>> $books
     */
    private function isStaleQueue(array $books, int $processing): bool
    {
        if ($processing > 0 || $books === []) {
            return false;
        }

        $waitSeconds = $this->queueWaitSeconds($books);

        return $waitSeconds >= self::STALE_QUEUE_AFTER_SECONDS;
    }

    /**
     * @param array<int, array<string, mixed>> $books
     */
    private function queueWaitSeconds(array $books): int
    {
        $oldest = null;

        foreach ($books as $book) {
            $queuedAt = $book['queued_at'] ?? null;
            if (! is_string($queuedAt) || trim($queuedAt) === '') {
                continue;
            }

            try {
                $parsed = Carbon::parse($queuedAt);
            } catch (\Throwable) {
                continue;
            }

            if ($oldest === null || $parsed->lt($oldest)) {
                $oldest = $parsed;
            }
        }

        if ($oldest === null) {
            return 0;
        }

        return max(0, $oldest->diffInSeconds(now()));
    }
}
