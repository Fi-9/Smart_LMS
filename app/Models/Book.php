<?php

namespace App\Models;

use App\Enums\BookStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Book extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'status' => BookStatus::class,
    ];

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

    public function scopeUnassigned($query)
    {
        return $query->whereNull('rack_id')->whereNull('position_code');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', BookStatus::AVAILABLE->value);
    }

    public function scopeBorrowed($query)
    {
        return $query->where('status', BookStatus::BORROWED->value);
    }
}
