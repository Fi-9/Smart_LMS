<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WebBookDescriptionService
{
    public function __construct(
        private readonly TavilySearchService $searchService,
        private readonly WebContentExtractorService $contentExtractorService,
        private readonly OllamaService $ollamaService,
        private readonly AppSettingsService $settingsService
    ) {
    }

    public function resolve(?string $title, ?string $author): ?array
    {
        return $this->resolveForDomains($title, $author, null);
    }

    /**
     * @param array<int, string> $domains
     */
    public function resolveForDomains(?string $title, ?string $author, ?array $domains): ?array
    {
        $cleanTitle = $this->clean($title);
        $cleanAuthor = $this->clean($author);

        if (! $this->isEnabled() || ! $cleanTitle) {
            return null;
        }

        $domainKey = $domains ? implode(',', array_map('strtolower', $domains)) : 'default';
        $cacheKey = 'book_lookup:websearch:' . sha1(strtolower($cleanTitle . '|' . ($cleanAuthor ?? '') . '|' . $domainKey));
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && array_key_exists('hit', $cached)) {
            return ($cached['hit'] ?? false) ? ($cached['data'] ?? null) : null;
        }

        $maxResults = max(1, min(5, $this->settingsService->getInt('ai.websearch.max_results', (int) config('services.websearch.max_results', 3))));
        $query = $cleanTitle . ($cleanAuthor ? ' ' . $cleanAuthor : '') . ' sinopsis penerbit resmi';
        if (is_array($domains) && $domains !== []) {
            $siteParts = array_map(fn (string $d): string => 'site:' . trim($d), $domains);
            $query .= ' ' . implode(' OR ', $siteParts);
        }
        Log::info('websearch.started', [
            'title' => $cleanTitle,
            'author' => $cleanAuthor,
            'query' => $query,
            'max_results' => $maxResults,
            'domains' => $domains,
        ]);

        $results = $this->searchService->search($query, $maxResults * 2);
        $filtered = $this->filterAllowedDomains($results, $domains);
        $filtered = $this->filterRelevantByTitle($filtered, $cleanTitle);
        $selected = array_slice($filtered, 0, $maxResults);

        Log::info('websearch.filtered', [
            'title' => $cleanTitle,
            'raw_count' => count($results),
            'filtered_count' => count($filtered),
            'selected_count' => count($selected),
        ]);

        if ($selected === []) {
            $this->cacheResult($cacheKey, null);
            Log::info('websearch.rejected', ['reason' => 'no_allowed_results', 'title' => $cleanTitle]);
            return null;
        }

        $contexts = [];
        foreach ($selected as $result) {
            $text = $this->contentExtractorService->extractMainText($result['url']);
            $contexts[] = [
                'url' => $result['url'],
                'title' => $result['title'],
                'snippet' => $result['snippet'] ?? null,
                'text' => $text,
            ];
        }

        try {
            $extracted = $this->ollamaService->extractBookDescriptionFromWeb(
                $cleanTitle,
                $cleanAuthor,
                $contexts
            );
        } catch (\RuntimeException $e) {
            Log::warning('websearch.description.ollama_failed', ['error' => $e->getMessage()]);
            $extracted = null;
        }

        $description = is_array($extracted) ? $this->clean($extracted['description'] ?? null) : null;
        $sourceUrl = is_array($extracted) ? $this->clean($extracted['source_url'] ?? null) : null;
        $confidence = is_array($extracted) && is_numeric($extracted['confidence'] ?? null)
            ? (float) $extracted['confidence']
            : 0.0;

        if (! $description || $confidence < 0.7) {
            $this->cacheResult($cacheKey, null);
            Log::info('websearch.rejected', [
                'reason' => ! $description ? 'empty_description' : 'low_confidence',
                'title' => $cleanTitle,
                'confidence' => $confidence,
            ]);
            return null;
        }

        $resolved = [
            'description' => $description,
            'source_url' => $sourceUrl,
            'source' => 'websearch',
            'confidence' => $confidence,
        ];

        $this->cacheResult($cacheKey, $resolved);

        Log::info('websearch.description.accepted', [
            'title' => $cleanTitle,
            'author' => $cleanAuthor,
            'source_url' => $sourceUrl,
            'confidence' => $confidence,
            'domains' => $domains,
        ]);

        return $resolved;
    }

    /**
     * @param array<int, array{title:string, url:string, snippet:?string}> $results
     * @param array<int, string>|null $preferredDomains
     * @return array<int, array{title:string, url:string, snippet:?string}>
     */
    private function filterAllowedDomains(array $results, ?array $preferredDomains): array
    {
        $allowedDomains = $preferredDomains ?: array_map(
            fn (string $domain) => strtolower(trim($domain)),
            array_filter(array_map('trim', explode(',', (string) $this->settingsService->get(
                'ai.websearch.allowed_domains',
                implode(',', (array) config('services.websearch.allowed_domains', []))
            ))))
        );

        if ($allowedDomains === []) {
            return $results;
        }

        return array_values(array_filter($results, function (array $row) use ($allowedDomains): bool {
            $host = parse_url($row['url'], PHP_URL_HOST);
            if (! is_string($host) || $host === '') {
                return false;
            }

            $host = strtolower($host);
            foreach ($allowedDomains as $domain) {
                if ($domain === '') {
                    continue;
                }

                if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                    return true;
                }
            }

            return false;
        }));
    }

    private function isEnabled(): bool
    {
        return $this->settingsService->getBool('ai.websearch.enabled', (bool) config('services.websearch.enabled', false))
            && $this->clean((string) $this->settingsService->get('ai.websearch.tavily_api_key', config('services.tavily.api_key'))) !== null;
    }

    /**
     * @param array<int, array{title:string, url:string, snippet:?string}> $results
     * @return array<int, array{title:string, url:string, snippet:?string}>
     */
    private function filterRelevantByTitle(array $results, string $title): array
    {
        return array_values(array_filter($results, function (array $row) use ($title): bool {
            $haystack = trim(($row['title'] ?? '') . ' ' . ($row['snippet'] ?? ''));
            if ($haystack === '') {
                return false;
            }

            return $this->titleSimilarity($title, $haystack) >= 0.45;
        }));
    }

    private function cacheResult(string $key, ?array $result): void
    {
        $hit = is_array($result);
        Cache::put(
            $key,
            ['hit' => $hit, 'data' => $result],
            now()->addMinutes($hit
                ? max(1, (int) config('services.websearch.cache_minutes', 180))
                : max(1, (int) config('services.websearch.cache_miss_minutes', 20)))
        );
    }

    private function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        return $trimmed !== '' ? $trimmed : null;
    }

    private function titleSimilarity(string $a, string $b): float
    {
        $normalize = static function (string $value): string {
            $value = strtolower($value);
            $value = preg_replace('/[^a-z0-9\s]/', ' ', $value) ?? $value;
            $value = preg_replace('/\s+/', ' ', $value) ?? $value;

            return trim($value);
        };

        $left = $normalize($a);
        $right = $normalize($b);

        if ($left === '' || $right === '') {
            return 0.0;
        }

        similar_text($left, $right, $percent);

        return $percent / 100;
    }
}
