<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string $status
 * @property string $accent
 * @property int $sort_order
 * @property-read \Illuminate\Database\Eloquent\Collection<Rack> $racks
 * @property-read \Illuminate\Database\Eloquent\Collection<Book> $books
 */
class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'accent',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function racks(): HasMany
    {
        return $this->hasMany(Rack::class);
    }

    public function books(): HasManyThrough
    {
        return $this->hasManyThrough(Book::class, Rack::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Accent color map for UI rendering.
     */
    public function getAccentClassesAttribute(): array
    {
        $map = [
            'emerald' => [
                'soft' => 'from-emerald-100 to-emerald-50 text-emerald-800',
                'icon' => 'bg-emerald-100 text-emerald-700',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'chip' => 'bg-emerald-600',
            ],
            'sky' => [
                'soft' => 'from-sky-100 to-sky-50 text-sky-800',
                'icon' => 'bg-sky-100 text-sky-700',
                'badge' => 'bg-sky-50 text-sky-700 border-sky-200',
                'chip' => 'bg-sky-600',
            ],
            'amber' => [
                'soft' => 'from-amber-100 to-amber-50 text-amber-800',
                'icon' => 'bg-amber-100 text-amber-700',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
                'chip' => 'bg-amber-500',
            ],
            'rose' => [
                'soft' => 'from-rose-100 to-rose-50 text-rose-800',
                'icon' => 'bg-rose-100 text-rose-700',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
                'chip' => 'bg-rose-600',
            ],
            'violet' => [
                'soft' => 'from-violet-100 to-violet-50 text-violet-800',
                'icon' => 'bg-violet-100 text-violet-700',
                'badge' => 'bg-violet-50 text-violet-700 border-violet-200',
                'chip' => 'bg-violet-600',
            ],
        ];

        return $map[$this->accent] ?? $map['emerald'];
    }
}
