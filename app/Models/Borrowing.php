<?php

namespace App\Models;

use App\Enums\BorrowingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $book_id
 * @property int|null $member_id
 * @property string $borrower_name
 * @property \Illuminate\Support\Carbon $borrowed_at
 * @property \Illuminate\Support\Carbon $due_date
 * @property \Illuminate\Support\Carbon|null $returned_at
 * @property BorrowingStatus $status
 * @property string|null $created_by
 * @property-read Book $book
 * @property-read Member|null $member
 */
use Illuminate\Database\Eloquent\SoftDeletes;

class Borrowing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'book_id',
        'member_id',
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

    protected static function booted(): void
    {
        static::created(function ($model) {
            app(\App\Services\AuditLogService::class)->log('create', $model->getTable(), (string) $model->id, null, $model->toArray());
        });

        static::updated(function ($model) {
            $old = array_intersect_key($model->getOriginal(), $model->getDirty());
            $new = $model->getDirty();
            app(\App\Services\AuditLogService::class)->log('update', $model->getTable(), (string) $model->id, $old, $new);
        });

        static::deleted(function ($model) {
            $action = method_exists($model, 'isForceDeleting') && $model->isForceDeleting() ? 'force_delete' : 'delete';
            app(\App\Services\AuditLogService::class)->log($action, $model->getTable(), (string) $model->id, $model->toArray(), null);
        });

        static::restored(function ($model) {
            app(\App\Services\AuditLogService::class)->log('restore', $model->getTable(), (string) $model->id, null, $model->toArray());
        });
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the borrower display name — uses member name if linked, otherwise falls back to borrower_name string.
     */
    public function getBorrowerDisplayAttribute(): string
    {
        return $this->member?->name ?? $this->borrower_name;
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [BorrowingStatus::BORROWED->value, BorrowingStatus::LATE->value]);
    }

    public function scopeReturned(Builder $query): Builder
    {
        return $query->where('status', BorrowingStatus::RETURNED->value);
    }

    public function scopeLate(Builder $query): Builder
    {
        return $query->where('status', BorrowingStatus::LATE->value);
    }
}
