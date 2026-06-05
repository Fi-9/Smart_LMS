<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nis
 * @property string $name
 * @property string|null $class
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $photo
 * @property string $type
 * @property string $status
 * @property string|null $address
 * @property-read \Illuminate\Database\Eloquent\Collection<Borrowing> $borrowings
 */
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nis',
        'name',
        'class',
        'phone',
        'email',
        'photo',
        'type',
        'status',
        'address',
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

    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class);
    }

    public function activeBorrowings(): HasMany
    {
        return $this->borrowings()->whereIn('status', ['borrowed', 'late']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Display label: "NIS - Name (Class)"
     */
    public function getDisplayLabelAttribute(): string
    {
        $label = "{$this->nis} — {$this->name}";
        if ($this->class) {
            $label .= " ({$this->class})";
        }
        return $label;
    }
}
