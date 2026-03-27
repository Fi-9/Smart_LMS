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
        private readonly RackService $rackService
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

        $occupied = Book::query()
            ->where('rack_id', $rack->id)
            ->where('position_code', $positionCode)
            ->where('id', '!=', $book->id)
            ->exists();

        if ($occupied) {
            throw ValidationException::withMessages([
                'position_code' => 'Selected rack position is already occupied.',
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
