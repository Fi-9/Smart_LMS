<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string $table_name
 * @property string $record_id
 * @property array|null $old_values
 * @property array|null $new_values
 * @property string|null $actor_name
 * @property string|null $actor_email
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $created_at
 * @property-read User|null $user
 */
class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'actor_name',
        'actor_email',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
