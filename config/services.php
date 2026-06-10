<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google_books' => [
        'api_key' => env('GOOGLE_BOOKS_API_KEY'),
        'cache_minutes' => (int) env('GOOGLE_BOOKS_CACHE_MINUTES', 120),
        'cache_miss_minutes' => (int) env('GOOGLE_BOOKS_CACHE_MISS_MINUTES', 15),
    ],

    'websearch' => [
        'enabled' => (bool) env('WEBSEARCH_ENABLED', false),
        'base_url' => env('TAVILY_BASE_URL', 'https://api.tavily.com'),
        'timeout' => (int) env('TAVILY_TIMEOUT', 15),
        'max_results' => (int) env('WEBSEARCH_MAX_RESULTS', 3),
        'allowed_domains' => array_values(
            array_filter(
                array_map('trim', explode(',', (string) env('WEBSEARCH_ALLOWED_DOMAINS', '')))
            )
        ),
        'cache_minutes' => (int) env('WEBSEARCH_CACHE_MINUTES', 180),
        'cache_miss_minutes' => (int) env('WEBSEARCH_CACHE_MISS_MINUTES', 20),
    ],

    'tavily' => [
        'base_url' => env('TAVILY_BASE_URL', 'https://api.tavily.com'),
        'api_key' => env('TAVILY_API_KEY'),
        'timeout' => (int) env('TAVILY_TIMEOUT', 15),
    ],

    'ai_scan' => [
        'cover_width' => (int) env('AI_COVER_WIDTH', 600),
        'cover_height' => (int) env('AI_COVER_HEIGHT', 900),
        'tls_verify' => (bool) env('AI_TLS_VERIFY', true),
    ],

    'n8n' => [
        'base_url' => env('N8N_BASE_URL', 'http://localhost:5678'),
        'api_key' => env('N8N_API_KEY', ''),
        'timeout' => (int) env('N8N_TIMEOUT', 240),
        'webhook_gemini_text' => env('N8N_WEBHOOK_GEMINI_TEXT', 'gemini-text'),
        'webhook_gemini_vision' => env('N8N_WEBHOOK_GEMINI_VISION', 'gemini-vision'),
        'webhook_smartlms_vision' => env('N8N_WEBHOOK_SMARTLMS_VISION', 'smartlms-vision'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'api_keys' => env('GEMINI_API_KEYS'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'vision_model' => env('GEMINI_VISION_MODEL', 'gemini-2.5-flash'),
        'temperature' => (float) env('GEMINI_TEMPERATURE', 0),
        'max_tokens' => (int) env('GEMINI_MAX_TOKENS', 1200),
    ],

    'ai_runtime' => [
        'profile' => env('AI_RUNTIME_PROFILE', 'n8n-gemini'),
        'default_scan_mode' => env('AI_SCAN_DEFAULT_MODE', 'auto'),
    ],

];
