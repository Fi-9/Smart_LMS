<?php

namespace App\Support;

/**
 * Single source of truth for mapping English book categories/subjects
 * to standardized Indonesian library categories.
 */
class CategoryMapper
{
    /**
     * English → Indonesian category mapping.
     * Keys are lowercase for case-insensitive matching.
     */
    private const CATEGORY_MAP = [
        // Fiction
        'fiction' => 'Fiksi',
        'literary fiction' => 'Fiksi',
        'general fiction' => 'Fiksi',

        // Non-Fiction
        'nonfiction' => 'Non-Fiksi',
        'non-fiction' => 'Non-Fiksi',
        'general nonfiction' => 'Non-Fiksi',

        // Education
        'education' => 'Pendidikan',
        'study aids' => 'Pendidikan',
        'textbooks' => 'Pendidikan',
        'teaching' => 'Pendidikan',

        // Technology
        'technology' => 'Teknologi',
        'technology & engineering' => 'Teknologi',
        'computers' => 'Teknologi',
        'computer science' => 'Teknologi',
        'programming' => 'Teknologi',

        // Religion
        'religion' => 'Agama',
        'islam' => 'Agama',
        'christianity' => 'Agama',
        'spirituality' => 'Agama',
        'body, mind & spirit' => 'Agama',

        // Business
        'business' => 'Bisnis',
        'business & economics' => 'Bisnis',
        'economics' => 'Bisnis',
        'finance' => 'Bisnis',
        'management' => 'Bisnis',

        // Children
        'juvenile fiction' => 'Anak',
        'juvenile nonfiction' => 'Anak',
        'children' => 'Anak',
        'young adult fiction' => 'Anak',
        'young adult nonfiction' => 'Anak',

        // Comics
        'comics' => 'Komik',
        'comics & graphic novels' => 'Komik',
        'manga' => 'Komik',
        'graphic novels' => 'Komik',

        // History
        'history' => 'Sejarah',
        'world history' => 'Sejarah',

        // Biography
        'biography' => 'Biografi',
        'biography & autobiography' => 'Biografi',
        'autobiography' => 'Biografi',

        // Science
        'science' => 'Sains',
        'mathematics' => 'Sains',
        'physics' => 'Sains',
        'chemistry' => 'Sains',
        'biology' => 'Sains',
        'nature' => 'Sains',

        // Law
        'law' => 'Hukum',
        'political science' => 'Hukum',

        // Health
        'health' => 'Kesehatan',
        'medical' => 'Kesehatan',
        'health & fitness' => 'Kesehatan',
        'psychology' => 'Kesehatan',
        'self-help' => 'Kesehatan',

        // Reference
        'reference' => 'Referensi',
        'encyclopedias' => 'Referensi',
        'dictionaries' => 'Referensi',
    ];

    /**
     * Categories that are already in Indonesian — skip mapping.
     */
    private const INDONESIAN_CATEGORIES = [
        'Fiksi', 'Non-Fiksi', 'Pendidikan', 'Teknologi', 'Agama',
        'Bisnis', 'Anak', 'Komik', 'Sejarah', 'Biografi',
        'Sains', 'Hukum', 'Kesehatan', 'Referensi',
    ];

    /**
     * Map a category string to its Indonesian equivalent.
     * Returns the original string if no mapping is found.
     */
    public static function toIndonesian(?string $category): ?string
    {
        if ($category === null || trim($category) === '') {
            return null;
        }

        $trimmed = trim($category);

        // Already Indonesian
        foreach (self::INDONESIAN_CATEGORIES as $indo) {
            if (strcasecmp($trimmed, $indo) === 0) {
                return $indo;
            }
        }

        // Exact match (case-insensitive)
        $lower = strtolower($trimmed);
        if (isset(self::CATEGORY_MAP[$lower])) {
            return self::CATEGORY_MAP[$lower];
        }

        // Partial match: check if any key is contained in the category
        foreach (self::CATEGORY_MAP as $key => $value) {
            if (str_contains($lower, $key)) {
                return $value;
            }
        }

        // No mapping found — return original
        return $trimmed;
    }
}
