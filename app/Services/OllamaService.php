<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use RuntimeException;

class OllamaService
{
    public function __construct(
        private readonly AiInfrastructureService $aiInfrastructureService,
        private readonly AppSettingsService $settingsService
    ) {
    }

    /**
     * @param array<int, UploadedFile> $images
     */
    public function extractBookSignals(array $images): array
    {
        if ($images === []) {
            return [
                'images' => [],
                'best' => [
                    'isbn' => null,
                    'title' => null,
                    'author' => null,
                    'category' => null,
                    'front_image_index' => null,
                ],
            ];
        }

        $response = $this->sendVisionRequest($images);
        $decoded = $this->decodeModelJson($response);

        $normalizedImages = collect($decoded['images'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row): array {
                $view = strtolower((string) ($row['view'] ?? 'unknown'));
                if (! in_array($view, ['front', 'back', 'unknown'], true)) {
                    $view = 'unknown';
                }

                return [
                    'index' => isset($row['index']) && is_numeric($row['index']) ? (int) $row['index'] : null,
                    'view' => $view,
                    'isbn' => $this->stringOrNull($row['isbn'] ?? null),
                    'title' => $this->stringOrNull($row['title'] ?? null),
                    'author' => $this->stringOrNull($row['author'] ?? null),
                    'category' => $this->stringOrNull($row['category'] ?? null),
                    'cover_box' => $this->normalizeCoverBox($row['cover_box'] ?? null),
                ];
            })
            ->values()
            ->all();

        // Fallback: If AI returned a flat object instead of nested schema
        if (!isset($decoded['best']) && (isset($decoded['title']) || isset($decoded['isbn']))) {
            $best = $decoded;
            // Also pretend it's the first image
            if (empty($normalizedImages)) {
                $normalizedImages[] = [
                    'index' => 0,
                    'view' => 'front',
                    'isbn' => $this->stringOrNull($decoded['isbn'] ?? null),
                    'title' => $this->stringOrNull($decoded['title'] ?? null),
                    'author' => $this->stringOrNull($decoded['author'] ?? null),
                    'category' => $this->stringOrNull($decoded['category'] ?? null),
                    'cover_box' => $this->normalizeCoverBox($decoded['cover_box'] ?? null),
                ];
            }
        } else {
            $best = is_array($decoded['best'] ?? null) ? $decoded['best'] : [];
        }

        return [
            'images' => $normalizedImages,
                'best' => [
                    'isbn' => $this->normalizeIsbn($best['isbn'] ?? null),
                    'title' => $this->stringOrNull($best['title'] ?? null),
                    'author' => $this->stringOrNull($best['author'] ?? null),
                    'category' => $this->stringOrNull($best['category'] ?? null),
                    'description' => $this->stringOrNull($best['description'] ?? null),
                    'publisher' => $this->stringOrNull($best['publisher'] ?? null),
                    'language' => $this->stringOrNull($best['language'] ?? null),
                    'front_image_index' => isset($best['front_image_index']) && is_numeric($best['front_image_index'])
                        ? (int) $best['front_image_index']
                        : null,
            ],
        ];
    }

