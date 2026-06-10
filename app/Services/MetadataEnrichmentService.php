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
            'publisher' => $google['publisher'] ?? $ol['publisher'] ?? null,
            'published_year' => $google['published_year'] ?? $ol['published_year'] ?? $vision['published_year'] ?? null,
            'description' => $google['description'] ?? $ol['description'] ?? null,
            'category' => $google['category'] ?? $ol['category'] ?? null,
            'cover_url' => $ol['cover_url'] ?? $google['cover_url'] ?? null,
            'language' => $google['language'] ?? $ol['language'] ?? 'id',
            'source_url' => $google['source_url'] ?? $ol['source_url'] ?? null,
        ];

        // Prefer longer descriptions between Google and OpenLibrary
        $descGoogle = $google['description'] ?? '';
        $descOL = $ol['description'] ?? '';
        if (strlen((string) $descOL) > strlen((string) $descGoogle) && strlen((string) $descOL) > 100) {
            $merged['description'] = $descOL;
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
        // Check if there is an exact/validated ISBN match
        $vIsbn = $vision['isbn'] ?? '';
        $mIsbn = $merged['isbn'] ?? '';
        $hasRealIsbnMatch = false;
        if (!empty($vIsbn) && !empty($mIsbn)) {
            $cleanV = preg_replace('/[^0-9Xx]/', '', $vIsbn);
            $cleanM = preg_replace('/[^0-9Xx]/', '', $mIsbn);
            if ($cleanV !== '' && $cleanM !== '' && $cleanV === $cleanM) {
                $hasRealIsbnMatch = true;
            }
        }

        // Base confidence score based on source
        $baseSource = $merged['source'] ?? $source;
        $baseConfidence = match ($baseSource) {
            'google_books+openlibrary' => 95,
            'google_books' => 85,
            'openlibrary' => 80,
            'websearch', 'tavily' => 65,
            'gemini_vision' => 50,
            'cache' => 100,
            default => 85,
        };

        $score = $baseConfidence;
        if ($hasRealIsbnMatch) {
            // Boost score for having an exact ISBN match to enable auto-approval
            if ($score === 95) {
                $score += 5; // Google+OL: 100
            } elseif ($score === 85) {
                $score += 10; // Google Only: 95
            } elseif ($score === 80) {
                $score += 15; // OL Only: 95
            } elseif ($score === 65) {
                $score += 15; // Tavily Only: 80
            }
        }

        // Apply a penalty if title/author from vision signals differs significantly from the merged metadata
        $penalty = 0;
        $vTitle = $vision['title'] ?? '';
        $mTitle = $merged['title'] ?? '';
        if (!empty($vTitle) && !empty($mTitle)) {
            $titleSim = $this->computeSimilarity($vTitle, $mTitle);
            if ($titleSim < 0.75) {
                $penalty += (int) round((0.75 - $titleSim) * 20);
            }
        }

        $vAuthor = $vision['author'] ?? '';
        $mAuthor = $merged['author'] ?? '';
        if (!empty($vAuthor) && !empty($mAuthor)) {
            $authorSim = $this->computeSimilarity($vAuthor, $mAuthor);
            if ($authorSim < 0.70) {
                $penalty += (int) round((0.70 - $authorSim) * 15);
            }
        }

        $finalScore = $score - $penalty;

        // Cap at 89 if no exact/validated ISBN match is present to prevent auto-approve
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
