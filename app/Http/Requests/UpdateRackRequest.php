<?php

namespace App\Http\Requests;

use App\Models\Rack;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Rack $rack */
        $rack = $this->route('rack');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('racks', 'name')->ignore($rack->id)],
            'rows' => ['required', 'integer', 'min:1', 'max:26'],
            'columns' => ['required', 'integer', 'min:1', 'max:6'],
        ];
    }
}
