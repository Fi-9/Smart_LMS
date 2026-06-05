<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $scan_id
 * @property string $provider
 * @property int $duration_ms
 * @property string $status
 * @property string|null $error
 * @property \Illuminate\Support\Carbon $created_at
 */
class ScanPipelineLog extends Model
{
    protected $table = 'scan_pipeline_logs';

    public $timestamps = false;

    protected $fillable = [
        'scan_id',
        'provider',
        'duration_ms',
        'status',
        'error',
    ];

    protected $casts = [
        'duration_ms' => 'integer',
        'created_at' => 'datetime',
    ];
}
