<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CoverImageService
{
    /**
     * Download a cover image from an external URL to local storage.
     * No GD required — saves raw bytes directly.
     *
     * @param string $url The external cover URL (Google Books / OpenLibrary)
     * @param int $scanJobId Used to generate a unique filename
     * @return string|null Local web path (e.g. /storage/book-covers/isbn-123-1749219200.jpg) or null on failure
     */
    public function downloadFromUrl(string $url, int $scanJobId): ?string
    {
        if (trim($url) === '' || !str_starts_with(trim($url), 'http')) {
            return null;
        }

        try {
            $http = Http::timeout(10);
            if (!config('services.ai_scan.tls_verify', true)) {
                $http = $http->withoutVerifying();
            }
            $response = $http->get($url);

            if (!$response->ok()) {
                Log::channel('ai_scan')->warning('Cover download: HTTP failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $body = $response->body();
            if (strlen($body) < 100) {
                // Too small to be a real image
                Log::channel('ai_scan')->warning('Cover download: response body too small', [
                    'url' => $url,
                    'size' => strlen($body),
                ]);
                return null;
            }

            // Detect extension from Content-Type header
            $contentType = $response->header('Content-Type') ?? '';
            $ext = match (true) {
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'gif') => 'gif',
                default => 'jpg',
            };

            $timestamp = now()->timestamp;
            $relativePath = "book-covers/isbn-{$scanJobId}-{$timestamp}.{$ext}";

            Storage::disk('public')->put($relativePath, $body);

            Log::channel('ai_scan')->info('Cover downloaded to local storage', [
                'url' => $url,
                'path' => $relativePath,
                'size' => strlen($body),
            ]);

            return '/storage/' . $relativePath;
        } catch (Throwable $e) {
            Log::channel('ai_scan')->warning('Cover download failed: ' . $e->getMessage(), [
                'url' => $url,
            ]);
            return null;
        }
    }

    /**
     * Crop front cover image using AI-provided normalized cover box.
     * If box is missing/invalid, fallback to center crop with 2:3 ratio.
     *
     * @param array{x:float,y:float,w:float,h:float}|null $coverBox
     */
    public function cropFrontCover(string $webPath, ?array $coverBox = null): ?string
    {
        return $this->processCover($webPath, $coverBox, 'front-cropped');
    }

    public function normalizeCoverFromUpload(string $webPath): ?string
    {
        return $this->processCover($webPath, null, 'normalized');
    }

    /**
     * @param array{x:float,y:float,w:float,h:float}|null $coverBox
     */
    private function processCover(string $webPath, ?array $coverBox, string $suffix): ?string
    {
        $relative = $this->toPublicDiskRelativePath($webPath);
        if (! $relative) {
            return null;
        }

        $sourceAbsPath = storage_path('app/public/' . $relative);
        if (! is_file($sourceAbsPath)) {
            return null;
        }

        if (!extension_loaded('gd') || !function_exists('imagecreatefromstring')) {
            \Illuminate\Support\Facades\Log::warning('GD extension not loaded. Skipping cover image processing and returning original path.', [
                'path' => $webPath
            ]);
            return $webPath;
        }

        $raw = @file_get_contents($sourceAbsPath);
        if ($raw === false) {
            return null;
        }

        $src = @imagecreatefromstring($raw);
        if (! $src && function_exists('imagecreatefromavif')) {
            $src = @imagecreatefromavif($sourceAbsPath);
        }
        if (! $src && function_exists('imagecreatefromwebp')) {
            $src = @imagecreatefromwebp($sourceAbsPath);
        }
        if (! $src) {
            return null;
        }

        try {
            $width = imagesx($src);
            $height = imagesy($src);
            if ($width <= 0 || $height <= 0) {
                return null;
            }

            [$x, $y, $w, $h] = $this->resolveCropRect($width, $height, $coverBox);

            $dest = imagecreatetruecolor($w, $h);
            if (! $dest) {
                return null;
            }

            // Preserve alpha for png/webp.
            imagealphablending($dest, false);
            imagesavealpha($dest, true);

            if (! imagecopyresampled($dest, $src, 0, 0, $x, $y, $w, $h, $w, $h)) {
                imagedestroy($dest);

                return null;
            }

            $normalized = $this->resizeToCoverStandard($dest);
            if (! $normalized) {
                imagedestroy($dest);

                return null;
            }

            $croppedRelativePath = $this->buildDerivedPath($relative, $suffix);
            $croppedAbsPath = storage_path('app/public/' . $croppedRelativePath);
            $croppedDir = dirname($croppedAbsPath);
            if (! is_dir($croppedDir) && ! @mkdir($croppedDir, 0775, true) && ! is_dir($croppedDir)) {
                imagedestroy($normalized);
                imagedestroy($dest);

                return null;
            }

            $ext = strtolower(pathinfo($croppedAbsPath, PATHINFO_EXTENSION));
            $saved = match ($ext) {
                'jpg', 'jpeg' => imagejpeg($normalized, $croppedAbsPath, 90),
                'png' => imagepng($normalized, $croppedAbsPath, 6),
                'webp' => function_exists('imagewebp') ? imagewebp($normalized, $croppedAbsPath, 85) : false,
                default => imagejpeg($normalized, $croppedAbsPath, 90),
            };

            imagedestroy($normalized);
            imagedestroy($dest);

            if (! $saved) {
                return null;
            }

            // Ensure path is visible via /storage symlink.
            if (! Storage::disk('public')->exists($croppedRelativePath)) {
                // In rare cases, the filesystem cache may lag.
                Storage::disk('public')->put($croppedRelativePath, file_get_contents($croppedAbsPath));
            }

            return '/storage/' . str_replace('\\', '/', $croppedRelativePath);
        } finally {
            imagedestroy($src);
        }
    }

    /**
     * @param array{x:float,y:float,w:float,h:float}|null $coverBox
     * @return array{int,int,int,int}
     */
    private function resolveCropRect(int $imgW, int $imgH, ?array $coverBox): array
    {
        if ($coverBox && $coverBox['w'] > 0.02 && $coverBox['h'] > 0.02) {
            $x = (int) floor($coverBox['x'] * $imgW);
            $y = (int) floor($coverBox['y'] * $imgH);
            $w = (int) floor($coverBox['w'] * $imgW);
            $h = (int) floor($coverBox['h'] * $imgH);

            // Add 5% padding
            $paddingW = (int) floor($w * 0.05);
            $paddingH = (int) floor($h * 0.05);

            $x -= $paddingW;
            $y -= $paddingH;
            $w += $paddingW * 2;
            $h += $paddingH * 2;

            $x = max(0, min($imgW - 1, $x));
            $y = max(0, min($imgH - 1, $y));
            $w = max(1, min($imgW - $x, $w));
            $h = max(1, min($imgH - $y, $h));

            return [$x, $y, $w, $h];
        }

        // Fallback: center crop with typical book ratio 2:3 (w:h).
        $targetRatio = 2 / 3;
        $imgRatio = $imgW / $imgH;

        if ($imgRatio > $targetRatio) {
            // too wide -> crop width
            $h = $imgH;
            $w = (int) round($h * $targetRatio);
            $x = (int) round(($imgW - $w) / 2);
            $y = 0;
        } else {
            // too tall -> crop height
            $w = $imgW;
            $h = (int) round($w / $targetRatio);
            $x = 0;
            $y = (int) round(($imgH - $h) / 2);
        }

        $w = max(1, min($imgW, $w));
        $h = max(1, min($imgH, $h));
        $x = max(0, min($imgW - $w, $x));
        $y = max(0, min($imgH - $h, $y));

        return [$x, $y, $w, $h];
    }

    private function toPublicDiskRelativePath(string $webPath): ?string
    {
        $trimmed = trim($webPath);
        if ($trimmed === '') {
            return null;
        }

        if (! str_starts_with($trimmed, '/storage/')) {
            return null;
        }

        return ltrim(substr($trimmed, strlen('/storage/')), '/');
    }

    private function buildDerivedPath(string $originalRelativePath, string $suffix): string
    {
        $ext = pathinfo($originalRelativePath, PATHINFO_EXTENSION);
        $filename = pathinfo($originalRelativePath, PATHINFO_FILENAME);
        $dirname = trim(pathinfo($originalRelativePath, PATHINFO_DIRNAME), '.');

        if ($ext === '') {
            throw new RuntimeException('Source image extension is missing.');
        }

        $targetDir = $dirname === '' ? 'book-scans/cropped' : $dirname . '/cropped';

        return trim($targetDir, '/') . '/' . $filename . '-' . $suffix . '.' . $ext;
    }

    private function resizeToCoverStandard(\GdImage $source): ?\GdImage
    {
        $srcW = imagesx($source);
        $srcH = imagesy($source);

        $maxWidth = max(120, (int) config('services.ai_scan.cover_width', 600));
        $maxHeight = max(180, (int) config('services.ai_scan.cover_height', 900));

        $ratio = min($maxWidth / max(1, $srcW), $maxHeight / max(1, $srcH));
        $targetWidth = max(1, (int) round($srcW * $ratio));
        $targetHeight = max(1, (int) round($srcH * $ratio));

        $dest = imagecreatetruecolor($targetWidth, $targetHeight);
        if (! $dest) {
            return null;
        }

        imagealphablending($dest, false);
        imagesavealpha($dest, true);

        $ok = imagecopyresampled(
            $dest,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $srcW,
            $srcH
        );

        if (! $ok) {
            imagedestroy($dest);

            return null;
        }

        return $dest;
    }
}
