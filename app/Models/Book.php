<?php

namespace App\Models;

use App\Enums\BookStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $title
 * @property string $author
 * @property string|null $isbn
 * @property int|null $category_id
 * @property int|null $rack_id
 * @property string|null $position_code
 * @property string|null $cover_url
 * @property string|null $qr_code_path
 * @property string|null $qr_code
 * @property BookStatus $status
 * @property-read Category|null $category
 * @property-read Rack|null $rack
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Borrowing> $borrowings
 * @property-read Borrowing|null $activeBorrowing
 */
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'author',
        'isbn',
        'category_id',
        'rack_id',
        'position_code',
        'cover_url',
        'qr_code_path',
        'qr_code',
        'status',
        'description',
        'condition_notes',
    ];

    protected $casts = [
        'status' => BookStatus::class,
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

    public function scanResults(): HasMany
    {
        return $this->hasMany(AiScanResult::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class);
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class);
    }

    public function activeBorrowing(): HasOne
    {
        return $this->hasOne(Borrowing::class)
            ->whereIn('status', ['borrowed', 'late'])
            ->latestOfMany('borrowed_at');
    }

    public function isAssigned(): bool
    {
        return ! is_null($this->rack_id) && ! is_null($this->position_code);
    }

    public function isAvailable(): bool
    {
        return $this->status === BookStatus::AVAILABLE;
    }

    public function isBorrowed(): bool
    {
        return $this->status === BookStatus::BORROWED;
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('rack_id')->whereNull('position_code');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', BookStatus::AVAILABLE->value);
    }

    public function scopeBorrowed(Builder $query): Builder
    {
        return $query->where('status', BookStatus::BORROWED->value);
    }
}
