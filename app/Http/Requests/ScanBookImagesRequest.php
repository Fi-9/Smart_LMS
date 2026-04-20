<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanBookImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:5'],
            'images.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,avif,heic,heif,bmp', 'max:10240'],
            'mode' => ['nullable', 'string', 'in:simple,full'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('mode')) {
            $this->merge(['mode' => 'full']);
        }
    }
}
