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
        Config::set('services.n8n.base_url', 'http://127.0.0.1:5678');
        Config::set('services.n8n.api_key', 'n8n-secret');
        Config::set('services.gemini.model', 'gemini-2.5-flash');
        Config::set('services.gemini.vision_model', 'gemini-2.5-flash');
        Config::set('services.websearch.enabled', false);
        Config::set('services.ai_runtime.default_scan_mode', 'auto');

        Http::fake([
            'http://127.0.0.1:5678/healthz' => Http::response([
                'status' => 'ok',
            ], 200),
        ]);

        $this->artisan('ai:status')
            ->expectsOutputToContain('AI Runtime Summary')
            ->expectsOutputToContain('gemini-2.5-flash')
            ->expectsOutputToContain('Connectivity Checks')
            ->expectsOutputToContain('Recommended batch scan mode: SIMPLE')
            ->assertExitCode(0);
    }

    public function test_ai_status_recommends_full_when_websearch_is_enabled(): void
    {
        Config::set('services.n8n.base_url', 'http://127.0.0.1:5678');
        Config::set('services.n8n.api_key', 'n8n-secret');
        Config::set('services.gemini.model', 'gemini-2.5-flash');
        Config::set('services.gemini.vision_model', 'gemini-2.5-flash');
        Config::set('services.websearch.enabled', true);
        Config::set('services.tavily.api_key', 'tvly-secret');
        Config::set('services.tavily.base_url', 'https://api.tavily.com');
        Config::set('services.ai_runtime.default_scan_mode', 'auto');

        Http::fake([
            'http://127.0.0.1:5678/healthz' => Http::response([
                'status' => 'ok',
            ], 200),
        ]);

        $this->artisan('ai:status')
            ->expectsOutputToContain('Recommended batch scan mode: FULL')
            ->assertExitCode(0);
    }
}
