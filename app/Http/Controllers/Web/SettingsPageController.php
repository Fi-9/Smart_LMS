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
            'school_logo_path' => $this->settingsService->get('school_logo_path'),
        ]);
    }

    public function update(UpdateAiSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('school_logo')) {
            $path = $request->file('school_logo')->store('logos', 'public');
            $this->settingsService->putMany(['school_logo_path' => $path]);
        }

        $this->settingsService->putMany([
            'google_books.api_key' => $validated['google_books_api_key'] ?? null,
            'ai.n8n.base_url' => $validated['n8n_base_url'],
            'ai.n8n.api_key' => $validated['n8n_api_key'] ?? null,
            'ai.websearch.enabled' => (bool) ($validated['websearch_enabled'] ?? false),
            'ai.websearch.tavily_api_key' => $validated['tavily_api_key'] ?? null,
            'ai.websearch.tavily_base_url' => $validated['tavily_base_url'],
            'ai.websearch.tavily_timeout' => (string) $validated['tavily_timeout'],
            'ai.websearch.max_results' => (string) $validated['websearch_max_results'],
            'ai.websearch.allowed_domains' => $validated['websearch_allowed_domains'] ?? null,
            'ai.scan.default_mode' => $validated['scan_default_mode'],
        ]);

        $envValues = [
            'GOOGLE_BOOKS_API_KEY' => $validated['google_books_api_key'] ?? null,
            'N8N_BASE_URL' => $validated['n8n_base_url'],
            'N8N_API_KEY' => $validated['n8n_api_key'] ?? null,
            'WEBSEARCH_ENABLED' => (bool) ($validated['websearch_enabled'] ?? false),
            'TAVILY_API_KEY' => $validated['tavily_api_key'] ?? null,
            'TAVILY_BASE_URL' => $validated['tavily_base_url'],
            'TAVILY_TIMEOUT' => (int) $validated['tavily_timeout'],
            'WEBSEARCH_MAX_RESULTS' => (int) $validated['websearch_max_results'],
            'WEBSEARCH_ALLOWED_DOMAINS' => $validated['websearch_allowed_domains'] ?? null,
            'AI_SCAN_DEFAULT_MODE' => $validated['scan_default_mode'],
        ];
        $envRemoveKeys = [
            'OLLAMA_BASE_URL',
            'OLLAMA_MODEL',
            'OLLAMA_VISION_MODEL',
            'OLLAMA_TEXT_MODEL',
            'OLLAMA_WEB_MODEL',
            'OLLAMA_TIMEOUT',
            'OLLAMA_CONNECT_TIMEOUT',
            'SEARXNG_BASE_URL',
            'SEARXNG_TIMEOUT',
            'OPENMAIC_BASE_URL',
            'OPENMAIC_API_KEY',
            'OPENMAIC_MODEL',
            'OPENMAIC_TIMEOUT',
            'OPENMAIC_CACHE_MINUTES',
            'OPENMAIC_CACHE_MISS_MINUTES',
        ];

        $this->envFileService->sync($envValues, $envRemoveKeys);

        // Brief pause: allow server to breathe after .env rewrite
        usleep(500000);

        return redirect()
            ->route('settings.index')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Pengaturan AI berhasil disimpan.',
            ]);
    }
}
