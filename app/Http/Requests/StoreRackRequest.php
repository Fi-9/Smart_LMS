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
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'name' => ['required', 'string', 'max:255', 'unique:racks,name'],
            'rows' => ['required', 'integer', 'min:1', 'max:26'],
            'columns' => ['required', 'integer', 'min:1', 'max:10'],
            'capacity_per_slot' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
