<?php

namespace App\Models;

use App\Enums\BookStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function isAssigned(): bool
    {
        return ! is_null($this->rack_id) && ! is_null($this->position_code);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('rack_id')->whereNull('position_code');
    }
}
