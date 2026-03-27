<?php

namespace App\Jobs;

use App\Models\Book;
use App\Services\QrCodeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateBookQrCodeJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $bookId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(QrCodeService $qrCodeService): void
    {
        $book = Book::query()->find($this->bookId);

        if (! $book) {
            return;
        }

        $qrCodePath = $qrCodeService->generateBookQrPath($book->id);
        $book->update(['qr_code_path' => $qrCodePath]);
    }
}
