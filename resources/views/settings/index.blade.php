@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <h1 class="page-title">AI Settings</h1>
        <p class="page-subtitle">Atur koneksi Ollama, Tavily, dan default scan mode langsung dari panel admin.</p>
    </div>

    <div class="mb-6 grid gap-4 xl:grid-cols-3">
        <div class="rounded-[1.25rem] border border-gray-200 bg-white p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Ollama</p>
            <h3 class="mt-2 text-lg font-bold text-gray-900">{{ strtoupper($ai_diagnostics['ollama']['status'] ?? 'unknown') }}</h3>
            <p class="mt-2 text-sm text-gray-600">{{ $ai_diagnostics['ollama']['detail'] ?? '' }}</p>
            <p class="mt-2 text-xs text-gray-500">{{ $ai_diagnostics['ollama']['endpoint'] ?? '-' }}</p>
        </div>
        <div class="rounded-[1.25rem] border border-gray-200 bg-white p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Tavily</p>
            <h3 class="mt-2 text-lg font-bold text-gray-900">{{ strtoupper($ai_diagnostics['websearch']['status'] ?? 'unknown') }}</h3>
            <p class="mt-2 text-sm text-gray-600">{{ $ai_diagnostics['websearch']['detail'] ?? '' }}</p>
            <p class="mt-2 text-xs text-gray-500">{{ $ai_diagnostics['websearch']['endpoint'] ?? '-' }}</p>
        </div>
        <div class="rounded-[1.25rem] border border-primary-100 bg-primary-50/70 p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-700">Scan Default</p>
            <h3 class="mt-2 text-lg font-bold text-gray-900">{{ strtoupper($ai_runtime['recommended_scan_mode'] ?? 'simple') }}</h3>
            <p class="mt-2 text-sm text-gray-600">Mode ini dipakai sebagai rekomendasi awal di halaman import.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
        @csrf
        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-[1.5rem] border border-gray-200 bg-white p-6">
                <h2 class="text-lg font-bold text-gray-900">Ollama Runtime</h2>
                <p class="mt-1 text-sm text-gray-500">Pengaturan koneksi utama untuk vision dan text model.</p>

                <div class="mt-5 grid gap-4">
                    <div>
                        <label class="form-label">Base URL</label>
                        <input type="url" name="ollama_base_url" value="{{ old('ollama_base_url', $settings['ollama_base_url']) }}" class="form-input" placeholder="http://127.0.0.1:11434">
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="form-label">Vision Model</label>
                            <input type="text" name="ollama_vision_model" value="{{ old('ollama_vision_model', $settings['ollama_vision_model']) }}" class="form-input" placeholder="gemma4:26b">
                        </div>
                        <div>
                            <label class="form-label">Text Model</label>
                            <input type="text" name="ollama_text_model" value="{{ old('ollama_text_model', $settings['ollama_text_model']) }}" class="form-input" placeholder="gemma4-id:26b">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Web Summary Model</label>
                        <input type="text" name="ollama_web_model" value="{{ old('ollama_web_model', $settings['ollama_web_model']) }}" class="form-input" placeholder="gemma4-id:26b">
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="form-label">Timeout</label>
                            <input type="number" name="ollama_timeout" value="{{ old('ollama_timeout', $settings['ollama_timeout']) }}" class="form-input" min="30" max="600">
                        </div>
                        <div>
                            <label class="form-label">Connect Timeout</label>
                            <input type="number" name="ollama_connect_timeout" value="{{ old('ollama_connect_timeout', $settings['ollama_connect_timeout']) }}" class="form-input" min="1" max="60">
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-[1.5rem] border border-gray-200 bg-white p-6">
                <h2 class="text-lg font-bold text-gray-900">Websearch & Catalog</h2>
                <p class="mt-1 text-sm text-gray-500">Tavily dipakai saat butuh fallback deskripsi resmi. Google Books API key opsional untuk lookup yang lebih stabil.</p>

                <div class="mt-5 grid gap-4">
                    <label class="inline-flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <input type="hidden" name="websearch_enabled" value="0">
                        <input type="checkbox" name="websearch_enabled" value="1" class="h-4 w-4 rounded border-gray-300 text-primary-700 focus:ring-primary-500" @checked(old('websearch_enabled', $settings['websearch_enabled']))>
                        <span class="text-sm font-medium text-gray-700">Aktifkan Tavily Websearch</span>
                    </label>

                    <div>
                        <label class="form-label">Tavily API Key</label>
                        <input type="password" name="tavily_api_key" value="{{ old('tavily_api_key', $settings['tavily_api_key']) }}" class="form-input" placeholder="{{ $masked_settings['tavily_api_key'] ?? 'tvly-...' }}">
                    </div>
                    <div>
                        <label class="form-label">Tavily Base URL</label>
                        <input type="url" name="tavily_base_url" value="{{ old('tavily_base_url', $settings['tavily_base_url']) }}" class="form-input" placeholder="https://api.tavily.com">
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="form-label">Tavily Timeout</label>
                            <input type="number" name="tavily_timeout" value="{{ old('tavily_timeout', $settings['tavily_timeout']) }}" class="form-input" min="5" max="120">
                        </div>
                        <div>
                            <label class="form-label">Max Results</label>
                            <input type="number" name="websearch_max_results" value="{{ old('websearch_max_results', $settings['websearch_max_results']) }}" class="form-input" min="1" max="10">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Allowed Domains</label>
                        <textarea name="websearch_allowed_domains" class="form-input min-h-[110px]" placeholder="gramedia.com,goodreads.com,openlibrary.org">{{ old('websearch_allowed_domains', $settings['websearch_allowed_domains']) }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">Google Books API Key</label>
                        <input type="password" name="google_books_api_key" value="{{ old('google_books_api_key', $settings['google_books_api_key']) }}" class="form-input" placeholder="{{ $masked_settings['google_books_api_key'] ?? 'optional' }}">
                    </div>
                    <div>
                        <label class="form-label">Default Scan Mode</label>
                        <select name="scan_default_mode" class="form-input">
                            <option value="simple" @selected(old('scan_default_mode', $settings['scan_default_mode']) === 'simple')>Simple</option>
                            <option value="full" @selected(old('scan_default_mode', $settings['scan_default_mode']) === 'full')>Full</option>
                        </select>
                    </div>
                </div>
            </section>
        </div>

        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                <p class="font-semibold">Pengaturan belum tersimpan.</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="sticky bottom-6 z-20 flex justify-end">
            <div class="rounded-2xl border border-primary-100 bg-white/95 px-4 py-4 shadow-xl backdrop-blur">
                <button type="submit" class="rounded-xl bg-primary-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-800">Simpan Pengaturan</button>
            </div>
        </div>
    </form>
@endsection
