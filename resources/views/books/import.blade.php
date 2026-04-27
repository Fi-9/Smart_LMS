@extends('layouts.app')

@section('content')
    @php
        /** @var \Illuminate\Support\ViewErrorBag $errors */
        $errors = $errors ?? new \Illuminate\Support\ViewErrorBag();
        $manualFieldNames = ['title', 'author', 'isbn', 'category_name', 'rack_id', 'cover_url', 'description'];
        $hasManualErrors = collect($manualFieldNames)->contains(fn ($name) => $errors->has($name));
        $defaultBatchBooks = old('books');
        if (! is_array($defaultBatchBooks) || count($defaultBatchBooks) === 0) {
            $defaultBatchBooks = [[]];
        }

        $draftBooks = collect($ai_scan_draft['books'] ?? []);
        $groupedDraftBooks = $draftBooks->groupBy(function (array $book): string {
            $category = trim((string) ($book['category_name'] ?? ''));
            return $category !== '' ? $category : 'Tanpa Kategori';
        })->sortKeys();
        $aiDraftFinished = (bool) ($ai_scan_draft['summary']['is_finished'] ?? false);
        $recommendedScanMode = $ai_runtime['recommended_scan_mode'] ?? 'simple';
        $ollamaDiagnostic = $ai_diagnostics['ollama'] ?? null;
        $visionOnline = ($ollamaDiagnostic['status'] ?? null) === 'ok';
    @endphp

    @php
        $fieldLabelMap = [
            'title' => 'Judul',
            'author' => 'Penulis',
            'isbn' => 'ISBN',
            'category' => 'Kategori',
            'description' => 'Deskripsi',
            'cover_url' => 'Cover',
            'publisher' => 'Penerbit',
            'published_year' => 'Tahun',
        ];
    @endphp

    <div class="mb-6">
        <h1 class="page-title">Smart Ingest</h1>
        <p class="page-subtitle">Unified ingest untuk upload aset, review draft metadata, dan routing buku ke lokasi fisik perpustakaan.</p>
    </div>

    <div class="mb-6 rounded-[1.35rem] border border-gray-200 bg-white px-4 py-3 shadow-sm">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-primary-50 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-primary-700">Profile</p>
                <div class="mt-1 flex items-center justify-between gap-3">
                    <span class="text-sm font-bold text-gray-900">{{ strtoupper($ai_runtime['profile'] ?? 'LOCAL') }}</span>
                    <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-primary-700">Mode {{ strtoupper($recommendedScanMode) }}</span>
                </div>
            </div>
            <div class="rounded-xl bg-gray-50 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gray-400">Vision</p>
                <div class="mt-1 flex items-center justify-between gap-3">
                    <span class="text-sm font-bold text-gray-900">{{ $ai_runtime['vision']['model'] ?? 'Belum diatur' }}</span>
                    <span class="text-xs font-semibold {{ ($ai_runtime['vision']['enabled'] ?? false) ? 'text-primary-700' : 'text-amber-700' }}">{{ $ai_runtime['vision']['status_label'] ?? 'Unknown' }}</span>
                </div>
            </div>
            <div class="rounded-xl bg-gray-50 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gray-400">Text</p>
                <div class="mt-1 flex items-center justify-between gap-3">
                    <span class="text-sm font-bold text-gray-900">{{ $ai_runtime['text']['model'] ?? 'Belum diatur' }}</span>
                    <span class="text-xs font-semibold {{ ($ai_runtime['text']['enabled'] ?? false) ? 'text-primary-700' : 'text-amber-700' }}">{{ $ai_runtime['text']['status_label'] ?? 'Unknown' }}</span>
                </div>
            </div>
            <div class="rounded-xl bg-gray-50 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gray-400">Websearch</p>
                <div class="mt-1 flex items-center justify-between gap-3">
                    <span class="text-sm font-bold text-gray-900">{{ ($ai_runtime['websearch']['enabled'] ?? false) ? 'Enabled' : 'Disabled' }}</span>
                    <span class="text-xs font-semibold {{ ($ai_runtime['websearch']['enabled'] ?? false) ? 'text-primary-700' : 'text-amber-700' }}">{{ $ai_runtime['websearch']['status_label'] ?? 'Unknown' }}</span>
                </div>
            </div>
        </div>
    </div>

    @if($import_summary)
        <div class="mb-6 rounded-2xl border border-primary-200 bg-primary-50 px-5 py-4 text-sm text-primary-900">
            Imported: <strong>{{ $import_summary['imported'] }}</strong> | Skipped: <strong>{{ $import_summary['skipped'] }}</strong>
            @if(!empty($import_summary['skipped_reasons']))
                <ul class="mt-2 list-inside list-disc text-xs text-primary-900">
                    @foreach($import_summary['skipped_reasons'] as $reason => $count)
                        <li>{{ $reason }} ({{ $count }})</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <div class="mb-5 flex items-center gap-1 rounded-xl bg-gray-100 p-1" style="width: fit-content">
        <button type="button" data-tab-trigger="ai" class="flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-medium transition-all duration-150">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-700 text-[10px] font-bold text-white">📷</span>
            AI Scan
        </button>
        <button type="button" data-tab-trigger="isbn" class="flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-medium transition-all duration-150">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-300 text-[10px] font-bold text-white">📖</span>
            ISBN Scan
        </button>
        <button type="button" data-tab-trigger="review" class="flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-medium transition-all duration-150">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-300 text-[10px] font-bold text-white">✓</span>
            Review & Grouping
        </button>
    </div>

    <div data-tab-panel="ai" class="{{ $hasManualErrors ? 'hidden' : '' }}">
        <div class="mb-5 rounded-[1.4rem] border border-primary-100 bg-gradient-to-r from-primary-50 via-white to-amber-50 px-5 py-4">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-primary-700">Waterfall Pipeline</p>
                    <h2 class="mt-1 text-xl font-black tracking-tight text-gray-900">Upload assets, review grouping, lalu tentukan routing fisik buku.</h2>
                </div>
                <div class="flex flex-wrap gap-3 text-sm text-gray-600">
                    <span class="rounded-full bg-white px-3 py-1.5">1. Upload Assets</span>
                    <span class="rounded-full bg-white px-3 py-1.5">2. Review & Grouping</span>
                    <span class="rounded-full bg-white px-3 py-1.5">3. Physical Routing</span>
                </div>
            </div>
            @if(!$visionOnline)
                <div class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Runtime vision belum siap. {{ $ollamaDiagnostic['detail'] ?? 'Scan AI tidak akan berjalan sampai koneksi Ollama dan model vision aktif.' }}
                </div>
            @endif
        </div>



        <div id="scan-upload-section">
            <form method="POST" action="{{ route('books.import.ai-batch-scan') }}" enctype="multipart/form-data" class="space-y-5" id="batch-scan-form" data-status-url-template="{{ route('books.import.ai-batch-status', ['token' => '__TOKEN__']) }}" data-cancel-url-template="{{ route('books.import.ai-batch-cancel', ['token' => '__TOKEN__']) }}">
                @csrf
                <div class="rounded-[1.5rem] border border-gray-200 bg-white p-6">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Upload Assets</h3>
                            <p class="mt-1 text-sm text-gray-500">Tambahkan banyak buku sekaligus lalu scan dalam satu proses.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <select name="mode" class="form-select w-auto min-w-[160px]">
                                <option value="full" @selected(old('mode', $recommendedScanMode) === 'full')>Mode: Full</option>
                                <option value="simple" @selected(old('mode', $recommendedScanMode) === 'simple')>Mode: Simple</option>
                            </select>
                            <button type="button" id="add-batch-book" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-primary-50 hover:text-primary-700">+ Tambah Buku</button>
                            <button type="button" id="add-batch-five" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-primary-50 hover:text-primary-700">+5 Slot</button>
                        </div>
                    </div>

                    <div id="batch-scan-status" class="mb-5 hidden rounded-2xl border border-primary-100 bg-primary-50 px-4 py-4">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="batch-spinner"></div>
                                <div>
                                    <p id="batch-scan-status-title" class="text-sm font-semibold text-primary-900">Menyiapkan batch scan...</p>
                                    <p id="batch-scan-status-subtitle" class="text-xs text-primary-700">0 buku siap diproses.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-primary-700">
                                    <span id="batch-scan-live-count">0</span>/<span id="batch-scan-live-total">0</span> buku
                                </div>
                            </div>
                        </div>
                        <p id="batch-scan-status-note" class="mt-3 text-xs text-primary-700">Batch akan diproses di background satu per satu agar server Ollama tetap aman.</p>
                    </div>

                    <div id="ai-batch-list" class="grid gap-4">
                        @foreach($defaultBatchBooks as $index => $book)
                            <div class="batch-slot-card rounded-[1.25rem] border border-gray-200 bg-gray-50/70 p-5" data-ai-book-item>
                                <div class="mb-4 flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Book Slot</p>
                                        <h4 class="ai-batch-title mt-1 text-lg font-bold text-gray-900">Buku {{ $index + 1 }}</h4>
                                    </div>
                                    <button type="button" class="remove-batch-book rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600">Hapus</button>
                                </div>

                                <div class="grid gap-4 xl:grid-cols-[140px_1fr]">
                                    <div class="batch-slot-preview">
                                        <img src="" alt="Preview cover" class="hidden h-full w-full object-contain p-3" data-slot-front-preview>
                                        <div class="batch-slot-placeholder" data-slot-placeholder>
                                            <span>Front cover</span>
                                        </div>
                                    </div>
                                    <div class="grid gap-4">
                                        <div class="flex flex-col gap-3 xl:flex-row xl:items-end">
                                            <div class="min-w-0 flex-1">
                                                <label class="mb-2 block text-sm font-semibold text-gray-700">Front Cover *</label>
                                                <input type="file" name="books[{{ $index }}][front_image]" accept=".jpg,.jpeg,.png,.webp,.avif,image/*" class="form-input" required data-field="front_image">
                                                <p class="mt-2 text-xs text-gray-500">Untuk cover, judul, penulis, ISBN, dan gambar katalog.</p>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <label class="mb-2 block text-sm font-semibold text-gray-700">Back Cover</label>
                                                <input type="file" name="books[{{ $index }}][back_image]" accept=".jpg,.jpeg,.png,.webp,.avif,image/*" class="form-input" data-field="back_image">
                                                <p class="mt-2 text-xs text-gray-500">Dipakai untuk bantu baca sinopsis jika deskripsi internet tidak cocok.</p>
                                            </div>
                                        </div>

                                        <div class="rounded-xl border border-dashed border-gray-200 bg-white px-4 py-3">
                                            <div class="flex flex-wrap gap-2 text-xs text-gray-500">
                                                <span class="rounded-full bg-gray-100 px-2.5 py-1" data-front-status>Front belum dipilih</span>
                                                <span class="rounded-full bg-gray-100 px-2.5 py-1" data-back-status>Back opsional</span>
                                                <span class="rounded-full bg-gray-100 px-2.5 py-1" data-scan-status>Belum discan</span>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-gray-700">Catatan Slot</label>
                                            <input type="text" name="books[{{ $index }}][notes]" value="{{ $book['notes'] ?? '' }}" class="form-input" placeholder="Contoh: tumpukan 1 / batch sejarah" data-field="notes">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-primary-100 bg-primary-50 px-4 py-4">
                        <p class="text-sm text-primary-900">Setelah scan selesai, sistem langsung pindah ke review dan grouping kategori.</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <div id="batch-ready-indicator" class="text-xs font-semibold text-primary-700">0 buku siap discan</div>
                            <button type="button" id="batch-cancel-btn" class="hidden inline-flex items-center gap-2 rounded-xl border border-red-200 bg-white px-5 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50">Batalkan Scan</button>
                            <button type="submit" id="batch-scan-submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-800 disabled:cursor-not-allowed disabled:bg-gray-400" @disabled(!$visionOnline)>
                                <span class="scan-action-spinner scan-action-spinner-light hidden" id="batch-scan-submit-spinner"></span>
                                <span id="batch-scan-submit-label">Mulai Waterfall Scan</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div data-tab-panel="review" class="hidden">
            @if($groupedDraftBooks->isNotEmpty())
                <form method="POST" action="{{ route('books.import.ai-review-commit') }}" class="space-y-5">
                    @csrf
                    <input type="hidden" name="draft_token" value="{{ $ai_scan_draft_token }}">

                    <div class="rounded-[1.5rem] border border-gray-200 bg-white p-6">
                        <h3 class="text-lg font-bold text-gray-900">Review & Grouping</h3>
                        <p class="mt-1 text-sm text-gray-500">Rapikan metadata hasil AI, kelompokkan kategori, lalu pilih rack sebagai langkah routing fisik sebelum commit.</p>
                    </div>

                    @php $flatIndex = 0; @endphp
                    @foreach($groupedDraftBooks as $categoryName => $books)
                        <section class="rounded-[1.5rem] border border-gray-200 bg-white p-6" data-review-section>
                            <div class="mb-5 flex items-center justify-between gap-3 border-b border-gray-100 pb-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-400">Kategori</p>
                                    <h4 class="mt-1 text-2xl font-black tracking-tight text-gray-900">{{ $categoryName }}</h4>
                                </div>
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-700" data-review-count>{{ $books->count() }} buku</span>
                            </div>

                            <div class="space-y-5" data-review-list>
                                @foreach($books as $book)
                                    <div class="rounded-[1.25rem] border {{ ($book['scan_status'] ?? 'success') === 'failed' ? 'border-amber-200 bg-amber-50/60' : 'border-gray-200 bg-gray-50/60' }} p-5" data-review-book-item>
                                        <input type="hidden" name="books[{{ $flatIndex }}][scan_id]" value="{{ $book['scan_id'] }}" data-review-field="scan_id">

                                        <div class="mb-4 flex items-start justify-between gap-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full {{ ($book['scan_status'] ?? 'success') === 'failed' ? 'bg-amber-100 text-amber-700' : 'bg-primary-100 text-primary-700' }} px-3 py-1 text-xs font-semibold uppercase tracking-wide">
                                                    {{ ($book['scan_status'] ?? 'success') === 'failed' ? 'Perlu Review' : 'Siap Dicek' }}
                                                </span>
                                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-gray-600">Source: {{ strtoupper($book['source'] ?? 'AI') }}</span>
                                                @if(!empty($book['source_url']))
                                                    <a href="{{ $book['source_url'] }}" target="_blank" rel="noopener noreferrer" class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-primary-700 underline decoration-transparent transition hover:decoration-primary-700">Lihat sumber</a>
                                                @endif
                                                @if(!empty($book['error']))
                                                    <span class="text-sm text-amber-700">{{ $book['error'] }}</span>
                                                @endif
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <button type="button" class="enrich-review-book inline-flex items-center gap-2 rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-primary-700 transition hover:bg-primary-100" title="Cari otomatis metadata yang kosong berdasarkan Judul & Penulis">
                                                    <span>Cari Data Kosong</span>
                                                    <span class="enrich-spinner border-primary-500 hidden h-3 w-3 animate-spin rounded-full border-2 border-t-transparent"></span>
                                                </button>
                                                <button type="button" class="remove-review-book rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-red-600 transition hover:bg-red-50">Hapus</button>
                                            </div>
                                        </div>

                                        @if(!empty($book['field_sources']) && is_array($book['field_sources']))
                                            <div class="mb-4 flex flex-wrap gap-2">
                                                @foreach($book['field_sources'] as $fieldKey => $fieldSource)
                                                    @if(!empty($fieldSource))
                                                        <span class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-600">
                                                            {{ $fieldLabelMap[$fieldKey] ?? ucfirst(str_replace('_', ' ', $fieldKey)) }}: {{ $fieldSource }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="grid gap-5 xl:grid-cols-[170px_1fr]">
                                            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                                                @if(!empty($book['cover_url']))
                                                    <img src="{{ $book['cover_url'] }}" alt="{{ $book['title'] ?? 'Book cover' }}" class="h-60 w-full bg-white object-contain p-2">
                                                @else
                                                    <div class="flex h-60 items-center justify-center text-center text-sm font-semibold text-gray-500">Cover belum tersedia</div>
                                                @endif
                                            </div>

                                            <div class="grid gap-4 md:grid-cols-2 content-start">
                                                <div>
                                                    <label class="form-label">Title</label>
                                                    <input type="text" name="books[{{ $flatIndex }}][title]" value="{{ $book['title'] ?? '' }}" class="form-input" data-review-field="title">
                                                </div>
                                                <div>
                                                    <label class="form-label">Author</label>
                                                    <input type="text" name="books[{{ $flatIndex }}][author]" value="{{ $book['author'] ?? '' }}" class="form-input" data-review-field="author">
                                                </div>
                                                <div>
                                                    <label class="form-label">ISBN</label>
                                                    <input type="text" name="books[{{ $flatIndex }}][isbn]" value="{{ $book['isbn'] ?? '' }}" class="form-input" data-review-field="isbn">
                                                </div>
                                                <div>
                                                    <label class="form-label">Category</label>
                                                    <input list="category-list" type="text" name="books[{{ $flatIndex }}][category_name]" value="{{ $book['category_name'] ?? $categoryName }}" class="form-input" data-review-field="category_name">
                                                </div>
                                                <div>
                                                    <label class="form-label">Rack</label>
                                                    <select name="books[{{ $flatIndex }}][rack_id]" class="form-input" data-review-field="rack_id">
                                                        <option value="">Auto Assign</option>
                                                        @foreach($racks as $rack)
                                                            <option value="{{ $rack->id }}">{{ $rack->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="form-label">Cover URL</label>
                                                    <input type="text" name="books[{{ $flatIndex }}][cover_url]" value="{{ $book['cover_url'] ?? '' }}" class="form-input" data-review-field="cover_url">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <label class="form-label">Description</label>
                                            <textarea name="books[{{ $flatIndex }}][description]" class="form-input min-h-[140px] text-justify" data-review-field="description">{{ $book['description'] ?? '' }}</textarea>
                                        </div>
                                    </div>
                                    @php $flatIndex++; @endphp
                                @endforeach
                            </div>
                        </section>
                    @endforeach

                    <div class="fixed bottom-6 right-8 z-50 flex justify-end">
                        <div class="rounded-2xl border border-primary-100 bg-white/95 px-4 py-4 shadow-2xl backdrop-blur-md">
                            <button type="submit" id="review-submit-button" class="rounded-xl bg-primary-700 px-6 py-3.5 text-sm font-bold tracking-wide text-white transition-all hover:bg-primary-800 hover:shadow-lg disabled:cursor-not-allowed disabled:bg-gray-400">Simpan Semua ke Library</button>
                        </div>
                    </div>
                </form>
            @else
                <div class="rounded-[1.5rem] border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                    <h3 class="text-2xl font-black tracking-tight text-gray-900">Belum ada hasil scan untuk direview</h3>
                    <p class="mt-3 text-sm leading-7 text-gray-500">Jalankan batch scan dulu. Setelah itu, semua buku akan otomatis dikelompokkan berdasarkan kategori di sini.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ISBN Scan Continuous Looper --}}
    <div data-tab-panel="isbn" class="hidden">
        <div class="mb-5 rounded-[1.4rem] border border-sky-100 bg-gradient-to-r from-sky-50 via-white to-indigo-50 px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-700">Continuous Looper</p>
            <h2 class="mt-1 text-xl font-black tracking-tight text-gray-900">Scan ISBN beruntun — tiiit tiiit tiiit! 🔫</h2>
            <p class="mt-2 text-sm text-gray-600">Scan ISBN → data otomatis ditarik → form reset → fokus kembali ke input. Tanpa sentuh mouse.</p>
        </div>

        <x-card class="mb-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end">
                <div class="flex-1">
                    <label for="isbn-looper-input" class="form-label text-lg font-bold">ISBN Scanner</label>
                    <input id="isbn-looper-input" type="text" class="form-input mt-2 text-xl font-mono tracking-widest" placeholder="Scan atau ketik ISBN lalu tekan Enter..." autofocus>
                </div>
                <button id="isbn-looper-fetch-btn" type="button" class="rounded-xl bg-sky-700 px-6 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-sky-600">
                    Fetch ISBN
                </button>
            </div>
            <p id="isbn-looper-status" class="mt-3 text-sm text-gray-500">Menunggu input ISBN...</p>
        </x-card>

        {{-- Fetched books list --}}
        <div id="isbn-looper-list" class="space-y-3"></div>

        <div id="isbn-looper-empty" class="rounded-[1.5rem] border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <h3 class="text-xl font-black tracking-tight text-gray-900">Belum ada buku di-fetch</h3>
            <p class="mt-2 text-sm text-gray-500">Mulai scan ISBN untuk menambahkan buku ke daftar review.</p>
        </div>
    </div>

    <template id="ai-batch-item-template">
        <div class="batch-slot-card rounded-[1.25rem] border border-gray-200 bg-gray-50/70 p-5" data-ai-book-item>
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Book Slot</p>
                    <h4 class="ai-batch-title mt-1 text-lg font-bold text-gray-900">Buku</h4>
                </div>
                <button type="button" class="remove-batch-book rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600">Hapus</button>
            </div>
            <div class="grid gap-4 xl:grid-cols-[140px_1fr]">
                <div class="batch-slot-preview">
                    <img src="" alt="Preview cover" class="hidden h-full w-full object-contain p-3" data-slot-front-preview>
                    <div class="batch-slot-placeholder" data-slot-placeholder>
                        <span>Front cover</span>
                    </div>
                </div>
                <div class="grid gap-4">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-end">
                        <div class="min-w-0 flex-1">
                            <label class="mb-2 block text-sm font-semibold text-gray-700">Front Cover *</label>
                            <input type="file" accept=".jpg,.jpeg,.png,.webp,image/*" class="form-input" required data-field="front_image">
                        </div>
                        <div class="min-w-0 flex-1">
                            <label class="mb-2 block text-sm font-semibold text-gray-700">Back Cover</label>
                            <input type="file" accept=".jpg,.jpeg,.png,.webp,image/*" class="form-input" data-field="back_image">
                        </div>
                    </div>
                    <div class="rounded-xl border border-dashed border-gray-200 bg-white px-4 py-3">
                        <div class="flex flex-wrap gap-2 text-xs text-gray-500">
                            <span class="rounded-full bg-gray-100 px-2.5 py-1" data-front-status>Front belum dipilih</span>
                            <span class="rounded-full bg-gray-100 px-2.5 py-1" data-back-status>Back opsional</span>
                            <span class="rounded-full bg-gray-100 px-2.5 py-1" data-scan-status>Belum discan</span>
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-700">Catatan Slot</label>
                        <input type="text" class="form-input" placeholder="Contoh: tumpukan 1 / batch sejarah" data-field="notes">
                    </div>
                </div>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        (() => {
            const tabTriggers = document.querySelectorAll('[data-tab-trigger]');
            const tabPanels = document.querySelectorAll('[data-tab-panel]');
            const activeClasses = ['bg-white', 'text-primary-800', 'shadow-sm', 'font-semibold'];
            const inactiveClasses = ['text-gray-500', 'hover:text-gray-700'];

            const setTopTab = (name) => {
                tabPanels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== name);
                });
                tabTriggers.forEach((trigger) => {
                    const isActive = trigger.getAttribute('data-tab-trigger') === name;
                    activeClasses.forEach((cls) => trigger.classList.toggle(cls, isActive));
                    inactiveClasses.forEach((cls) => trigger.classList.toggle(cls, !isActive));
                    const dot = trigger.querySelector('span');
                    if (dot) {
                        dot.classList.toggle('bg-primary-700', isActive);
                        dot.classList.toggle('bg-gray-300', !isActive);
                    }
                });
            };

            tabTriggers.forEach((trigger) => {
                trigger.addEventListener('click', (event) => {
                    event.preventDefault();
                    setTopTab(trigger.getAttribute('data-tab-trigger'));
                });
            });

            // Auto-select tab based on state
            const autoTab = @json($groupedDraftBooks->isNotEmpty() && $aiDraftFinished ? 'review' : 'ai');
            setTopTab(autoTab);

            const batchList = document.getElementById('ai-batch-list');
            const batchTemplate = document.getElementById('ai-batch-item-template');
            const batchForm = document.getElementById('batch-scan-form');
            const batchScanStatus = document.getElementById('batch-scan-status');
            const batchScanStatusTitle = document.getElementById('batch-scan-status-title');
            const batchScanStatusSubtitle = document.getElementById('batch-scan-status-subtitle');
            const batchScanStatusNote = document.getElementById('batch-scan-status-note');
            const batchScanLiveCount = document.getElementById('batch-scan-live-count');
            const batchScanLiveTotal = document.getElementById('batch-scan-live-total');
            const batchCancelButton = document.getElementById('batch-cancel-btn');
            const batchReadyIndicator = document.getElementById('batch-ready-indicator');
            const batchScanSubmit = document.getElementById('batch-scan-submit');
            const batchScanSubmitSpinner = document.getElementById('batch-scan-submit-spinner');
            const batchScanSubmitLabel = document.getElementById('batch-scan-submit-label');
            const existingBatchDraftToken = @json($ai_scan_draft_token);
            let activeBatchDraftToken = existingBatchDraftToken;
            let batchPollingTimer = null;
            const validateImageFile = (file) => {
                if (!file) return false;
                const ext = file.name.split('.').pop().toLowerCase();
                return ['jpg', 'jpeg', 'png', 'webp', 'avif', 'heic', 'heif', 'bmp'].includes(ext) || file.type.startsWith('image/');
            };

            const updateSlotPreview = (card) => {
                if (!card) return;
                const frontInput = card.querySelector('[data-field="front_image"]');
                const backInput = card.querySelector('[data-field="back_image"]');
                const preview = card.querySelector('[data-slot-front-preview]');
                const placeholder = card.querySelector('[data-slot-placeholder]');
                const frontStatus = card.querySelector('[data-front-status]');
                const backStatus = card.querySelector('[data-back-status]');
                const scanStatus = card.querySelector('[data-scan-status]');

                const frontFile = frontInput?.files?.[0];
                const backFile = backInput?.files?.[0];
                const hasFrontRaw = Boolean(frontFile);
                const hasFrontValid = validateImageFile(frontFile);
                const hasBackValid = validateImageFile(backFile);

                if (frontStatus) {
                    if (hasFrontValid) {
                        frontStatus.textContent = `Front siap: ${frontFile.name}`;
                        frontStatus.className = 'rounded-full bg-primary-100 px-2.5 py-1 text-primary-700';
                    } else if (hasFrontRaw) {
                        frontStatus.textContent = `Format tidak valid`;
                        frontStatus.className = 'rounded-full bg-red-100 px-2.5 py-1 text-red-700';
                    } else {
                        frontStatus.textContent = 'Front belum dipilih';
                        frontStatus.className = 'rounded-full bg-gray-100 px-2.5 py-1 text-gray-500';
                    }
                }
                if (backStatus) {
                    if (hasBackValid) {
                        backStatus.textContent = `Back siap: ${backFile.name}`;
                        backStatus.className = 'rounded-full bg-amber-100 px-2.5 py-1 text-amber-700';
                    } else if (backFile) {
                        backStatus.textContent = `Format tidak valid`;
                        backStatus.className = 'rounded-full bg-red-100 px-2.5 py-1 text-red-700';
                    } else {
                        backStatus.textContent = 'Back opsional';
                        backStatus.className = 'rounded-full bg-gray-100 px-2.5 py-1 text-gray-500';
                    }
                }
                if (scanStatus) {
                    if (hasFrontValid) {
                        scanStatus.textContent = 'Siap discan';
                        scanStatus.className = 'rounded-full bg-sky-100 px-2.5 py-1 text-sky-700';
                    } else {
                        scanStatus.textContent = 'Belum/Tidak bisa discan';
                        scanStatus.className = 'rounded-full bg-gray-100 px-2.5 py-1 text-gray-500';
                    }
                }
                card.classList.toggle('is-scanning', hasFrontValid || hasBackValid);

                if (preview && placeholder) {
                    if (hasFrontValid) {
                        preview.src = URL.createObjectURL(frontFile);
                        preview.classList.remove('hidden');
                        placeholder.classList.add('hidden');
                    } else {
                        preview.classList.add('hidden');
                        preview.removeAttribute('src');
                        placeholder.classList.remove('hidden');
                    }
                }
            };

            const updateBatchCounters = () => {
                if (!batchList) return;
                const cards = [...batchList.querySelectorAll('[data-ai-book-item]')];
                const ready = cards.filter((card) => validateImageFile(card.querySelector('[data-field="front_image"]')?.files?.[0])).length;
                if (batchReadyIndicator) {
                    batchReadyIndicator.textContent = `${ready} buku siap discan`;
                }
                if (batchScanLiveCount) batchScanLiveCount.textContent = String(ready);
                if (batchScanLiveTotal) batchScanLiveTotal.textContent = String(cards.length);
            };

            const renderBatchCardProgress = (card, book) => {
                if (!card || !book) return;
                const scanStatus = card.querySelector('[data-scan-status]');
                if (!scanStatus) return;

                const status = book.scan_status || 'pending';
                if (status === 'pending') {
                    scanStatus.textContent = 'Menunggu antrian';
                    scanStatus.className = 'rounded-full bg-gray-100 px-2.5 py-1 text-gray-600';
                    return;
                }

                if (status === 'processing') {
                    scanStatus.textContent = 'Sedang diproses';
                    scanStatus.className = 'rounded-full bg-sky-100 px-2.5 py-1 text-sky-700';
                    return;
                }

                if (status === 'success') {
                    scanStatus.textContent = 'Sudah discan';
                    scanStatus.className = 'rounded-full bg-primary-100 px-2.5 py-1 text-primary-700';
                    return;
                }

                if (status === 'failed') {
                    scanStatus.textContent = 'Gagal, perlu review';
                    scanStatus.className = 'rounded-full bg-red-100 px-2.5 py-1 text-red-700';
                    return;
                }

                if (status === 'cancelled') {
                    scanStatus.textContent = 'Dibatalkan';
                    scanStatus.className = 'rounded-full bg-amber-100 px-2.5 py-1 text-amber-700';
                }
            };

            const setBatchSubmitLoading = (loading) => {
                if (batchScanSubmit) {
                    batchScanSubmit.disabled = loading;
                    batchScanSubmit.classList.toggle('is-loading', loading);
                }
                if (batchScanSubmitSpinner) {
                    batchScanSubmitSpinner.classList.toggle('hidden', !loading);
                }
                if (batchScanSubmitLabel) {
                    batchScanSubmitLabel.textContent = loading ? 'Memasukkan ke antrian...' : 'Mulai Waterfall Scan';
                }
            };

            const getBatchStatusUrl = (token) => {
                const template = batchForm?.dataset.statusUrlTemplate || '';
                return template.replace('__TOKEN__', token);
            };

            const getBatchCancelUrl = (token) => {
                const template = batchForm?.dataset.cancelUrlTemplate || '';
                return template.replace('__TOKEN__', token);
            };

            const stopBatchPolling = () => {
                if (batchPollingTimer) {
                    clearTimeout(batchPollingTimer);
                    batchPollingTimer = null;
                }
            };

            const renderBatchStatusSummary = (draft) => {
                const summary = draft?.summary;
                if (!summary || !batchScanStatus) return;

                batchScanStatus.classList.remove('hidden');
                if (batchCancelButton) {
                    const isCancellable = !(summary.is_finished || draft?.status === 'cancelled');
                    batchCancelButton.classList.toggle('hidden', !isCancellable);
                    batchCancelButton.disabled = !isCancellable;
                }
                if (batchScanStatusTitle) {
                    batchScanStatusTitle.textContent = draft?.status === 'cancelled'
                        ? 'Batch scan dibatalkan.'
                        : summary.is_finished
                        ? 'Batch scan selesai.'
                        : (summary.is_stale_queue
                            ? 'Antrian scan belum diproses worker.'
                            : 'Batch scan sedang berjalan di background...');
                }
                if (batchScanStatusSubtitle) {
                    batchScanStatusSubtitle.textContent = draft?.status === 'cancelled'
                        ? `${summary.cancelled || 0} dibatalkan, ${summary.success} berhasil, ${summary.failed} gagal.`
                        : summary.is_stale_queue
                        ? `${summary.success} berhasil, ${summary.failed} gagal, ${summary.pending + summary.processing} masih menunggu.`
                        : `${summary.success} berhasil, ${summary.failed} gagal, ${summary.pending + summary.processing} masih berjalan.`;
                }
                if (batchScanStatusNote) {
                    batchScanStatusNote.textContent = draft?.status === 'cancelled'
                        ? 'Antrian scan sudah dibatalkan. Buku yang belum diproses tidak akan dilanjutkan.'
                        : summary.is_finished
                        ? 'Halaman akan disegarkan agar hasil review sinkron.'
                        : (summary.is_stale_queue
                            ? `Job sudah masuk antrian lebih dari ${summary.queue_wait_seconds || 0} detik. Jalankan worker queue: php artisan queue:work database --queue=ai-scan --tries=1 --sleep=1`
                            : 'Worker memproses satu per satu agar GPU/VRAM Ollama tetap aman.');
                }
                if (batchScanLiveCount) batchScanLiveCount.textContent = String(summary.completed);
                if (batchScanLiveTotal) batchScanLiveTotal.textContent = String(summary.total);
            };

            const pollBatchStatus = async (token) => {
                if (!token) return;

                try {
                    const response = await fetch(getBatchStatusUrl(token), {
                        headers: { Accept: 'application/json' },
                    });
                    if (!response.ok) {
                        throw new Error('Gagal mengambil progress batch scan.');
                    }

                    const draft = await response.json();
                    renderBatchStatusSummary(draft);

                    const cards = [...batchList.querySelectorAll('[data-ai-book-item]')];
                    (draft.books || []).forEach((book, index) => renderBatchCardProgress(cards[index], book));

                    if (draft.status === 'cancelled') {
                        setBatchSubmitLoading(false);
                        stopBatchPolling();
                        window.setTimeout(() => window.location.reload(), 600);
                        return;
                    }

                    if (draft.summary?.is_finished) {
                        setBatchSubmitLoading(false);
                        stopBatchPolling();
                        window.setTimeout(() => window.location.reload(), 1200);
                        return;
                    }
                } catch (error) {
                    if (batchScanStatusTitle) batchScanStatusTitle.textContent = 'Progress batch belum bisa dibaca.';
                    if (batchScanStatusSubtitle) batchScanStatusSubtitle.textContent = error.message || 'Periksa koneksi ke server.';
                }

                batchPollingTimer = window.setTimeout(() => pollBatchStatus(token), 2000);
            };

            const reindexBatchCards = () => {
                if (!batchList) return;
                const cards = [...batchList.querySelectorAll('[data-ai-book-item]')];
                cards.forEach((card, index) => {
                    const title = card.querySelector('.ai-batch-title');
                    if (title) title.textContent = `Buku ${index + 1}`;
                    card.querySelectorAll('[data-field]').forEach((field) => {
                        field.name = `books[${index}][${field.dataset.field}]`;
                    });
                });
                cards.forEach((card) => {
                    const button = card.querySelector('.remove-batch-book');
                    if (!button) return;
                    button.disabled = cards.length === 1;
                    button.classList.toggle('opacity-50', cards.length === 1);
                });
                cards.forEach((card) => updateSlotPreview(card));
                updateBatchCounters();
            };

            const addBatchCard = (count = 1) => {
                if (!batchList || !batchTemplate) return;
                for (let i = 0; i < count; i += 1) {
                    batchList.appendChild(batchTemplate.content.cloneNode(true));
                }
                reindexBatchCards();
            };

            document.getElementById('add-batch-book')?.addEventListener('click', (event) => {
                event.preventDefault();
                addBatchCard(1);
            });

            document.getElementById('add-batch-five')?.addEventListener('click', (event) => {
                event.preventDefault();
                addBatchCard(5);
            });

            batchList?.addEventListener('click', (event) => {
                const removeButton = event.target.closest('.remove-batch-book');
                if (!removeButton) return;
                event.preventDefault();
                if (batchList.querySelectorAll('[data-ai-book-item]').length <= 1) return;
                removeButton.closest('[data-ai-book-item]')?.remove();
                reindexBatchCards();
            });
            batchList?.addEventListener('change', (event) => {
                const changedInput = event.target.closest('[data-field="front_image"], [data-field="back_image"]');
                if (!changedInput) return;
                updateSlotPreview(changedInput.closest('[data-ai-book-item]'));
                updateBatchCounters();
            });
            reindexBatchCards();

            batchForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const cards = [...batchList.querySelectorAll('[data-ai-book-item]')];
                const total = cards.length;
                const ready = cards.filter((card) => validateImageFile(card.querySelector('[data-field="front_image"]')?.files?.[0])).length;

                if (ready === 0) {
                    if (batchScanStatus) batchScanStatus.classList.remove('hidden');
                    if (batchScanStatusTitle) batchScanStatusTitle.textContent = 'Batch scan belum bisa dijalankan.';
                    if (batchScanStatusSubtitle) batchScanStatusSubtitle.textContent = 'Pilih minimal 1 front cover terlebih dahulu.';
                    return;
                }

                if (batchScanStatus) batchScanStatus.classList.remove('hidden');
                if (batchScanStatusTitle) batchScanStatusTitle.textContent = 'Mengirim batch ke antrian scan...';
                if (batchScanStatusSubtitle) batchScanStatusSubtitle.textContent = `${ready} dari ${total} buku sedang dipersiapkan.`;
                if (batchScanStatusNote) batchScanStatusNote.textContent = 'Setelah job masuk queue, progress akan dipantau otomatis.';
                if (batchScanLiveCount) batchScanLiveCount.textContent = '0';
                if (batchScanLiveTotal) batchScanLiveTotal.textContent = String(ready);
                setBatchSubmitLoading(true);
                stopBatchPolling();

                cards.forEach((card) => {
                    const frontFile = card.querySelector('[data-field="front_image"]')?.files?.[0];
                    if (frontFile) {
                        if (validateImageFile(frontFile)) {
                            renderBatchCardProgress(card, { scan_status: 'pending' });
                        } else {
                            renderBatchCardProgress(card, { scan_status: 'failed' });
                            const scanStatus = card.querySelector('[data-scan-status]');
                            if(scanStatus) {
                                scanStatus.textContent = 'Format salah, dilewati';
                                scanStatus.className = 'rounded-full bg-red-100 px-2.5 py-1 text-red-700';
                            }
                        }
                    }
                });

                try {
                    const formData = new FormData(batchForm);
                    const response = await fetch(batchForm.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Batch scan gagal dimasukkan ke antrian.');
                    }

                    activeBatchDraftToken = data.draft_token || null;
                    if (batchScanStatusTitle) batchScanStatusTitle.textContent = 'Batch scan sudah masuk antrian.';
                    if (batchScanStatusSubtitle) batchScanStatusSubtitle.textContent = `${ready} buku sedang menunggu worker.`;
                    pollBatchStatus(activeBatchDraftToken);
                } catch (error) {
                    setBatchSubmitLoading(false);
                    if (batchScanStatusTitle) batchScanStatusTitle.textContent = 'Batch scan gagal dijalankan.';
                    if (batchScanStatusSubtitle) batchScanStatusSubtitle.textContent = error.message || 'Periksa koneksi dan runtime.';
                }
            });

            if (existingBatchDraftToken && !@json($aiDraftFinished)) {
                pollBatchStatus(existingBatchDraftToken);
            }

            batchCancelButton?.addEventListener('click', async () => {
                if (!activeBatchDraftToken) return;
                if (!window.confirm('Batalkan batch scan ini? Buku yang belum selesai diproses tidak akan dilanjutkan.')) {
                    return;
                }

                batchCancelButton.disabled = true;

                try {
                    const response = await fetch(getBatchCancelUrl(activeBatchDraftToken), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            Accept: 'application/json',
                        },
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Gagal membatalkan batch scan.');
                    }

                    stopBatchPolling();
                    window.location.reload();
                } catch (error) {
                    batchCancelButton.disabled = false;
                    if (batchScanStatusNote) {
                        batchScanStatusNote.textContent = error.message || 'Gagal membatalkan batch scan.';
                    }
                }
            });

            const reviewSubmitButton = document.getElementById('review-submit-button');
            const reindexReviewCards = () => {
                const reviewCards = [...document.querySelectorAll('[data-review-book-item]')];
                reviewCards.forEach((card, index) => {
                    card.querySelectorAll('[data-review-field]').forEach((field) => {
                        field.name = `books[${index}][${field.dataset.reviewField}]`;
                    });
                });

                document.querySelectorAll('[data-review-section]').forEach((section) => {
                    const items = section.querySelectorAll('[data-review-book-item]');
                    const badge = section.querySelector('[data-review-count]');
                    if (badge) {
                        badge.textContent = `${items.length} buku`;
                    }

                    if (items.length === 0) {
                        section.remove();
                    }
                });

                if (reviewSubmitButton) {
                    reviewSubmitButton.disabled = reviewCards.length === 0;
                }
            };

            document.querySelectorAll('[data-review-list]').forEach((list) => {
                list.addEventListener('click', async (event) => {
                    const removeButton = event.target.closest('.remove-review-book');
                    if (removeButton) {
                        event.preventDefault();
                        removeButton.closest('[data-review-book-item]')?.remove();
                        reindexReviewCards();
                        return;
                    }

                    const enrichButton = event.target.closest('.enrich-review-book');
                    if (enrichButton) {
                        event.preventDefault();
                        const card = enrichButton.closest('[data-review-book-item]');
                        if (!card) return;

                        const titleInput = card.querySelector('[data-review-field="title"]');
                        const authorInput = card.querySelector('[data-review-field="author"]');
                        const isbnInput = card.querySelector('[data-review-field="isbn"]');
                        const descInput = card.querySelector('[data-review-field="description"]');
                        const coverUrlInput = card.querySelector('[data-review-field="cover_url"]');

                        const spinner = enrichButton.querySelector('.enrich-spinner');
                        const span = enrichButton.querySelector('span:not(.enrich-spinner)');
                        enrichButton.disabled = true;
                        spinner?.classList.remove('hidden');
                        if (span) span.textContent = 'Mencari...';

                        try {
                            const response = await fetch("{{ route('books.import.enrich') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    title: titleInput?.value || '',
                                    author: authorInput?.value || '',
                                    isbn: isbnInput?.value || ''
                                }),
                            });
                            const data = await response.json();
                            if (!response.ok) throw new Error(data.message || 'Gagal mencari data');

                            if (titleInput && data.title) titleInput.value = data.title;
                            if (authorInput && data.author) authorInput.value = data.author;
                            if (isbnInput && data.isbn) isbnInput.value = data.isbn;
                            if (descInput && data.description && !descInput.value) descInput.value = data.description;
                            if (coverUrlInput && data.cover_url && !coverUrlInput.value) coverUrlInput.value = data.cover_url;

                            enrichButton.classList.add('bg-green-50', 'text-green-700', 'border-green-200');
                            enrichButton.classList.remove('bg-primary-50', 'text-primary-700', 'border-primary-200');
                            if (span) span.textContent = 'Berhasil';
                            
                            setTimeout(() => {
                                enrichButton.classList.remove('bg-green-50', 'text-green-700', 'border-green-200');
                                enrichButton.classList.add('bg-primary-50', 'text-primary-700', 'border-primary-200');
                                if (span) span.textContent = 'Cari Data Kosong';
                            }, 3000);

                        } catch (error) {
                            alert('Pencarian data gagal: ' + error.message);
                            if (span) span.textContent = 'Cari Data Kosong';
                        } finally {
                            enrichButton.disabled = false;
                            spinner?.classList.add('hidden');
                        }
                    }
                });
            });

            reindexReviewCards();

            const lookupButton = document.getElementById('isbn-lookup-btn');
            const statusNode = document.getElementById('isbn-lookup-status');
            const isbnInput = document.getElementById('isbn-input');
            const titleInput = document.getElementById('title-input');
            const authorInput = document.getElementById('author-input');
            const categoryNameInput = document.getElementById('category-name-input');
            const descriptionInput = document.getElementById('description-input');
            const descriptionSourceNote = document.getElementById('description-source-note');
            const coverUrlInput = document.getElementById('cover-url-input');
            const coverPreviewWrap = document.getElementById('cover-preview-wrap');
            const coverPreviewImage = document.getElementById('cover-preview-img');
            const coverPreviewCaption = document.getElementById('cover-preview-caption');
            const csrfToken = '{{ csrf_token() }}';

            const updateCoverPreview = (url, caption = 'Cover preview') => {
                if (!coverPreviewWrap || !coverPreviewImage) return;
                const value = (url || '').trim();
                if (!value) {
                    coverPreviewWrap.classList.add('hidden');
                    coverPreviewImage.removeAttribute('src');
                    return;
                }
                coverPreviewWrap.classList.remove('hidden');
                coverPreviewImage.src = value;
                if (coverPreviewCaption) coverPreviewCaption.textContent = caption;
            };

            const renderDescriptionSourceNote = (data) => {
                if (!descriptionSourceNote) return;
                const label = data?.source_url ? `<a class="underline hover:text-primary-700" href="${data.source_url}" target="_blank" rel="noopener noreferrer">${data.source}</a>` : (data?.source || 'provider');
                descriptionSourceNote.innerHTML = data?.description ? `Deskripsi otomatis diambil dari ${label}.` : `Deskripsi belum tersedia dari ${data?.source || 'provider'}.`;
                descriptionSourceNote.className = data?.description ? 'mt-1 text-xs text-primary-700' : 'mt-1 text-xs text-amber-700';
            };

            coverUrlInput?.addEventListener('input', (event) => updateCoverPreview(event.target.value));
            updateCoverPreview(coverUrlInput?.value || '');

            lookupButton?.addEventListener('click', async () => {
                const isbn = isbnInput.value.trim();
                if (!isbn) {
                    statusNode.textContent = 'ISBN is required for lookup.';
                    statusNode.className = 'mt-1 text-xs text-red-600';
                    return;
                }

                lookupButton.disabled = true;
                lookupButton.textContent = 'Fetching...';
                statusNode.textContent = 'Fetching metadata...';
                statusNode.className = 'mt-1 text-xs text-gray-500';

                try {
                    const response = await fetch("{{ route('books.import.isbn-lookup') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ isbn }),
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Lookup failed');

                    titleInput.value = data.title ?? titleInput.value;
                    authorInput.value = data.author ?? authorInput.value;
                    categoryNameInput.value = data.category ?? categoryNameInput.value;
                    descriptionInput.value = data.description ?? descriptionInput.value;
                    coverUrlInput.value = data.cover_url ?? coverUrlInput.value;
                    updateCoverPreview(coverUrlInput.value, `Cover preview (${data.source ?? 'provider'})`);
                    renderDescriptionSourceNote(data);

                    statusNode.textContent = `Metadata loaded from ${data.source ?? 'provider'}.`;
                    statusNode.className = 'mt-1 text-xs text-primary-700';
                } catch (error) {
                    statusNode.textContent = error.message;
                    statusNode.className = 'mt-1 text-xs text-red-600';
                } finally {
                    lookupButton.disabled = false;
                    lookupButton.textContent = 'Fetch';
                }
            });

            const aiScanButton = document.getElementById('ai-scan-btn');
            const aiImageInput = document.getElementById('ai-image-input');
            const aiScanStatusNode = document.getElementById('ai-scan-status');
            const aiModeSelect = document.getElementById('ai-scan-mode');
            const manualAiFilesStatus = document.getElementById('manual-ai-files-status');
            const manualAiSourceStatus = document.getElementById('manual-ai-source-status');
            const manualAiSpinner = document.getElementById('manual-ai-scan-spinner');
            const manualAiScanLabel = document.getElementById('manual-ai-scan-label');

            const updateManualAiPreview = () => {
                const files = aiImageInput?.files;
                const total = files?.length || 0;

                if (manualAiFilesStatus) {
                    manualAiFilesStatus.textContent = total === 0 ? 'Belum ada gambar dipilih' : `${total} gambar siap dianalisis`;
                    manualAiFilesStatus.className = `rounded-full px-3 py-1 font-semibold ${total === 0 ? 'bg-white text-gray-600' : 'bg-primary-100 text-primary-700'}`;
                }

                if (manualAiSourceStatus) {
                    manualAiSourceStatus.textContent = total >= 2 ? 'Front + back siap' : (total === 1 ? 'Front cover siap' : 'Siap untuk analisis AI');
                    manualAiSourceStatus.className = `rounded-full px-3 py-1 font-semibold ${total === 0 ? 'bg-white text-gray-600' : 'bg-amber-100 text-amber-700'}`;
                }
            };

            aiImageInput?.addEventListener('change', updateManualAiPreview);
            updateManualAiPreview();

            aiScanButton?.addEventListener('click', async () => {
                const files = aiImageInput?.files;
                if (!files || files.length === 0) {
                    aiScanStatusNode.textContent = 'Pilih minimal 1 gambar buku untuk AI scan.';
                    aiScanStatusNode.className = 'mt-1 text-xs text-red-600';
                    return;
                }

                aiScanButton.disabled = true;
                aiScanButton.classList.add('is-loading');
                manualAiSpinner?.classList.remove('hidden');
                if (manualAiScanLabel) manualAiScanLabel.textContent = 'Scanning...';
                aiScanStatusNode.textContent = 'Menganalisis gambar buku...';
                aiScanStatusNode.className = 'mt-1 text-xs text-gray-500';
                if (manualAiSourceStatus) {
                    manualAiSourceStatus.textContent = 'AI sedang membaca cover';
                    manualAiSourceStatus.className = 'rounded-full bg-primary-100 px-3 py-1 font-semibold text-primary-700';
                }

                try {
                    const formData = new FormData();
                    Array.from(files).forEach((file) => formData.append('images[]', file));
                    formData.append('mode', aiModeSelect?.value || 'full');

                    const response = await fetch("{{ route('books.import.ai-scan') }}", {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: formData,
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'AI scan failed');

                    titleInput.value = data.title ?? titleInput.value;
                    authorInput.value = data.author ?? authorInput.value;
                    categoryNameInput.value = data.category ?? categoryNameInput.value;
                    isbnInput.value = data.isbn ?? isbnInput.value;
                    descriptionInput.value = data.description ?? descriptionInput.value;
                    coverUrlInput.value = data.cover_url ?? coverUrlInput.value;
                    updateCoverPreview(coverUrlInput.value, String(data.cover_url || '').includes('/cropped/') ? 'Cover preview (AI processed cover)' : `Cover preview (${data.source ?? 'ai'})`);
                    renderDescriptionSourceNote(data);

                    aiScanStatusNode.textContent = `Autofill berhasil dari source ${data.source ?? 'unknown'}.`;
                    aiScanStatusNode.className = 'mt-1 text-xs text-primary-700';
                    if (manualAiSourceStatus) {
                        manualAiSourceStatus.textContent = `Sumber: ${data.source ?? 'AI'}`;
                        manualAiSourceStatus.className = 'rounded-full bg-primary-100 px-3 py-1 font-semibold text-primary-700';
                    }
                } catch (error) {
                    aiScanStatusNode.textContent = error.message || 'AI scan gagal.';
                    aiScanStatusNode.className = 'mt-1 text-xs text-red-600';
                    if (manualAiSourceStatus) {
                        manualAiSourceStatus.textContent = 'Scan gagal, periksa koneksi/runtime';
                        manualAiSourceStatus.className = 'rounded-full bg-red-100 px-3 py-1 font-semibold text-red-700';
                    }
                    updateManualAiPreview();
                } finally {
                    aiScanButton.disabled = false;
                    aiScanButton.classList.remove('is-loading');
                    manualAiSpinner?.classList.add('hidden');
                    if (manualAiScanLabel) manualAiScanLabel.textContent = 'Scan with AI';
                }
            });

            const scanButton = document.getElementById('isbn-scan-btn');
            const qrReaderElement = document.getElementById('qr-reader');
            let html5QrcodeScanner = null;

            scanButton?.addEventListener('click', () => {
                if (qrReaderElement.classList.contains('hidden')) {
                    qrReaderElement.classList.remove('hidden');
                    scanButton.textContent = 'Stop Scan';
                    if (!html5QrcodeScanner) {
                        html5QrcodeScanner = new Html5QrcodeScanner('qr-reader', { fps: 10, qrbox: { width: 250, height: 150 } }, false);
                    }
                    html5QrcodeScanner.render((decodedText) => {
                        isbnInput.value = decodedText;
                        html5QrcodeScanner.clear();
                        qrReaderElement.classList.add('hidden');
                        scanButton.textContent = 'Scan';
                        lookupButton?.click();
                    }, () => {});
                } else {
                    html5QrcodeScanner?.clear();
                    qrReaderElement.classList.add('hidden');
                    scanButton.textContent = 'Scan';
                }
            });
        })();

        // ISBN Continuous Looper
        (() => {
            const input = document.getElementById('isbn-looper-input');
            const fetchBtn = document.getElementById('isbn-looper-fetch-btn');
            const status = document.getElementById('isbn-looper-status');
            const list = document.getElementById('isbn-looper-list');
            const empty = document.getElementById('isbn-looper-empty');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const lookupUrl = @json(route('books.import.isbn-lookup'));
            let fetchCount = 0;

            if (!input || !fetchBtn) return;

            const doFetch = async () => {
                const isbn = input.value.trim();
                if (!isbn) return;

                fetchBtn.disabled = true;
                fetchBtn.textContent = '⏳...';
                status.textContent = 'Mencari data ISBN: ' + isbn + '...';
                status.className = 'mt-3 text-sm text-gray-500';

                try {
                    const res = await fetch(lookupUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify({ isbn }),
                    });
                    const data = await res.json();

                    if (res.ok && data.title) {
                        fetchCount++;
                        const card = document.createElement('div');
                        card.className = 'rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4 flex items-start gap-4';
                        card.innerHTML = `
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-lg font-black text-emerald-700">${fetchCount}</div>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-base font-bold text-gray-900">${data.title}</h4>
                                <p class="mt-1 text-sm text-gray-600">${data.author || '-'}</p>
                                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                    <span class="rounded-full bg-white px-2.5 py-1 font-semibold text-gray-600">ISBN: ${isbn}</span>
                                    ${data.category ? '<span class="rounded-full bg-white px-2.5 py-1 font-semibold text-sky-700">' + data.category + '</span>' : ''}
                                </div>
                            </div>
                        `;
                        list.prepend(card);
                        empty.classList.add('hidden');

                        status.textContent = '✅ Berhasil! ' + fetchCount + ' buku terfetch. Lanjutkan scan...';
                        status.className = 'mt-3 text-sm font-semibold text-emerald-700';

                        // THE LOOP: Reset & refocus
                        input.value = '';
                        input.focus();
                    } else {
                        status.textContent = '❌ ' + (data.message || 'ISBN tidak ditemukan: ' + isbn);
                        status.className = 'mt-3 text-sm font-semibold text-red-600';
                        input.select();
                    }
                } catch (err) {
                    status.textContent = '❌ Gagal menghubungi server.';
                    status.className = 'mt-3 text-sm font-semibold text-red-600';
                }

                fetchBtn.disabled = false;
                fetchBtn.textContent = 'Fetch ISBN';
            };

            fetchBtn.onclick = doFetch;
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); doFetch(); }
            });
        })();
    </script>
@endpush
