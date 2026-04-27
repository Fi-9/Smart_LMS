<?php

namespace App\Services;

use App\Enums\BookStatus;
use App\Enums\BorrowingStatus;
use App\Models\Book;
use App\Models\Borrowing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BorrowingService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Borrowing::query()
            ->with(['book:id,title,author,rack_id,position_code', 'book.rack:id,name', 'member:id,nis,name,class'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('borrower_name', 'like', "%{$search}%")
                        ->orWhereHas('member', fn ($mq) => $mq->where('name', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%"))
                        ->orWhereHas('book', fn ($bq) => $bq->where('title', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('borrowed_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function borrowBook(int $bookId, ?string $borrowerName, string $dueDate, ?int $memberId = null, string $createdBy = 'admin'): Borrowing
    {
        return DB::transaction(function () use ($bookId, $borrowerName, $dueDate, $memberId, $createdBy) {
            $book = Book::query()->findOrFail($bookId);

            if ($book->isBorrowed()) {
                throw ValidationException::withMessages([
                    'book_id' => 'Buku ini sedang dipinjam oleh orang lain.',
                ]);
            }

            $borrowing = Borrowing::query()->create([
                'book_id' => $bookId,
                'member_id' => $memberId,
                'borrower_name' => $borrowerName ?? '',
                'borrowed_at' => Carbon::now(),
                'due_date' => Carbon::parse($dueDate)->endOfDay(),
                'status' => BorrowingStatus::BORROWED->value,
                'created_by' => $createdBy,
            ]);

            $book->update(['status' => BookStatus::BORROWED->value]);

            return $borrowing->load('book');
        });
    }

    public function returnBook(int $borrowingId): Borrowing
    {
        return DB::transaction(function () use ($borrowingId) {
            $borrowing = Borrowing::query()->with('book')->findOrFail($borrowingId);

            if (! $borrowing->isActive()) {
                throw ValidationException::withMessages([
                    'borrowing_id' => 'Peminjaman ini sudah dikembalikan.',
                ]);
            }

            $borrowing->update([
                'returned_at' => Carbon::now(),
                'status' => BorrowingStatus::RETURNED->value,
            ]);

            $borrowing->book->update(['status' => BookStatus::AVAILABLE->value]);

            return $borrowing->refresh()->load('book');
        });
    }

    public function markLateBorrowings(): int
    {
        return Borrowing::query()
            ->where('status', BorrowingStatus::BORROWED->value)
            ->where('due_date', '<', Carbon::now())
            ->whereNull('returned_at')
            ->update(['status' => BorrowingStatus::LATE->value]);
    }

    public function stats(): array
    {
        return [
            'total_borrowed' => Borrowing::query()->active()->count(),
            'total_late' => Borrowing::query()->late()->count(),
            'total_returned' => Borrowing::query()->returned()->count(),
            'recent_borrowings' => Borrowing::query()
                ->with(['book:id,title,author'])
                ->orderByDesc('borrowed_at')
                ->limit(5)
                ->get(['id', 'book_id', 'borrower_name', 'borrowed_at', 'due_date', 'status']),
        ];
    }
}
