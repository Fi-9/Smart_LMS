<?php

namespace App\Models;

use App\Enums\BorrowingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Borrowing extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'borrower_name',
        'borrowed_at',
        'due_date',
        'returned_at',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => BorrowingStatus::class,
        'borrowed_at' => 'datetime',
        'due_date' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [BorrowingStatus::BORROWED, BorrowingStatus::LATE]);
    }

    public function isLate(): bool
    {
        return $this->status === BorrowingStatus::LATE
            || ($this->status === BorrowingStatus::BORROWED && $this->due_date->isPast());
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [BorrowingStatus::BORROWED->value, BorrowingStatus::LATE->value]);
    }

    public function scopeReturned($query)
    {
        return $query->where('status', BorrowingStatus::RETURNED->value);
    }

    public function scopeLate($query)
    {
        return $query->where('status', BorrowingStatus::LATE->value);
    }
}
