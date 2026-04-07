<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAiSettingsRequest;
use App\Services\AiInfrastructureService;
use App\Services\AppSettingsService;
use App\Services\EnvFileService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingsPageController extends Controller
{
    public function __construct(
        private readonly AppSettingsService $settingsService,
        private readonly AiInfrastructureService $aiInfrastructureService,
        private readonly EnvFileService $envFileService
    ) {
    }

    public function index(): View
    {
        return view('settings.index', [
            'settings' => $this->settingsService->aiSettings(),
            'masked_settings' => $this->settingsService->maskedAiSettings(),
            'ai_runtime' => $this->aiInfrastructureService->runtimeSummary(),
            'ai_diagnostics' => $this->aiInfrastructureService->diagnostics(),
        ]);
    }

    public function update(UpdateAiSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settingsService->putMany([
            'google_books.api_key' => $validated['google_books_api_key'] ?? null,
            'ai.ollama.base_url' => $validated['ollama_base_url'],
            'ai.ollama.vision_model' => $validated['ollama_vision_model'],
            'ai.ollama.text_model' => $validated['ollama_text_model'],
            'ai.ollama.web_model' => $validated['ollama_web_model'] ?? null,
            'ai.ollama.timeout' => (string) $validated['ollama_timeout'],
            'ai.ollama.connect_timeout' => (string) $validated['ollama_connect_timeout'],
            'ai.websearch.enabled' => (bool) ($validated['websearch_enabled'] ?? false),
            'ai.websearch.tavily_api_key' => $validated['tavily_api_key'] ?? null,
            'ai.websearch.tavily_base_url' => $validated['tavily_base_url'],
            'ai.websearch.tavily_timeout' => (string) $validated['tavily_timeout'],
            'ai.websearch.max_results' => (string) $validated['websearch_max_results'],
            'ai.websearch.allowed_domains' => $validated['websearch_allowed_domains'] ?? null,
            'ai.scan.default_mode' => $validated['scan_default_mode'],
        ]);

        $this->envFileService->sync([
            'GOOGLE_BOOKS_API_KEY' => $validated['google_books_api_key'] ?? null,
            'OLLAMA_BASE_URL' => $validated['ollama_base_url'],
            'OLLAMA_VISION_MODEL' => $validated['ollama_vision_model'],
            'OLLAMA_TEXT_MODEL' => $validated['ollama_text_model'],
            'OLLAMA_WEB_MODEL' => $validated['ollama_web_model'] ?? null,
            'OLLAMA_TIMEOUT' => (int) $validated['ollama_timeout'],
            'OLLAMA_CONNECT_TIMEOUT' => (int) $validated['ollama_connect_timeout'],
            'WEBSEARCH_ENABLED' => (bool) ($validated['websearch_enabled'] ?? false),
            'TAVILY_API_KEY' => $validated['tavily_api_key'] ?? null,
            'TAVILY_BASE_URL' => $validated['tavily_base_url'],
            'TAVILY_TIMEOUT' => (int) $validated['tavily_timeout'],
            'WEBSEARCH_MAX_RESULTS' => (int) $validated['websearch_max_results'],
            'WEBSEARCH_ALLOWED_DOMAINS' => $validated['websearch_allowed_domains'] ?? null,
            'AI_SCAN_DEFAULT_MODE' => $validated['scan_default_mode'],
        ], [
            'SEARXNG_BASE_URL',
            'SEARXNG_TIMEOUT',
            'OPENMAIC_BASE_URL',
            'OPENMAIC_API_KEY',
            'OPENMAIC_MODEL',
            'OPENMAIC_TIMEOUT',
            'OPENMAIC_CACHE_MINUTES',
            'OPENMAIC_CACHE_MISS_MINUTES',
        ]);

        return redirect()
            ->route('settings.index')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Pengaturan AI berhasil disimpan.',
            ]);
    }
}
