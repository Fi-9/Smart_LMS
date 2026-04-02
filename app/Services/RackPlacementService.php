<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Rack;
use Illuminate\Validation\ValidationException;

class RackPlacementService
{
    public function __construct(
        private readonly RackService $rackService,
        private readonly BookService $bookService
    ) {
    }

    public function assign(Rack $rack, int $bookId, string $positionCode): Book
    {
        return $this->assignBookToPosition($bookId, $rack->id, $positionCode);
    }

    public function assignBookToPosition(int $bookId, int $rackId, string $positionCode): Book
    {
        $rack = Rack::query()->findOrFail($rackId);
        $positionCode = strtoupper(trim($positionCode));

        if (! $rack->isValidPosition($positionCode)) {
            throw ValidationException::withMessages([
                'position_code' => 'Selected position is outside rack grid.',
            ]);
        }

        $book = Book::query()->findOrFail($bookId);

        return $this->bookService->assignToRackPosition($book, $rack, $positionCode);
    }
}
