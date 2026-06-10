<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\QueryException;

class AiInfrastructureService
{
    private const DIAGNOSTICS_CACHE_MINUTES = 1;

    public function __construct(
        private readonly AppSettingsService $settingsService
    ) {
    }

    public function runtimeSummary(): array
    {
        $n8nBaseUrl = $this->clean((string) $this->settingsService->get('ai.n8n.base_url', config('services.n8n.base_url', '')));
        $n8nConfigured = $n8nBaseUrl !== null;
        $websearchEnabled = $this->settingsService->getBool('ai.websearch.enabled', (bool) config('services.websearch.enabled', false));

        return [
            'profile' => $this->clean((string) config('services.ai_runtime.profile', 'n8n-gemini')) ?? 'n8n-gemini',
            'recommended_scan_mode' => $this->recommendedScanMode(),
            'vision' => [
                'provider' => 'gemini-via-n8n',
                'enabled' => $n8nConfigured,
                'base_url' => $n8nBaseUrl,
                'model' => config('services.gemini.vision_model', 'gemini-2.5-flash'),
                'status_label' => $n8nConfigured ? 'Aktif' : 'Belum siap',
                'note' => $n8nConfigured
                    ? 'Dipakai untuk baca front/back cover dan ekstraksi metadata via Gemini Vision.'
                    : 'N8N_BASE_URL belum diatur. Set n8n dan Gemini workflow terlebih dahulu.',
            ],
            'text' => [
                'provider' => 'gemini-via-n8n',
                'enabled' => $n8nConfigured,
                'base_url' => $n8nBaseUrl,
                'model' => config('services.gemini.model', 'gemini-2.5-flash'),
                'status_label' => $n8nConfigured ? 'Aktif' : 'Belum siap',
                'note' => 'Dipakai untuk terjemahan dan enrichment metadata via Gemini via n8n.',
            ],
            'websearch' => [
                'provider' => 'tavily+gemini',
                'enabled' => $websearchEnabled && $n8nConfigured,
                'base_url' => $this->clean((string) $this->settingsService->get('ai.websearch.tavily_base_url', config('services.tavily.base_url', 'https://api.tavily.com'))),
                'model' => config('services.gemini.model', 'gemini-2.5-flash'),
                'status_label' => ($websearchEnabled && $n8nConfigured) ? 'Aktif' : 'Mati',
                'note' => ($websearchEnabled && $n8nConfigured)
                    ? 'Dipakai untuk cari dan ringkas informasi dari web via Tavily + Gemini.'
                    : 'Websearch tidak aktif. Scan tetap jalan tanpa enrichment deskripsi internet.',
            ],
        ];
    }

    public function recommendedScanMode(): string
    {
        $configured = strtolower((string) $this->settingsService->get('ai.scan.default_mode', config('services.ai_runtime.default_scan_mode', 'auto')));
        if (in_array($configured, ['simple', 'full'], true)) {
            return $configured;
        }

        $hasWebsearch = $this->settingsService->getBool('ai.websearch.enabled', (bool) config('services.websearch.enabled', false))
            && $this->clean((string) $this->settingsService->get('ai.websearch.tavily_api_key', config('services.tavily.api_key'))) !== null;

        return $hasWebsearch ? 'full' : 'simple';
    }

    public function diagnostics(): array
    {
        $summary = $this->runtimeSummary();
        $cacheKey = 'ai_runtime:diagnostics:' . sha1(json_encode([
            'n8n' => $summary['vision']['base_url'] ?? null,
            'websearch' => $summary['websearch']['base_url'] ?? null,
            'models' => [
                $summary['vision']['model'] ?? null,
                $summary['text']['model'] ?? null,
            ],
            'websearch_enabled' => $summary['websearch']['enabled'] ?? false,
        ]));

        return cache()->remember($cacheKey, now()->addMinutes(self::DIAGNOSTICS_CACHE_MINUTES), function () use ($summary): array {
            return [
                'n8n' => $this->checkN8n($summary),
                'websearch' => $this->checkWebsearch($summary),
                'queue_worker' => $this->checkQueueWorker(),
            ];
        });
    }

    public function ensureVisionRuntimeAvailable(): ?string
    {
        $summary = $this->runtimeSummary();
        if (! ($summary['vision']['enabled'] ?? false)) {
            return 'Runtime vision belum siap. Periksa N8N_BASE_URL.';
        }

        $n8nStatus = $this->diagnostics()['n8n'] ?? null;
        if (($n8nStatus['status'] ?? null) !== 'ok') {
            return 'Server n8n belum bisa dijangkau. ' . ($n8nStatus['detail'] ?? 'Periksa koneksi ke n8n.');
        }

        return null;
    }

