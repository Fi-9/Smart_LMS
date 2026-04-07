<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanBookBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => ['nullable', 'string', 'in:simple,full'],
            'books' => ['required', 'array', 'min:1', 'max:30'],
            'books.*.front_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'books.*.back_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'books.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('mode')) {
            $this->merge(['mode' => 'full']);
        }
    }
}
