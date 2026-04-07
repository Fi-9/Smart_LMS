<?php

namespace App\Services;

use App\Enums\BookStatus;
use App\Jobs\GenerateBookQrCodeJob;
use App\Models\Book;
use App\Models\Rack;
use App\Repositories\Contracts\BookRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookService
{
    public function __construct(
        private readonly BookRepositoryInterface $bookRepository,
        private readonly RackService $rackService,
        private readonly QrCodeService $qrCodeService
    ) {
    }

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->bookRepository->paginate($filters, $perPage);
    }

    public function findOrFail(int $bookId): Book
    {
        return $this->bookRepository->findOrFail($bookId);
    }

    public function create(array $attributes): Book
    {
        return DB::transaction(function () use ($attributes) {
            if (! isset($attributes['status'])) {
                $attributes['status'] = BookStatus::AVAILABLE->value;
            }

            $book = $this->bookRepository->create($attributes);

            if (! $book->isAssigned()) {
                $this->autoAssignFirstAvailableSlot($book);
            }

            GenerateBookQrCodeJob::dispatch($book->id)->afterCommit();

            return $book->refresh();
        });
    }

    public function createManual(array $attributes): Book
    {
        return DB::transaction(function () use ($attributes) {
            $preferredRackId = $attributes['rack_id'] ?? null;

            if (! isset($attributes['status'])) {
                $attributes['status'] = BookStatus::AVAILABLE->value;
            }

            $attributes['rack_id'] = null;
            $attributes['position_code'] = null;

            $book = $this->bookRepository->create($attributes);

            if ($preferredRackId) {
                $slot = $this->rackService->firstAvailableSlotInRack((int) $preferredRackId);

                if ($slot) {
                    $rack = Rack::query()->find($slot['rack_id']);
                    if ($rack) {
                        $this->assignToRackPosition($book, $rack, $slot['position_code']);
                    }
                }
            }

            if (! $book->isAssigned()) {
                $this->autoAssignFirstAvailableSlot($book);
            }

            GenerateBookQrCodeJob::dispatch($book->id)->afterCommit();

            return $book->refresh();
        });
    }

    public function update(Book $book, array $attributes): Book
    {
        return $this->bookRepository->update($book, $attributes);
    }

    public function delete(Book $book): void
    {
        $this->bookRepository->delete($book);
    }

    public function assignToRackPosition(Book $book, Rack $rack, string $positionCode): Book
    {
        $positionCode = strtoupper(trim($positionCode));
        $capacity = $rack->capacity_per_slot ?? 1;

        $currentCount = Book::query()
            ->where('rack_id', $rack->id)
            ->where('position_code', $positionCode)
            ->where('id', '!=', $book->id)
            ->count();

        if ($currentCount >= $capacity) {
            throw ValidationException::withMessages([
                'position_code' => "Selected rack position '{$positionCode}' is already at full capacity ({$capacity} books).",
            ]);
        }

        return $this->bookRepository->update($book, [
            'rack_id' => $rack->id,
            'position_code' => $positionCode,
        ]);
    }

    public function autoAssignUnassignedBooks(int $limit = 50): int
    {
        $assignedCount = 0;
        $slots = $this->rackService->availableSlots();

        if ($slots === []) {
            return 0;
        }

        $unassignedBooks = Book::query()
            ->unassigned()
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($unassignedBooks as $book) {
            $slot = array_shift($slots);
            if (! $slot) {
                break;
            }

            $rack = Rack::query()->find($slot['rack_id']);
            if (! $rack) {
                continue;
            }

            try {
                $this->assignToRackPosition($book, $rack, $slot['position_code']);
                $assignedCount++;
            } catch (ValidationException) {
                continue;
            }
        }

        return $assignedCount;
    }

    public function queueMissingQrCodes(?int $rackId = null, ?int $categoryId = null, array $selectedBookIds = []): int
    {
        $query = Book::query()
            ->when($rackId, fn ($q) => $q->where('rack_id', $rackId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($selectedBookIds !== [], fn ($q) => $q->whereIn('id', $selectedBookIds))
            ->where(function ($q) {
                $q->whereNull('qr_code_path')->orWhereNull('qr_code');
            })
            ->orderBy('id');

        $bookIds = $query->pluck('id')->all();

        foreach ($bookIds as $bookId) {
            GenerateBookQrCodeJob::dispatchSync((int) $bookId);
        }

        return count($bookIds);
    }

    public function generateQrCode(Book $book): string
    {
        $base64 = $this->qrCodeService->generateBase64($book->id);
        
        $book->update(['qr_code' => $base64]);
        
        return $base64;
    }

    private function autoAssignFirstAvailableSlot(Book $book): void
    {
        $slot = $this->rackService->availableSlots()[0] ?? null;

        if (! $slot) {
            return;
        }

        $rack = Rack::query()->find($slot['rack_id']);
        if (! $rack) {
            return;
        }

        $this->assignToRackPosition($book, $rack, $slot['position_code']);
    }
}
