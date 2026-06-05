<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Gemini AI Service
 *
 * Vision:  Gemini direct API (primary) + n8n webhook (fallback)
 * Text:    Direct Gemini API call
 */
class GeminiService
{
    public function __construct(
        private readonly AppSettingsService $settingsService,
    ) {
    }

    // ──────────────────────────────────────────────
    //  PUBLIC API
    // ──────────────────────────────────────────────

    /**
     * Extract book metadata from cover images via Gemini Vision.
     * Uses DIRECT Gemini API (most reliable).
     *
     * @param array<int, UploadedFile> $images  Up to 5 images (front + back cover)
     * @param array|null $ocrResults
     * @return array{images: array, best: array}
     */
    public function extractBookSignals(array $images, ?array $ocrResults = null): array
    {
        if ($images === []) {
            return $this->emptyResult();
        }

        $profile = config('services.ai_runtime.profile', 'n8n-gemini');
        if ($profile === 'n8n-gemini') {
            try {
                $result = $this->extractViaN8nPipeline($images, $ocrResults);
            } catch (RuntimeException $e) {
                Log::channel('ai_scan')->warning('n8n vision unavailable, falling back to direct Gemini', [
                    'error' => $e->getMessage(),
                ]);

                return $this->extractViaDirectGemini($images);
            }

            if ($this->hasUsefulSignals($result)) {
                return $result;
            }

            Log::channel('ai_scan')->warning('n8n vision returned empty signals, falling back to direct Gemini');

            return $this->extractViaDirectGemini($images);
        }

        return $this->extractViaDirectGemini($images);
    }

    // ──────────────────────────────────────────────
    //  N8N FULL PIPELINE (primary)
    // ──────────────────────────────────────────────

    /**
     * Send image to n8n pipeline which handles:
     * Gemini Vision → Google Books → OpenLibrary → merge → JSON
     */
    private function extractViaN8nPipeline(array $images, ?array $ocrResults = null): array
    {
        return $this->runWithKeyRotation(function (string $apiKey) use ($images, $ocrResults) {
            $baseUrl = $this->resolveBaseUrl();
            $webhookPath = config('services.n8n.webhook_smartlms_vision', 'smartlms-vision');
            $url = rtrim($baseUrl, '/') . '/webhook/' . $webhookPath;
            $timeout = max(120, $this->resolveTimeout());
            $n8nApiKey = $this->resolveApiKey();

            Log::channel('ai_scan')->info('Sending to n8n full pipeline', [
                'url' => $url,
                'image_count' => count($images),
                'has_ocr' => !empty($ocrResults),
            ]);

            // Send first image (front cover) as primary
            $frontFile = $images[0];
            $http = Http::timeout($timeout)->acceptJson()->withoutVerifying();

            if ($n8nApiKey) {
                $http = $http->withHeader('X-N8N-API-KEY', $n8nApiKey);
            }
            $http = $http->withHeader('X-Gemini-API-Key', $apiKey);

            $tavilyApiKey = (string) $this->settingsService->get('ai.websearch.tavily_api_key', config('services.tavily.api_key'));
            if ($tavilyApiKey !== '') {
                $http = $http->withHeader('X-Tavily-API-Key', $tavilyApiKey);
            }

            $http = $http->attach('image', file_get_contents($frontFile->getRealPath()), $frontFile->getClientOriginalName());

            // Attach back cover image if available
            if (count($images) > 1) {
                $backFile = $images[1];
                $http = $http->attach('back_image', file_get_contents($backFile->getRealPath()), $backFile->getClientOriginalName());
            }

            // Attach OCR fields if available
            if ($ocrResults) {
                if (!empty($ocrResults['title'])) {
                    $http = $http->attach('title', $ocrResults['title']);
                }
                if (!empty($ocrResults['author'])) {
                    $http = $http->attach('author', $ocrResults['author']);
                }
                if (!empty($ocrResults['isbn'])) {
                    $http = $http->attach('isbn', $ocrResults['isbn']);
                }
            }

            $response = $http->post($url)->throw();
            $data = $response->json() ?: [];

            Log::channel('ai_scan')->info('n8n pipeline responded', [
                'found' => $data['found'] ?? false,
                'source' => $data['source'] ?? 'unknown',
            ]);

            // Check if n8n returned enriched data
            if (!empty($data['book'])) {
                $book = $data['book'];
                return [
                    'images' => [],
                    'best' => [
                        'isbn' => $book['isbn'] ?? null,
                        'title' => $book['title'] ?? null,
                        'author' => $book['author'] ?? null,
                        'publisher' => $book['publisher'] ?? null,
                        'category' => $book['category'] ?? null,
                        'description' => $book['description'] ?? null,
                        'front_image_index' => 0,
                    ],
                    // Indicate data came from n8n pipeline (already enriched)
                    '_n8n_enriched' => $book,
                ];
            }

            // n8n returned something but without 'book' — try parsing like direct response
            return $this->decodeResponse($response->body());
        });
    }

