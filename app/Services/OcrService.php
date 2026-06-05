<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * OCR Book Cover Service
 *
 * Uses Python + Tesseract OCR to extract metadata from book cover images.
 * Fallback when Gemini Vision is unavailable or user prefers OCR.
 */
class OcrService
{
    private string $scriptPath;
    private int $timeout;

    public function __construct()
    {
        $this->scriptPath = base_path('scripts/ocr_book_cover.py');
        $this->timeout = 60;
    }

    /**
     * Extract book metadata from cover images using Tesseract OCR.
     *
     * @param array<int, UploadedFile> $images
     * @return array{images: array, best: array}
     */
    public function extract(array $images): array
    {
        if ($images === []) {
            return $this->emptyResult();
        }

        // Save images to temp files
        $tempPaths = [];
        foreach ($images as $i => $file) {
            $tempPath = Storage::disk('local')->path('temp/ocr_' . Str::uuid() . '.jpg');
            $dir = dirname($tempPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($tempPath, file_get_contents($file->getRealPath()));
            $tempPaths[] = $tempPath;
        }

        try {
            $output = $this->runPythonScript($tempPaths);
            return $this->parseOutput($output);
        } finally {
            // Clean up temp files
            foreach ($tempPaths as $p) {
                @unlink($p);
            }
        }
    }

    /**
     * Check if Tesseract OCR is available on this system.
     */
    public function isAvailable(): bool
    {
        $checkScript = $this->scriptPath;

        // Check both tesseract and python dependencies
        $command = sprintf(
            '%s -c "import pytesseract; from PIL import Image; print(\"OK\")" 2>&1',
            escapeshellarg($this->getPythonExecutable())
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        return $exitCode === 0 && trim(implode("\n", $output)) === 'OK';
    }

    private function runPythonScript(array $tempPaths): string
    {
        $paths = implode(' ', array_map('escapeshellarg', $tempPaths));
        $command = sprintf(
            '%s %s %s 2>&1',
            escapeshellarg($this->getPythonExecutable()),
            escapeshellarg($this->scriptPath),
            $paths
        );

        Log::channel('ai_scan')->info('Running OCR', ['command' => $command]);

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        // Set TESSERACT_PATH in environment
        $env = $_ENV;
        $env['TESSERACT_PATH'] = 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe';
        $env['OCR_LANG'] = 'ind+eng';

        $process = proc_open($command, $descriptorSpec, $pipes, null, $env);

        if (!is_resource($process)) {
            throw new RuntimeException('Gagal menjalankan proses OCR Python.');
        }

        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0 || empty(trim((string)$stdout))) {
            $error = trim((string)$stderr) ?: 'OCR process returned exit code ' . $exitCode;
            Log::channel('ai_scan')->error('OCR failed', ['error' => $error, 'stdout' => $stdout]);
            throw new RuntimeException('OCR gagal: ' . $error);
        }

        return (string)$stdout;
    }

    private function parseOutput(string $raw): array
    {
        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            Log::channel('ai_scan')->warning('OCR returned invalid JSON', ['raw' => substr($raw, 0, 500)]);
            throw new RuntimeException('OCR mengembalikan data tidak valid.');
        }

        if (isset($decoded['error'])) {
            throw new RuntimeException('OCR error: ' . $decoded['error']);
        }

        $best = $decoded['best'] ?? [];
        return [
            'images' => $decoded['images'] ?? [],
            'best' => [
                'isbn' => $best['isbn'] ?? null,
                'title' => $best['title'] ?? null,
                'author' => $best['author'] ?? null,
                'publisher' => $best['publisher'] ?? null,
                'category' => $best['category'] ?? null,
                'front_image_index' => 0,
            ],
        ];
    }

    private function emptyResult(): array
    {
        return [
            'images' => [],
            'best' => [
                'isbn' => null,
                'title' => null,
                'author' => null,
                'publisher' => null,
                'category' => null,
                'front_image_index' => null,
            ],
        ];
    }

    private function getPythonExecutable(): string
    {
        $localPath = 'C:\\Users\\renre\\.cache\\codex-runtimes\\codex-primary-runtime\\dependencies\\python\\python.exe';
        if (file_exists($localPath)) {
            return $localPath;
        }
        return 'python';
    }
}
