<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MetadataEnrichmentService
{
    /**
     * Merge metadata using preference layers.
     *
     * @param array $vision The book identification result (from Stage 1)
     * @param array|null $google Google Books data (from Stage 2)
     * @param array|null $ol OpenLibrary data (from Stage 2)
     * @return array
     */
    public function enrich(array $vision, ?array $google, ?array $ol): array
    {
        $merged = [
            'title' => $google['title'] ?? $ol['title'] ?? $vision['title'] ?? null,
            'author' => $google['author'] ?? $ol['author'] ?? $vision['author'] ?? null,
            'isbn' => $google['isbn'] ?? $ol['isbn'] ?? $vision['isbn'] ?? null,
            'publisher' => $google['publisher'] ?? $ol['publisher'] ?? $vision['publisher_hint'] ?? $vision['publisher'] ?? null,
            'published_year' => $google['published_year'] ?? $ol['published_year'] ?? $vision['published_year'] ?? null,
            'description' => $vision['description_back_cover'] ?? $vision['description'] ?? $google['description'] ?? $ol['description'] ?? null,
            'category' => $google['category'] ?? $ol['category'] ?? $vision['category'] ?? null,
            'cover_url' => $google['cover_url'] ?? $ol['cover_url'] ?? null,
            'language' => $google['language'] ?? $ol['language'] ?? 'id',
            'source_url' => $google['source_url'] ?? $ol['source_url'] ?? null,
        ];

        // Prefer longer descriptions between Google and OpenLibrary if vision description is not set
        if (empty($vision['description_back_cover']) && empty($vision['description'])) {
            $descGoogle = $google['description'] ?? '';
            $descOL = $ol['description'] ?? '';
            if (strlen((string) $descOL) > strlen((string) $descGoogle) && strlen((string) $descOL) > 100) {
                $merged['description'] = $descOL;
            }
        }

        return $merged;
    }

    /**
     * Calculate dynamic weighted confidence score.
     * Formula: Title (35%), Author (25%), ISBN (20%), Publisher (10%), Description (10%).
     * Weights are dynamically normalized based on fields present in vision signals.
     *
     * @param array $vision The book identification result (from Stage 1)
     * @param array $merged The merged/enriched metadata
     * @param string $source The metadata source (e.g. google_books, openlibrary, cache, gemini_vision)
     * @return int
     */
    public function calculateConfidence(array $vision, array $merged, string $source): int
    {
        // If no external source was found, base confidence is 60
        if ($source === 'gemini_vision') {
            return 60;
        }

        $weights = [];
        $scores = [];

        // Title (35%)
        $vTitle = $vision['title'] ?? '';
        $mTitle = $merged['title'] ?? '';
        if (!empty($vTitle)) {
            $weights['title'] = 35;
            $scores['title'] = $this->computeSimilarity($vTitle, $mTitle);
        }

        // Author (25%)
        $vAuthor = $vision['author'] ?? '';
        $mAuthor = $merged['author'] ?? '';
        if (!empty($vAuthor)) {
            $weights['author'] = 25;
            $scores['author'] = $this->computeSimilarity($vAuthor, $mAuthor);
        }

        // ISBN (20%)
        $vIsbn = $vision['isbn'] ?? '';
        $mIsbn = $merged['isbn'] ?? '';
        $isbnMatch = 0.0;
        $hasRealIsbnMatch = false;
        if (!empty($vIsbn) && !empty($mIsbn)) {
            $cleanV = preg_replace('/[^0-9Xx]/', '', $vIsbn);
            $cleanM = preg_replace('/[^0-9Xx]/', '', $mIsbn);
            if ($cleanV !== '' && $cleanM !== '') {
                if ($cleanV === $cleanM) {
                    $isbnMatch = 1.0;
                    $hasRealIsbnMatch = true;
                }
            }
        }
        if (!empty($vIsbn)) {
            $weights['isbn'] = 20;
            $scores['isbn'] = $isbnMatch;
        }

        // Publisher (10%)
        $vPublisher = $vision['publisher_hint'] ?? $vision['publisher'] ?? '';
        $mPublisher = $merged['publisher'] ?? '';
        if (!empty($vPublisher)) {
            $weights['publisher'] = 10;
            $scores['publisher'] = $this->computeSimilarity($vPublisher, $mPublisher);
        }

        // Description (10%)
        $vDesc = $vision['description_back_cover'] ?? $vision['description'] ?? '';
        $mDesc = $merged['description'] ?? '';
        if (!empty($vDesc)) {
            $weights['description'] = 10;
            $scores['description'] = $this->computeSimilarity($vDesc, $mDesc);
        }

        // If no weights are active, fallback to 50
        if (empty($weights)) {
            return 50;
        }

        // Calculate weighted average
        $totalWeight = array_sum($weights);
        $weightedSum = 0;
        foreach ($weights as $key => $weight) {
            $weightedSum += $scores[$key] * $weight;
        }

        // Normalize to 0-100 scale
        $finalScore = (int) round(($weightedSum / $totalWeight) * 100);

        // Cap at 89 if no real/exact ISBN match is present to prevent auto-approve
        if (!$hasRealIsbnMatch && $finalScore > 89) {
            $finalScore = 89;
        }

        return max(50, min(100, $finalScore));
    }

    /**
     * Compute string similarity based on Levenshtein distance.
     *
     * @param string $a
     * @param string $b
     * @return float
     */
    private function computeSimilarity(string $a, string $b): float
    {
        $a = strtolower(trim($a));
        $b = strtolower(trim($b));
        if ($a === '' || $b === '') return 0.0;
        if ($a === $b) return 1.0;

        $maxLen = max(strlen($a), strlen($b));
        if ($maxLen === 0) return 0.0;

        return 1.0 - (levenshtein($a, $b) / $maxLen);
    }
}
