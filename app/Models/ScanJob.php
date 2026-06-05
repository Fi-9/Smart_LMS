<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $scan_session_id
 * @property string $front_cover_path
 * @property string|null $back_cover_path
 * @property string|null $front_cover_hash
 * @property string|null $back_cover_hash
 * @property string $priority
 * @property string $status // waiting, processing, completed, failed
 * @property int $attempts
 * @property int|null $confidence_score
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ScanJob extends Model
{
    protected $table = 'scan_jobs';

    protected $fillable = [
        'scan_session_id',
        'front_cover_path',
        'back_cover_path',
        'front_cover_hash',
        'back_cover_hash',
        'priority',
        'status',
        'attempts',
        'confidence_score',
        'error_message',
        'started_at',
        'finished_at',
        'current_stage',
        'stage_status',
        'stage_message',
        'pipeline_metrics',
        'identification_result',
    ];

    protected $casts = [
        'scan_session_id' => 'integer',
        'attempts' => 'integer',
        'confidence_score' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'pipeline_metrics' => 'array',
        'identification_result' => 'array',
    ];

    public function scanSession(): BelongsTo
    {
        return $this->belongsTo(ScanSession::class, 'scan_session_id');
    }
}
