<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LookupIsbnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'isbn' => ['required', 'string', 'max:32'],
        ];
    }
}

