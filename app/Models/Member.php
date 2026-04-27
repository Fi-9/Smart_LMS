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
class Member extends Model
{
    use HasFactory;

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
