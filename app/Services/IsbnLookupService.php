<?php

namespace App\Services;

use App\Support\CategoryMapper;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IsbnLookupService
{
    public function __construct(
        private readonly WebBookDescriptionService $webDescriptionService,
        private readonly \App\Services\AppSettingsService $settingsService
    ) {
    }

    public function lookupGoogleByIsbnOnly(string $isbn): ?array
    {
        $normalizedIsbn = $this->normalizeIsbn($isbn);
        if (! $normalizedIsbn) {
            return null;
        }

        $cacheKey = 'book_lookup:google:isbn:' . $normalizedIsbn;

        return $this->cachedNullableLookup($cacheKey, fn () => $this->lookupGoogleByIsbn($normalizedIsbn), 'google');
    }

    public function searchGoogleByTitleAuthorOnly(?string $title, ?string $author): ?array
    {
        $cleanTitle = $this->clean($title);
        $cleanAuthor = $this->clean($author);

        if (! $cleanTitle) {
            return null;
        }

        $cacheKey = 'book_lookup:google:title_author:' . sha1(strtolower(($cleanTitle ?? '') . '|' . ($cleanAuthor ?? '')));

        return $this->cachedNullableLookup($cacheKey, function () use ($cleanTitle, $cleanAuthor) {
            $exact = $this->lookupGoogleByTitleAuthor($cleanTitle, $cleanAuthor);
            if ($exact && $this->isCandidateRelevant($exact, $cleanTitle, 0.48)) {
                return $exact;
            }

            $titleOnly = $this->lookupGoogleByTitleAuthor($cleanTitle, null);
            if ($titleOnly && $this->isCandidateRelevant($titleOnly, $cleanTitle, 0.48)) {
                return $titleOnly;
            }

            return null;
        }, 'google');
    }

    public function lookup(string $isbn): ?array
    {
        $metadata = $this->lookupByIsbn($isbn);

        if (! $metadata) {
            return null;
        }

        return [
            'title' => $metadata['title'] ?? null,
            'author' => $metadata['author'] ?? null,
            'category' => $metadata['category'] ?? null,
            'description' => $metadata['description'] ?? null,
            'publisher' => $metadata['publisher'] ?? null,
            'published_year' => $metadata['published_year'] ?? null,
            'isbn' => $metadata['isbn'] ?? $isbn,
            'cover_url' => $metadata['cover_url'] ?? null,
            'source' => $metadata['source'] ?? null,
            'source_url' => $metadata['source_url'] ?? null,
        ];
    }

    public function lookupOpenLibraryByIsbn(string $isbn): ?array
    {
        $normalizedIsbn = $this->normalizeIsbn($isbn);
        if (! $normalizedIsbn) {
            return null;
        }

        return $this->lookupOpenLibraryByIsbnInternal($normalizedIsbn);
    }

    public function lookupOpenLibraryByIsbnOnly(string $isbn): ?array
    {
        return $this->lookupOpenLibraryByIsbn($isbn);
    }

    public function lookupOpenLibraryByTitleAuthor(?string $title, ?string $author): ?array
    {
        $cleanTitle = $this->clean($title);
        $cleanAuthor = $this->clean($author);

        if (! $cleanTitle && ! $cleanAuthor) {
            return null;
        }

        return $this->lookupOpenLibraryByTitleAuthorInternal($cleanTitle, $cleanAuthor);
    }

    public function searchOpenLibraryByTitleAuthorOnly(?string $title, ?string $author): ?array
    {
        return $this->lookupOpenLibraryByTitleAuthor($title, $author);
    }

    public function lookupByIsbn(string $isbn): ?array
    {
        $normalizedIsbn = $this->normalizeIsbn($isbn);
        if (! $normalizedIsbn) {
            return null;
        }

        $cacheKey = 'book_lookup:isbn:' . $normalizedIsbn;

        return $this->cachedNullableLookup($cacheKey, function () use ($normalizedIsbn) {
            $google = $this->lookupGoogleByIsbn($normalizedIsbn);
            $openLibrary = $this->lookupOpenLibraryByIsbnInternal($normalizedIsbn);

            $primary = null;
            if ($google) {
                $primary = $this->mergeMissingMetadataFields($google, $openLibrary);
            } elseif ($openLibrary) {
                $primary = $openLibrary;
            }

            if ($primary && $this->clean($primary['description'] ?? null) !== null) {
                return $primary;
            }

            // Fallback to Tavily (Web Search) if both failed OR description is missing
            $web = $this->webDescriptionService->resolveByIsbn($normalizedIsbn);
            if ($web) {
                if ($primary) {
                    return $this->mergeMissingMetadataFields($primary, $web);
                }
                return $web;
            }

            return $primary;
        });
    }

    public function searchByTitleAuthor(?string $title, ?string $author): ?array
    {
        $cleanTitle = $this->clean($title);
        $cleanAuthor = $this->clean($author);

        // Guardrail: jangan lakukan lookup metadata tanpa judul.
        // Mode "author-only" sangat rawan salah match untuk buku niche.
        if (! $cleanTitle) {
            return null;
        }

        $cacheKey = 'book_lookup:title_author:' . sha1(strtolower(($cleanTitle ?? '') . '|' . ($cleanAuthor ?? '')));

        return $this->cachedNullableLookup($cacheKey, function () use ($cleanTitle, $cleanAuthor) {
            $candidates = [];
            $minSimilarity = 0.48;

            $googleExact = $this->lookupGoogleByTitleAuthor($cleanTitle, $cleanAuthor);
            if ($googleExact && $this->isCandidateRelevant($googleExact, $cleanTitle, $minSimilarity)) {
                $candidates[] = $googleExact;
            }

            if ($cleanTitle) {
                $googleTitleOnly = $this->lookupGoogleByTitleAuthor($cleanTitle, null);
                if ($googleTitleOnly && $this->isCandidateRelevant($googleTitleOnly, $cleanTitle, $minSimilarity)) {
                    $candidates[] = $googleTitleOnly;
                }
            }

            $openLibraryExact = $this->lookupOpenLibraryByTitleAuthorInternal($cleanTitle, $cleanAuthor);
            if ($openLibraryExact && $this->isCandidateRelevant($openLibraryExact, $cleanTitle, $minSimilarity)) {
                $candidates[] = $openLibraryExact;
            }

            if ($cleanTitle) {
                $openLibraryTitleOnly = $this->lookupOpenLibraryByTitleAuthorInternal($cleanTitle, null);
                if ($openLibraryTitleOnly && $this->isCandidateRelevant($openLibraryTitleOnly, $cleanTitle, $minSimilarity)) {
                    $candidates[] = $openLibraryTitleOnly;
                }
            }

            $best = null;
            if ($candidates !== []) {
                $best = array_shift($candidates);
                foreach ($candidates as $candidate) {
                    if (is_array($best)) {
                        $best = $this->mergeMissingMetadataFields($best, $candidate);
                    }
                }
            }

            $web = $this->webDescriptionService->resolve($cleanTitle, $cleanAuthor);
            if ($best && $web) {
                return $this->mergeMissingMetadataFields($best, $web);
            }

            return $best ?: $web;
        }, 'google_openlibrary');
    }

    private function mergeMissingMetadataFields(array $primary, ?array $secondary): array
    {
        if (! is_array($secondary)) {
            return $primary;
        }

        foreach (['category', 'description', 'publisher', 'published_year', 'cover_url', 'source_url'] as $field) {
            if ($this->clean($primary[$field] ?? null) === null && $this->clean($secondary[$field] ?? null) !== null) {
                $primary[$field] = $secondary[$field];
            }
        }

        return $primary;
    }

    private function cachedNullableLookup(string $key, \Closure $resolver, string $provider = 'google_openlibrary'): ?array
    {
        $cached = Cache::get($key);
        if (is_array($cached) && array_key_exists('hit', $cached)) {
            $cachedData = ($cached['hit'] ?? false) ? ($cached['data'] ?? null) : null;
            $missingDescription = is_array($cachedData)
                && $this->clean($cachedData['description'] ?? null) === null;

            if (! $missingDescription) {
                Log::info('book_lookup.cache', [
                    'provider' => $provider,
                    'cache_key' => $key,
                    'cache_status' => 'hit',
                    'resolved' => (bool) ($cached['hit'] ?? false),
                ]);

                return $cachedData;
            }

            Log::info('book_lookup.cache', [
                'provider' => $provider,
                'cache_key' => $key,
                'cache_status' => 'refresh_missing_description',
                'resolved' => (bool) ($cached['hit'] ?? false),
            ]);
        }

        $result = $resolver();
        $hit = is_array($result);

        $ttlMinutes = 15;
        if ($hit) {
            $score = $this->calculateCompleteness($result);
            if ($score > 90) {
                $ttlMinutes = 24 * 60;
            } elseif ($score >= 70) {
                $ttlMinutes = 6 * 60;
            } elseif ($score >= 50) {
                $ttlMinutes = 60;
            } else {
                $ttlMinutes = 15;
            }
        } else {
            $ttlMinutes = $this->cacheMissMinutes();
        }

        Cache::put(
            $key,
            ['hit' => $hit, 'data' => $hit ? $result : null],
            now()->addMinutes($ttlMinutes)
        );

        Log::info('book_lookup.cache', [
            'provider' => $provider,
            'cache_key' => $key,
            'cache_status' => 'miss',
            'resolved' => $hit,
            'source' => $hit ? ($result['source'] ?? null) : null,
        ]);

        return $result;
    }

    private function calculateCompleteness(?array $merged): int
    {
        if (!$merged) {
            return 0;
        }

        $fields = [
            'title' => $merged['title'] ?? null,
            'author' => $merged['author'] ?? null,
            'isbn' => $merged['isbn'] ?? null,
            'cover' => $merged['cover_url'] ?? null,
            'description' => $merged['description'] ?? null,
            'category' => $merged['category'] ?? null,
            'publisher' => $merged['publisher'] ?? null,
            'published_year' => $merged['published_year'] ?? null,
        ];

        $filled = 0;
        foreach ($fields as $name => $value) {
            if ($value !== null && $value !== '' && $value !== 'Unknown') {
                $filled++;
            }
        }

        return (int) round(($filled / count($fields)) * 100);
    }

    private function cacheMinutes(): int
    {
        return max(1, (int) config('services.google_books.cache_minutes', 120));
    }

    private function cacheMissMinutes(): int
    {
        return max(1, (int) config('services.google_books.cache_miss_minutes', 1));
    }

    private function lookupGoogleByIsbn(string $isbn): ?array
    {
        return $this->lookupGoogle(['q' => "isbn:{$isbn}"]);
    }

    private function lookupGoogleByTitleAuthor(?string $title, ?string $author): ?array
    {
        $parts = [];
        if ($title) {
            $parts[] = "intitle:{$title}";
        }
        if ($author) {
            $parts[] = "inauthor:{$author}";
        }

        if ($parts === []) {
            return null;
        }

        return $this->lookupGoogle(['q' => implode('+', $parts)]);
    }

    private function lookupGoogle(array $query): ?array
    {
        $apiKey = $this->settingsService->get('google_books.api_key', config('services.google_books.api_key'));
        if (is_string($apiKey) && $apiKey !== '') {
            $query['key'] = $apiKey;
        }

        try {
            $http = Http::timeout(10)->acceptJson();
            if (!config('services.ai_scan.tls_verify', true)) {
                $http = $http->withoutVerifying();
            }
            $response = $http->get('https://www.googleapis.com/books/v1/volumes', $query);
        } catch (ConnectionException) {
            Log::warning('Book lookup: failed connecting to Google Books API.', ['query' => $query]);

            return null;
        }

        if (! $response->ok()) {
            Log::warning('Book lookup: Google Books API returned non-OK response.', [
                'query' => $query,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $items = $response->json('items');
        if (! is_array($items)) {
            return null;
        }

        $requestedIsbn = $this->extractRequestedIsbnFromQuery($query['q'] ?? null);
        $requestedTitle = $this->extractRequestedTitleFromQuery($query['q'] ?? null);
        $volumeInfo = $this->pickBestGoogleVolumeInfo($items, $requestedIsbn, $requestedTitle);
        if (! $volumeInfo) {
            return null;
        }

        $isbn = $this->extractGoogleIsbn($volumeInfo);

        return [
            'title' => $this->clean($volumeInfo['title'] ?? null),
            'author' => $this->clean($volumeInfo['authors'][0] ?? null),
            'category' => CategoryMapper::toIndonesian(
                $this->clean($volumeInfo['categories'][0] ?? null)
            ),
            'description' => $this->clean($volumeInfo['description'] ?? null),
            'publisher' => $this->clean($volumeInfo['publisher'] ?? null),
            'published_year' => $this->normalizeYear($volumeInfo['publishedDate'] ?? null),
            'isbn' => $isbn,
            'cover_url' => $this->upgradeGoogleCoverUrl(
                $this->clean($volumeInfo['imageLinks']['thumbnail'] ?? null)
            ),
            'source' => 'google',
            'source_url' => $this->clean($volumeInfo['infoLink'] ?? null) ?? 'https://books.google.com/',
        ];
    }

    private function extractRequestedIsbnFromQuery(mixed $query): ?string
    {
        if (! is_string($query)) {
            return null;
        }

        if (! preg_match('/isbn:([0-9Xx-]+)/', $query, $matches)) {
            return null;
        }

        return $this->normalizeIsbn($matches[1] ?? null);
    }

    private function extractRequestedTitleFromQuery(mixed $query): ?string
    {
        if (! is_string($query)) {
            return null;
        }

        if (! preg_match('/intitle:([^+]+)/i', $query, $matches)) {
            return null;
        }

        return $this->clean(str_replace('+', ' ', urldecode((string) ($matches[1] ?? ''))));
    }

    /**
     * @param array<int, mixed> $items
     */
    private function pickBestGoogleVolumeInfo(array $items, ?string $requestedIsbn, ?string $requestedTitle): ?array
    {
        $best = null;
        $bestScore = PHP_INT_MIN;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $volumeInfo = $item['volumeInfo'] ?? null;
            if (! is_array($volumeInfo)) {
                continue;
            }

            $score = 0;
            if ($this->clean($volumeInfo['title'] ?? null)) {
                $score += 6;
            }
            if ($this->clean($volumeInfo['authors'][0] ?? null)) {
                $score += 4;
            }
            if ($this->clean($volumeInfo['description'] ?? null)) {
                $score += 40;
            }
            if ($this->clean($volumeInfo['imageLinks']['thumbnail'] ?? null)) {
                $score += 2;
            }
            if ($requestedIsbn && $this->googleVolumeHasIsbn($volumeInfo, $requestedIsbn)) {
                $score += 100;
            }
            if ($requestedTitle) {
                $candidateTitle = $this->clean($volumeInfo['title'] ?? null);
                if ($candidateTitle) {
                    $similarity = $this->titleSimilarity($requestedTitle, $candidateTitle);
                    $score += (int) round($similarity * 35);
                    if ($similarity >= 0.72) {
                        $score += 20;
                    }
                }
            }

            if ($score > $bestScore) {
                $best = $volumeInfo;
                $bestScore = $score;
            }
        }

        if ($requestedTitle && is_array($best)) {
            $bestTitle = $this->clean($best['title'] ?? null);
            if ($bestTitle && $this->titleSimilarity($requestedTitle, $bestTitle) < 0.52) {
                return null;
            }
        }

        return $best;
    }

    private function googleVolumeHasIsbn(array $volumeInfo, string $requestedIsbn): bool
    {
        $identifiers = $volumeInfo['industryIdentifiers'] ?? null;
        if (! is_array($identifiers)) {
            return false;
        }

        foreach ($identifiers as $identifier) {
            if (! is_array($identifier)) {
                continue;
            }

            if ($this->normalizeIsbn($identifier['identifier'] ?? null) === $requestedIsbn) {
                return true;
            }
        }

        return false;
    }

    private function extractGoogleIsbn(array $volumeInfo): ?string
    {
        $identifiers = $volumeInfo['industryIdentifiers'] ?? null;
        if (! is_array($identifiers)) {
            return null;
        }

        foreach ($identifiers as $identifier) {
            if (! is_array($identifier)) {
                continue;
            }

            $type = strtoupper((string) ($identifier['type'] ?? ''));
            $value = $this->normalizeIsbn($identifier['identifier'] ?? null);

            if (! $value) {
                continue;
            }

            if ($type === 'ISBN_13') {
                return $value;
            }
        }

        foreach ($identifiers as $identifier) {
            $value = $this->normalizeIsbn($identifier['identifier'] ?? null);
            if ($value) {
                return $value;
            }
        }

        return null;
    }

    private function lookupOpenLibraryByIsbnInternal(string $isbn): ?array
    {
        try {
            $http = Http::timeout(10)->acceptJson();
            if (!config('services.ai_scan.tls_verify', true)) {
                $http = $http->withoutVerifying();
            }
            $response = $http->get("https://openlibrary.org/isbn/{$isbn}.json");
        } catch (ConnectionException) {
            Log::warning('Book lookup: failed connecting to OpenLibrary ISBN API.', ['isbn' => $isbn]);

            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $book = $response->json();
        if (! is_array($book)) {
            return null;
        }

        return $this->mapOpenLibraryEdition($book, $isbn);
    }

    private function lookupOpenLibraryByTitleAuthorInternal(?string $title, ?string $author): ?array
    {
        $query = [];
        if ($title) {
            $query['title'] = $title;
        }
        if ($author) {
            $query['author'] = $author;
        }
        $query['limit'] = 1;

        try {
            $http = Http::timeout(10)->acceptJson();
            if (!config('services.ai_scan.tls_verify', true)) {
                $http = $http->withoutVerifying();
            }
            $response = $http->get('https://openlibrary.org/search.json', $query);
        } catch (ConnectionException) {
            Log::warning('Book lookup: failed connecting to OpenLibrary search API.', ['query' => $query]);

            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $doc = $response->json('docs.0');
        if (! is_array($doc)) {
            return null;
        }

        $isbn = null;
        if (isset($doc['isbn'][0]) && is_string($doc['isbn'][0])) {
            $isbn = $this->normalizeIsbn($doc['isbn'][0]);
        }

        $coverUrl = null;
        if (isset($doc['cover_i']) && is_numeric($doc['cover_i'])) {
            $coverUrl = 'https://covers.openlibrary.org/b/id/' . $doc['cover_i'] . '-M.jpg';
        }

        return [
            'title' => $this->clean($doc['title'] ?? null),
            'author' => $this->clean($doc['author_name'][0] ?? null),
            'category' => CategoryMapper::toIndonesian(
                $this->extractOpenLibrarySearchCategory($doc)
            ),
            'description' => null,
            'publisher' => $this->clean($doc['publisher'][0] ?? null),
            'published_year' => $this->normalizeYear($doc['first_publish_year'] ?? null),
            'isbn' => $isbn,
            'cover_url' => $coverUrl,
            'source' => 'openlibrary',
            'source_url' => $this->buildOpenLibrarySourceUrl($isbn),
        ];
    }

    private function mapOpenLibraryEdition(array $book, ?string $fallbackIsbn = null): array
    {
        $authorName = $this->clean($book['by_statement'] ?? null);
        if (! $authorName && isset($book['authors'][0]['key']) && is_string($book['authors'][0]['key'])) {
            $authorName = $this->fetchOpenLibraryAuthorName($book['authors'][0]['key']);
        }

        $coverUrl = null;
        if (isset($book['covers'][0]) && is_numeric($book['covers'][0])) {
            $coverUrl = 'https://covers.openlibrary.org/b/id/' . $book['covers'][0] . '-M.jpg';
        }

        $description = null;
        if (isset($book['description'])) {
            if (is_string($book['description'])) {
                $description = $this->clean($book['description']);
            } elseif (is_array($book['description'])) {
                $description = $this->clean($book['description']['value'] ?? null);
            }
        }

        $isbn = $fallbackIsbn;
        if (isset($book['isbn_13'][0]) && is_string($book['isbn_13'][0])) {
            $isbn = $this->normalizeIsbn($book['isbn_13'][0]);
        }

        return [
            'title' => $this->clean($book['title'] ?? null),
            'author' => $authorName,
            'category' => CategoryMapper::toIndonesian(
                $this->extractOpenLibraryEditionCategory($book)
            ),
            'description' => $description,
            'publisher' => $this->clean($book['publishers'][0] ?? null),
            'published_year' => $this->normalizeYear($book['publish_date'] ?? null),
            'isbn' => $isbn,
            'cover_url' => $coverUrl,
            'source' => 'openlibrary',
            'source_url' => $this->buildOpenLibrarySourceUrl($isbn),
        ];
    }

    private function buildOpenLibrarySourceUrl(?string $isbn): string
    {
        if ($isbn) {
            return "https://openlibrary.org/isbn/{$isbn}";
        }

        return 'https://openlibrary.org/';
    }

    private function fetchOpenLibraryAuthorName(string $authorKey): ?string
    {
        try {
            $http = Http::timeout(8)->acceptJson();
            if (!config('services.ai_scan.tls_verify', true)) {
                $http = $http->withoutVerifying();
            }
            $authorResponse = $http->get('https://openlibrary.org' . $authorKey . '.json');
        } catch (ConnectionException) {
            return null;
        }

        if (! $authorResponse->ok()) {
            return null;
        }

        $authorPayload = $authorResponse->json();

        return is_array($authorPayload) ? $this->clean($authorPayload['name'] ?? null) : null;
    }

    private function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function normalizeIsbn(mixed $isbn): ?string
    {
        if (! is_string($isbn)) {
            return null;
        }

        $normalized = preg_replace('/[^0-9Xx]/', '', trim($isbn));

        return $normalized !== '' ? strtoupper($normalized) : null;
    }

    private function normalizeYear(mixed $value): ?string
    {
        $string = is_string($value) ? $value : (is_numeric($value) ? (string) $value : null);
        if (! $string) {
            return null;
        }

        preg_match('/\b(1[6-9]\d{2}|20\d{2}|2100)\b/', $string, $matches);

        return $matches[1] ?? null;
    }

    private function extractOpenLibraryEditionCategory(array $book): ?string
    {
        if (isset($book['subjects']) && is_array($book['subjects'])) {
            foreach ($book['subjects'] as $subject) {
                $normalized = $this->normalizeOpenLibrarySubject($subject);
                if ($normalized) {
                    return $normalized;
                }
            }
        }

        return null;
    }

    private function extractOpenLibrarySearchCategory(array $doc): ?string
    {
        foreach (['subject', 'subject_facet'] as $key) {
            if (! isset($doc[$key]) || ! is_array($doc[$key])) {
                continue;
            }

            foreach ($doc[$key] as $subject) {
                $normalized = $this->normalizeOpenLibrarySubject($subject);
                if ($normalized) {
                    return $normalized;
                }
            }
        }

        return null;
    }

    private function normalizeOpenLibrarySubject(mixed $subject): ?string
    {
        if (is_array($subject)) {
            $subject = $subject['name'] ?? $subject['value'] ?? null;
        }

        $clean = $this->clean($subject);
        if (! $clean) {
            return null;
        }

        if (str_contains($clean, '--')) {
            $clean = trim((string) preg_replace('/\s*--\s*/', ' - ', $clean));
        }

        return $clean !== '' ? $clean : null;
    }

    private function isCandidateRelevant(array $candidate, ?string $requestedTitle, float $minSimilarity): bool
    {
        if (! $requestedTitle) {
            return true;
        }

        $candidateTitle = $this->clean($candidate['title'] ?? null);
        if (! $candidateTitle) {
            return false;
        }

        $requestedNorm = $this->normalizeTitle($requestedTitle);
        $candidateNorm = $this->normalizeTitle($candidateTitle);

        if ($requestedNorm === '' || $candidateNorm === '') {
            return false;
        }

        if (str_contains($candidateNorm, $requestedNorm) || str_contains($requestedNorm, $candidateNorm)) {
            return true;
        }

        $similarity = $this->titleSimilarity($requestedTitle, $candidateTitle);
        if ($similarity >= $minSimilarity) {
            return true;
        }

        $requestedTokens = array_values(array_filter(explode(' ', $requestedNorm), fn (string $token): bool => $token !== ''));
        $candidateTokens = array_values(array_filter(explode(' ', $candidateNorm), fn (string $token): bool => $token !== ''));
        if ($requestedTokens === [] || $candidateTokens === []) {
            return false;
        }

        $intersection = array_intersect($requestedTokens, $candidateTokens);
        $overlap = count($intersection) / max(1, count(array_unique($requestedTokens)));

        return $overlap >= 0.5 || (count($requestedTokens) <= 3 && $overlap >= 0.34);
    }

    private function titleSimilarity(string $a, string $b): float
    {
        $left = $this->normalizeTitle($a);
        $right = $this->normalizeTitle($b);

        if ($left === '' || $right === '') {
            return 0.0;
        }

        similar_text($left, $right, $percent);

        return $percent / 100;
    }

    private function normalizeTitle(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * Upgrade Google Books thumbnail URL to higher resolution.
     * Replaces zoom=N with zoom=0 for max resolution, removes curl edge effect, forces HTTPS.
     */
    private function upgradeGoogleCoverUrl(?string $url): ?string
    {
        if (!$url) return null;
        $url = preg_replace('/zoom=\d+/', 'zoom=0', $url) ?? $url;
        $url = str_replace('&edge=curl', '', $url);
        return str_replace('http://', 'https://', $url);
    }
}

