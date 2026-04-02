<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignRackPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'rack_id' => ['required', 'integer', 'exists:racks,id'],
            'position_code' => ['required', 'string', 'max:10'],
        ];
    }
}
