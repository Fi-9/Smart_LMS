<?php

namespace App\Services;

use App\Enums\BookStatus;
use App\Models\Book;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BulkImportService
{
    private const PREVIEW_CACHE_PREFIX = 'bulk-import-preview:';
    private const PREVIEW_CACHE_TTL_MINUTES = 30;
    private const COMMIT_CHUNK_SIZE = 100;
    private const MAX_PREVIEW_ROWS = 2000;

    public function __construct(
        private readonly BookService $bookService,
        private readonly RackService $rackService
    ) {
    }

    public function preview(UploadedFile $file): array
    {
        $rows = $this->parseCsv($file);
        $defaultRackId = $this->rackService->findDefaultRackId();

        if (count($rows) > self::MAX_PREVIEW_ROWS) {
            return [
                'message' => 'CSV exceeds maximum rows for preview.',
                'preview_token' => null,
                'summary' => [
                    'total_rows' => count($rows),
                    'valid_rows' => 0,
                    'invalid_rows' => count($rows),
                ],
                'errors' => [[
                    'row' => 0,
                    'errors' => ["Maximum ".self::MAX_PREVIEW_ROWS.' rows allowed per preview.'],
                ]],
                'preview_rows' => [],
                'analyzed_rows' => [],
            ];
        }

        if (! $defaultRackId) {
            return [
                'message' => 'No rack found. Create at least one rack before bulk import.',
                'preview_token' => null,
                'summary' => [
                    'total_rows' => 0,
                    'valid_rows' => 0,
                    'invalid_rows' => 0,
                ],
                'errors' => [['row' => 0, 'errors' => ['Default rack is not available']]],
            ];
        }

        $existingIsbn = Book::query()
            ->whereNotNull('isbn')
            ->pluck('isbn')
            ->flip()
            ->all();

        $seenIsbn = [];
        $validRows = [];
        $errors = [];
        $analyzedRows = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $normalizedRow = $this->normalizeRow($row, $defaultRackId);
            $rowErrors = $this->validateRow($normalizedRow);

            $isbn = $normalizedRow['isbn'] ?? null;
            if ($isbn) {
                if (isset($seenIsbn[$isbn])) {
                    $rowErrors[] = 'Duplicate ISBN found inside CSV file.';
                }

                if (isset($existingIsbn[$isbn])) {
                    $rowErrors[] = 'ISBN already exists in database.';
                }

                $seenIsbn[$isbn] = true;
            }

            if ($rowErrors !== []) {
                $errors[] = [
                    'row' => $rowNumber,
                    'errors' => $rowErrors,
                ];
                $analyzedRows[] = [
                    'row' => $rowNumber,
                    'is_valid' => false,
                    'errors' => $rowErrors,
                    'data' => $normalizedRow,
                ];
                continue;
            }

            $validRows[] = $normalizedRow;
            $analyzedRows[] = [
                'row' => $rowNumber,
                'is_valid' => true,
                'errors' => [],
                'data' => $normalizedRow,
            ];
        }

        $previewToken = (string) Str::uuid();
        Cache::put(
            self::PREVIEW_CACHE_PREFIX.$previewToken,
            ['rows' => $validRows],
            now()->addMinutes(self::PREVIEW_CACHE_TTL_MINUTES)
        );

        return [
            'message' => 'Preview generated successfully.',
            'preview_token' => $previewToken,
            'summary' => [
                'total_rows' => count($rows),
                'valid_rows' => count($validRows),
                'invalid_rows' => count($errors),
            ],
            'errors' => $errors,
            'preview_rows' => $validRows,
            'analyzed_rows' => $analyzedRows,
        ];
    }

    public function commit(string $previewToken): array
    {
        $payload = Cache::get(self::PREVIEW_CACHE_PREFIX.$previewToken);

        if (! $payload || ! isset($payload['rows']) || ! is_array($payload['rows'])) {
            return [
                'message' => 'Preview token is invalid or expired.',
                'imported' => 0,
                'skipped' => 0,
            ];
        }

        $imported = 0;
        $skipped = 0;
        $skippedReasons = [];

        foreach (array_chunk($payload['rows'], self::COMMIT_CHUNK_SIZE) as $chunk) {
            foreach ($chunk as $row) {
                try {
                    $category = \App\Models\Category::firstOrCreate(['name' => $row['category_name']]);
                    $row['category_id'] = $category->id;
                    unset($row['category_name']);
                    
                    $this->bookService->create($row);
                    $imported++;
                } catch (\Throwable $exception) {
                    $skipped++;
                    $reason = $exception->getMessage();
                    $skippedReasons[$reason] = ($skippedReasons[$reason] ?? 0) + 1;

                    Log::warning('Bulk import row skipped.', [
                        'isbn' => $row['isbn'] ?? null,
                        'title' => $row['title'] ?? null,
                        'reason' => $reason,
                    ]);
                }
            }
        }

        Cache::forget(self::PREVIEW_CACHE_PREFIX.$previewToken);

        return [
            'message' => 'Bulk import completed.',
            'imported' => $imported,
            'skipped' => $skipped,
            'skipped_reasons' => $skippedReasons,
        ];
    }

    private function parseCsv(UploadedFile $file): array
    {
        $rows = [];
        $handle = fopen($file->getRealPath(), 'rb');

        if (! $handle) {
            return $rows;
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return $rows;
        }

        $header = array_map(static fn ($col) => trim((string) $col), $header);

        while (($line = fgetcsv($handle)) !== false) {
            if ($line === [null] || $line === false) {
                continue;
            }

            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $line[$i] ?? null;
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function normalizeRow(array $row, int $defaultRackId): array
    {
        return [
            'title' => trim((string) ($row['title'] ?? '')),
            'author' => trim((string) ($row['author'] ?? '')),
            'isbn' => $this->nullableTrimmedValue($row['isbn'] ?? null),
            'category_name' => trim((string) ($row['category'] ?? $row['category_name'] ?? 'Uncategorized')),
            'rack_id' => $this->normalizeRackId($row['rack_id'] ?? null, $defaultRackId),
            'position_code' => strtoupper(trim((string) ($row['position_code'] ?? ''))),
            'cover_url' => $this->nullableTrimmedValue($row['cover_url'] ?? null),
            'status' => $this->normalizeStatus($row['status'] ?? null),
        ];
    }

    private function validateRow(array $row): array
    {
        $validator = Validator::make($row, [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:32'],
            'category_name' => ['required', 'string', 'max:255'],
            'rack_id' => ['required', 'integer', 'exists:racks,id'],
            'position_code' => ['required', 'string', 'max:10'],
            'cover_url' => ['nullable', 'url', 'max:1024'],
            'status' => ['required', Rule::in(array_column(BookStatus::cases(), 'value'))],
        ]);

        return $validator->errors()->all();
    }

    private function nullableTrimmedValue(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeRackId(mixed $value, int $defaultRackId): int
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return $defaultRackId;
        }

        return (int) $normalized;
    }

    private function normalizeStatus(mixed $value): string
    {
        $status = strtolower(trim((string) $value));

        if ($status === '') {
            return BookStatus::AVAILABLE->value;
        }

        return $status;
    }
}
