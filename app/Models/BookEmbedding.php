<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $book_id
 * @property string $embedding -- PGVector format
 * @property string $model_name
 * @property string $created_at
 * @property-read Book $book
 */
class BookEmbedding extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'book_id',
        'embedding',
        'model_name',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
