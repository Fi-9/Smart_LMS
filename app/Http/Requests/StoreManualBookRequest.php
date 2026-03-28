<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'rack_id' => ['nullable', 'integer', 'exists:racks,id'],
            'cover_url' => ['nullable', 'url', 'max:1024'],
        ];
    }
}
