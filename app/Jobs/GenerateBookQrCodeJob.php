<?php

namespace App\Jobs;

use App\Models\Book;
use App\Services\QrCodeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateBookQrCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function backoff(): array
    {
        return [2, 10, 30];
    }

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
