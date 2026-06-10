<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TavilySearchService
{
    public function __construct(
        private readonly AppSettingsService $settingsService
    ) {
    }

    /**
     * @return array<int, array{title:string, url:string, snippet:string|null}>
     */
    public function search(string $query, int $limit = 3): array
    {
        $apiKey = $this->clean((string) $this->settingsService->get('ai.websearch.tavily_api_key', config('services.tavily.api_key')));
        if ($apiKey === null) {
            return [];
        }

        $baseUrl = rtrim((string) $this->settingsService->get('ai.websearch.tavily_base_url', config('services.tavily.base_url', 'https://api.tavily.com')), '/');
        $timeout = max(5, $this->settingsService->getInt('ai.websearch.tavily_timeout', (int) config('services.tavily.timeout', 15)));
        $allowedDomains = array_values(array_filter(array_map(
            fn (string $value): string => strtolower(trim($value)),
            explode(',', (string) $this->settingsService->get('ai.websearch.allowed_domains', implode(',', (array) config('services.websearch.allowed_domains', []))))
        )));

        $payload = [
            'query' => $query,
            'search_depth' => 'basic',
            'max_results' => max(1, min(10, $limit)),
            'include_answer' => false,
            'include_images' => false,
            'include_raw_content' => false,
        ];

        if ($allowedDomains !== []) {
            $payload['include_domains'] = $allowedDomains;
        }

        try {
            $http = Http::timeout($timeout)->acceptJson();
            if (!config('services.ai_scan.tls_verify', true)) {
                $http = $http->withoutVerifying();
            }
            $response = $http->withToken($apiKey)
                ->post($baseUrl . '/search', $payload)
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            Log::warning('websearch.tavily.failed', ['error' => $e->getMessage()]);

            return [];
        }

        $results = $response->json('results');
        if (! is_array($results)) {
            return [];
        }

        $rows = [];
        foreach ($results as $result) {
            if (! is_array($result)) {
                continue;
            }

            $url = $this->clean($result['url'] ?? null);
            $title = $this->clean($result['title'] ?? null);
            $snippet = $this->clean($result['content'] ?? null);

            if (! $url || ! $title) {
                continue;
            }

            $rows[] = [
                'title' => $title,
                'url' => $url,
                'snippet' => $snippet,
            ];
        }

        return $rows;
    }

    private function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
