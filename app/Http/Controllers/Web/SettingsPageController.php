<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAiSettingsRequest;
use App\Services\AiInfrastructureService;
use App\Services\AppSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingsPageController extends Controller
{
    public function __construct(
        private readonly AppSettingsService $settingsService,
        private readonly AiInfrastructureService $aiInfrastructureService
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

        return redirect()
            ->route('settings.index')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Pengaturan AI berhasil disimpan.',
            ]);
    }
}
