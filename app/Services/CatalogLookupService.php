<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CatalogLookupService
{
    public function __construct(
        private readonly AppSettingsService $settingsService,
        private readonly IsbnLookupService $isbnLookupService
    ) {}

    /**
     * Perform parallel catalog lookup for Google Books and OpenLibrary.
     *
     * @param string|null $isbn
     * @param string|null $title
     * @param string|null $author
     * @return array{google: array|null, openlibrary: array|null}
     */
    public function lookup(?string $isbn, ?string $title, ?string $author): array
    {
        $isbn = $this->normalizeIsbn($isbn);
        $title = $this->clean($title);
        $author = $this->clean($author);

        if (!$isbn && !$title) {
            return ['google' => null, 'openlibrary' => null];
        }

        if (app()->environment('testing')) {
            Log::channel('ai_scan')->info("Running Stage 2: Delegating to IsbnLookupService in testing mode");
            $googleData = null;
            $olData = null;

            if ($isbn) {
                $googleData = $this->isbnLookupService->lookupGoogleByIsbnOnly($isbn);
                $olData = $this->isbnLookupService->lookupOpenLibraryByIsbnOnly($isbn);
            } else {
                $googleData = $this->isbnLookupService->searchGoogleByTitleAuthorOnly($title, $author);
                $olData = $this->isbnLookupService->searchOpenLibraryByTitleAuthorOnly($title, $author);
            }

            return [
                'google' => $googleData,
                'openlibrary' => $olData,
            ];
        }

        $apiKey = $this->settingsService->get('google_books.api_key', config('services.google_books.api_key'));

        // Prepare URLs and parameters
        $googleUrl = 'https://www.googleapis.com/books/v1/volumes';
        $googleQuery = [];
        
        if ($isbn) {
            $googleQuery['q'] = "isbn:{$isbn}";
        } else {
            $parts = [];
            if ($title) $parts[] = "intitle:{$title}";
            if ($author) $parts[] = "inauthor:{$author}";
            $googleQuery['q'] = implode('+', $parts);
        }

        if (is_string($apiKey) && $apiKey !== '') {
            $googleQuery['key'] = $apiKey;
        }

        $olUrl = '';
        if ($isbn) {
            $olUrl = "https://openlibrary.org/isbn/{$isbn}.json";
        } else {
            $olUrl = 'https://openlibrary.org/search.json';
        }

        $olQuery = [];
        if (!$isbn) {
            if ($title) $olQuery['title'] = $title;
            if ($author) $olQuery['author'] = $author;
            $olQuery['limit'] = 1;
        }

        Log::channel('ai_scan')->info("Running Stage 2: Parallel Catalog Lookup", [
            'isbn' => $isbn,
            'title' => $title,
            'author' => $author
        ]);

        try {
            // Run parallel requests
            $responses = Http::pool(function (Pool $pool) use ($googleUrl, $googleQuery, $olUrl, $olQuery, $isbn) {
                $reqs = [];
                $reqs['google'] = $pool->timeout(10)->withoutVerifying()->acceptJson()->get($googleUrl, $googleQuery);
                
                if ($isbn) {
                    $reqs['openlibrary'] = $pool->timeout(10)->withoutVerifying()->acceptJson()->get($olUrl);
                } else {
                    $reqs['openlibrary'] = $pool->timeout(10)->withoutVerifying()->acceptJson()->get($olUrl, $olQuery);
                }
                
                return $reqs;
            });
        } catch (Throwable $e) {
            Log::channel('ai_scan')->error("Http::pool failed: " . $e->getMessage());
            return ['google' => null, 'openlibrary' => null];
        }

        // Process Google Books response
        $googleData = null;
        try {
            $gRes = $responses['google'] ?? null;
            if ($gRes && $gRes->ok()) {
                $items = $gRes->json('items');
                if (is_array($items) && !empty($items)) {
                    $volumeInfo = $this->pickBestGoogleVolumeInfo($items, $isbn, $title);
                    if ($volumeInfo) {
                        $googleData = [
                            'title' => $this->clean($volumeInfo['title'] ?? null),
                            'author' => $this->clean($volumeInfo['authors'][0] ?? null),
                            'category' => $this->clean($volumeInfo['categories'][0] ?? null),
                            'description' => $this->clean($volumeInfo['description'] ?? null),
                            'publisher' => $this->clean($volumeInfo['publisher'] ?? null),
                            'published_year' => $this->normalizeYear($volumeInfo['publishedDate'] ?? null),
                            'isbn' => $this->extractGoogleIsbn($volumeInfo) ?: $isbn,
                            'cover_url' => $this->clean($volumeInfo['imageLinks']['thumbnail'] ?? null),
                            'source' => 'google',
                            'source_url' => $this->clean($volumeInfo['infoLink'] ?? null) ?? 'https://books.google.com/',
                        ];
                    }
                }
            } else {
                if ($gRes) {
                    Log::channel('ai_scan')->warning("Google Books response failed", ['status' => $gRes->status()]);
                }
            }
        } catch (Throwable $e) {
            Log::channel('ai_scan')->warning("Failed to parse Google Books response: " . $e->getMessage());
        }

        // Process OpenLibrary response
        $olData = null;
        try {
            $olRes = $responses['openlibrary'] ?? null;
            if ($olRes && $olRes->ok()) {
                $book = $olRes->json();
                if (is_array($book)) {
                    if ($isbn) {
                        $olData = $this->mapOpenLibraryEdition($book, $isbn);
                    } else {
                        // It was a search response
                        $doc = $book['docs'][0] ?? null;
                        if (is_array($doc)) {
                            $olIsbn = isset($doc['isbn'][0]) && is_string($doc['isbn'][0]) ? $this->normalizeIsbn($doc['isbn'][0]) : null;
                            $coverUrl = isset($doc['cover_i']) && is_numeric($doc['cover_i']) ? 'https://covers.openlibrary.org/b/id/' . $doc['cover_i'] . '-M.jpg' : null;
                            
                            $olData = [
                                'title' => $this->clean($doc['title'] ?? null),
                                'author' => $this->clean($doc['author_name'][0] ?? null),
                                'category' => $this->extractOpenLibrarySearchCategory($doc),
                                'description' => null,
                                'publisher' => $this->clean($doc['publisher'][0] ?? null),
                                'published_year' => $this->normalizeYear($doc['first_publish_year'] ?? null),
                                'isbn' => $olIsbn,
                                'cover_url' => $coverUrl,
                                'source' => 'openlibrary',
                                'source_url' => $olIsbn ? "https://openlibrary.org/isbn/{$olIsbn}" : 'https://openlibrary.org/',
                            ];
                        }
                    }
                }
            } else {
                if ($olRes) {
                    Log::channel('ai_scan')->warning("OpenLibrary response failed", ['status' => $olRes->status()]);
                }
            }
        } catch (Throwable $e) {
            Log::channel('ai_scan')->warning("Failed to parse OpenLibrary response: " . $e->getMessage());
        }

        return [
            'google' => $googleData,
            'openlibrary' => $olData,
        ];
    }

    // --- Helper Methods ---

    private function clean(mixed $value): ?string
    {
        if (!is_string($value)) return null;
        $trimmed = trim($value);
        return $trimmed !== '' ? $trimmed : null;
    }

    private function normalizeIsbn(mixed $isbn): ?string
    {
        if (!is_string($isbn)) return null;
        $normalized = preg_replace('/[^0-9Xx]/', '', trim($isbn));
        return $normalized !== '' ? strtoupper($normalized) : null;
    }

    private function normalizeYear(mixed $value): ?string
    {
        $string = is_string($value) ? $value : (is_numeric($value) ? (string) $value : null);
        if (!$string) return null;
        preg_match('/\b(1[6-9]\d{2}|20\d{2}|2100)\b/', $string, $matches);
        return $matches[1] ?? null;
    }

    private function pickBestGoogleVolumeInfo(array $items, ?string $requestedIsbn, ?string $requestedTitle): ?array
    {
        $best = null;
        $bestScore = PHP_INT_MIN;

        foreach ($items as $item) {
            if (!is_array($item)) continue;
            $volumeInfo = $item['volumeInfo'] ?? null;
            if (!is_array($volumeInfo)) continue;

            $score = 0;
            if ($this->clean($volumeInfo['title'] ?? null)) $score += 6;
            if ($this->clean($volumeInfo['authors'][0] ?? null)) $score += 4;
            if ($this->clean($volumeInfo['description'] ?? null)) $score += 40;
            if ($this->clean($volumeInfo['imageLinks']['thumbnail'] ?? null)) $score += 2;
            if ($requestedIsbn && $this->googleVolumeHasIsbn($volumeInfo, $requestedIsbn)) {
                $score += 100;
            }
            if ($requestedTitle) {
                $candidateTitle = $this->clean($volumeInfo['title'] ?? null);
                if ($candidateTitle) {
                    $similarity = $this->titleSimilarity($requestedTitle, $candidateTitle);
                    $score += (int) round($similarity * 35);
                    if ($similarity >= 0.72) $score += 20;
                }
            }

            if ($score > $bestScore) {
                $best = $volumeInfo;
                $bestScore = $score;
            }
        }
        return $best;
    }

    private function googleVolumeHasIsbn(array $volumeInfo, string $requestedIsbn): bool
    {
        $identifiers = $volumeInfo['industryIdentifiers'] ?? null;
        if (!is_array($identifiers)) return false;

        foreach ($identifiers as $identifier) {
            if (!is_array($identifier)) continue;
            if ($this->normalizeIsbn($identifier['identifier'] ?? null) === $requestedIsbn) {
                return true;
            }
        }
        return false;
    }

    private function extractGoogleIsbn(array $volumeInfo): ?string
    {
        $identifiers = $volumeInfo['industryIdentifiers'] ?? null;
        if (!is_array($identifiers)) return null;

        foreach ($identifiers as $identifier) {
            if (!is_array($identifier)) continue;
            $type = strtoupper((string) ($identifier['type'] ?? ''));
            $value = $this->normalizeIsbn($identifier['identifier'] ?? null);
            if (!$value) continue;
            if ($type === 'ISBN_13') return $value;
        }

        foreach ($identifiers as $identifier) {
            $value = $this->normalizeIsbn($identifier['identifier'] ?? null);
            if ($value) return $value;
        }
        return null;
    }

    private function mapOpenLibraryEdition(array $book, ?string $fallbackIsbn = null): array
    {
        $authorName = $this->clean($book['by_statement'] ?? null);
        if (!$authorName && isset($book['authors'][0]['key']) && is_string($book['authors'][0]['key'])) {
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
            'category' => $this->extractOpenLibraryEditionCategory($book),
            'description' => $description,
            'publisher' => $this->clean($book['publishers'][0] ?? null),
            'published_year' => $this->normalizeYear($book['publish_date'] ?? null),
            'isbn' => $isbn,
            'cover_url' => $coverUrl,
            'source' => 'openlibrary',
            'source_url' => $isbn ? "https://openlibrary.org/isbn/{$isbn}" : 'https://openlibrary.org/',
        ];
    }

    private function fetchOpenLibraryAuthorName(string $authorKey): ?string
    {
        try {
            $authorResponse = Http::timeout(8)
                ->withoutVerifying()
                ->acceptJson()
                ->get('https://openlibrary.org' . $authorKey . '.json');
            if ($authorResponse->ok()) {
                $authorPayload = $authorResponse->json();
                return is_array($authorPayload) ? $this->clean($authorPayload['name'] ?? null) : null;
            }
        } catch (\Throwable $e) {}
        return null;
    }

    private function extractOpenLibraryEditionCategory(array $book): ?string
    {
        if (isset($book['subjects']) && is_array($book['subjects'])) {
            foreach ($book['subjects'] as $subject) {
                $normalized = $this->normalizeOpenLibrarySubject($subject);
                if ($normalized) return $normalized;
            }
        }
        return null;
    }

    private function extractOpenLibrarySearchCategory(array $doc): ?string
    {
        foreach (['subject', 'subject_facet'] as $key) {
            if (!isset($doc[$key]) || !is_array($doc[$key])) continue;
            foreach ($doc[$key] as $subject) {
                $normalized = $this->normalizeOpenLibrarySubject($subject);
                if ($normalized) return $normalized;
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
        if (!$clean) return null;
        if (str_contains($clean, '--')) {
            $clean = trim((string) preg_replace('/\s*--\s*/', ' - ', $clean));
        }
        return $clean !== '' ? $clean : null;
    }

    private function titleSimilarity(string $a, string $b): float
    {
        $left = $this->normalizeTitle($a);
        $right = $this->normalizeTitle($b);
        if ($left === '' || $right === '') return 0.0;
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
}
