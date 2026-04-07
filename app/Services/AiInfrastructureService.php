<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class AiInfrastructureService
{
    private const DIAGNOSTICS_CACHE_MINUTES = 1;

    public function __construct(
        private readonly AppSettingsService $settingsService
    ) {
    }

    public function runtimeSummary(): array
    {
        $ollamaBaseUrl = $this->clean((string) $this->settingsService->get('ai.ollama.base_url', config('services.ollama.base_url', '')));
        $visionModel = $this->resolveOllamaModel('vision');
        $textModel = $this->resolveOllamaModel('text');
        $webModel = $this->resolveOllamaModel('web');
        $websearchBaseUrl = $this->clean((string) $this->settingsService->get('ai.websearch.tavily_base_url', config('services.tavily.base_url', 'https://api.tavily.com')));
        $websearchEnabled = $this->settingsService->getBool('ai.websearch.enabled', (bool) config('services.websearch.enabled', false));
        $tavilyConfigured = $this->clean((string) $this->settingsService->get('ai.websearch.tavily_api_key', config('services.tavily.api_key'))) !== null;

        return [
            'profile' => $this->clean((string) config('services.ai_runtime.profile', 'local-ollama')) ?? 'local-ollama',
            'recommended_scan_mode' => $this->recommendedScanMode(),
            'vision' => [
                'provider' => 'ollama',
                'enabled' => $ollamaBaseUrl !== null && $visionModel !== null,
                'base_url' => $ollamaBaseUrl,
                'model' => $visionModel,
                'status_label' => $ollamaBaseUrl !== null && $visionModel !== null ? 'Aktif' : 'Belum siap',
                'note' => $visionModel
                    ? 'Dipakai untuk baca front/back cover dan ekstraksi sinyal buku.'
                    : 'Model vision belum diatur.',
            ],
            'text' => [
                'provider' => 'ollama',
                'enabled' => $ollamaBaseUrl !== null && $textModel !== null,
                'base_url' => $ollamaBaseUrl,
                'model' => $textModel,
                'status_label' => $ollamaBaseUrl !== null && $textModel !== null ? 'Aktif' : 'Belum siap',
                'note' => 'Dipakai untuk terjemahan dan fallback deskripsi lokal.',
            ],
            'websearch' => [
                'provider' => 'tavily+ollama',
                'enabled' => $websearchEnabled && $tavilyConfigured,
                'base_url' => $websearchBaseUrl,
                'model' => $webModel,
                'status_label' => ($websearchEnabled && $tavilyConfigured) ? 'Aktif' : 'Mati',
                'note' => ($websearchEnabled && $tavilyConfigured)
                    ? 'Dipakai untuk ambil hasil pencarian Tavily lalu diringkas model text.'
                    : 'Mode lokal aktif. Scan tetap jalan tanpa websearch, tetapi enrichment deskripsi internet dimatikan.',
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
            'ollama' => $summary['vision']['base_url'] ?? null,
            'websearch' => $summary['websearch']['base_url'] ?? null,
            'models' => [
                $summary['vision']['model'] ?? null,
                $summary['text']['model'] ?? null,
                $summary['websearch']['model'] ?? null,
            ],
            'websearch_enabled' => $summary['websearch']['enabled'] ?? false,
        ]));

        return cache()->remember($cacheKey, now()->addMinutes(self::DIAGNOSTICS_CACHE_MINUTES), function () use ($summary): array {
            return [
                'ollama' => $this->checkOllama($summary),
                'websearch' => $this->checkWebsearch($summary),
            ];
        });
    }

    public function ensureVisionRuntimeAvailable(): ?string
    {
        $summary = $this->runtimeSummary();
        if (! ($summary['vision']['enabled'] ?? false)) {
            return 'Runtime vision belum siap. Periksa OLLAMA_BASE_URL dan model vision.';
        }

        $ollamaStatus = $this->diagnostics()['ollama'] ?? null;
        if (($ollamaStatus['status'] ?? null) !== 'ok') {
            return 'Server vision Ollama belum bisa dijangkau. ' . ($ollamaStatus['detail'] ?? 'Periksa koneksi ke endpoint Ollama.');
        }

        return null;
    }

    public function resolveOllamaModel(string $task): ?string
    {
        $task = strtolower($task);

        $model = match ($task) {
            'vision' => $this->clean((string) $this->settingsService->get('ai.ollama.vision_model', config('services.ollama.vision_model', ''))),
            'text' => $this->clean((string) $this->settingsService->get('ai.ollama.text_model', config('services.ollama.text_model', ''))),
            'web' => $this->clean((string) $this->settingsService->get('ai.ollama.web_model', config('services.ollama.web_model', ''))),
            default => null,
        };

        if ($model !== null) {
            return $model;
        }

        return $this->clean((string) $this->settingsService->get('ai.ollama.model', config('services.ollama.model', '')));
    }

    private function checkOllama(array $summary): array
    {
        $baseUrl = $summary['vision']['base_url'] ?? null;
        if (! is_string($baseUrl) || trim($baseUrl) === '') {
            return [
                'service' => 'Ollama',
                'status' => 'disabled',
                'endpoint' => null,
                'detail' => 'OLLAMA_BASE_URL belum diatur.',
            ];
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(10)
                ->acceptJson()
                ->get(rtrim($baseUrl, '/') . '/api/tags')
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            return [
                'service' => 'Ollama',
                'status' => 'error',
                'endpoint' => $baseUrl,
                'detail' => $e->getMessage(),
            ];
        }

        $models = collect($response->json('models', []))
            ->map(fn (array $model): ?string => $this->clean((string) ($model['name'] ?? '')))
            ->filter()
            ->values()
            ->all();

        $requiredModels = array_values(array_unique(array_filter([
            $summary['vision']['model'] ?? null,
            $summary['text']['model'] ?? null,
            $summary['websearch']['model'] ?? null,
        ])));

        $missingModels = array_values(array_filter($requiredModels, fn (string $model): bool => ! in_array($model, $models, true)));

        return [
            'service' => 'Ollama',
            'status' => $missingModels === [] ? 'ok' : 'warning',
            'endpoint' => $baseUrl,
            'detail' => $missingModels === []
                ? 'Endpoint tersambung dan model utama tersedia.'
                : 'Endpoint tersambung, tetapi model berikut belum ada: ' . implode(', ', $missingModels),
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
            'detail' => 'Tavily siap dipakai karena API key sudah diatur.',
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
