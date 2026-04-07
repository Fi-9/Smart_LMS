<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class CommitScannedBooksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'draft_token' => ['required', 'string'],
            'books' => ['required', 'array', 'min:1', 'max:100'],
            'books.*.scan_id' => ['required', 'string'],
            'books.*.title' => ['nullable', 'string', 'max:255'],
            'books.*.author' => ['nullable', 'string', 'max:255'],
            'books.*.isbn' => ['nullable', 'string', 'max:32'],
            'books.*.category_name' => ['nullable', 'string', 'max:255'],
            'books.*.description' => ['nullable', 'string'],
            'books.*.cover_url' => [
                'nullable',
                'string',
                'max:1024',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }

                    $trimmed = trim($value);
                    if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
                        return;
                    }

                    if (Str::startsWith($trimmed, '/storage/')) {
                        return;
                    }

                    $fail('Cover URL must be an absolute URL or a local /storage path.');
                },
            ],
            'books.*.rack_id' => ['nullable', 'integer', 'exists:racks,id'],
        ];
    }
}
