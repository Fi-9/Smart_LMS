<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $scan_session_id
 * @property int|null $scanned_by
 * @property int|null $rack_id
 * @property string|null $title
 * @property string|null $author
 * @property string|null $isbn
 * @property string|null $publisher
 * @property int|null $published_year
 * @property string|null $description
 * @property string|null $category
 * @property string|null $language
 * @property string|null $cover_front_path
 * @property string|null $cover_back_path
 * @property string|null $source
 * @property string|null $source_url
 * @property float $confidence
 * @property string $status  // pending, approved, rejected, routed
 * @property string|null $rejection_reason
 * @property array|null $scan_data
 * @property string|null $position_code
 * @property int|null $routed_by
 * @property string|null $routed_at
 */
class BookInbox extends Model
{
    use SoftDeletes;

    protected $table = 'book_inbox';

    protected $fillable = [
        'scan_session_id',
        'scanned_by',
        'scan_job_id',
        'rack_id',
        'title',
        'author',
        'isbn',
        'publisher',
        'published_year',
        'description',
        'category',
        'language',
        'cover_front_path',
        'cover_back_path',
        'source',
        'source_url',
        'confidence',
        'confidence_score',
        'status',
        'rejection_reason',
        'scan_data',
        'position_code',
        'routed_by',
        'routed_at',
        'processing_notes',
        'source_chain',
        'stage_completed_at',
        'metadata_completeness',
        'metadata_missing',
    ];

    protected $casts = [
        'scan_data' => 'array',
        'source_chain' => 'array',
        'metadata_missing' => 'array',
        'confidence' => 'float',
        'confidence_score' => 'integer',
        'metadata_completeness' => 'integer',
        'published_year' => 'integer',
        'routed_at' => 'datetime',
        'stage_completed_at' => 'datetime',
    ];

    public function scanSession(): BelongsTo
    {
        return $this->belongsTo(ScanSession::class);
    }

    public function scanJob(): BelongsTo
    {
        return $this->belongsTo(ScanJob::class);
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class);
    }

    public function routedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'routed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeRouted($query)
    {
        return $query->where('status', 'routed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
