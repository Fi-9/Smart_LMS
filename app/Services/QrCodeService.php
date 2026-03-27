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
        $targetUrl = url("/book/{$bookId}");

        Storage::disk('public')->put(
            $relativePath,
            QrCode::format('png')->size(300)->generate($targetUrl)
        );

        return "/{$publicPath}";
    }
}

