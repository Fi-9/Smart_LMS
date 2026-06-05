<?php

namespace App\Services;

use App\Models\ScanJob;
use Illuminate\Support\Facades\Log;
use Throwable;

class FallbackEngineService
{
    public function __construct(
        private readonly GeminiService $geminiService
    ) {}

    /**
     * Apply fallbacks to merged/enriched metadata.
     *
     * @param array $merged The merged metadata from Stage 3
     * @param array $vision The book identification result from Stage 1
     * @param ScanJob $scanJob The active scan job (contains cover image paths)
     * @return array
     */
    public function fallback(array $merged, array $vision, ScanJob $scanJob): array
    {
        Log::channel('ai_scan')->info("Running Stage 4: Fallback Engine", ['scan_job_id' => $scanJob->id]);

        // 1. cover_url fallback to front_cover_path
        if (empty($merged['cover_url'])) {
            if ($scanJob->front_cover_path) {
                // Keep the path format consistent with what CoverImageService or local path needs
                $merged['cover_url'] = '/storage/' . $scanJob->front_cover_path;
                Log::channel('ai_scan')->info("Fallback: cover_url set to front cover web path");
            }
        }

        // 2. description fallback to description_back_cover
        if (empty($merged['description'])) {
            $backDesc = $vision['description_back_cover'] ?? $vision['description'] ?? null;
            if ($backDesc) {
                $merged['description'] = $backDesc;
                Log::channel('ai_scan')->info("Fallback: description set to back cover description from Stage 1");
            }
        }

        // 3. publisher fallback to publisher_hint
        if (empty($merged['publisher'])) {
            $pubHint = $vision['publisher_hint'] ?? $vision['publisher'] ?? null;
            if ($pubHint) {
                $merged['publisher'] = $pubHint;
                Log::channel('ai_scan')->info("Fallback: publisher set to publisher_hint from Stage 1");
            }
        }

        // 4. category fallback to Gemini text classification
        if (empty($merged['category'])) {
            $title = $merged['title'] ?? $vision['title'] ?? '';
            $desc = $merged['description'] ?? '';
            if ($title) {
                try {
                    $category = $this->classifyCategory($title, $desc);
                    if ($category) {
                        $merged['category'] = $category;
                        Log::channel('ai_scan')->info("Fallback: category classified via Gemini text API", ['category' => $category]);
                    }
                } catch (Throwable $e) {
                    Log::channel('ai_scan')->warning("Fallback category classification failed: " . $e->getMessage());
                }
            }
        }

        return $merged;
    }

    /**
     * Classify book category using Gemini direct text prompt.
     *
     * @param string $title
     * @param string $description
     * @return string|null
     */
    private function classifyCategory(string $title, string $description): ?string
    {
        $prompt = <<<PROMPT
Klasifikasikan kategori buku berikut berdasarkan judul dan deskripsinya.
Judul: {$title}
Deskripsi: {$description}

Kembalikan HANYA salah satu kategori berikut dalam format JSON valid {"category": "KATEGORI"}:
"Fiksi", "Non-Fiksi", "Pendidikan", "Teknologi", "Agama", "Bisnis", "Anak", "Komik", "Sejarah", "Biografi", "Sains", "Hukum", "Kesehatan", "Referensi"

Hanya JSON, jangan ada teks penjelasan lain.
PROMPT;

        try {
            $response = $this->geminiService->callGeminiDirect($prompt, 100);
            $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            $cleaned = trim($text);
            if (str_starts_with($cleaned, '```')) {
                $cleaned = preg_replace('/^```(?:json)?\s*\n?/', '', $cleaned);
                $cleaned = preg_replace('/\n?```$/', '', $cleaned);
            }
            
            $decoded = json_decode(trim($cleaned), true);
            if (!is_array($decoded)) {
                if (preg_match('/\{[\s\S]*\}/', $cleaned, $m)) {
                    $decoded = json_decode($m[0], true);
                }
            }
            
            $category = $decoded['category'] ?? null;
            if ($category) {
                // Normalize category name
                $allowed = ["Fiksi", "Non-Fiksi", "Pendidikan", "Teknologi", "Agama", "Bisnis", "Anak", "Komik", "Sejarah", "Biografi", "Sains", "Hukum", "Kesehatan", "Referensi"];
                foreach ($allowed as $c) {
                    if (strcasecmp($category, $c) === 0) {
                        return $c;
                    }
                }
            }
        } catch (Throwable $e) {
            Log::channel('ai_scan')->warning("Failed to classify category via Gemini: " . $e->getMessage());
        }

        return null;
    }
}
