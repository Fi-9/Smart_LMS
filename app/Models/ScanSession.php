<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'operator_name',
        'book_count',
        'started_at',
        'ended_at',
        'total_books',
        'waiting_count',
        'processing_count',
        'completed_count',
        'failed_count',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'book_count' => 'integer',
        'total_books' => 'integer',
        'waiting_count' => 'integer',
        'processing_count' => 'integer',
        'completed_count' => 'integer',
        'failed_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