    public function translateTextToIndonesian(string $text): ?string
    {
        $content = trim($text);
        if ($content === '') {
            return null;
        }

        $baseUrl = $this->resolveBaseUrl();
        $model = $this->resolveModel('text');
        $timeout = max(30, $this->settingsService->getInt('ai.ollama.timeout', (int) config('services.ollama.timeout', 240)));
        $connectTimeout = $this->settingsService->getInt('ai.ollama.connect_timeout', (int) config('services.ollama.connect_timeout', 10));

        $payload = [
            'model' => $model,
            'stream' => false,
            'prompt' => $this->buildTranslatePrompt($content),
            'keep_alive' => '2h',
            'options' => [
                'temperature' => 0,
                'top_p' => 0.1,
                'seed' => 42,
                'num_predict' => 600,
            ],
        ];

        Log::debug('[OllamaService] Starting translation', [
            'model' => $model,
            'text_length' => strlen($content),
        ]);

        try {
            $response = Http::connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->acceptJson()
                ->retry(1, 300)
                ->post($baseUrl . '/api/generate', $payload)
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            Log::error('[OllamaService] Translation request failed', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException(
                sprintf('Ollama text request failed for model [%s] at [%s]: %s', $model, $baseUrl, $e->getMessage()),
                0,
                $e
            );
        }

        $translated = trim((string) $response->json('response', ''));

        // Strip Qwen3.5 thinking tags if present
        $translated = preg_replace('/<think>.*?<\/think>/s', '', $translated) ?? $translated;
        $translated = trim($translated);

        Log::debug('[OllamaService] Translation result', [
            'model' => $model,
            'result_length' => strlen($translated),
            'preview' => mb_substr($translated, 0, 200),
        ]);

        if ($translated === '') {
            return null;
        }

        $translated = trim($translated, "\" \n\r\t");
        $translated = preg_replace('/^(terjemahan|hasil terjemahan)\s*:\s*/i', '', $translated) ?? $translated;
        $translated = trim($translated);

        return $translated !== '' ? $translated : null;
    }

    /**
     * @param array<int, array{url:string,title:string,snippet:?string,text:?string}> $contexts
     */
    public function extractBookDescriptionFromWeb(string $title, ?string $author, array $contexts): ?array
    {
        if ($contexts === []) {
            return null;
        }

        $baseUrl = $this->resolveBaseUrl();
        $model = $this->resolveModel('web');
        $timeout = max(30, $this->settingsService->getInt('ai.ollama.timeout', (int) config('services.ollama.timeout', 240)));
        $connectTimeout = $this->settingsService->getInt('ai.ollama.connect_timeout', (int) config('services.ollama.connect_timeout', 10));

        $payload = [
            'model' => $model,
            'stream' => false,
            'prompt' => $this->buildWebDescriptionPrompt($title, $author, $contexts),
            'keep_alive' => '2h',
            'format' => 'json',
            'options' => [
                'temperature' => 0,
                'top_p' => 0.1,
                'seed' => 42,
                'num_predict' => 350,
            ],
        ];

        try {
            $response = Http::connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->acceptJson()
                ->retry(1, 300)
                ->post($baseUrl . '/api/generate', $payload)
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            throw new RuntimeException(
                sprintf('Ollama web extraction failed for model [%s] at [%s]: %s', $model, $baseUrl, $e->getMessage()),
                0,
                $e
            );
        }

        $decoded = $this->decodeModelJson((string) $response->json('response', ''));
        $description = $this->stringOrNull($decoded['description'] ?? null);
        $sourceUrl = $this->stringOrNull($decoded['source_url'] ?? null);
        $confidence = is_numeric($decoded['confidence'] ?? null)
            ? (float) $decoded['confidence']
            : 0.0;

        return [
            'description' => $description,
            'confidence' => max(0.0, min(1.0, $confidence)),
            'source_url' => $sourceUrl,
        ];
    }

    /**
     * @param array<int, array{url:string,title:string,snippet:?string,text:?string}> $contexts
     */
    public function extractBookInfoFromWebByIsbn(string $isbn, array $contexts): ?array
    {
        if ($contexts === []) {
            return null;
        }

        $baseUrl = $this->resolveBaseUrl();
        $model = $this->resolveModel('web');
        $timeout = max(30, $this->settingsService->getInt('ai.ollama.timeout', (int) config('services.ollama.timeout', 240)));
        $connectTimeout = $this->settingsService->getInt('ai.ollama.connect_timeout', (int) config('services.ollama.connect_timeout', 10));

        $payload = [
            'model' => $model,
            'stream' => false,
            'prompt' => $this->buildWebIsbnPrompt($isbn, $contexts),
            'keep_alive' => '2h',
            'format' => 'json',
            'options' => [
                'temperature' => 0,
                'top_p' => 0.1,
                'seed' => 42,
                'num_predict' => 450,
            ],
        ];

        try {
            $response = Http::connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->acceptJson()
                ->retry(1, 300)
                ->post($baseUrl . '/api/generate', $payload)
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            throw new RuntimeException(
                sprintf('Ollama web extraction failed for model [%s] at [%s]: %s', $model, $baseUrl, $e->getMessage()),
                0,
                $e
            );
        }

        $decoded = $this->decodeModelJson((string) $response->json('response', ''));
        
        $confidence = is_numeric($decoded['confidence'] ?? null)
            ? (float) $decoded['confidence']
            : 0.0;

        if ($confidence < 0.6) {
            return null;
        }

        return [
            'title' => $this->stringOrNull($decoded['title'] ?? null),
            'author' => $this->stringOrNull($decoded['author'] ?? null),
            'description' => $this->stringOrNull($decoded['description'] ?? null),
            'publisher' => $this->stringOrNull($decoded['publisher'] ?? null),
            'category' => $this->stringOrNull($decoded['category'] ?? null),
            'confidence' => max(0.0, min(1.0, $confidence)),
            'source_url' => $this->stringOrNull($decoded['source_url'] ?? null),
        ];
    }

    /**
     * @param array<int, UploadedFile> $images
     */
    private function sendVisionRequest(array $images): string
    {
        $baseUrl = $this->resolveBaseUrl();
        $model = $this->resolveModel('vision');
        $timeout = max(120, $this->settingsService->getInt('ai.ollama.timeout', (int) config('services.ollama.timeout', 240)));
        $connectTimeout = max(10, $this->settingsService->getInt('ai.ollama.connect_timeout', (int) config('services.ollama.connect_timeout', 10)));

        $imageCount = count($images);
        Log::channel('ai_scan')->info("Sending {$imageCount} image(s) to Vision model [{$model}]");

        // NOTE: 'format' => 'json' is intentionally OMITTED.
        // Qwen3-VL (and many VL models) return EMPTY responses when forced JSON
        // mode is active. Instead, we instruct JSON output via prompt and use
        // aggressive regex extraction in decodeModelJson().
        //
        // ROOT CAUSE (2026-04-22): eval_count=600 + response_length=0 means
        // Qwen3-VL thinking mode consumed ALL 600 tokens in <think>...</think>
        // before it could output any JSON. Fix: raise num_predict to 1200,
        // num_ctx to 8192, and add 'think: false' to disable thinking mode.
        $encodedImages = array_map(
            fn (UploadedFile $file) => $this->encodeVisionImage($file),
            $images
        );

        // Verification step: check that at least one image has non-empty payload
        $nonEmptyImages = array_filter($encodedImages, fn (string $b64): bool => $b64 !== '');
        if (count($nonEmptyImages) === 0) {
            Log::channel('ai_scan')->error('❌ ALL images produced empty base64 payload! Aborting Vision request.');
            throw new RuntimeException('All uploaded images produced empty payloads. Check image format support.');
        }
        if (count($nonEmptyImages) < $imageCount) {
            Log::channel('ai_scan')->warning('⚠️ ' . ($imageCount - count($nonEmptyImages)) . ' image(s) produced empty payload and will be skipped.');
        }

        $totalPayloadKb = round(array_sum(array_map('strlen', $nonEmptyImages)) / 1024);
        Log::channel('ai_scan')->info("📦 Total image payload: {$totalPayloadKb} KB for " . count($nonEmptyImages) . ' image(s)');

        $payload = [
            'model' => $model,
            'stream' => false,
            'prompt' => $this->buildVisionPrompt($imageCount),
            'images' => array_values($nonEmptyImages),
            'keep_alive' => '2h',
            // 'think' => false disables Qwen3-VL internal reasoning/thinking mode.
            // Without this, the model burns ALL num_predict tokens on <think> blocks
            // and returns empty response (eval_count=600, response_length=0).
            'think' => false,
            'options' => [
                'temperature' => 0,
                'top_p' => 0.1,
                'seed' => 42,
                'num_predict' => 1200,
                'num_ctx' => 8192,
            ],
        ];

        try {
            $response = Http::connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->acceptJson()
                ->retry(1, 500)
                ->post($baseUrl . '/api/generate', $payload)
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            Log::channel('ai_scan')->error("Ollama Vision request FAILED: " . $e->getMessage());
            throw new RuntimeException(
                sprintf('Ollama vision request failed for model [%s] at [%s]: %s', $model, $baseUrl, $e->getMessage()),
                0,
                $e
            );
        }

        $responseBody = (string) $response->json('response', '');

        $evalCount = (int) $response->json('eval_count', 0);
        $promptEvalCount = (int) $response->json('prompt_eval_count', 0);
        $durationSec = round(($response->json('total_duration', 0) / 1e9), 2);
        $responseLen = strlen($responseBody);

        Log::debug('[OllamaService] Full vision API response keys', [
            'model' => $model,
            'done' => $response->json('done'),
            'response_length' => $responseLen,
            'total_duration' => $response->json('total_duration'),
            'eval_count' => $evalCount,
            'prompt_eval_count' => $promptEvalCount,
        ]);
        Log::channel('ai_scan')->info("Ollama responded in {$durationSec}s | eval_count: {$evalCount} | prompt_eval: {$promptEvalCount} | response_length: {$responseLen}");

        // Diagnostic: if eval_count == num_predict AND response is empty,
        // it almost certainly means the model ran out of tokens mid-think.
        if ($responseLen === 0 && $evalCount > 0) {
            Log::channel('ai_scan')->error("🚨 EMPTY RESPONSE DETECTED: eval_count={$evalCount}, prompt_eval={$promptEvalCount}. Likely causes: (1) thinking mode exhausted num_predict, (2) image payload rejected by model, (3) model VRAM overload.");
        }

        return $responseBody;
    }

    private function decodeModelJson(string $raw): array
    {
        // Log full raw body for debugging model output issues
        Log::channel('ai_scan')->info("Raw Vision Response: \n" . $raw);
        Log::info('DEBUG OLLAMA RAW BODY', [
            'length' => strlen($raw),
            'body' => mb_substr($raw, 0, 3000),
        ]);
        Log::debug('[OllamaService] Raw model response', [
            'length' => strlen($raw),
            'preview' => mb_substr($raw, 0, 500),
        ]);

        // Strip Qwen3 <think>...</think> reasoning tags before parsing
        $raw = preg_replace('/<think>.*?<\/think>/s', '', $raw) ?? $raw;
        $raw = trim($raw);

        if ($raw === '') {
            Log::error('[OllamaService] Ollama returned empty response (possibly model too slow or num_predict too low)');
            throw new RuntimeException('Ollama model returned an empty response. Try increasing num_predict or check model health.');
        }

        $candidates = $this->buildJsonCandidates($raw);

        foreach ($candidates as $candidate) {
            $decoded = $this->decodeJsonCandidate($candidate);
            if (is_array($decoded)) {
                Log::channel('ai_scan')->info('JSON decoded successfully from candidate', [
                    'preview' => mb_substr($candidate, 0, 200),
                ]);
                return $decoded;
            }
        }

        Log::error('[OllamaService] Failed to decode JSON from Ollama', [
            'raw_full' => mb_substr($raw, 0, 2000),
            'candidates_count' => count($candidates),
            'candidates_preview' => array_map(fn($c) => mb_substr($c, 0, 300), $candidates),
        ]);
        Log::channel('ai_scan')->error("❌ GAGAL PARSE JSON! Raw response (first 2000 chars): " . mb_substr($raw, 0, 2000));

        throw new RuntimeException('Invalid JSON returned by Ollama model.');
    }

    /**
     * @return array<int, string>
     */
    private function buildJsonCandidates(string $raw): array
    {
        $content = trim($raw);

        // Aggressively strip Markdown code fences (Qwen3-VL often wraps JSON in these)
        $content = str_replace(['```json', '```JSON', '```'], '', $content);
        $content = preg_replace('/^```[a-zA-Z]*\s*/m', '', $content) ?? $content;
        $content = trim($content);

        // Replace smart quotes with standard quotes
        $content = str_replace(["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}"], ['"', '"', "'", "'"], $content);

        $candidates = [$content];

        // Try balanced brace extraction (handles leading/trailing text)
        $extracted = $this->extractFirstJsonObject($content);
        if ($extracted !== null) {
            $candidates[] = $extracted;
        }

        // Greedy regex: grab everything between first { and last }
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $candidates[] = $matches[0];
        }

        // Try removing trailing commas
        $sanitized = preg_replace('/,\s*([}\]])/', '$1', $content);
        if (is_string($sanitized) && $sanitized !== $content) {
            $candidates[] = $sanitized;
            $extractedSanitized = $this->extractFirstJsonObject($sanitized);
            if ($extractedSanitized !== null) {
                $candidates[] = $extractedSanitized;
            }
        }

        return array_values(array_unique(array_filter($candidates, fn (mixed $row): bool => is_string($row) && trim($row) !== '')));
    }

