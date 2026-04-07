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

        $best = is_array($decoded['best'] ?? null) ? $decoded['best'] : [];

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
            'keep_alive' => '20m',
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
            'keep_alive' => '20m',
            'format' => 'json',
            'options' => [
                'temperature' => 0,
                'top_p' => 0.1,
                'seed' => 42,
                'num_predict' => 512,
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
     * @param array<int, UploadedFile> $images
     */
    private function sendVisionRequest(array $images): string
    {
        $baseUrl = $this->resolveBaseUrl();
        $model = $this->resolveModel('vision');
        $timeout = $this->settingsService->getInt('ai.ollama.timeout', (int) config('services.ollama.timeout', 240));
        $connectTimeout = $this->settingsService->getInt('ai.ollama.connect_timeout', (int) config('services.ollama.connect_timeout', 10));

        $payload = [
            'model' => $model,
            'stream' => false,
            'prompt' => $this->buildVisionPrompt(),
            'images' => array_map(
                fn (UploadedFile $file) => $this->encodeVisionImage($file),
                $images
            ),
            'keep_alive' => '20m',
            'format' => 'json',
            'options' => [
                'temperature' => 0,
                'top_p' => 0.1,
                'seed' => 42,
                'num_predict' => 800,
            ],
        ];

        try {
            $response = Http::connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->acceptJson()
                ->retry(1, 400)
                ->post($baseUrl . '/api/generate', $payload)
                ->throw();
        } catch (ConnectionException|RequestException $e) {
            throw new RuntimeException(
                sprintf('Ollama vision request failed for model [%s] at [%s]: %s', $model, $baseUrl, $e->getMessage()),
                0,
                $e
            );
        }

        Log::debug('[OllamaService] Full vision API response keys', [
            'model' => $model,
            'done' => $response->json('done'),
            'response_length' => strlen((string) $response->json('response', '')),
            'total_duration' => $response->json('total_duration'),
            'eval_count' => $response->json('eval_count'),
        ]);

        return (string) $response->json('response', '');
    }

    private function decodeModelJson(string $raw): array
    {
        Log::debug('[OllamaService] Raw model response', [
            'length' => strlen($raw),
            'preview' => mb_substr($raw, 0, 500),
        ]);

        $candidates = $this->buildJsonCandidates($raw);

        foreach ($candidates as $candidate) {
            $decoded = $this->decodeJsonCandidate($candidate);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        Log::error('[OllamaService] Failed to decode JSON from Ollama', [
            'raw_full' => mb_substr($raw, 0, 2000),
            'candidates_count' => count($candidates),
        ]);

        throw new RuntimeException('Invalid JSON returned by Ollama model.');
    }

    /**
     * @return array<int, string>
     */
    private function buildJsonCandidates(string $raw): array
    {
        $content = trim($raw);
        $content = preg_replace('/^```json\s*/i', '', $content) ?? $content;
        $content = preg_replace('/^```\s*/', '', $content) ?? $content;
        $content = preg_replace('/\s*```$/', '', $content) ?? $content;
        $content = str_replace(["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}"], ['"', '"', "'", "'"], $content);

        $candidates = [$content];

        $extracted = $this->extractFirstJsonObject($content);
        if ($extracted !== null) {
            $candidates[] = $extracted;
        }

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

    private function buildVisionPrompt(): string
    {
        return <<<'PROMPT'
Anda adalah sistem ekstraksi metadata buku dari gambar.

Input dapat berisi 1 atau lebih gambar buku.
Setiap gambar bisa berupa cover depan, belakang, punggung, atau tidak jelas.

Tugas:
1) Untuk setiap gambar, tentukan:
   - index: indeks gambar (0-based)
   - view: "front" | "back" | "unknown"
   - isbn: ISBN jika terlihat jelas, jika tidak null
   - title: judul jika terlihat, jika tidak null
   - author: penulis jika terlihat, jika tidak null
   - publisher: penerbit jika terlihat, jika tidak null
   - category: kategori/genre buku (contoh: Teknologi, Sejarah, Novel, Fiksi, Bisnis), jika tidak yakin null
   - cover_box: koordinat area cover buku pada gambar dalam format normalisasi 0..1:
     {"x": number, "y": number, "w": number, "h": number}
     Jika tidak yakin atau bukan front cover, isi null.
2) Tentukan objek "best":
   - isbn: ISBN terbaik dari semua gambar (prioritas paling jelas), jika tidak ada null
   - title: judul terbaik dari semua gambar, jika tidak ada null
   - author: penulis terbaik dari semua gambar, jika tidak ada null
   - publisher: penerbit terbaik dari semua gambar, jika tidak ada null
   - language: bahasa buku jika terlihat jelas (contoh: Indonesia, English), jika tidak yakin null
   - category: kategori/genre terbaik dari semua gambar, jika tidak ada null
   - description: ringkasan singkat buku dalam Bahasa Indonesia berdasarkan teks yang benar-benar terlihat pada gambar (cover belakang/sinopsis). Jika tidak terlihat jelas, null.
   - front_image_index: index gambar cover depan jika ada, jika tidak null

Aturan ketat:
- Jangan mengarang data.
- Jangan menebak isi buku dari pengetahuan umum/model memory. Hanya pakai teks yang terlihat pada gambar.
- Abaikan teks testimoni, kutipan pujian, badge promo, dan ornamen non-metadata.
- Jika tidak yakin, isi null.
- Jangan menambah field selain schema.
- Output HARUS JSON valid murni, tanpa teks lain.
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
        if (! is_array($box)) {
            return null;
        }

        foreach (['x', 'y', 'w', 'h'] as $key) {
            if (! array_key_exists($key, $box) || ! is_numeric($box[$key])) {
                return null;
            }
        }

        $x = (float) $box['x'];
        $y = (float) $box['y'];
        $w = (float) $box['w'];
        $h = (float) $box['h'];

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
            return '';
        }

        $image = @imagecreatefromstring($raw);
        if (! $image) {
            return base64_encode($raw);
        }

        try {
            $maxSide = 1024;
            $width = imagesx($image);
            $height = imagesy($image);

            if ($width <= 0 || $height <= 0) {
                return base64_encode($raw);
            }

            $scale = min(1, $maxSide / max($width, $height));
            if ($scale >= 1) {
                return base64_encode($raw);
            }

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
            imagejpeg($resized, null, 82);
            $jpeg = ob_get_clean();
            imagedestroy($resized);

            if (! is_string($jpeg) || $jpeg === '') {
                return base64_encode($raw);
            }

            return base64_encode($jpeg);
        } catch (Throwable) {
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
}
