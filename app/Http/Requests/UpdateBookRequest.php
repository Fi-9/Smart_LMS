<?php

namespace App\Http\Requests;

use App\Enums\BookStatus;
use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Book $book */
        $book = $this->route('book');

        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:32', Rule::unique('books', 'isbn')->ignore($book->id)],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'rack_id' => ['required', 'integer', 'exists:racks,id'],
            'position_code' => ['required', 'string', 'max:10'],
            'cover_url' => ['nullable', 'url', 'max:1024'],
            'status' => ['required', Rule::in(array_column(BookStatus::cases(), 'value'))],
        ];
    }
}