    private function extractViaDirectGemini(array $images): array
    {
        return $this->runWithKeyRotation(function (string $apiKey) use ($images) {
            $model = config('services.gemini.vision_model', config('services.gemini.model', 'gemini-2.5-flash'));
            $imageCount = count($images);

            Log::channel('ai_scan')->info("Sending {$imageCount} image(s) to Gemini Vision (direct API)", [
                'model' => $model,
            ]);

            // Build parts: prompt + images as inlineData
            $parts = [];
            $parts[] = ['text' => $this->buildVisionPrompt($imageCount)];

            foreach ($images as $i => $file) {
                $mime = $file->getMimeType() ?: 'image/jpeg';
                $base64 = base64_encode(file_get_contents($file->getRealPath()));
                $parts[] = [
                    'inlineData' => [
                        'mimeType' => $mime,
                        'data' => $base64,
                    ],
                ];
            }

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $payload = [
                'contents' => [['parts' => $parts]],
                'generationConfig' => [
                    'temperature' => 0,
                    'maxOutputTokens' => 1024,
                ],
            ];

            $response = Http::timeout(90)
                ->withoutVerifying()
                ->acceptJson()
                ->asJson()
                ->post($url, $payload)
                ->throw();

            $data = $response->json() ?: [];
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            Log::channel('ai_scan')->info('Direct Gemini Vision responded', [
                'text_len' => strlen($text),
                'finish_reason' => $data['candidates'][0]['finishReason'] ?? 'unknown',
            ]);

            if (empty(trim($text))) {
                throw new RuntimeException('Gemini Vision returned empty text.');
            }

            return $this->decodeResponse($text);
        });
    }

    // ──────────────────────────────────────────────
    //  N8N WEBHOOK VISION (fallback)
    // ──────────────────────────────────────────────

