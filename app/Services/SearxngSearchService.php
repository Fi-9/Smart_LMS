<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SearxngSearchService
{
    /**
     * @return array<int, array{title:string, url:string, snippet:string|null}>
     */
    public function search(string $query, int $limit = 3): array
    {
        $baseUrl = trim((string) config('services.websearch.base_url', ''));
        if ($baseUrl === '') {
            return [];
        }

        $endpoint = rtrim($baseUrl, '/');
        if (! str_ends_with(strtolower($endpoint), '/search')) {
            $endpoint .= '/search';
        }

        $timeout = max(3, (int) config('services.websearch.timeout', 12));

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get($endpoint, [
                    'q' => $query,
                    'format' => 'json',
                    'language' => 'id',
                    'safesearch' => 1,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('websearch.searxng.connection_failed', ['error' => $e->getMessage()]);

            return [];
        }

        if (! $response->ok()) {
            Log::warning('websearch.searxng.non_ok', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

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

            if (count($rows) >= $limit) {
                break;
            }
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

