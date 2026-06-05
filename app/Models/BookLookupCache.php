<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $isbn
 * @property string|null $title_author_hash
 * @property string $title
 * @property string|null $author
 * @property string|null $publisher
 * @property int|null $published_year
 * @property string|null $description
 * @property string|null $category
 * @property string|null $cover_url
 * @property string|null $language
 * @property array|null $metadata_json
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class BookLookupCache extends Model
{
    protected $table = 'book_lookup_cache';

    protected $fillable = [
        'isbn',
        'title_author_hash',
        'title',
        'author',
        'publisher',
        'published_year',
        'description',
        'category',
        'cover_url',
        'language',
        'metadata_json',
    ];

    protected $casts = [
        'published_year' => 'integer',
        'metadata_json' => 'array',
    ];
}
