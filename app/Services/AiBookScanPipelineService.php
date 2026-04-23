<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AiBookScanPipelineService
{
    public function __construct(
        private readonly OllamaService $ollamaService,
        private readonly IsbnLookupService $isbnLookupService,
        private readonly WebBookDescriptionService $webBookDescriptionService,
        private readonly CoverImageService $coverImageService,
    ) {
    }

    /**
     * @param array<int, UploadedFile> $images
     */
    public function scan(array $images, string $mode = 'full'): array
    {
        $pipelineStart = microtime(true);
        \Illuminate\Support\Facades\Log::channel('ai_scan')->info("=== START AI SCAN ===");

        $visionStart = microtime(true);
        $vision = $this->ollamaService->extractBookSignals($images);
        $visionMs = round((microtime(true) - $visionStart) * 1000);
        \Illuminate\Support\Facades\Log::channel('ai_scan')->info("⏱️ Vision selesai dalam {$visionMs}ms");

        $best = is_array($vision['best'] ?? null) ? $vision['best'] : [];
        $imageSignals = is_array($vision['images'] ?? null) ? $vision['images'] : [];
        $mode = $mode === 'simple' ? 'simple' : 'full';

        $isbn = $this->clean($best['isbn'] ?? null);
        $title = $this->cleanTitleSignal($best['title'] ?? null);
        $author = $this->clean($best['author'] ?? null);
        $category = $this->clean($best['category'] ?? null);
        $descriptionFromVision = $this->clean($best['description'] ?? null);
        $publisherFromVision = $this->clean($best['publisher'] ?? null);

        \Illuminate\Support\Facades\Log::channel('ai_scan')->info("Vision dapet judul: " . ($title ?? 'Tidak ditemukan'));
        \Illuminate\Support\Facades\Log::channel('ai_scan')->info("Vision dapet penulis: " . ($author ?? 'Tidak ditemukan'));
        \Illuminate\Support\Facades\Log::channel('ai_scan')->info("Vision dapet ISBN: " . ($isbn ?? 'Tidak ditemukan'));

        $source = 'ai';
        $metadata = $this->seedMetadataFromVision(
            $title,
            $author,
            $category,
            $descriptionFromVision,
            $isbn,
            $publisherFromVision
        );
        $fieldSources = $this->seedFieldSources($title, $author, $category, $descriptionFromVision, $isbn, $publisherFromVision);
        $lookupTitleCandidates = $this->buildLookupTitleCandidates($title);

        if ($mode === 'full') {
            $providerStart = microtime(true);
            $providerMetadata = $this->resolvePrimaryProviderMetadata($isbn, $lookupTitleCandidates, $title, $author);

            if ($providerMetadata) {
                $metadata = $this->mergeMissingFields($metadata, $providerMetadata);
                $fieldSources = $this->applyProviderFieldSources($fieldSources, $metadata, $providerMetadata);
                if (
                    $this->clean($metadata['title'] ?? null) === null
                    && $this->clean($providerMetadata['title'] ?? null) !== null
                ) {
                    $metadata['title'] = $this->clean($providerMetadata['title'] ?? null);
                    $fieldSources['title'] = $this->normalizeFieldSourceLabel($providerMetadata['source'] ?? null) ?? 'Provider';
                }
                if (
                    $this->clean($metadata['author'] ?? null) === null
                    && $this->clean($providerMetadata['author'] ?? null) !== null
                ) {
                    $metadata['author'] = $this->clean($providerMetadata['author'] ?? null);
                    $fieldSources['author'] = $this->normalizeFieldSourceLabel($providerMetadata['source'] ?? null) ?? 'Provider';
                }
                if (
                    $this->clean($metadata['category'] ?? null) === null
                    && $this->clean($providerMetadata['category'] ?? null) !== null
                ) {
                    $metadata['category'] = $providerMetadata['category'];
                    $fieldSources['category'] = $this->normalizeFieldSourceLabel($providerMetadata['source'] ?? null);
                }
                if (
                    $this->clean($metadata['isbn'] ?? null) === null
                    && $this->clean($providerMetadata['isbn'] ?? null) !== null
                ) {
                    $metadata['isbn'] = $providerMetadata['isbn'];
                    $fieldSources['isbn'] = $this->normalizeFieldSourceLabel($providerMetadata['source'] ?? null);
                }
                $source = $this->clean($providerMetadata['source'] ?? null) ?? $source;
            }

            if ($metadata) {
                $enriched = $this->enrichMissingMetadataFromProviders($metadata, $fieldSources, $isbn, $title, $author, $lookupTitleCandidates);
                $metadata = $enriched['metadata'];
                $fieldSources = $enriched['field_sources'];
                if (($this->clean($metadata['source'] ?? null) === null || ($metadata['source'] ?? null) === 'ai') && $this->clean($enriched['source'] ?? null) !== null) {
                    $metadata['source'] = $enriched['source'];
                }
            }

            if ($this->isOpenLibrarySource($metadata) && $this->hasMissingCatalogFields($metadata)) {
                $googleRetry = $this->retryGoogleAfterOpenLibrary(
                    $lookupTitleCandidates,
                    $this->preferredAuthorForSearch($this->clean($metadata['author'] ?? null), $author)
                );

                if ($googleRetry) {
                    $metadata = $this->mergeMissingFields($metadata, $googleRetry);
                    $fieldSources = $this->applyProviderFieldSources($fieldSources, $metadata, $googleRetry);
                    $source = $this->clean($metadata['source'] ?? null) ?? $source;
                }
            }
            $providerMs = round((microtime(true) - $providerStart) * 1000);
            \Illuminate\Support\Facades\Log::channel('ai_scan')->info("⏱️ Provider lookup selesai dalam {$providerMs}ms");

            $hasVisionDescription = $this->clean($metadata['description'] ?? null) !== null;
            if (! $hasVisionDescription) {
                $webStart = microtime(true);
                $searchTitleCandidates = $this->buildSearchTitleCandidates(
                    $this->clean($metadata['title'] ?? null),
                    $lookupTitleCandidates
                );
                $officialDescription = $this->resolveTrustedWebDescription(
                    $searchTitleCandidates,
                    $this->preferredAuthorForSearch($this->clean($metadata['author'] ?? null), $author)
                );

                if (is_array($officialDescription)) {
                    if ($this->clean($officialDescription['description'] ?? null) !== null) {
                        $metadata['description'] = $officialDescription['description'];
                        $metadata['source_url'] = $officialDescription['source_url'] ?? null;
                        $fieldSources['description'] = $this->normalizeDescriptionSourceLabel($officialDescription['source_url'] ?? null);
                        $metadata['source'] = 'websearch';
                        $source = 'websearch';
                    }
                }
                $webMs = round((microtime(true) - $webStart) * 1000);
                \Illuminate\Support\Facades\Log::channel('ai_scan')->info("⏱️ Websearch selesai dalam {$webMs}ms");
            }

        }

        $totalMs = round((microtime(true) - $pipelineStart) * 1000);
        \Illuminate\Support\Facades\Log::channel('ai_scan')->info("⏱️ TOTAL PIPELINE: {$totalMs}ms ({$mode} mode)");

        $frontImageIndex = $this->resolveFrontImageIndex($imageSignals, $best['front_image_index'] ?? null);
        $storedImages = $this->storeUploads($images);
        $frontCoverOriginal = $frontImageIndex !== null
            ? ($storedImages[$frontImageIndex] ?? null)
            : null;
        $frontCoverSignal = $frontImageIndex !== null
            ? collect($imageSignals)->first(fn ($signal) => (int) ($signal['index'] ?? -1) === $frontImageIndex)
            : null;
        $frontCoverBox = is_array($frontCoverSignal) ? ($frontCoverSignal['cover_box'] ?? null) : null;
        $frontCoverCropped = null; // Disable AI cropping, force using the normalized full image
        $frontCoverNormalized = (! $frontCoverCropped && $frontCoverOriginal)
            ? $this->coverImageService->normalizeCoverFromUpload($frontCoverOriginal)
            : null;
        $fallbackUploadCover = $storedImages[0] ?? null;
        $fallbackUploadCoverNormalized = ($fallbackUploadCover && $fallbackUploadCover !== $frontCoverOriginal)
            ? $this->coverImageService->normalizeCoverFromUpload($fallbackUploadCover)
            : null;

        $titleFinal = $this->clean($metadata['title'] ?? null) ?? $title;
        $authorFinal = $this->clean($metadata['author'] ?? null) ?? $author;
        $categoryFinal = $this->localizeCategoryToIndonesian(
            $this->clean($metadata['category'] ?? null) ?? $category
        );
        $descriptionFinal = $this->localizeDescriptionToIndonesian(
            $this->clean($metadata['description'] ?? null)
        );
        $source = $this->clean($metadata['source'] ?? null) ?? $source;

        return [
            'title' => $titleFinal,
            'author' => $authorFinal,
            'category' => $categoryFinal,
            'description' => $descriptionFinal,
            'publisher' => $this->clean($metadata['publisher'] ?? null),
            'published_year' => $this->clean($metadata['published_year'] ?? null),
            'isbn' => $this->clean($metadata['isbn'] ?? null) ?? $isbn,
            'source_url' => $this->clean($metadata['source_url'] ?? null),
            'cover_url' => $frontCoverCropped
                ?? $frontCoverNormalized
                ?? $frontCoverOriginal
                ?? $this->clean($metadata['cover_url'] ?? null)
                ?? $fallbackUploadCoverNormalized
                ?? $fallbackUploadCover,
            'source' => $source,
            'field_sources' => $this->finalizeFieldSources(
                $fieldSources,
                $frontCoverCropped,
                $frontCoverNormalized,
                $frontCoverOriginal,
                $metadata
            ),
        ];
    }

    private function seedMetadataFromVision(
        ?string $title,
        ?string $author,
        ?string $category,
        ?string $description,
        ?string $isbn,
        ?string $publisher
    ): array {
        return [
            'title' => $title,
            'author' => $author,
            'category' => $category,
            'description' => $description,
            'publisher' => $publisher,
            'published_year' => null,
            'isbn' => $isbn,
            'cover_url' => null,
            'source' => 'ai',
            'source_url' => null,
        ];
    }

    public function enrichMetadata(string $title, ?string $author, ?string $isbn = null): array
    {
        $lookupTitleCandidates = $this->buildLookupTitleCandidates($title);
        
        $primary = $this->seedMetadataFromVision($title, $author, null, null, $isbn, null);
        $fieldSources = $this->seedFieldSources($title, $author, null, null, $isbn, null);

        $enriched = $this->enrichMissingMetadataFromProviders($primary, $fieldSources, $isbn, $title, $author, $lookupTitleCandidates);

        $metadata = $enriched['metadata'];
        
        $metadata['category'] = $this->localizeCategoryToIndonesian($this->clean($metadata['category'] ?? null));
        $metadata['description'] = $this->localizeDescriptionToIndonesian($this->clean($metadata['description'] ?? null));
        
        return [
            'metadata' => $metadata,
            'source' => $enriched['source'],
            'field_sources' => $enriched['field_sources'],
        ];
    }

    private function seedFieldSources(
        ?string $title,
        ?string $author,
        ?string $category,
        ?string $description,
        ?string $isbn,
        ?string $publisher
    ): array {
        return array_filter([
            'title' => $title ? 'AI Cover' : null,
            'author' => $author ? 'AI Cover' : null,
            'category' => $category ? 'AI Cover' : null,
            'description' => $description ? 'Back Cover' : null,
            'isbn' => $isbn ? 'AI Cover' : null,
            'publisher' => $publisher ? 'AI Cover' : null,
        ], fn (mixed $value): bool => is_string($value) && trim($value) !== '');
    }

    private function enrichMissingMetadataFromProviders(array $primary, array $fieldSources, ?string $isbn, ?string $title, ?string $author, array $lookupTitleCandidates): array
    {
        $needsEnrichment = $this->clean($primary['description'] ?? null) === null
            || $this->clean($primary['category'] ?? null) === null
            || $this->clean($primary['author'] ?? null) === null
            || $this->clean($primary['publisher'] ?? null) === null
            || $this->clean($primary['published_year'] ?? null) === null;

        if (! $needsEnrichment) {
            return [
                'metadata' => $primary,
                'field_sources' => $fieldSources,
                'source' => $this->clean($primary['source'] ?? null),
            ];
        }

        $merged = $primary;
        $source = $this->clean($primary['source'] ?? null);

        $google = $this->resolveGoogleMetadata($isbn, $lookupTitleCandidates, $title, $author);
        if (is_array($google)) {
            $merged = $this->mergeMissingFields($merged, $google);
            $fieldSources = $this->applyProviderFieldSources($fieldSources, $merged, $google);
            $source = $this->preferCatalogSource($source, $merged, $google);
        }

        if ($this->hasMissingCatalogFields($merged) || $this->clean($merged['author'] ?? null) === null) {
            $openLibrary = $this->resolveOpenLibraryMetadata($isbn, $lookupTitleCandidates, $title, $author);
            if (is_array($openLibrary)) {
                $merged = $this->mergeMissingFields($merged, $openLibrary);
                $fieldSources = $this->applyProviderFieldSources($fieldSources, $merged, $openLibrary);
                $source = $this->preferCatalogSource($source, $merged, $openLibrary);
            }
        }

        if ($this->clean($merged['description'] ?? null) === null) {
            $web = $this->resolveTrustedWebDescription($lookupTitleCandidates, $author);
            if (is_array($web)) {
                $merged = $this->mergeMissingFields($merged, $web);
                $fieldSources = $this->applyProviderFieldSources($fieldSources, $merged, $web);
                $source = $this->preferCatalogSource($source, $merged, $web);

                if ($this->clean($web['description'] ?? null) !== null) {
                    $merged['source'] = $this->clean($web['source'] ?? null) ?? $merged['source'] ?? $source;
                    $merged['source_url'] = $this->clean($web['source_url'] ?? null) ?? $merged['source_url'] ?? null;
                }
            }
        }

        return [
            'metadata' => $merged,
            'field_sources' => $fieldSources,
            'source' => $source,
        ];
    }

    private function resolvePrimaryProviderMetadata(?string $isbn, array $lookupTitleCandidates, ?string $aiTitle, ?string $aiAuthor): ?array
    {
        $google = $this->resolveGoogleMetadata($isbn, $lookupTitleCandidates, $aiTitle, $aiAuthor);
        if ($google) {
            \Illuminate\Support\Facades\Log::channel('ai_scan')->info("Google Books nemu ISBN: " . ($google['isbn'] ?? 'Tidak ditemukan') . " | Judul: " . ($google['title'] ?? '-'));
            return $google;
        }

        $ol = $this->resolveOpenLibraryMetadata($isbn, $lookupTitleCandidates, $aiTitle, $aiAuthor);
        if ($ol) {
            \Illuminate\Support\Facades\Log::channel('ai_scan')->info("OpenLibrary nemu ISBN: " . ($ol['isbn'] ?? 'Tidak ditemukan') . " | Judul: " . ($ol['title'] ?? '-'));
            return $ol;
        }

        \Illuminate\Support\Facades\Log::channel('ai_scan')->info("Provider API tidak menemukan hasil.");
        return null;
    }

    private function resolveGoogleMetadata(?string $isbn, array $lookupTitleCandidates, ?string $aiTitle, ?string $aiAuthor): ?array
    {
        if ($isbn) {
            $googleByIsbn = $this->isbnLookupService->lookupGoogleByIsbnOnly($isbn);
            if ($googleByIsbn && $this->isMetadataConsistentWithAiSignals($googleByIsbn, $aiTitle, $aiAuthor)) {
                return $googleByIsbn;
            }
        }

        foreach ($lookupTitleCandidates as $lookupTitle) {
            $google = $this->isbnLookupService->searchGoogleByTitleAuthorOnly($lookupTitle, $aiAuthor);
            if ($google && $this->isMetadataConsistentWithAiSignals($google, $aiTitle, $aiAuthor)) {
                return $google;
            }
        }

        return null;
    }

    private function resolveOpenLibraryMetadata(?string $isbn, array $lookupTitleCandidates, ?string $aiTitle, ?string $aiAuthor): ?array
    {
        if ($isbn) {
            $openLibraryByIsbn = $this->isbnLookupService->lookupOpenLibraryByIsbn($isbn);
            if ($openLibraryByIsbn && $this->isMetadataConsistentWithAiSignals($openLibraryByIsbn, $aiTitle, $aiAuthor)) {
                return $openLibraryByIsbn;
            }
        }

        foreach ($lookupTitleCandidates as $lookupTitle) {
            $openLibrary = $this->isbnLookupService->lookupOpenLibraryByTitleAuthor($lookupTitle, $aiAuthor);
            if ($openLibrary && $this->isMetadataConsistentWithAiSignals($openLibrary, $aiTitle, $aiAuthor)) {
                return $openLibrary;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function buildLookupTitleCandidates(?string $title): array
    {
        $cleanTitle = $this->clean($title);
        if (! $cleanTitle) {
            return [];
        }

        $variants = [$cleanTitle];
        $normalized = preg_replace('/[^a-z0-9\s]+/i', ' ', $cleanTitle) ?? $cleanTitle;
        $normalized = trim((string) preg_replace('/\s+/', ' ', $normalized));

        if ($normalized !== '' && ! $this->titleVariantExists($variants, $normalized)) {
            $variants[] = $normalized;
        }

        $segments = preg_split('/\s*[:\-]\s*/', $cleanTitle) ?: [];
        foreach ($segments as $segment) {
            $segment = $this->clean($segment);
            if ($segment && mb_strlen($segment) >= 8 && ! $this->titleVariantExists($variants, $segment)) {
                $variants[] = $segment;
            }
        }

        $stopwords = [
            'the', 'a', 'an', 'of', 'and', 'for', 'to', 'in', 'on', 'with', 'from', 'by',
            'edisi', 'edition', 'vol', 'volume', 'series', 'book',
        ];
        $tokens = preg_split('/\s+/', strtolower($normalized)) ?: [];
        $keywords = [];
        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '' || in_array($token, $stopwords, true) || mb_strlen($token) < 3) {
                continue;
            }
            if (! in_array($token, $keywords, true)) {
                $keywords[] = $token;
            }
            if (count($keywords) >= 7) {
                break;
            }
        }

        if ($keywords !== []) {
            $keywordTitle = implode(' ', $keywords);
            if (! $this->titleVariantExists($variants, $keywordTitle)) {
                $variants[] = $keywordTitle;
            }
        }

        // Partial title search: first 2-3 significant words (for fuzzy matching)
        $significantTokens = array_values(array_filter($tokens, fn ($t) => ! in_array($t, $stopwords, true) && mb_strlen($t) >= 3));
        if (count($significantTokens) >= 2) {
            $partial = implode(' ', array_slice($significantTokens, 0, min(3, count($significantTokens))));
            if (! $this->titleVariantExists($variants, $partial)) {
                $variants[] = $partial;
            }
        }

        // OCR typo correction: common character substitution pairs
        $ocrFixes = $this->generateOcrCorrectionVariants($normalized);
        foreach ($ocrFixes as $fix) {
            if (! $this->titleVariantExists($variants, $fix)) {
                $variants[] = $fix;
            }
        }

        return array_values(array_filter($variants, fn (mixed $value): bool => is_string($value) && trim($value) !== ''));
    }

    /**
     * Generate title variants by swapping commonly confused OCR characters.
     * E.g. "BATT" could be "BAIT", "BATI", etc.
     *
     * @return array<int, string>
     */
    private function generateOcrCorrectionVariants(string $title): array
    {
        // Common OCR confusion pairs (case-insensitive swaps applied to whole title)
        $swapPairs = [
            ['T', 'I'],  // BATT → BAIT, BATI
            ['T', 'L'],  // TL confusion
            ['B', 'R'],  // B/R confusion
            ['O', 'Q'],  // O/Q confusion
            ['O', '0'],  // O/zero
            ['l', '1'],  // l/one
            ['S', '5'],  // S/5
            ['rn', 'm'], // rn often read as m
        ];

        $variants = [];
        $upper = strtoupper($title);

        foreach ($swapPairs as [$a, $b]) {
            $upperA = strtoupper($a);
            $upperB = strtoupper($b);

            if (stripos($upper, $upperA) !== false) {
                // Try replacing each occurrence individually (not all at once)
                $positions = [];
                $offset = 0;
                while (($pos = stripos($upper, $upperA, $offset)) !== false) {
                    $positions[] = $pos;
                    $offset = $pos + strlen($upperA);
                }

                foreach ($positions as $pos) {
                    $variant = substr_replace($title, $b, $pos, strlen($a));
                    $variant = trim($variant);
                    if ($variant !== '' && $variant !== $title) {
                        $variants[] = $variant;
                    }
                    if (count($variants) >= 5) {
                        break 2; // Limit to avoid explosion
                    }
                }
            }
        }

        return $variants;
    }

    /**
     * @param array<int, string> $variants
     */
    private function titleVariantExists(array $variants, string $candidate): bool
    {
        $candidate = strtolower(trim($candidate));

        foreach ($variants as $variant) {
            if (strtolower(trim($variant)) === $candidate) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, string> $titleCandidates
     */
    private function resolveTrustedWebDescription(array $titleCandidates, ?string $author): ?array
    {
        foreach ($titleCandidates as $title) {
            $official = $this->webBookDescriptionService->resolveForDomains(
                $title,
                $author,
                ['gramedia.com', 'gramedia.digital']
            );

            if (is_array($official) && $this->clean($official['description'] ?? null) !== null) {
                \Illuminate\Support\Facades\Log::channel('ai_scan')->info("Tavily dapet deskripsi dari: " . ($official['source_url'] ?? 'Web Resmi'));
                return $official;
            }
        }

        foreach ($titleCandidates as $title) {
            $resolved = $this->webBookDescriptionService->resolve($title, $author);
            if (is_array($resolved) && $this->clean($resolved['description'] ?? null) !== null) {
                \Illuminate\Support\Facades\Log::channel('ai_scan')->info("Tavily dapet deskripsi dari: " . ($resolved['source_url'] ?? 'Web Terpercaya'));
                return $resolved;
            }
        }

        \Illuminate\Support\Facades\Log::channel('ai_scan')->info("Tavily gagal mendapatkan deskripsi dari web.");
        return null;
    }

    private function mergeMissingFields(array $primary, ?array $secondary): array
    {
        if (! is_array($secondary)) {
            return $primary;
        }

        foreach (['title', 'author', 'isbn', 'cover_url', 'category', 'description', 'publisher', 'published_year', 'source_url'] as $field) {
            if ($this->clean($primary[$field] ?? null) === null && $this->clean($secondary[$field] ?? null) !== null) {
                $primary[$field] = $secondary[$field];
            }
        }

        if ($this->clean($primary['description'] ?? null) !== null && $this->clean($primary['source'] ?? null) === null) {
            $primary['source'] = $secondary['source'] ?? null;
        }

        if ($this->clean($primary['description'] ?? null) === null && $this->clean($secondary['description'] ?? null) !== null) {
            if ($this->clean($secondary['source'] ?? null) !== null) {
                $primary['source'] = $secondary['source'];
            }
        }

        if (
            in_array($this->clean($primary['source'] ?? null), [null, 'ai'], true)
            && $this->clean($secondary['source'] ?? null) !== null
        ) {
            $primary['source'] = $secondary['source'];
        }

        return $primary;
    }

    private function preferCatalogSource(?string $currentSource, array $mergedMetadata, array $secondary): ?string
    {
        $secondarySource = $this->clean($secondary['source'] ?? null);
        if ($secondarySource === null) {
            return $currentSource;
        }

        if ($this->clean($mergedMetadata['description'] ?? null) !== null && $this->clean($secondary['description'] ?? null) !== null) {
            return $secondarySource;
        }

        if (in_array($currentSource, [null, 'ai'], true)) {
            return $secondarySource;
        }

        return $currentSource;
    }

    private function applyProviderFieldSources(array $fieldSources, array $mergedMetadata, array $secondary): array
    {
        $provider = $this->normalizeFieldSourceLabel($secondary['source'] ?? null);
        if ($provider === null) {
            return $fieldSources;
        }

        foreach (['title', 'author', 'category', 'isbn', 'publisher', 'published_year', 'cover_url'] as $field) {
            if ($this->clean($secondary[$field] ?? null) !== null && $this->clean($mergedMetadata[$field] ?? null) !== null) {
                if (! isset($fieldSources[$field]) || in_array($field, ['category', 'isbn', 'publisher', 'published_year', 'cover_url'], true)) {
                    $fieldSources[$field] = $provider;
                }
            }
        }

        if ($this->clean($secondary['description'] ?? null) !== null && ! isset($fieldSources['description'])) {
            $fieldSources['description'] = strtolower((string) ($secondary['source'] ?? '')) === 'websearch'
                ? $this->normalizeDescriptionSourceLabel($secondary['source_url'] ?? null)
                : $provider;
        }

        return $fieldSources;
    }

    private function finalizeFieldSources(
        array $fieldSources,
        ?string $frontCoverCropped,
        ?string $frontCoverNormalized,
        ?string $frontCoverOriginal,
        array $metadata
    ): array {
        if ($frontCoverCropped) {
            $fieldSources['cover_url'] = 'AI Cropped Cover';
        } elseif ($frontCoverNormalized || $frontCoverOriginal) {
            $fieldSources['cover_url'] = 'Upload Cover';
        } elseif ($this->clean($metadata['cover_url'] ?? null) !== null) {
            $fieldSources['cover_url'] = $this->normalizeFieldSourceLabel($metadata['source'] ?? null) ?? 'Provider';
        }

        return $fieldSources;
    }

    private function normalizeFieldSourceLabel(mixed $source): ?string
    {
        $value = $this->clean(is_string($source) ? $source : null);
        if (! $value) {
            return null;
        }

        return match (strtolower($value)) {
            'ai' => 'AI Cover',
            'google' => 'Google Books',
            'openlibrary', 'open_library' => 'Open Library',
            'websearch' => 'Web Resmi',
            default => ucwords(str_replace(['_', '-'], ' ', $value)),
        };
    }

    private function normalizeDescriptionSourceLabel(?string $sourceUrl): string
    {
        $host = parse_url((string) $sourceUrl, PHP_URL_HOST);
        $host = is_string($host) ? strtolower($host) : '';

        if ($host !== '') {
            if (str_contains($host, 'gramedia')) {
                return 'Web Resmi Gramedia';
            }

            return 'Web Terpercaya';
        }

        return 'Web Resmi';
    }

    private function localizeDescriptionToIndonesian(?string $description): ?string
    {
        $text = $this->clean($description);
        if (! $text) {
            return null;
        }

        if (! $this->shouldTranslateToIndonesian($text)) {
            \Illuminate\Support\Facades\Log::debug('[Pipeline] Description already in Indonesian, skipping translation', [
                'preview' => mb_substr($text, 0, 100),
            ]);
            return $text;
        }

        \Illuminate\Support\Facades\Log::info('[Pipeline] Translating description to Indonesian', [
            'text_length' => strlen($text),
        ]);

        $cacheKey = 'book_desc:id_translation:' . sha1($text);
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && trim($cached) !== '') {
            return $cached;
        }

        try {
            $translated = $this->ollamaService->translateTextToIndonesian($text);
        } catch (\RuntimeException $e) {
            \Illuminate\Support\Facades\Log::error('[Pipeline] Translation failed, returning original text', [
                'error' => $e->getMessage(),
            ]);
            $translated = null;
        }

        if (! $translated || $this->translationLooksUnchanged($text, $translated) || $this->stillLooksEnglish($translated)) {
            \Illuminate\Support\Facades\Log::warning('[Pipeline] Translation returned empty, using original English text');
            return $text;
        }

        Cache::put($cacheKey, $translated, now()->addHours(24));

        return $translated;
    }

    private function shouldTranslateToIndonesian(string $text): bool
    {
        if (preg_match_all('/\b(the|and|with|from|that|this|your|you|for|into|keep|work|life|creative|rules|daily|today|playing|creating)\b/i', $text) >= 3) {
            return true;
        }

        if ($this->isLikelyEnglish($text)) {
            return true;
        }

        $lower = ' ' . strtolower($text) . ' ';
        $englishMarkers = [
            ' the ',
            ' and ',
            ' with ',
            ' for ',
            ' from ',
            ' best seller ',
            ' new york times ',
            ' published ',
            ' available ',
        ];

        $hits = 0;
        foreach ($englishMarkers as $marker) {
            if (str_contains($lower, $marker)) {
                $hits++;
            }
        }

        return $hits >= 2;
    }

    private function localizeCategoryToIndonesian(?string $category): ?string
    {
        $value = $this->clean($category);
        if (! $value) {
            return null;
        }

        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'self-help', 'self help' => 'Pengembangan Diri',
            'business & economics' => 'Bisnis & Ekonomi',
            'juvenile fiction' => 'Fiksi Anak',
            'juvenile nonfiction', 'juvenile non-fiction' => 'Nonfiksi Anak',
            'history' => 'Sejarah',
            'fiction' => 'Fiksi',
            'nonfiction', 'non-fiction' => 'Nonfiksi',
            'technology' => 'Teknologi',
            'computers' => 'Komputer',
            default => $value,
        };
    }

    private function translationLooksUnchanged(string $source, string $translated): bool
    {
        return $this->normalizeComparableText($source) === $this->normalizeComparableText($translated);
    }

    private function stillLooksEnglish(string $text): bool
    {
        return $this->shouldTranslateToIndonesian($text);
    }

    private function isLikelyEnglish(string $text): bool
    {
        $lower = strtolower($text);

        $englishHints = [' the ', ' and ', ' with ', ' from ', ' between ', ' life ', ' death ', ' finds ', ' chance '];
        $indoHints = [' dan ', ' dengan ', ' untuk ', ' yang ', ' dalam ', ' pada ', ' dari ', ' kehidupan ', ' kematian '];

        $enScore = 0;
        foreach ($englishHints as $hint) {
            if (str_contains(' ' . $lower . ' ', $hint)) {
                $enScore++;
            }
        }

        $idScore = 0;
        foreach ($indoHints as $hint) {
            if (str_contains(' ' . $lower . ' ', $hint)) {
                $idScore++;
            }
        }

        return $enScore > $idScore;
    }

    /**
     * @param array<int, array<string, mixed>> $imageSignals
     */
    private function resolveFrontImageIndex(array $imageSignals, mixed $preferredIndex): ?int
    {
        if (is_numeric($preferredIndex)) {
            return (int) $preferredIndex;
        }

        foreach ($imageSignals as $signal) {
            if (($signal['view'] ?? null) === 'front' && isset($signal['index']) && is_numeric($signal['index'])) {
                return (int) $signal['index'];
            }
        }

        return null;
    }

    /**
     * @param array<int, UploadedFile> $images
     * @return array<int, string>
     */
    private function storeUploads(array $images): array
    {
        $paths = [];

        foreach ($images as $index => $file) {
            $path = $file->store('book-scans', 'public');
            $paths[$index] = '/storage/' . $path;
        }

        return $paths;
    }

    private function titleSimilarity(string $a, string $b): float
    {
        $normalize = function (string $value): string {
            $value = strtolower($value);
            $value = preg_replace('/[^a-z0-9\s]/', ' ', $value) ?? $value;

            return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
        };

        $left = $normalize($a);
        $right = $normalize($b);
        if ($left === '' || $right === '') {
            return 0.0;
        }

        similar_text($left, $right, $percent);

        return $percent / 100;
    }

    private function isMetadataConsistentWithAiSignals(array $metadata, ?string $aiTitle, ?string $aiAuthor): bool
    {
        $candidateTitle = $this->clean($metadata['title'] ?? null);
        $candidateAuthor = $this->clean($metadata['author'] ?? null);
        $cleanAiAuthor = $this->normalizeAiAuthorSignal($aiAuthor);
        $cleanAiTitle = $this->clean($aiTitle);

        $titleScore = null;
        if ($cleanAiTitle && $candidateTitle) {
            $titleScore = $this->titleSimilarity($cleanAiTitle, $candidateTitle);
        }

        $authorScore = null;
        if ($cleanAiAuthor && $candidateAuthor) {
            $authorScore = $this->titleSimilarity($cleanAiAuthor, $candidateAuthor);
        }

        if ($titleScore !== null && $titleScore < 0.34) {
            return false;
        }

        if ($titleScore !== null && $authorScore !== null) {
            return $titleScore >= 0.50 || $authorScore >= 0.58;
        }

        if ($titleScore !== null) {
            return $titleScore >= 0.50;
        }

        if ($authorScore !== null) {
            return $authorScore >= 0.58;
        }

        return true;
    }

    private function normalizeAiAuthorSignal(?string $author): ?string
    {
        $value = $this->clean($author);
        if (! $value) {
            return null;
        }

        $lower = strtolower($value);
        $noiseMarkers = ['top_left', 'corner', 'align', 'bbox', 'position', 'ocr'];
        foreach ($noiseMarkers as $marker) {
            if (str_contains($lower, $marker)) {
                return null;
            }
        }

        if (str_contains($value, '_') || mb_strlen($value) > 64) {
            return null;
        }

        return $value;
    }

    private function cleanTitleSignal(mixed $title): ?string
    {
        $value = $this->clean(is_string($title) ? $title : null);
        if (! $value) {
            return null;
        }

        $value = trim($value, " \t\n\r\0\x0B{}[]()<>\"'`|_.,;:!?");
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return $this->clean($value);
    }

    private function shouldPreferProviderTitle(?string $aiTitle, mixed $providerTitle): bool
    {
        $provider = $this->clean(is_string($providerTitle) ? $providerTitle : null);
        if (! $provider) {
            return false;
        }

        $ai = $this->cleanTitleSignal($aiTitle);
        if (! $ai) {
            return true;
        }

        if ($this->normalizeComparableText($ai) === $this->normalizeComparableText($provider) && $ai !== $provider) {
            return true;
        }

        if ($this->titleSimilarity($ai, $provider) >= 0.92) {
            return false;
        }

        return str_starts_with($ai, '}')
            || str_starts_with($ai, ']')
            || str_starts_with($ai, ')')
            || str_contains($ai, '  ')
            || mb_strlen($provider) > mb_strlen($ai);
    }

    private function shouldPreferProviderAuthor(?string $aiAuthor, mixed $providerAuthor): bool
    {
        $provider = $this->clean(is_string($providerAuthor) ? $providerAuthor : null);
        if (! $provider) {
            return false;
        }

        $ai = $this->normalizeAiAuthorSignal($aiAuthor);
        if (! $ai) {
            return true;
        }

        if ($this->normalizeComparableText($ai) === $this->normalizeComparableText($provider) && $ai !== $provider) {
            return true;
        }

        return $this->titleSimilarity($ai, $provider) < 0.55;
    }

    /**
     * @param array<int, string> $lookupTitleCandidates
     * @return array<int, string>
     */
    private function buildSearchTitleCandidates(?string $preferredTitle, array $lookupTitleCandidates): array
    {
        $candidates = [];

        foreach (array_merge([$preferredTitle], $lookupTitleCandidates) as $candidate) {
            $clean = $this->cleanTitleSignal($candidate);
            if ($clean && ! $this->titleVariantExists($candidates, $clean)) {
                $candidates[] = $clean;
            }
        }

        usort($candidates, fn (string $a, string $b): int => strlen($a) <=> strlen($b));

        return $candidates;
    }

    private function preferredAuthorForSearch(?string $providerAuthor, ?string $aiAuthor): ?string
    {
        $provider = $this->clean($providerAuthor);
        if ($provider) {
            return $provider;
        }

        return $this->normalizeAiAuthorSignal($aiAuthor);
    }

    private function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function normalizeComparableText(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    private function isOpenLibrarySource(array $metadata): bool
    {
        return strtolower((string) ($this->clean($metadata['source'] ?? null) ?? '')) === 'openlibrary';
    }

    private function hasMissingCatalogFields(array $metadata): bool
    {
        foreach (['description', 'category', 'isbn', 'publisher', 'published_year'] as $field) {
            if ($this->clean($metadata[$field] ?? null) === null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, string> $lookupTitleCandidates
     */
    private function retryGoogleAfterOpenLibrary(array $lookupTitleCandidates, ?string $author): ?array
    {
        foreach ($lookupTitleCandidates as $lookupTitle) {
            $result = $this->isbnLookupService->searchGoogleByTitleAuthorOnly($lookupTitle, $author);
            if (is_array($result) && strtolower((string) ($result['source'] ?? '')) === 'google') {
                return $result;
            }
        }

        return null;
    }
}
