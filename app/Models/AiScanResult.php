<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $book_id
 * @property array $ocr_result
 * @property float|null $confidence
 * @property string|null $model_name
 * @property string $created_at
 * @property-read Book $book
 */
class AiScanResult extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'book_id',
        'ocr_result',
        'confidence',
        'model_name',
    ];

    protected $casts = [
        'ocr_result' => 'array',
        'confidence' => 'float',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
