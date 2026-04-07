<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AiScanObservabilityService
{
    public function recordSuccess(
        string $channel,
        string $mode,
        string $source,
        int $durationMs,
        int $imageCount
    ): void {
        $dateKey = now()->format('Ymd');
        $base = "ai_scan:metrics:{$dateKey}";
        $ttl = now()->addDays(8);

        $this->increment("{$base}:total", $ttl);
        $this->increment("{$base}:success", $ttl);
        $this->increment("{$base}:duration_total_ms", $ttl, max(0, $durationMs));
        $this->increment("{$base}:source:{$source}", $ttl);
        $this->increment("{$base}:channel:{$channel}", $ttl);
        $this->increment("{$base}:mode:{$mode}", $ttl);

        Log::info('ai_scan.completed', [
            'channel' => $channel,
            'mode' => $mode,
            'source' => $source,
            'duration_ms' => $durationMs,
            'image_count' => $imageCount,
            'date_key' => $dateKey,
        ]);
    }

    public function recordFailure(
        string $channel,
        string $mode,
        int $durationMs,
        int $imageCount,
        string $error
    ): void {
        $dateKey = now()->format('Ymd');
        $base = "ai_scan:metrics:{$dateKey}";
        $ttl = now()->addDays(8);

        $this->increment("{$base}:total", $ttl);
        $this->increment("{$base}:failed", $ttl);
        $this->increment("{$base}:channel:{$channel}", $ttl);
        $this->increment("{$base}:mode:{$mode}", $ttl);

        Log::warning('ai_scan.failed', [
            'channel' => $channel,
            'mode' => $mode,
            'duration_ms' => $durationMs,
            'image_count' => $imageCount,
            'error' => $error,
            'date_key' => $dateKey,
        ]);
    }

    public function todayStats(): array
    {
        $dateKey = now()->format('Ymd');
        $base = "ai_scan:metrics:{$dateKey}";

        $total = (int) Cache::get("{$base}:total", 0);
        $success = (int) Cache::get("{$base}:success", 0);
        $failed = (int) Cache::get("{$base}:failed", 0);
        $durationTotalMs = (int) Cache::get("{$base}:duration_total_ms", 0);

        $avgLatencyMs = $success > 0
            ? (int) round($durationTotalMs / $success)
            : 0;

        $sourceDistribution = [
            'google' => (int) Cache::get("{$base}:source:google", 0),
            'openlibrary' => (int) Cache::get("{$base}:source:openlibrary", 0),
            'websearch' => (int) Cache::get("{$base}:source:websearch", 0),
            'ai' => (int) Cache::get("{$base}:source:ai", 0),
        ];

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($success / $total) * 100, 1) : 0.0,
            'avg_latency_ms' => $avgLatencyMs,
            'source_distribution' => $sourceDistribution,
        ];
    }

    private function increment(string $key, \DateTimeInterface $ttl, int $by = 1): void
    {
        if (! Cache::has($key)) {
            Cache::put($key, 0, $ttl);
        }

        Cache::increment($key, $by);
    }
}
