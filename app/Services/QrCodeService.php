<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function generateBookQrPath(int $bookId): string
    {
        $relativePath = "qrcodes/book-{$bookId}.png";
        $publicPath = "storage/{$relativePath}";
        $targetUrl = route('books.public.show', $bookId);

        Storage::disk('public')->put(
            $relativePath,
            QrCode::format('png')->size(300)->errorCorrection('H')->generate($targetUrl)
        );

        return "/{$publicPath}";
    }

    public function generateBase64(int $bookId): string
    {
        $targetUrl = route('books.public.show', $bookId);
        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($targetUrl);
        
        return 'data:image/png;base64,' . base64_encode($qrCode);
    }
}

