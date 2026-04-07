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
            'ollama_base_url' => ['required', 'url', 'max:255'],
            'ollama_vision_model' => ['required', 'string', 'max:255'],
            'ollama_text_model' => ['required', 'string', 'max:255'],
            'ollama_web_model' => ['nullable', 'string', 'max:255'],
            'ollama_timeout' => ['required', 'integer', 'min:30', 'max:600'],
            'ollama_connect_timeout' => ['required', 'integer', 'min:1', 'max:60'],
            'websearch_enabled' => ['nullable', 'boolean'],
            'tavily_api_key' => ['nullable', 'string', 'max:512'],
            'tavily_base_url' => ['required', 'url', 'max:255'],
            'tavily_timeout' => ['required', 'integer', 'min:5', 'max:120'],
            'websearch_max_results' => ['required', 'integer', 'min:1', 'max:10'],
            'websearch_allowed_domains' => ['nullable', 'string', 'max:2000'],
            'scan_default_mode' => ['required', Rule::in(['simple', 'full'])],
        ];
    }
}
