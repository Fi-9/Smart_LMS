<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebContentExtractorService
{
    public function extractMainText(string $url): ?string
    {
        $timeout = max(3, (int) config('services.websearch.timeout', 12));

        try {
            $http = Http::timeout($timeout)->withHeaders([
                'User-Agent' => 'SmartLibraryBot/1.0 (+https://localhost)',
                'Accept-Language' => 'id,en;q=0.8',
            ]);

            if (!config('services.ai_scan.tls_verify', true)) {
                $http = $http->withoutVerifying();
            }

            $response = $http->get($url);
        } catch (ConnectionException $e) {
            Log::warning('websearch.extract.connection_failed', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->ok()) {
            Log::warning('websearch.extract.non_ok', ['url' => $url, 'status' => $response->status()]);

            return null;
        }

        $html = (string) $response->body();
        if (trim($html) === '') {
            return null;
        }

        // Remove scripts/styles to reduce noise before stripping tags.
        $clean = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html) ?? $html;
        $clean = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $clean) ?? $clean;
        $clean = preg_replace('/<noscript\b[^>]*>.*?<\/noscript>/is', ' ', $clean) ?? $clean;
        $clean = strip_tags($clean);
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;
        $clean = trim($clean);

        if (mb_strlen($clean) < 180) {
            return null;
        }

        // Keep payload small enough for local LLM extraction.
        return mb_substr($clean, 0, 5500);
    }
}

