<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'google_books_api_key' => ['nullable', 'string', 'max:512'],
            'n8n_base_url' => ['required', 'string', 'max:255'],
            'n8n_api_key' => ['nullable', 'string', 'max:2048'],
            'websearch_enabled' => ['nullable', 'boolean'],
            'tavily_api_key' => ['nullable', 'string', 'max:512'],
            'tavily_base_url' => ['required', 'url', 'max:255'],
            'tavily_timeout' => ['required', 'integer', 'min:5', 'max:120'],
            'websearch_max_results' => ['required', 'integer', 'min:1', 'max:10'],
            'websearch_allowed_domains' => ['nullable', 'string', 'max:2000'],
            'scan_default_mode' => ['required', Rule::in(['simple', 'full'])],
            'school_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ];
    }
}