    private function sendVisionRequest(array $images): string
    {
        $imageCount = count($images);
        $baseUrl = $this->resolveBaseUrl();
        $url = rtrim($baseUrl, '/') . '/webhook/gemini-vision';
        $timeout = max(120, $this->resolveTimeout());
        $apiKey = $this->resolveApiKey();

        Log::channel('ai_scan')->info("Sending {$imageCount} image(s) to Gemini Vision via n8n");

        $http = Http::timeout($timeout)
            ->withoutVerifying()
            ->acceptJson()
            ->attach('prompt', $this->buildVisionPrompt($imageCount));

        foreach ($images as $i => $file) {
            $http = $http->attach(
                'image' . ($i > 0 ? (string) $i : ''),
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            );
        }

        if ($apiKey) {
            $http = $http->withHeader('X-N8N-API-KEY', $apiKey);
        }
        $http = $http->withHeader('X-Gemini-API-Key', $this->resolveGeminiApiKey());

        try {
            $response = $http->post($url)->throw();
            $body = $response->body();
            Log::channel('ai_scan')->info("Gemini Vision (n8n) responded", ['response_length' => strlen($body)]);

            if (empty(trim($body))) {
                throw new RuntimeException('Gemini Vision (n8n) returned empty response.');
            }

            return $body;

        } catch (ConnectionException|RequestException $e) {
            Log::channel('ai_scan')->error("Gemini Vision (n8n) request failed: " . $e->getMessage());
            throw new RuntimeException('Gemini Vision (n8n) request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    // ──────────────────────────────────────────────
    //  RESPONSE PARSING
    // ──────────────────────────────────────────────

    private function parseResponse(string $raw): array
    {
        return $this->decodeResponse($raw);
    }

    // ──────────────────────────────────────────────
    //  PUBLIC API — Text (direct Gemini)
    // ──────────────────────────────────────────────

    public function describeBook(string $title, ?string $author = null): array
    {
        $prompt = "Berikan deskripsi singkat dalam bahasa Indonesia untuk buku berjudul \"{$title}\"";
        if ($author) {
            $prompt .= " karya {$author}";
        }
        $prompt .= ". Format: {description: '...', category: '...'}. Hanya JSON.";

        $response = $this->callGeminiDirect($prompt, 800);
        return $this->decodeResponse($this->extractText($response));
    }

    public function translateToEnglish(string $text): array
    {
        $prompt = "Terjemahkan ke bahasa Inggris: \"{$text}\"\nFormat JSON: {english: '...'}. Hanya JSON.";
        $response = $this->callGeminiDirect($prompt, 500);
        return $this->decodeResponse($this->extractText($response));
    }

    public function translateTextToIndonesian(string $text): string
    {
        $prompt = "Terjemahkan teks bahasa Inggris berikut ke bahasa Indonesia secara alami, akurat, dan mengalir. Kembalikan HANYA hasil terjemahan teksnya saja tanpa tanda kutip di awal dan di akhir, tanpa penjelasan tambahan:\n\n\"{$text}\"";
        $response = $this->callGeminiDirect($prompt, 800);
        return trim($this->extractText($response));
    }

    public function translateToIndonesian(string $text): string
    {
        return $this->translateTextToIndonesian($text);
    }

    public function enrichMetadata(array $metadata): array
    {
        $json = json_encode($metadata, JSON_UNESCAPED_UNICODE);
        $prompt = "Lengkapi metadata buku berikut: {$json}\nFormat JSON dengan field: title, author, isbn, publisher, published_year, category, description. Hanya JSON.";
        $response = $this->callGeminiDirect($prompt, 500);
        return $this->decodeResponse($this->extractText($response));
    }

    public function extractWebDescription(string $url): string
    {
        $prompt = "Ekstrak deskripsi buku dari halaman: {$url}\nRingkasan dalam 3 kalimat bahasa Indonesia.";
        $response = $this->callGeminiDirect($prompt, 300);
        return $this->extractText($response);
    }

    /**
     * @param array<int, array{url:string, title:string, snippet:?string, text:?string}> $contexts
     * @return array{description:?string, source_url:?string, confidence:float}
     */
    public function extractBookDescriptionFromWeb(string $title, ?string $author, array $contexts): array
    {
        $payload = $this->prepareWebContexts($contexts);
        $authorLine = $author ? "Penulis yang dicari: {$author}\n" : '';
        $prompt = <<<PROMPT
Kamu adalah pustakawan digital. Berdasarkan kumpulan hasil web berikut, pilih sumber yang paling relevan untuk buku ini dan ekstrak hanya deskripsi bukunya.

Judul yang dicari: {$title}
{$authorLine}
Kembalikan HANYA JSON valid:
{
  "description": "sinopsis/deskripsi buku dalam bahasa sumber aslinya",
  "source_url": "url sumber yang paling mendukung deskripsi",
  "confidence": 0.0
}

Aturan:
- Gunakan hanya fakta yang didukung context di bawah.
- Jika tidak yakin itu benar-benar buku yang sama, turunkan confidence.
- Jika deskripsi tidak ditemukan, isi null dan confidence 0.

Contexts:
{$payload}
PROMPT;

        $data = $this->decodeResponse($this->extractText($this->callGeminiDirect($prompt, 900)));

        return [
            'description' => $this->stringOrNull($data['description'] ?? null),
            'source_url' => $this->stringOrNull($data['source_url'] ?? null),
            'confidence' => $this->normalizeConfidence($data['confidence'] ?? null),
        ];
    }

    /**
     * @param array<int, array{url:string, title:string, snippet:?string, text:?string}> $contexts
     * @return array{title:?string, author:?string, description:?string, publisher:?string, category:?string, isbn:?string, source_url:?string, confidence:float}
     */
    public function extractBookInfoFromWebByIsbn(string $isbn, array $contexts): array
    {
        $payload = $this->prepareWebContexts($contexts);
        $prompt = <<<PROMPT
Kamu adalah pustakawan digital. Berdasarkan context halaman web berikut, temukan metadata buku untuk ISBN {$isbn}.

Kembalikan HANYA JSON valid:
{
  "title": "judul buku atau null",
  "author": "penulis atau null",
  "description": "deskripsi buku atau null",
  "publisher": "penerbit atau null",
  "category": "kategori buku atau null",
  "isbn": "{$isbn}",
  "source_url": "url sumber terbaik atau null",
  "confidence": 0.0
}

Aturan:
- Gunakan hanya context yang relevan dengan ISBN {$isbn}.
- Jika title tidak yakin, isi null dan confidence 0.
- Description boleh null jika tidak ditemukan.

Contexts:
{$payload}
PROMPT;

        $data = $this->decodeResponse($this->extractText($this->callGeminiDirect($prompt, 1100)));

        return [
            'title' => $this->stringOrNull($data['title'] ?? null),
            'author' => $this->stringOrNull($data['author'] ?? null),
            'description' => $this->stringOrNull($data['description'] ?? null),
            'publisher' => $this->stringOrNull($data['publisher'] ?? null),
            'category' => $this->stringOrNull($data['category'] ?? null),
            'isbn' => $this->stringOrNull($data['isbn'] ?? null) ?? $isbn,
            'source_url' => $this->stringOrNull($data['source_url'] ?? null),
            'confidence' => $this->normalizeConfidence($data['confidence'] ?? null),
        ];
    }

    // ──────────────────────────────────────────────
    //  DIRECT GEMINI API (text only)
    // ──────────────────────────────────────────────

    public function callGeminiDirect(string $prompt, int $maxTokens = 800): array
    {
        return $this->runWithKeyRotation(function (string $apiKey) use ($prompt, $maxTokens) {
            $model = config('services.gemini.model', 'gemini-2.5-flash');
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
            $timeout = $this->resolveTimeout();

            Log::channel('ai_scan')->info("Calling Gemini direct API", [
                'model' => $model,
                'prompt_len' => strlen($prompt),
            ]);

            $payload = [
                'contents' => [[
                    'parts' => [['text' => $prompt]],
                ]],
                'generationConfig' => [
                    'temperature' => 0,
                    'maxOutputTokens' => $maxTokens,
                ],
            ];

            $response = Http::timeout($timeout)
                ->withoutVerifying()
                ->acceptJson()
                ->asJson()
                ->post($url, $payload)
                ->throw();

            $data = $response->json() ?: [];
            return $data;
        });
    }

    // ──────────────────────────────────────────────
    //  CONFIG RESOLUTION
    // ──────────────────────────────────────────────

    private function resolveBaseUrl(): string
    {
        $url = rtrim((string) ($this->settingsService->get('ai.n8n.base_url', config('services.n8n.base_url', ''))), '/');
        if ($url === '') {
            throw new RuntimeException('N8N_BASE_URL belum diatur di .env');
        }
        return $url;
    }

    private function resolveApiKey(): string
    {
        return (string) ($this->settingsService->get('ai.n8n.api_key', config('services.n8n.api_key', '')));
    }

    private function resolveGeminiApiKey(): string
    {
        $key = $this->settingsService->get('ai.gemini.api_key', config('services.gemini.api_key', ''));
        if ($key === '' || $key === null) {
            throw new RuntimeException('GEMINI_API_KEY belum diatur di .env');
        }
        return (string) $key;
    }

    private function resolveTimeout(): int
    {
        return max(30, (int) $this->settingsService->get('ai.n8n.timeout', (int) config('services.n8n.timeout', 240)));
    }

    // ──────────────────────────────────────────────
    //  PROMPT
    // ──────────────────────────────────────────────

    private function buildVisionPrompt(int $imageCount): string
    {
        $countHint = $imageCount > 1
            ? "{$imageCount} images: first = FRONT cover, rest = BACK/SPINE."
            : '1 image: FRONT cover of a book.';

        return <<<PROMPT
You are a librarian cataloguing system. Analyze the book cover image(s).

{$countHint}

Extract these fields and return ONLY valid JSON (no markdown, no explanation):
{"best": {"isbn": null, "title": null, "author": null, "publisher": null, "category": null, "description": null}}

RULES:
- TITLE: The LARGEST text on the FRONT cover (centered, bold). This is the book's main title.
- AUTHOR: Smaller text below the title. Often with "by", "karya", "penulis" prefix.
- ISBN: If clearly visible (13 or 10 digits, no hyphens). If UNCLEAR, use null.
- PUBLISHER: Logo or small text at bottom/spine/back.
- CATEGORY: "Fiksi", "Non-Fiksi", "Pendidikan", "Teknologi", "Agama", "Bisnis", "Anak", "Komik", "Sejarah", "Biografi", "Sains", "Hukum", "Kesehatan", "Referensi", or null.
- DESCRIPTION: If a BACK cover is provided, extract the synopsis/description printed on it (in its original language, max 3-4 sentences). If not available or only front cover is provided, return null.

OUTPUT ONLY THE JSON OBJECT. No markdown, no explanation.
PROMPT;
    }

    // ──────────────────────────────────────────────
    //  HELPERS
    // ──────────────────────────────────────────────

    private function emptyResult(): array
    {
        return [
            'images' => [],
            'best' => [
                'isbn' => null,
                'title' => null,
                'author' => null,
                'category' => null,
                'description' => null,
                'front_image_index' => null,
            ],
        ];
    }

    private function decodeResponse(string $raw): array
    {
        $cleaned = trim($raw);
        // Strip markdown fences
        if (str_starts_with($cleaned, '```')) {
            $cleaned = preg_replace('/^```(?:json)?\s*\n?/', '', $cleaned);
            $cleaned = preg_replace('/\n?```$/', '', $cleaned);
        }
        // Try JSON decode
        $decoded = json_decode($cleaned, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        // Try extracting JSON substring
        if (preg_match('/\{[\s\S]*\}/', $cleaned, $m)) {
            $extracted = json_decode($m[0], true);
            if (is_array($extracted)) {
                return $extracted;
            }
        }
        Log::channel('ai_scan')->warning('Failed to decode Gemini response as JSON', ['raw' => substr($raw, 0, 500)]);
        return ['images' => [], 'best' => []];
    }

    private function extractText(array $response): string
    {
        return $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function normalizeCoverBox(mixed $box): ?array
    {
        if (!is_array($box) || count($box) !== 4) return null;
        $values = array_values($box);
        return array_map(fn($v) => is_numeric($v) ? (int) $v : null, $values);
    }

    private function hasUsefulSignals(array $result): bool
    {
        if (is_array($result['_n8n_enriched'] ?? null) && ! empty($result['_n8n_enriched'])) {
            return true;
        }

        $best = is_array($result['best'] ?? null) ? $result['best'] : [];

        foreach (['isbn', 'title', 'author', 'publisher', 'category'] as $field) {
            if ($this->stringOrNull($best[$field] ?? null) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array{url:string, title:string, snippet:?string, text:?string}> $contexts
     */
    private function prepareWebContexts(array $contexts): string
    {
        $prepared = array_map(function (array $context): array {
            $text = $this->stringOrNull($context['text'] ?? null);

            return [
                'url' => $context['url'] ?? null,
                'title' => $context['title'] ?? null,
                'snippet' => $context['snippet'] ?? null,
                'text' => $text ? mb_substr($text, 0, 1800) : null,
            ];
        }, array_slice($contexts, 0, 4));

        return (string) json_encode($prepared, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function normalizeConfidence(mixed $value): float
    {
        if (is_numeric($value)) {
            $number = (float) $value;

            if ($number > 1.0) {
                $number /= 100;
            }

            return max(0.0, min(1.0, $number));
        }

        return 0.0;
    }

    // ──────────────────────────────────────────────
    //  ROTATION & RELIABILITY HELPERS
    // ──────────────────────────────────────────────

    public function runWithKeyRotation(\Closure $callback): mixed
    {
        $keys = $this->getGeminiKeysPool();
        if (empty($keys)) {
            $singleKey = $this->resolveGeminiApiKey();
            return $this->executeWithRetry($singleKey, $callback);
        }

        $lastException = null;
        
        for ($attempt = 0; $attempt < count($keys); $attempt++) {
            $key = \Illuminate\Support\Facades\Cache::lock('gemini_key_select_lock', 10)->block(5, function() use ($keys) {
                return $this->selectKeyWithLowestUsage($keys);
            });

            if (!$key) {
                break;
            }

            try {
                $this->incrementKeyUsage($key);
                return $this->executeWithRetry($key, $callback);
            } catch (Throwable $e) {
                $lastException = $e;
                
                if ($this->isRateLimitOrQuotaException($e)) {
                    Log::channel('ai_scan')->warning("Gemini key rate limited or quota exceeded, blocking key temporarily.", [
                        'key_md5' => md5($key),
                        'error' => $e->getMessage()
                    ]);
                    $this->blockKey($key);
                    continue;
                }
                
                throw $e;
            }
        }
        
        throw new RuntimeException("Seluruh API key di pool terblokir atau habis kuota. Error terakhir: " . ($lastException ? $lastException->getMessage() : 'Unknown'), 0, $lastException);
    }

    private function executeWithRetry(string $key, \Closure $callback): mixed
    {
        $backoffMs = [1000, 2000, 4000]; // Exponential backoff (1s, 2s, 4s)
        $attempts = 0;
        
        while (true) {
            try {
                return $callback($key);
            } catch (\Throwable $e) {
                if ($this->isRateLimitOrQuotaException($e)) {
                    throw $e;
                }
                
                $attempts++;
                if ($attempts >= 3) {
                    throw $e;
                }
                
                $sleepMs = $backoffMs[$attempts - 1] ?? end($backoffMs);
                Log::channel('ai_scan')->warning("Gemini transient error. Retrying in {$sleepMs}ms...", [
                    'attempt' => $attempts,
                    'error' => $e->getMessage()
                ]);
                usleep($sleepMs * 1000);
            }
        }
    }

    private function getGeminiKeysPool(): array
    {
        $keysString = config('services.gemini.api_keys');
        if (!$keysString) {
            return [];
        }
        
        return array_values(array_filter(array_map('trim', explode(',', $keysString))));
    }

    private function blockKey(string $key): void
    {
        $hash = md5($key);
        \Illuminate\Support\Facades\Cache::put("gemini_key_blocked:{$hash}", true, 45); // block for 45 seconds
    }

    private function isKeyBlocked(string $key): bool
    {
        $hash = md5($key);
        return \Illuminate\Support\Facades\Cache::has("gemini_key_blocked:{$hash}");
    }

    private function incrementKeyUsage(string $key): void
    {
        $hash = md5($key);
        $minute = date('i');
        $cacheKey = "gemini_key_usage:{$hash}:{$minute}";
        
        try {
            if (!\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                \Illuminate\Support\Facades\Cache::put($cacheKey, 1, 60);
            } else {
                \Illuminate\Support\Facades\Cache::increment($cacheKey);
            }
        } catch (\Throwable $e) {
            $count = (int) \Illuminate\Support\Facades\Cache::get($cacheKey, 0);
            \Illuminate\Support\Facades\Cache::put($cacheKey, $count + 1, 60);
        }
    }

    private function getKeyUsage(string $key): int
    {
        $hash = md5($key);
        $minute = date('i');
        $cacheKey = "gemini_key_usage:{$hash}:{$minute}";
        
        return (int) \Illuminate\Support\Facades\Cache::get($cacheKey, 0);
    }

    private function selectKeyWithLowestUsage(array $keys): ?string
    {
        $availableKeys = array_filter($keys, fn($k) => !$this->isKeyBlocked($k));
        
        if (empty($availableKeys)) {
            return null;
        }
        
        usort($availableKeys, fn($a, $b) => $this->getKeyUsage($a) <=> $this->getKeyUsage($b));
        
        return $availableKeys[0];
    }
    
    private function isRateLimitOrQuotaException(\Throwable $e): bool
    {
        $msg = strtolower($e->getMessage());
        if (str_contains($msg, '429') || 
            str_contains($msg, '503') || 
            str_contains($msg, 'quota exceeded') || 
            str_contains($msg, 'rate limit') ||
            str_contains($msg, 'high demand') ||
            str_contains($msg, 'resource exhausted')
        ) {
            return true;
        }
        return false;
    }
}
