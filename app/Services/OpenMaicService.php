<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenMaicService
{
    public function searchBookMetadata(?string $title, ?string $author): ?array
    {
        $title = $this->clean($title);
        $author = $this->clean($author);

        if (! $title && ! $author) {
            return null;
        }

        $cacheKey = 'book_lookup:openmaic:' . sha1(strtolower($title . '|' . $author));

        return $this->cachedNullableLookup($cacheKey, function () use ($title, $author) {
            return $this->performLookup($title, $author);
        });
    }

    private function performLookup(string $title, string $author): ?array
    {
        $baseUrl = rtrim((string) config('services.openmaic.base_url', ''), '/');
        $apiKey = (string) config('services.openmaic.api_key', '');
        $model = (string) config('services.openmaic.model', 'openmaic-chat');
        $timeout = (int) config('services.openmaic.timeout', 30);

        if ($baseUrl === '') {
            return null;
        }

        $payload = [
            'model' => $model,
            'temperature' => 0,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->buildSystemPrompt(),
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildUserPrompt($title, $author),
                ],
            ],
            'response_format' => [
                'type' => 'json_object',
            ],
        ];

        try {
            $request = Http::timeout($timeout)->acceptJson();
            if ($apiKey !== '') {
                $request = $request->withToken($apiKey);
            }

            $response = $request
                ->post($baseUrl . '/v1/chat/completions', $payload)
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            Log::warning('OpenMAIC lookup failed.', ['error' => $e->getMessage()]);

            return null;
        }

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            return null;
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            return null;
        }

        return [
            'title' => $this->clean($decoded['title'] ?? null),
            'author' => $this->clean($decoded['author'] ?? null),
            'category' => $this->clean($decoded['category'] ?? null),
            'description' => $this->clean($decoded['description'] ?? null),
            'publisher' => $this->clean($decoded['publisher'] ?? null),
            'published_year' => $this->normalizeYear($decoded['published_year'] ?? null),
            'confidence' => $this->normalizeConfidence($decoded['confidence'] ?? null),
            'source' => 'openmaic',
            'source_url' => $this->clean($decoded['source_url'] ?? null),
        ];
    }

    private function cachedNullableLookup(string $key, \Closure $resolver): ?array
    {
        $cached = Cache::get($key);
        if (is_array($cached) && array_key_exists('hit', $cached)) {
            Log::info('openmaic_lookup.cache', [
                'cache_key' => $key,
                'cache_status' => 'hit',
                'resolved' => (bool) ($cached['hit'] ?? false),
            ]);

            return ($cached['hit'] ?? false) ? ($cached['data'] ?? null) : null;
        }

        $result = $resolver();
        $hit = is_array($result);

        Cache::put(
            $key,
            ['hit' => $hit, 'data' => $hit ? $result : null],
            now()->addMinutes($hit ? $this->cacheMinutes() : $this->cacheMissMinutes())
        );

        Log::info('openmaic_lookup.cache', [
            'cache_key' => $key,
            'cache_status' => 'miss',
            'resolved' => $hit,
        ]);

        return $result;
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
Tugas:
Cari informasi buku berdasarkan judul dan penulis dari internet.

Aturan:
1. Gunakan sumber terpercaya: penerbit resmi, toko buku besar, Goodreads.
2. Jangan mengarang data.
3. Jika tidak yakin, gunakan null.
4. Prioritaskan data konsisten lintas sumber.
5. Field "description" harus ditulis dalam Bahasa Indonesia yang natural.
6. Jawaban HARUS JSON valid saja.

Schema output:
{
  "title": string|null,
  "author": string|null,
  "category": string|null,
  "description": string|null,
  "publisher": string|null,
  "published_year": string|null,
  "confidence": number,
  "source_url": string|null
}
PROMPT;
    }

    private function buildUserPrompt(?string $title, ?string $author): string
    {
        $titlePart = $title ?: '(null)';
        $authorPart = $author ?: '(null)';

        return "Keyword pencarian: {$titlePart} {$authorPart}";
    }

    private function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function normalizeYear(mixed $value): ?string
    {
        $clean = $this->clean(is_string($value) ? $value : (is_numeric($value) ? (string) $value : null));
        if (! $clean) {
            return null;
        }

        preg_match('/\b(1[6-9]\d{2}|20\d{2}|2100)\b/', $clean, $matches);

        return $matches[1] ?? null;
    }

    private function normalizeConfidence(mixed $value): float
    {
        if (is_numeric($value)) {
            $num = (float) $value;

            return max(0.0, min(1.0, $num));
        }

        return 0.0;
    }

    private function cacheMinutes(): int
    {
        return max(1, (int) config('services.openmaic.cache_minutes', 180));
    }

    private function cacheMissMinutes(): int
    {
        return max(1, (int) config('services.openmaic.cache_miss_minutes', 20));
    }
}
