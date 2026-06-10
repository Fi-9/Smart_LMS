@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <h1 class="page-title">AI Settings</h1>
        <p class="page-subtitle">Atur koneksi n8n + Gemini, Tavily, dan default scan mode langsung dari panel admin.</p>
    </div>

    <div class="mb-6 grid gap-4 xl:grid-cols-4">
        <div class="rounded-[1.25rem] border border-gray-200 bg-white p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">n8n + Gemini</p>
            <h3 class="mt-2 text-lg font-bold text-gray-900">{{ strtoupper($ai_diagnostics['n8n']['status'] ?? 'unknown') }}</h3>
            <p class="mt-2 text-sm text-gray-600">{{ $ai_diagnostics['n8n']['detail'] ?? '' }}</p>
            <p class="mt-2 text-xs text-gray-500">{{ $ai_diagnostics['n8n']['endpoint'] ?? '-' }}</p>
        </div>
        <div class="rounded-[1.25rem] border border-gray-200 bg-white p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Tavily</p>
            <h3 class="mt-2 text-lg font-bold text-gray-900">{{ strtoupper($ai_diagnostics['websearch']['status'] ?? 'unknown') }}</h3>
            <p class="mt-2 text-sm text-gray-600">{{ $ai_diagnostics['websearch']['detail'] ?? '' }}</p>
            <p class="mt-2 text-xs text-gray-500">{{ $ai_diagnostics['websearch']['endpoint'] ?? '-' }}</p>
        </div>
        <div class="rounded-[1.25rem] border border-gray-200 bg-white p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">AI Queue</p>
            <h3 class="mt-2 text-lg font-bold text-gray-900">{{ strtoupper($ai_diagnostics['queue_worker']['status'] ?? 'unknown') }}</h3>
            <p class="mt-2 text-sm text-gray-600">{{ $ai_diagnostics['queue_worker']['detail'] ?? '' }}</p>
            <p class="mt-2 text-xs text-gray-500">{{ $ai_diagnostics['queue_worker']['endpoint'] ?? '-' }}</p>
        </div>
        <div class="rounded-[1.25rem] border border-primary-100 bg-primary-50/70 p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-700">Scan Default</p>
            <h3 class="mt-2 text-lg font-bold text-gray-900">{{ strtoupper($ai_runtime['recommended_scan_mode'] ?? 'simple') }}</h3>
            <p class="mt-2 text-sm text-gray-600">Mode ini dipakai sebagai rekomendasi awal di halaman import.</p>
        </div>
    </div>

    @if(($ai_diagnostics['queue_worker']['status'] ?? null) === 'warning')
        <div class="mb-6 rounded-[1.5rem] border border-amber-200 bg-amber-50 px-5 py-4">
            <p class="text-sm font-semibold text-amber-800">Worker `ai-scan` belum memproses antrian.</p>
            <p class="mt-1 text-sm text-amber-700">{{ $ai_diagnostics['queue_worker']['detail'] ?? '' }}</p>
            <code class="mt-3 block overflow-x-auto rounded-xl bg-white px-4 py-3 text-xs text-gray-800">{{ $ai_diagnostics['queue_worker']['command'] ?? 'php artisan queue:work database --queue=ai-scan --tries=1 --sleep=1' }}</code>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-[1.5rem] border border-gray-200 bg-white p-6">
                <h2 class="text-lg font-bold text-gray-900">n8n + Gemini Runtime</h2>
                <p class="mt-1 text-sm text-gray-500">n8n menjembatani Gemini API untuk vision dan text processing.</p>

                <div class="mt-5 grid gap-4">
                    <div>
                        <label class="form-label">n8n Base URL</label>
                        <input type="text" name="n8n_base_url" value="{{ old('n8n_base_url', $settings['n8n_base_url']) }}" class="form-input" placeholder="http://localhost:5678">
                    </div>
                    <div>
                        <label class="form-label">n8n API Key (JWT Token)</label>
                        <input type="password" name="n8n_api_key" value="{{ old('n8n_api_key', $settings['n8n_api_key'] ? '********' : '') }}" class="form-input" placeholder="{{ $masked_settings['n8n_api_key'] ?? 'eyJh...' }}">
                        <p class="mt-1 text-xs text-gray-400">Dari n8n Settings → Public API → Generate API Key.</p>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-700">
                        <strong>Webhook yang perlu ada di n8n:</strong>
                        <ul class="mt-1 list-inside list-disc">
                            <li><code class="text-blue-900">/webhook/gemini-text</code> — Text generation & translation</li>
                            <li><code class="text-blue-900">/webhook/gemini-vision</code> — Vision / cover image analysis</li>
                        </ul>
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
                        <input type="password" name="tavily_api_key" value="{{ old('tavily_api_key', $settings['tavily_api_key'] ? '********' : '') }}" class="form-input" placeholder="{{ $masked_settings['tavily_api_key'] ?? 'tvly-...' }}">
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
                        <input type="password" name="google_books_api_key" value="{{ old('google_books_api_key', $settings['google_books_api_key'] ? '********' : '') }}" class="form-input" placeholder="{{ $masked_settings['google_books_api_key'] ?? 'optional' }}">
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
        
        <section class="rounded-[1.5rem] border border-gray-200 bg-white p-6">
            <h2 class="text-lg font-bold text-gray-900">School Identity</h2>
            <p class="mt-1 text-sm text-gray-500">Identitas sekolah untuk cetak stiker dan laporan.</p>

            <div class="mt-5">
                <label class="form-label">School Logo</label>
                <div class="mt-2 flex items-center gap-4">
                    @if($school_logo_path)
                        <img src="{{ Storage::url($school_logo_path) }}" alt="School Logo" class="h-16 w-16 object-contain rounded border bg-gray-50 p-1">
                    @endif
                    <input type="file" name="school_logo" accept="image/png, image/jpeg, image/jpg" class="form-input w-full max-w-md">
                </div>
                <p class="mt-2 text-xs text-gray-500">Disarankan rasio 1:1, format PNG transparan atau JPG. Maks 2MB. Logo ini akan dicetak di tengah QR Code stiker buku.</p>
            </div>
        </section>

        <section class="rounded-[1.5rem] border border-gray-200 bg-white p-6">
            <h2 class="text-lg font-bold text-gray-900">AI Scan Worker</h2>
            <p class="mt-1 text-sm text-gray-500">Gunakan Supervisor atau systemd agar queue `ai-scan` selalu hidup otomatis.</p>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 bg-gray-50 px-5 py-4">
                    <p class="text-sm font-semibold text-gray-900">Supervisor</p>
                    <p class="mt-2 text-sm text-gray-600">Template siap pakai ada di <span class="font-mono text-xs">deploy/supervisor/smart-lms-ai-scan.conf</span></p>
                    <code class="mt-3 block overflow-x-auto rounded-xl bg-white px-4 py-3 text-xs text-gray-800">sudo cp deploy/supervisor/smart-lms-ai-scan.conf /etc/supervisor/conf.d/</code>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-gray-50 px-5 py-4">
                    <p class="text-sm font-semibold text-gray-900">systemd</p>
                    <p class="mt-2 text-sm text-gray-600">Template siap pakai ada di <span class="font-mono text-xs">deploy/systemd/smart-lms-ai-scan.service</span></p>
                    <code class="mt-3 block overflow-x-auto rounded-xl bg-white px-4 py-3 text-xs text-gray-800">sudo cp deploy/systemd/smart-lms-ai-scan.service /etc/systemd/system/</code>
                </div>
            </div>

            <div class="mt-4 rounded-2xl border border-primary-100 bg-primary-50/70 px-5 py-4">
                <p class="text-sm font-semibold text-primary-900">Command worker</p>
                <code class="mt-3 block overflow-x-auto rounded-xl bg-white px-4 py-3 text-xs text-gray-800">php artisan queue:work database --queue=ai-scan --tries=1 --sleep=1 --timeout=600 --backoff=5 --memory=768</code>
                <p class="mt-3 text-xs text-primary-800">Panduan lengkap ada di <span class="font-mono">docs/queue_worker_setup.md</span>.</p>
            </div>
        </section>

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
