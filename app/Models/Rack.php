<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $rows
 * @property int $columns
 * @property int|null $capacity_per_slot
 * @property string|null $column_category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Book> $books
 */
class Rack extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rows',
        'columns',
        'capacity_per_slot',
        'column_category',
    ];

    protected $casts = [
        'rows' => 'integer',
        'columns' => 'integer',
        'capacity_per_slot' => 'integer',
    ];

    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
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
}
