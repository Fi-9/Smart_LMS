<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiInfrastructureStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_status_command_reports_runtime_and_connectivity(): void
    {
        Config::set('services.ollama.base_url', 'http://127.0.0.1:11434');
        Config::set('services.ollama.vision_model', 'gemma4:26b');
        Config::set('services.ollama.text_model', 'gemma4:26b');
        Config::set('services.ollama.web_model', 'gemma4:26b');
        Config::set('services.websearch.enabled', false);
        Config::set('services.websearch.base_url', '');
        Config::set('services.ai_runtime.default_scan_mode', 'auto');

        Http::fake([
            'http://127.0.0.1:11434/api/tags' => Http::response([
                'models' => [
                    ['name' => 'gemma4:26b'],
                ],
            ], 200),
        ]);

        $this->artisan('ai:status')
            ->expectsOutputToContain('AI Runtime Summary')
            ->expectsOutputToContain('gemma4:26b')
            ->expectsOutputToContain('Connectivity Checks')
            ->expectsOutputToContain('Recommended batch scan mode: SIMPLE')
            ->assertExitCode(0);
    }

    public function test_ai_status_recommends_full_when_websearch_is_enabled(): void
    {
        Config::set('services.ollama.base_url', 'http://127.0.0.1:11434');
        Config::set('services.ollama.vision_model', 'gemma4-id:26b');
        Config::set('services.ollama.text_model', 'gemma4-id:26b');
        Config::set('services.ollama.web_model', 'gemma4-id:26b');
        Config::set('services.websearch.enabled', true);
        Config::set('services.websearch.base_url', 'https://search.local');
        Config::set('services.tavily.api_key', 'tvly-secret');
        Config::set('services.tavily.base_url', 'https://search.local');
        Config::set('services.ai_runtime.default_scan_mode', 'auto');

        Http::fake([
            'http://127.0.0.1:11434/api/tags' => Http::response([
                'models' => [
                    ['name' => 'gemma4-id:26b'],
                ],
            ], 200),
            'https://search.local/search*' => Http::response([
                'results' => [],
            ], 200),
        ]);

        $this->artisan('ai:status')
            ->expectsOutputToContain('Recommended batch scan mode: FULL')
            ->assertExitCode(0);
    }
}
