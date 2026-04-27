<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $room_id
 * @property string $name
 * @property int $rows
 * @property int $columns
 * @property int|null $capacity_per_slot
 * @property array|null $column_categories
 * @property array|null $metadata
 * @property-read Room|null $room
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Book> $books
 */
class Rack extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'name',
        'rows',
        'columns',
        'capacity_per_slot',
        'column_categories',
        'metadata',
    ];

    protected $casts = [
        'rows' => 'integer',
        'columns' => 'integer',
        'capacity_per_slot' => 'integer',
        'column_categories' => 'array',
        'metadata' => 'array',
    ];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function isValidPosition(string $positionCode): bool
    {
        return in_array(strtoupper(trim($positionCode)), $this->generatePositions(), true);
    }

    public function generatePositions(): array
    {
        $positions = [];

        for ($rowIndex = 0; $rowIndex < $this->rows; $rowIndex++) {
            for ($columnIndex = 1; $columnIndex <= $this->columns; $columnIndex++) {
                $positions[] = chr(65 + $rowIndex).$columnIndex;
            }
        }

        return $positions;
    }

    public function slotCategoryMap(): array
    {
        $metadata = $this->metadata ?? [];
        $slotCategories = $metadata['slot_categories'] ?? [];

        return is_array($slotCategories) ? $slotCategories : [];
    }

    public function slotCategoryId(string $positionCode): ?int
    {
        $slotCategories = $this->slotCategoryMap();
        $value = $slotCategories[strtoupper(trim($positionCode))] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }
}