    private function checkN8n(array $summary): array
    {
        $baseUrl = $summary['vision']['base_url'] ?? null;
        if (! is_string($baseUrl) || trim($baseUrl) === '') {
            return [
                'service' => 'n8n',
                'status' => 'disabled',
                'endpoint' => null,
                'detail' => 'N8N_BASE_URL belum diatur.',
            ];
        }

        $apiKey = $this->clean((string) $this->settingsService->get('ai.n8n.api_key', config('services.n8n.api_key', '')));

        try {
            $http = Http::connectTimeout(5)
                ->timeout(10)
                ->acceptJson();

            if (!config('services.ai_scan.tls_verify', true)) {
                $http = $http->withoutVerifying();
            }

            if ($apiKey) {
                $http = $http->withHeader('X-N8N-API-KEY', $apiKey);
            }

            $response = $http->get(rtrim($baseUrl, '/') . '/healthz');
        } catch (ConnectionException|RequestException $e) {
            return [
                'service' => 'n8n',
                'status' => 'error',
                'endpoint' => $baseUrl,
                'detail' => $e->getMessage(),
            ];
        }

        return [
            'service' => 'n8n',
            'status' => 'ok',
            'endpoint' => $baseUrl,
            'detail' => 'n8n tersambung. Gemini ready via webhook.',
        ];
    }

    private function checkWebsearch(array $summary): array
    {
        $baseUrl = $summary['websearch']['base_url'] ?? null;
        $enabled = (bool) ($summary['websearch']['enabled'] ?? false);

        if (! $enabled || ! is_string($baseUrl) || trim($baseUrl) === '') {
            return [
                'service' => 'Tavily',
                'status' => 'disabled',
                'endpoint' => $baseUrl,
                'detail' => 'Tavily dimatikan atau API key belum diisi.',
            ];
        }

        $apiKey = $this->clean((string) $this->settingsService->get('ai.websearch.tavily_api_key', config('services.tavily.api_key')));
        if ($apiKey === null) {
            return [
                'service' => 'Tavily',
                'status' => 'disabled',
                'endpoint' => rtrim($baseUrl, '/') . '/search',
                'detail' => 'Tavily API key belum diisi.',
            ];
        }

        return [
            'service' => 'Tavily',
            'status' => 'ok',
            'endpoint' => rtrim($baseUrl, '/') . '/search',
            'detail' => 'Tavily siap dipakai.',
        ];
    }

    private function checkQueueWorker(): array
    {
        $queueConnection = (string) config('queue.default', 'database');
        $workerCommand = 'php artisan queue:work ' . $queueConnection . ' --queue=ai-scan --tries=1 --sleep=1';

        try {
            $pendingCount = DB::table('jobs')->where('queue', 'ai-scan')->count();
            $oldestPending = DB::table('jobs')
                ->where('queue', 'ai-scan')
                ->orderBy('available_at')
                ->value('available_at');
        } catch (QueryException) {
            return [
                'service' => 'Queue Worker',
                'status' => 'disabled',
                'endpoint' => 'queue:' . $queueConnection,
                'detail' => 'Tabel jobs belum tersedia atau queue database belum siap.',
                'pending_jobs' => 0,
                'oldest_wait_seconds' => 0,
                'command' => $workerCommand,
            ];
        }

        if ((int) $pendingCount === 0) {
            return [
                'service' => 'Queue Worker',
                'status' => 'ok',
                'endpoint' => 'queue:' . $queueConnection,
                'detail' => 'Tidak ada job ai-scan yang menunggu. Worker tampak idle.',
                'pending_jobs' => 0,
                'oldest_wait_seconds' => 0,
                'command' => $workerCommand,
            ];
        }

        $waitSeconds = 0;
        if (is_numeric($oldestPending)) {
            $waitSeconds = max(0, now()->timestamp - (int) $oldestPending);
        }

        return [
            'service' => 'Queue Worker',
            'status' => $waitSeconds >= 20 ? 'warning' : 'ok',
            'endpoint' => 'queue:' . $queueConnection,
            'detail' => $waitSeconds >= 20
                ? "Ada {$pendingCount} job ai-scan menunggu {$waitSeconds} detik. Worker kemungkinan belum jalan."
                : "Ada {$pendingCount} job ai-scan di antrian dan masih dalam ambang tunggu normal.",
            'pending_jobs' => (int) $pendingCount,
            'oldest_wait_seconds' => $waitSeconds,
            'command' => $workerCommand,
        ];
    }

    private function clean(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