    private function decodeJsonCandidate(string $candidate): ?array
    {
        $decoded = json_decode($candidate, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (is_string($decoded)) {
            $nested = json_decode($decoded, true);
            if (is_array($nested)) {
                return $nested;
            }
        }

        return null;
    }

    private function extractFirstJsonObject(string $content): ?string
    {
        $start = strpos($content, '{');
        if ($start === false) {
            return null;
        }

        $length = strlen($content);
        $depth = 0;
        $inString = false;
        $escaped = false;

        for ($i = $start; $i < $length; $i++) {
            $char = $content[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }

                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }

                if ($char === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"') {
                $inString = true;
                continue;
            }

            if ($char === '{') {
                $depth++;
                continue;
            }

            if ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($content, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    private function buildVisionPrompt(int $imageCount = 1): string
    {
        $dualImageInstructions = '';
        if ($imageCount >= 2) {
            $dualImageInstructions = '
- Image 0 = Front Cover, Image 1 = Back Cover.
- front_image_index = 0
- Read synopsis/sinopsis text from Back Cover image for the "description" field.
- If back cover has clear paragraph text, copy it as description.';
        }

        return <<<PROMPT
You are a book metadata extractor. Respond with ONLY a JSON object. No explanation. No markdown fences.

Extract from the image(s):
{"title":"...","author":"...","isbn":"...","category":"...","description":null,"publisher":"...","language":"...","front_image_index":0}

Rules:
- title = largest prominent text on front cover. DOUBLE CHECK every character for spelling accuracy!
- author = person name on cover (below or above title). Verify spelling carefully.
- isbn = 10 or 13 digit number near barcode, null if not visible
- category: one of Fiksi/Novel/Sejarah/Teknologi/Bisnis/Sains/Agama/Pendidikan or null
- description: synopsis text from back cover only, null if not visible
- publisher: publisher name if visible, null otherwise
- IMPORTANT: Read each letter carefully. Common OCR mistakes: I vs T, B vs R, A vs O. Double check!
- If unsure about any field, use null. Do NOT invent data.
- Ignore testimonials, badges, promo stickers.{$dualImageInstructions}
PROMPT;
    }

    private function resolveBaseUrl(): string
    {
        $baseUrl = rtrim((string) $this->settingsService->get('ai.ollama.base_url', config('services.ollama.base_url', '')), '/');
        if ($baseUrl === '') {
            throw new RuntimeException('OLLAMA_BASE_URL belum diatur.');
        }

        return $baseUrl;
    }

    private function resolveModel(string $task): string
    {
        $model = $this->aiInfrastructureService->resolveOllamaModel($task);
        if (! is_string($model) || trim($model) === '') {
            throw new RuntimeException('Model Ollama untuk task [' . $task . '] belum diatur.');
        }

        return $model;
    }

    private function stringOrNull(mixed $value): ?string
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

        $normalized = preg_replace('/[^0-9Xx]/', '', $isbn);

        return $normalized !== '' ? strtoupper($normalized) : null;
    }

    private function normalizeCoverBox(mixed $box): ?array
    {
        if (! is_array($box) || count($box) !== 4) {
            return null;
        }

        // Expected: [ymin, xmin, ymax, xmax]
        if (! is_numeric($box[0]) || ! is_numeric($box[1]) || ! is_numeric($box[2]) || ! is_numeric($box[3])) {
            return null;
        }

        $ymin = (float) $box[0];
        $xmin = (float) $box[1];
        $ymax = (float) $box[2];
        $xmax = (float) $box[3];

        if ($ymin >= $ymax || $xmin >= $xmax) {
            return null;
        }

        $x = $xmin;
        $y = $ymin;
        $w = $xmax - $xmin;
        $h = $ymax - $ymin;

        if ($w <= 0 || $h <= 0) {
            return null;
        }

        // Clamp to normalized coordinate space.
        $x = max(0.0, min(1.0, $x));
        $y = max(0.0, min(1.0, $y));
        $w = max(0.0, min(1.0, $w));
        $h = max(0.0, min(1.0, $h));

        if ($x + $w > 1.0) {
            $w = 1.0 - $x;
        }

        if ($y + $h > 1.0) {
            $h = 1.0 - $y;
        }

        if ($w <= 0 || $h <= 0) {
            return null;
        }

        return compact('x', 'y', 'w', 'h');
    }

    private function encodeVisionImage(UploadedFile $file): string
    {
        $raw = @file_get_contents($file->getRealPath());
        if (! is_string($raw) || $raw === '') {
            Log::warning('[OllamaService] Image file is empty or unreadable', [
                'path' => $file->getRealPath(),
                'original_name' => $file->getClientOriginalName(),
            ]);
            return '';
        }

        $originalName = $file->getClientOriginalName();
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $format = 'unknown';

        Log::channel('ai_scan')->info("🖼️ Processing image: {$originalName} ({$extension}, " . round(strlen($raw)/1024, 1) . " KB)");

        // Try standard decode first (works for JPEG, PNG, GIF, BMP)
        // NOTE: imagecreatefromstring MAY also support AVIF on GD >= 2.3.0+libavif
        $image = @imagecreatefromstring($raw);
        if ($image) {
            $format = 'standard (imagecreatefromstring)';
        }

        // AVIF explicit fallback — force imagecreatefromavif when extension is .avif
        // or when imagecreatefromstring failed. This is the most reliable path.
        if (! $image && function_exists('imagecreatefromavif')) {
            $image = @imagecreatefromavif($file->getRealPath());
            if ($image) {
                $format = 'AVIF (imagecreatefromavif)';
            } else {
                Log::channel('ai_scan')->error("❌ imagecreatefromavif FAILED for: {$originalName}");
            }
        }

        // WebP fallback
        if (! $image && function_exists('imagecreatefromwebp')) {
            $image = @imagecreatefromwebp($file->getRealPath());
            if ($image) {
                $format = 'WebP (imagecreatefromwebp)';
            }
        }

        if (! $image) {
            // Last resort: send raw bytes. This will likely fail for AVIF on Ollama
            // side (Ollama's internal decoder may not support AVIF).
            // Log clearly so operator knows this is risky.
            Log::channel('ai_scan')->error("❌ ALL GD decoders FAILED for '{$originalName}' ({$extension}). Sending raw bytes — Ollama may reject this format!", [
                'raw_size' => strlen($raw),
                'extension' => $extension,
            ]);
            Log::warning('[OllamaService] All GD image decoders failed, sending raw file as base64', [
                'file' => $originalName,
                'extension' => $extension,
                'raw_size' => strlen($raw),
            ]);
            return base64_encode($raw);
        }

        Log::info('[OllamaService] Image converted from: ' . $format, [
            'file' => $originalName,
            'original_size' => strlen($raw),
        ]);

        try {
            $maxSide = 1024;
            $jpegQuality = 85;
            $width = imagesx($image);
            $height = imagesy($image);

            if ($width <= 0 || $height <= 0) {
                Log::warning('[OllamaService] Image has zero dimensions', ['file' => $originalName]);
                return base64_encode($raw);
            }

            $scale = min(1, $maxSide / max($width, $height));
            
            if ($scale < 1) {
                $newWidth = max(1, (int) round($width * $scale));
                $newHeight = max(1, (int) round($height * $scale));

                $resized = imagecreatetruecolor($newWidth, $newHeight);
                if (! $resized) {
                    return base64_encode($raw);
                }

                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                ob_start();
                imagejpeg($resized, null, $jpegQuality);
                $jpeg = ob_get_clean();
                imagedestroy($resized);

                Log::debug('[OllamaService] Image resized for Vision', [
                    'file' => $originalName,
                    'from' => "{$width}x{$height}",
                    'to' => "{$newWidth}x{$newHeight}",
                    'jpeg_size' => is_string($jpeg) ? strlen($jpeg) : 0,
                ]);
            } else {
                ob_start();
                imagejpeg($image, null, $jpegQuality);
                $jpeg = ob_get_clean();
            }

            if (! is_string($jpeg) || $jpeg === '') {
                Log::warning('[OllamaService] JPEG encoding produced empty output', ['file' => $originalName]);
                return base64_encode($raw);
            }

            return base64_encode($jpeg);
        } catch (\Throwable $e) {
            Log::error('[OllamaService] Image encoding exception', [
                'file' => $originalName,
                'error' => $e->getMessage(),
            ]);
            return base64_encode($raw);
        } finally {
            imagedestroy($image);
        }
    }

    private function buildTranslatePrompt(string $text): string
    {
        return <<<PROMPT
Anda adalah penerjemah profesional EN->ID.

Terjemahkan teks berikut ke Bahasa Indonesia yang natural, utuh, dan mudah dipahami.
Aturan:
- Jangan menambah informasi baru.
- Pertahankan makna asli.
- Jika teks sumber berbahasa Inggris, hasil wajib berbahasa Indonesia.
- Jangan mengembalikan teks asli bahasa Inggris.
- Output HANYA teks terjemahan final, tanpa komentar, catatan, atau penjelasan tambahan.

Teks:
{$text}
PROMPT;
    }

    /**
     * @param array<int, array{url:string,title:string,snippet:?string,text:?string}> $contexts
     */
    private function buildWebDescriptionPrompt(string $title, ?string $author, array $contexts): string
    {
        $authorPart = $author ? "Penulis target: {$author}" : 'Penulis target: (tidak diketahui)';

        $contextBlocks = [];
        foreach (array_values($contexts) as $index => $context) {
            $url = $context['url'] ?? '';
            $ctxTitle = $context['title'] ?? '';
            $snippet = $context['snippet'] ?? '';
            $text = $context['text'] ?? '';

            $contextBlocks[] = sprintf(
                "[Sumber %d]\nURL: %s\nJudul Halaman: %s\nSnippet: %s\nKonten: %s\n",
                $index + 1,
                $url,
                $ctxTitle,
                $snippet ?: '(kosong)',
                $text ?: '(kosong)'
            );
        }

        $joinedContexts = implode("\n", $contextBlocks);

        return <<<PROMPT
Anda adalah sistem ekstraksi deskripsi buku dari hasil web search.

Judul target: {$title}
{$authorPart}

Aturan:
1. Gunakan hanya informasi dari daftar sumber yang diberikan.
2. Jangan mengarang fakta.
3. Jika tidak yakin teks membahas buku target, isi description = null.
4. Description harus Bahasa Indonesia, ringkas 2-5 kalimat.
5. confidence di rentang 0..1.
6. source_url wajib diambil dari URL sumber yang paling mendukung description.
7. Output HARUS JSON valid sesuai schema, tanpa teks tambahan.

Daftar sumber:
{$joinedContexts}
PROMPT;
    }

    /**
     * @param array<int, array{url:string,title:string,snippet:?string,text:?string}> $contexts
     */
    private function buildWebIsbnPrompt(string $isbn, array $contexts): string
    {
        $contextBlocks = [];
        foreach (array_values($contexts) as $index => $context) {
            $url = $context['url'] ?? '';
            $ctxTitle = $context['title'] ?? '';
            $snippet = $context['snippet'] ?? '';
            $text = $context['text'] ?? '';

            $contextBlocks[] = sprintf(
                "[Sumber %d]\nURL: %s\nJudul Halaman: %s\nSnippet: %s\nKonten: %s\n",
                $index + 1,
                $url,
                $ctxTitle,
                $snippet ?: '(kosong)',
                $text ?: '(kosong)'
            );
        }

        $joinedContexts = implode("\n", $contextBlocks);

        return <<<PROMPT
Anda adalah sistem ekstraksi metadata buku dari hasil pencarian web berdasarkan ISBN.

ISBN Target: {$isbn}

Ekstrak metadata buku dengan format JSON:
{"title":"...","author":"...","description":"...","publisher":"...","category":"...","confidence":0.9,"source_url":"..."}

Aturan:
1. Gunakan HANYA informasi dari daftar sumber yang diberikan.
2. Jika informasi (seperti publisher atau category) tidak ada, gunakan null.
3. Description harus Bahasa Indonesia, ringkas 2-5 kalimat.
4. Confidence (0.0 - 1.0) menunjukkan tingkat keyakinan bahwa metadata ini benar milik buku dengan ISBN {$isbn}.
5. Source_url harus diisi dengan URL sumber yang paling banyak memberikan informasi.
6. Output HARUS JSON valid, tanpa teks penjelasan tambahan.

Daftar sumber:
{$joinedContexts}
PROMPT;
    }
}
