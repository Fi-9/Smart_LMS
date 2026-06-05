<?php

namespace App\Services;

use App\Models\BookInbox;
use App\Models\ScanJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiObservabilityService
{
    /**
     * Get aggregate statistics and metrics for the dashboard.
     *
     * @param string $range 'today', '7days', or '30days'
     * @return array
     */
    public function stats(string $range): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($range);

        // Fetch jobs in date range
        $jobs = ScanJob::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get(['status', 'current_stage', 'stage_status', 'pipeline_metrics', 'error_message', 'confidence_score']);

        $totalScans = $jobs->count();
        $completedScans = $jobs->where('status', 'completed')->count();
        $failedScans = $jobs->where('status', 'failed')->count();
        $totalProcessed = $completedScans + $failedScans;

        $successRate = $totalProcessed > 0
            ? round(($completedScans / $totalProcessed) * 100, 1)
            : 0.0;

        // Calculate average latency per stage
        $latencies = [
            'identification' => [],
            'lookup' => [],
            'enrichment' => [],
            'fallback' => [],
            'inbox' => [],
            'total' => [],
        ];

        foreach ($jobs->where('status', 'completed') as $job) {
            $metrics = $job->pipeline_metrics;
            if (is_array($metrics)) {
                $totalJobDuration = 0;
                foreach (['identification', 'lookup', 'enrichment', 'fallback', 'inbox'] as $stage) {
                    if (isset($metrics[$stage]) && is_numeric($metrics[$stage])) {
                        $duration = (int) $metrics[$stage];
                        $latencies[$stage][] = $duration;
                        $totalJobDuration += $duration;
                    }
                }
                if ($totalJobDuration > 0) {
                    $latencies['total'][] = $totalJobDuration;
                }
            }
        }

        $avgLatency = [];
        foreach ($latencies as $stage => $durations) {
            $avgLatency[$stage] = count($durations) > 0
                ? (int) round(array_sum($durations) / count($durations))
                : 0;
        }

        // Calculate stage failure distribution
        $failedJobs = $jobs->where('status', 'failed');
        $failureDistribution = [
            'identification' => 0,
            'lookup' => 0,
            'enrichment' => 0,
            'fallback' => 0,
            'inbox' => 0,
        ];

        foreach ($failedJobs as $job) {
            $failedStage = $job->current_stage ?: 'identification';
            if (array_key_exists($failedStage, $failureDistribution)) {
                $failureDistribution[$failedStage]++;
            }
        }

        // Count rate limit and API errors
        $apiFailures = 0;
        foreach ($failedJobs as $job) {
            $err = strtolower($job->error_message ?? '');
            if (str_contains($err, 'rate limit') || 
                str_contains($err, 'quota') || 
                str_contains($err, '503') || 
                str_contains($err, 'unavailable') || 
                str_contains($err, 'resource exhausted')
            ) {
                $apiFailures++;
            }
        }

        // Fetch completed books from book_inbox for hit rates and confidence
        $inboxes = BookInbox::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get(['source', 'confidence_score', 'created_at']);

        $totalInbox = $inboxes->count();
        
        $sourceCounts = [
            'cache' => 0,
            'google_books' => 0,
            'openlibrary' => 0,
            'google_books+openlibrary' => 0,
            'gemini_vision' => 0,
            'websearch' => 0,
        ];

        foreach ($inboxes as $inbox) {
            $src = $inbox->source ?: 'gemini_vision';
            if (array_key_exists($src, $sourceCounts)) {
                $sourceCounts[$src]++;
            }
        }

        $cacheHitRate = $totalInbox > 0
            ? round(($sourceCounts['cache'] / $totalInbox) * 100, 1)
            : 0.0;

        $avgConfidence = $totalInbox > 0
            ? round($inboxes->avg('confidence_score'), 1)
            : 0.0;

        // Fetch recent failures
        $recentFailures = ScanJob::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'failed')
            ->with('scanSession')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'current_stage' => $job->current_stage ?: 'identification',
                    'stage_message' => $job->stage_message ?: ($job->error_message ?: 'Unknown error'),
                    'operator_name' => $job->scanSession->operator_name ?? 'System',
                    'created_at' => $job->created_at->toIso8601String(),
                ];
            })->toArray();

        // Trends (Volume & Confidence)
        $trends = $this->calculateTrends($startDate, $endDate);

        // Queue info
        $queueStats = DB::table('scan_jobs')
            ->selectRaw("sum(case when status = 'waiting' then 1 else 0 end) as waiting,
                         sum(case when status = 'processing' then 1 else 0 end) as processing")
            ->first();

        return [
            'range' => $range,
            'total_scans' => $totalScans,
            'success_rate' => $successRate,
            'avg_latency' => $avgLatency,
            'failure_distribution' => $failureDistribution,
            'api_failures' => $apiFailures,
            'cache_hit_rate' => $cacheHitRate,
            'avg_confidence' => $avgConfidence,
            'source_distribution' => $sourceCounts,
            'recent_failures' => $recentFailures,
            'trends' => $trends,
            'queue' => [
                'waiting' => (int) ($queueStats->waiting ?? 0),
                'processing' => (int) ($queueStats->processing ?? 0),
            ]
        ];
    }

    /**
     * Resolve start and end dates based on range string.
     *
     * @param string $range
     * @return array{Carbon, Carbon}
     */
    private function resolveDateRange(string $range): array
    {
        $now = Carbon::now();
        switch ($range) {
            case '7days':
                return [$now->copy()->subDays(7)->startOfDay(), $now->copy()->endOfDay()];
            case '30days':
                return [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()];
            case 'today':
            default:
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
        }
    }

    /**
     * Calculate trend statistics grouped by date.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    private function calculateTrends(Carbon $start, Carbon $end): array
    {
        // Get date format depending on DB driver (Sqlite vs Pgsql)
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $dateGroup = $isSqlite ? "strftime('%Y-%m-%d', created_at)" : "DATE(created_at)";

        // Volume trend
        $volumes = DB::table('book_inbox')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("{$dateGroup} as date, count(*) as total")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        // Confidence trend
        $confidences = DB::table('book_inbox')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("{$dateGroup} as date, avg(confidence_score) as avg_conf")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('avg_conf', 'date')
            ->toArray();

        // Populate days
        $days = [];
        $temp = $start->copy();
        while ($temp->lte($end)) {
            $formatted = $temp->format('Y-m-d');
            $days[$formatted] = [
                'date' => $temp->format('d M'),
                'volume' => (int) ($volumes[$formatted] ?? 0),
                'confidence' => isset($confidences[$formatted]) ? round((float) $confidences[$formatted], 1) : 0.0,
            ];
            $temp->addDay();
        }

        return array_values($days);
    }
}
