<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:racks,name'],
            'rows' => ['required', 'integer', 'min:1', 'max:26'],
            'columns' => ['required', 'integer', 'min:1', 'max:6'],
        ];
    }
}
