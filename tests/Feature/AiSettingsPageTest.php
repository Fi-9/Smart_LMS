<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*' => Http::response(['models' => []], 200),
        ]);
    }

    public function test_settings_page_can_be_rendered_for_authenticated_user(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertSee('AI Settings');
        $response->assertSee('Ollama Runtime');
        $response->assertSee('Tavily');
    }

    public function test_settings_page_persists_runtime_configuration(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);

        $response = $this->actingAs($user)->post(route('settings.update'), [
            'google_books_api_key' => 'google-secret',
            'ollama_base_url' => 'http://127.0.0.1:11434',
            'ollama_vision_model' => 'gemma4:26b',
            'ollama_text_model' => 'gemma4-id:26b',
            'ollama_web_model' => 'gemma4-id:26b',
            'ollama_timeout' => 240,
            'ollama_connect_timeout' => 10,
            'websearch_enabled' => '1',
            'tavily_api_key' => 'tvly-secret',
            'tavily_base_url' => 'https://api.tavily.com',
            'tavily_timeout' => 15,
            'websearch_max_results' => 4,
            'websearch_allowed_domains' => 'gramedia.com,openlibrary.org',
            'scan_default_mode' => 'full',
        ]);

        $response->assertRedirect(route('settings.index'));
        $this->assertDatabaseHas('app_settings', ['key' => 'ai.ollama.base_url']);
        $this->assertDatabaseHas('app_settings', ['key' => 'ai.websearch.tavily_api_key']);
        $this->assertDatabaseHas('app_settings', ['key' => 'ai.scan.default_mode', 'value' => 'full']);
    }
}
