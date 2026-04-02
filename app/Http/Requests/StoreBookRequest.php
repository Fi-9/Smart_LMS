<?php

namespace App\Http\Requests;

use App\Enums\BookStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
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
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'rack_id' => ['required', 'integer', 'exists:racks,id'],
            'position_code' => ['required', 'string', 'max:10'],
            'cover_url' => ['nullable', 'url', 'max:1024'],
            'status' => ['sometimes', Rule::in(array_column(BookStatus::cases(), 'value'))],
        ];
    }
}

