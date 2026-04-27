<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class QrCodeService
{
    public function generateBookQrPath(int $bookId): string
    {
        $targetUrl = route('books.web.show', $bookId);
        $qrPayload = $this->buildQrPayload($targetUrl);
        $relativePath = "qrcodes/book-{$bookId}.{$qrPayload['extension']}";
        $publicPath = "storage/{$relativePath}";

        Storage::disk('public')->put(
            $relativePath,
            $qrPayload['raw']
        );

        return "/{$publicPath}";
    }

    public function generateBase64(int $bookId): string
    {
        $targetUrl = route('books.web.show', $bookId);
        $qrPayload = $this->buildQrPayload($targetUrl);

        return 'data:' . $qrPayload['mime'] . ';base64,' . base64_encode($qrPayload['raw']);
    }

    private function buildQrPayload(string $targetUrl): array
    {
        $logoPath = app(\App\Services\AppSettingsService::class)->get('school_logo_path');
        $absoluteLogoPath = $logoPath ? storage_path('app/public/' . $logoPath) : null;

        try {
            $qr = QrCode::format('png')
                ->size(300)
                ->margin(1)
                ->errorCorrection('H');
                
            if ($absoluteLogoPath && file_exists($absoluteLogoPath)) {
                $qr = $qr->merge($absoluteLogoPath, 0.25, true);
            }

            return [
                'raw' => $qr->generate($targetUrl),
                'mime' => 'image/png',
                'extension' => 'png',
            ];
        } catch (Throwable) {
            $qrSvg = QrCode::format('svg')
                ->size(300)
                ->margin(1)
                ->errorCorrection('H');
                
            if ($absoluteLogoPath && file_exists($absoluteLogoPath)) {
                $qrSvg = $qrSvg->merge($absoluteLogoPath, 0.25, true);
            }

            return [
                'raw' => $qrSvg->generate($targetUrl),
                'mime' => 'image/svg+xml',
                'extension' => 'svg',
            ];
        }
    }
}
