<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreManualBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:32', 'unique:books,isbn'],
            'category_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'rack_id' => ['nullable', 'integer', 'exists:racks,id'],
            'cover_url' => [
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
        ];
    }
}
