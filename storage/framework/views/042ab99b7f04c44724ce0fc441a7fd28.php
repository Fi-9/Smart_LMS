<?php $__env->startSection('content'); ?>
    <?php
        /** @var \Illuminate\Support\ViewErrorBag $errors */
        $errors = $errors ?? new \Illuminate\Support\ViewErrorBag();
        $manualFieldNames = ['title', 'author', 'isbn', 'category_name', 'rack_id', 'cover_url', 'description'];
        $hasManualErrors = collect($manualFieldNames)->contains(fn ($name) => $errors->has($name));
        $defaultBatchBooks = old('books');
        if (! is_array($defaultBatchBooks) || count($defaultBatchBooks) === 0) {
            $defaultBatchBooks = [[], [], []];
        }

        $draftBooks = collect($ai_scan_draft['books'] ?? []);
        $groupedDraftBooks = $draftBooks->groupBy(function (array $book): string {
            $category = trim((string) ($book['category_name'] ?? ''));
            return $category !== '' ? $category : 'Tanpa Kategori';
        })->sortKeys();
        $recommendedScanMode = $ai_runtime['recommended_scan_mode'] ?? 'simple';
        $ollamaDiagnostic = $ai_diagnostics['ollama'] ?? null;
        $visionOnline = ($ollamaDiagnostic['status'] ?? null) === 'ok';
    ?>

    <?php
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
    ?>

    <div class="mb-6">
        <h1 class="page-title">Import Books</h1>
        <p class="page-subtitle">Scan AI untuk banyak buku sekaligus, review hasil per kategori, lalu baru masuk ke rack.</p>
    </div>

    <div class="mb-6 rounded-[1.35rem] border border-gray-200 bg-white px-4 py-3 shadow-sm">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-primary-50 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-primary-700">Profile</p>
                <div class="mt-1 flex items-center justify-between gap-3">
                    <span class="text-sm font-bold text-gray-900"><?php echo e(strtoupper($ai_runtime['profile'] ?? 'LOCAL')); ?></span>
                    <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-primary-700">Mode <?php echo e(strtoupper($recommendedScanMode)); ?></span>
                </div>
            </div>
            <div class="rounded-xl bg-gray-50 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gray-400">Vision</p>
                <div class="mt-1 flex items-center justify-between gap-3">
                    <span class="text-sm font-bold text-gray-900"><?php echo e($ai_runtime['vision']['model'] ?? 'Belum diatur'); ?></span>
                    <span class="text-xs font-semibold <?php echo e(($ai_runtime['vision']['enabled'] ?? false) ? 'text-primary-700' : 'text-amber-700'); ?>"><?php echo e($ai_runtime['vision']['status_label'] ?? 'Unknown'); ?></span>
                </div>
            </div>
            <div class="rounded-xl bg-gray-50 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gray-400">Text</p>
                <div class="mt-1 flex items-center justify-between gap-3">
                    <span class="text-sm font-bold text-gray-900"><?php echo e($ai_runtime['text']['model'] ?? 'Belum diatur'); ?></span>
                    <span class="text-xs font-semibold <?php echo e(($ai_runtime['text']['enabled'] ?? false) ? 'text-primary-700' : 'text-amber-700'); ?>"><?php echo e($ai_runtime['text']['status_label'] ?? 'Unknown'); ?></span>
                </div>
            </div>
            <div class="rounded-xl bg-gray-50 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gray-400">Websearch</p>
                <div class="mt-1 flex items-center justify-between gap-3">
                    <span class="text-sm font-bold text-gray-900"><?php echo e(($ai_runtime['websearch']['enabled'] ?? false) ? 'Enabled' : 'Disabled'); ?></span>
                    <span class="text-xs font-semibold <?php echo e(($ai_runtime['websearch']['enabled'] ?? false) ? 'text-primary-700' : 'text-amber-700'); ?>"><?php echo e($ai_runtime['websearch']['status_label'] ?? 'Unknown'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if($import_summary): ?>
        <div class="mb-6 rounded-2xl border border-primary-200 bg-primary-50 px-5 py-4 text-sm text-primary-900">
            Imported: <strong><?php echo e($import_summary['imported']); ?></strong> | Skipped: <strong><?php echo e($import_summary['skipped']); ?></strong>
            <?php if(!empty($import_summary['skipped_reasons'])): ?>
                <ul class="mt-2 list-inside list-disc text-xs text-primary-900">
                    <?php $__currentLoopData = $import_summary['skipped_reasons']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reason => $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($reason); ?> (<?php echo e($count); ?>)</li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="mb-5 flex gap-1 rounded-xl bg-gray-100 p-1" style="width: fit-content">
        <button type="button" data-tab-trigger="ai" class="rounded-lg px-5 py-2 text-sm font-medium transition-all duration-150">AI Scan</button>
        <button type="button" data-tab-trigger="manual" class="rounded-lg px-5 py-2 text-sm font-medium transition-all duration-150">Manual Input</button>
    </div>

    <div data-tab-panel="ai" class="<?php echo e($hasManualErrors ? 'hidden' : ''); ?>">
        <div class="mb-5 rounded-[1.4rem] border border-primary-100 bg-gradient-to-r from-primary-50 via-white to-amber-50 px-5 py-4">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-primary-700">AI Batch Intake</p>
                    <h2 class="mt-1 text-xl font-black tracking-tight text-gray-900">Front cover wajib, back cover opsional untuk bantu baca sinopsis.</h2>
                </div>
                <div class="flex flex-wrap gap-3 text-sm text-gray-600">
                    <span class="rounded-full bg-white px-3 py-1.5">1. Tambah slot buku</span>
                    <span class="rounded-full bg-white px-3 py-1.5">2. Upload & scan batch</span>
                    <span class="rounded-full bg-white px-3 py-1.5">3. Review per kategori</span>
                </div>
            </div>
            <?php if(!$visionOnline): ?>
                <div class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Runtime vision belum siap. <?php echo e($ollamaDiagnostic['detail'] ?? 'Scan AI tidak akan berjalan sampai koneksi Ollama dan model vision aktif.'); ?>

                </div>
            <?php endif; ?>
        </div>

        <div class="mb-5 flex gap-1 rounded-xl bg-gray-100 p-1" style="width: fit-content">
            <button type="button" data-ai-tab-trigger="scan" class="rounded-lg px-5 py-2 text-sm font-medium transition-all duration-150">Batch Scan</button>
            <button type="button" data-ai-tab-trigger="review" class="rounded-lg px-5 py-2 text-sm font-medium transition-all duration-150">
                Review & Grouping
                <?php if($groupedDraftBooks->isNotEmpty()): ?>
                    <span class="ml-2 rounded-full bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-700"><?php echo e($draftBooks->count()); ?></span>
                <?php endif; ?>
            </button>
        </div>

        <div data-ai-tab-panel="scan">
            <form method="POST" action="<?php echo e(route('books.import.ai-batch-scan')); ?>" enctype="multipart/form-data" class="space-y-5" id="batch-scan-form">
                <?php echo csrf_field(); ?>
                <div class="rounded-[1.5rem] border border-gray-200 bg-white p-6">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Batch Scan Form</h3>
                            <p class="mt-1 text-sm text-gray-500">Tambahkan banyak buku sekaligus lalu scan dalam satu proses.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <select name="mode" class="form-select w-auto min-w-[160px]">
                                <option value="full" <?php if(old('mode', $recommendedScanMode) === 'full'): echo 'selected'; endif; ?>>Mode: Full</option>
                                <option value="simple" <?php if(old('mode', $recommendedScanMode) === 'simple'): echo 'selected'; endif; ?>>Mode: Simple</option>
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
                            <div class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-primary-700">
                                <span id="batch-scan-live-count">0</span>/<span id="batch-scan-live-total">0</span> buku
                            </div>
                        </div>
                    </div>

                    <div id="ai-batch-list" class="grid gap-4">
                        <?php $__currentLoopData = $defaultBatchBooks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="batch-slot-card rounded-[1.25rem] border border-gray-200 bg-gray-50/70 p-5" data-ai-book-item>
                                <div class="mb-4 flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Book Slot</p>
                                        <h4 class="ai-batch-title mt-1 text-lg font-bold text-gray-900">Buku <?php echo e($index + 1); ?></h4>
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
                                                <input type="file" name="books[<?php echo e($index); ?>][front_image]" accept=".jpg,.jpeg,.png,.webp,image/*" class="form-input" required data-field="front_image">
                                                <p class="mt-2 text-xs text-gray-500">Untuk cover, judul, penulis, ISBN, dan gambar katalog.</p>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <label class="mb-2 block text-sm font-semibold text-gray-700">Back Cover</label>
                                                <input type="file" name="books[<?php echo e($index); ?>][back_image]" accept=".jpg,.jpeg,.png,.webp,image/*" class="form-input" data-field="back_image">
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
                                            <input type="text" name="books[<?php echo e($index); ?>][notes]" value="<?php echo e($book['notes'] ?? ''); ?>" class="form-input" placeholder="Contoh: tumpukan 1 / batch sejarah" data-field="notes">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-primary-100 bg-primary-50 px-4 py-4">
                        <p class="text-sm text-primary-900">Setelah scan selesai, sistem langsung pindah ke review dan grouping kategori.</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <div id="batch-ready-indicator" class="text-xs font-semibold text-primary-700">0 buku siap discan</div>
                            <button type="submit" id="batch-scan-submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-800 disabled:cursor-not-allowed disabled:bg-gray-400" <?php if(!$visionOnline): echo 'disabled'; endif; ?>>
                                <span class="scan-action-spinner scan-action-spinner-light hidden" id="batch-scan-submit-spinner"></span>
                                <span id="batch-scan-submit-label">Jalankan Batch Scan AI</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div data-ai-tab-panel="review" class="hidden">
            <?php if($groupedDraftBooks->isNotEmpty()): ?>
                <form method="POST" action="<?php echo e(route('books.import.ai-review-commit')); ?>" class="space-y-5">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="draft_token" value="<?php echo e($ai_scan_draft_token); ?>">

                    <div class="rounded-[1.5rem] border border-gray-200 bg-white p-6">
                        <h3 class="text-lg font-bold text-gray-900">Review Hasil Scan</h3>
                        <p class="mt-1 text-sm text-gray-500">Setiap buku sudah dikelompokkan otomatis berdasarkan kategori. Rapikan data lalu pilih rack bila perlu.</p>
                    </div>

                    <?php $flatIndex = 0; ?>
                    <?php $__currentLoopData = $groupedDraftBooks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $categoryName => $books): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <section class="rounded-[1.5rem] border border-gray-200 bg-white p-6" data-review-section>
                            <div class="mb-5 flex items-center justify-between gap-3 border-b border-gray-100 pb-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-400">Kategori</p>
                                    <h4 class="mt-1 text-2xl font-black tracking-tight text-gray-900"><?php echo e($categoryName); ?></h4>
                                </div>
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-700" data-review-count><?php echo e($books->count()); ?> buku</span>
                            </div>

                            <div class="space-y-5" data-review-list>
                                <?php $__currentLoopData = $books; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="rounded-[1.25rem] border <?php echo e(($book['scan_status'] ?? 'success') === 'failed' ? 'border-amber-200 bg-amber-50/60' : 'border-gray-200 bg-gray-50/60'); ?> p-5" data-review-book-item>
                                        <input type="hidden" name="books[<?php echo e($flatIndex); ?>][scan_id]" value="<?php echo e($book['scan_id']); ?>" data-review-field="scan_id">

                                        <div class="mb-4 flex items-start justify-between gap-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full <?php echo e(($book['scan_status'] ?? 'success') === 'failed' ? 'bg-amber-100 text-amber-700' : 'bg-primary-100 text-primary-700'); ?> px-3 py-1 text-xs font-semibold uppercase tracking-wide">
                                                    <?php echo e(($book['scan_status'] ?? 'success') === 'failed' ? 'Perlu Review' : 'Siap Dicek'); ?>

                                                </span>
                                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-gray-600">Source: <?php echo e(strtoupper($book['source'] ?? 'AI')); ?></span>
                                                <?php if(!empty($book['source_url'])): ?>
                                                    <a href="<?php echo e($book['source_url']); ?>" target="_blank" rel="noopener noreferrer" class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-primary-700 underline decoration-transparent transition hover:decoration-primary-700">Lihat sumber</a>
                                                <?php endif; ?>
                                                <?php if(!empty($book['error'])): ?>
                                                    <span class="text-sm text-amber-700"><?php echo e($book['error']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="remove-review-book rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-red-600 transition hover:bg-red-50">Hapus</button>
                                        </div>

                                        <?php if(!empty($book['field_sources']) && is_array($book['field_sources'])): ?>
                                            <div class="mb-4 flex flex-wrap gap-2">
                                                <?php $__currentLoopData = $book['field_sources']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fieldKey => $fieldSource): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php if(!empty($fieldSource)): ?>
                                                        <span class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-600">
                                                            <?php echo e($fieldLabelMap[$fieldKey] ?? ucfirst(str_replace('_', ' ', $fieldKey))); ?>: <?php echo e($fieldSource); ?>

                                                        </span>
                                                    <?php endif; ?>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="grid gap-5 xl:grid-cols-[170px_1fr]">
                                            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                                                <?php if(!empty($book['cover_url'])): ?>
                                                    <img src="<?php echo e($book['cover_url']); ?>" alt="<?php echo e($book['title'] ?? 'Book cover'); ?>" class="h-60 w-full bg-white object-contain p-2">
                                                <?php else: ?>
                                                    <div class="flex h-60 items-center justify-center text-center text-sm font-semibold text-gray-500">Cover belum tersedia</div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="grid gap-4">
                                                <div class="grid gap-4 md:grid-cols-2">
                                                    <div>
                                                        <label class="form-label">Title</label>
                                                        <input type="text" name="books[<?php echo e($flatIndex); ?>][title]" value="<?php echo e($book['title'] ?? ''); ?>" class="form-input" data-review-field="title">
                                                    </div>
                                                    <div>
                                                        <label class="form-label">Author</label>
                                                        <input type="text" name="books[<?php echo e($flatIndex); ?>][author]" value="<?php echo e($book['author'] ?? ''); ?>" class="form-input" data-review-field="author">
                                                    </div>
                                                    <div>
                                                        <label class="form-label">ISBN</label>
                                                        <input type="text" name="books[<?php echo e($flatIndex); ?>][isbn]" value="<?php echo e($book['isbn'] ?? ''); ?>" class="form-input" data-review-field="isbn">
                                                    </div>
                                                    <div>
                                                        <label class="form-label">Category</label>
                                                        <input list="category-list" type="text" name="books[<?php echo e($flatIndex); ?>][category_name]" value="<?php echo e($book['category_name'] ?? $categoryName); ?>" class="form-input" data-review-field="category_name">
                                                    </div>
                                                    <div>
                                                        <label class="form-label">Rack</label>
                                                        <select name="books[<?php echo e($flatIndex); ?>][rack_id]" class="form-input" data-review-field="rack_id">
                                                            <option value="">Auto Assign</option>
                                                            <?php $__currentLoopData = $racks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rack): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <option value="<?php echo e($rack->id); ?>"><?php echo e($rack->name); ?></option>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="form-label">Cover URL</label>
                                                        <input type="text" name="books[<?php echo e($flatIndex); ?>][cover_url]" value="<?php echo e($book['cover_url'] ?? ''); ?>" class="form-input" data-review-field="cover_url">
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="form-label">Description</label>
                                                    <textarea name="books[<?php echo e($flatIndex); ?>][description]" class="form-input min-h-[140px]" data-review-field="description"><?php echo e($book['description'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php $flatIndex++; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </section>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    <div class="sticky bottom-6 z-20 flex justify-end">
                        <div class="rounded-2xl border border-primary-100 bg-white/95 px-4 py-4 shadow-xl backdrop-blur">
                            <button type="submit" id="review-submit-button" class="rounded-xl bg-primary-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-800 disabled:cursor-not-allowed disabled:bg-gray-400">Simpan Semua ke Library</button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="rounded-[1.5rem] border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                    <h3 class="text-2xl font-black tracking-tight text-gray-900">Belum ada hasil scan untuk direview</h3>
                    <p class="mt-3 text-sm leading-7 text-gray-500">Jalankan batch scan dulu. Setelah itu, semua buku akan otomatis dikelompokkan berdasarkan kategori di sini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div data-tab-panel="manual" class="<?php echo e($hasManualErrors ? '' : 'hidden'); ?>">
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
            <h2 class="section-title mb-4">Manual Book Entry</h2>
            <form method="POST" action="<?php echo e(route('books.import.manual')); ?>" class="space-y-4">
                <?php echo csrf_field(); ?>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="isbn-input" class="form-label">ISBN / Scan Input (optional)</label>
                        <div class="mb-2 flex gap-2">
                            <input id="isbn-input" name="isbn" value="<?php echo e(old('isbn')); ?>" type="text" class="form-input" placeholder="Scan or type ISBN">
                            <button id="isbn-scan-btn" type="button" class="inline-flex items-center gap-1 rounded-lg border border-border bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-200">Scan</button>
                            <button id="isbn-lookup-btn" type="button" class="inline-flex items-center gap-1 rounded-lg border border-border bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-primary-50 hover:text-primary-700">Fetch</button>
                        </div>
                        <div id="qr-reader" class="hidden overflow-hidden rounded-xl border border-gray-200" style="width: 100%; max-width: 400px;"></div>
                        <?php $__errorArgs = ['isbn'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <p id="isbn-lookup-status" class="mt-1 text-xs text-gray-500"></p>
                    </div>

                    <div class="md:col-span-2">
                        <label for="ai-image-input" class="form-label">AI Book Scan (optional)</label>
                        <div class="manual-scan-shell">
                            <div class="grid gap-4">
                                <div class="flex flex-col gap-3 xl:flex-row xl:items-end">
                                    <div class="min-w-0 flex-1">
                                        <input id="ai-image-input" type="file" class="form-input" accept=".jpg,.jpeg,.png,.webp,image/*" multiple>
                                        <p class="mt-2 text-xs text-gray-500">Upload 1 gambar untuk front cover, atau 2 gambar dengan urutan front dulu lalu back cover.</p>
                                    </div>
                                    <div class="w-full xl:w-auto">
                                        <select id="ai-scan-mode" class="form-input w-full min-w-[160px] xl:w-auto">
                                            <option value="full">Mode: Full</option>
                                            <option value="simple">Mode: Simple</option>
                                        </select>
                                    </div>
                                    <button id="ai-scan-btn" type="button" class="scan-action-button inline-flex items-center justify-center gap-2 rounded-xl border border-border bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-primary-50 hover:text-primary-700">
                                        <span class="scan-action-spinner hidden" id="manual-ai-scan-spinner"></span>
                                        <span id="manual-ai-scan-label">Scan with AI</span>
                                    </button>
                                </div>
                                <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-3">
                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                        <span class="rounded-full bg-white px-3 py-1 font-semibold text-gray-600" id="manual-ai-files-status">Belum ada gambar dipilih</span>
                                        <span class="rounded-full bg-white px-3 py-1 font-semibold text-gray-600" id="manual-ai-source-status">Siap untuk analisis AI</span>
                                    </div>
                                    <p id="ai-scan-status" class="mt-3 text-xs text-gray-500">Pilih gambar untuk mulai scan AI dan autofill data buku.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="title-input" class="form-label">Title</label>
                        <input id="title-input" name="title" value="<?php echo e(old('title')); ?>" type="text" class="form-input" required>
                    </div>
                    <div>
                        <label for="author-input" class="form-label">Author</label>
                        <input id="author-input" name="author" value="<?php echo e(old('author')); ?>" type="text" class="form-input" required>
                    </div>
                    <div>
                        <label for="category-name-input" class="form-label">Category</label>
                        <input list="category-list" id="category-name-input" name="category_name" value="<?php echo e(old('category_name')); ?>" type="text" class="form-input" required placeholder="Type or select category">
                        <datalist id="category-list">
                            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($category->name); ?>"></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </datalist>
                    </div>
                    <div>
                        <label for="rack-id-input" class="form-label">Rack (optional)</label>
                        <select id="rack-id-input" name="rack_id" class="form-input">
                            <option value="">Auto Assign</option>
                            <?php $__currentLoopData = $racks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rack): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($rack->id); ?>" <?php if((string) old('rack_id') === (string) $rack->id): echo 'selected'; endif; ?>><?php echo e($rack->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="cover-url-input" class="form-label">Cover URL (optional)</label>
                        <input id="cover-url-input" name="cover_url" value="<?php echo e(old('cover_url')); ?>" type="text" class="form-input" placeholder="https://... atau /storage/...">
                        <div id="cover-preview-wrap" class="mt-3 <?php echo e(old('cover_url') ? '' : 'hidden'); ?>">
                            <p id="cover-preview-caption" class="mb-1 text-xs font-medium text-gray-500">Cover preview</p>
                            <img id="cover-preview-img" src="<?php echo e(old('cover_url') ?? ''); ?>" alt="Cover preview" class="h-52 w-36 rounded-lg border border-gray-200 bg-white object-contain p-2 shadow-sm">
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label for="description-input" class="form-label">Description (optional)</label>
                        <textarea id="description-input" name="description" class="form-input h-24"><?php echo e(old('description')); ?></textarea>
                        <p id="description-source-note" class="mt-1 text-xs text-gray-500">Sumber deskripsi akan muncul di sini setelah ISBN lookup atau AI scan.</p>
                    </div>
                </div>

                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','variant' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'success']); ?>Save Book <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
            </form>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
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
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        (() => {
            const tabTriggers = document.querySelectorAll('[data-tab-trigger]');
            const tabPanels = document.querySelectorAll('[data-tab-panel]');
            const aiTabTriggers = document.querySelectorAll('[data-ai-tab-trigger]');
            const aiTabPanels = document.querySelectorAll('[data-ai-tab-panel]');
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
                });
            };

            const setAiTab = (name) => {
                aiTabPanels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.getAttribute('data-ai-tab-panel') !== name);
                });
                aiTabTriggers.forEach((trigger) => {
                    const isActive = trigger.getAttribute('data-ai-tab-trigger') === name;
                    activeClasses.forEach((cls) => trigger.classList.toggle(cls, isActive));
                    inactiveClasses.forEach((cls) => trigger.classList.toggle(cls, !isActive));
                });
            };

            tabTriggers.forEach((trigger) => {
                trigger.addEventListener('click', (event) => {
                    event.preventDefault();
                    setTopTab(trigger.getAttribute('data-tab-trigger'));
                });
            });

            aiTabTriggers.forEach((trigger) => {
                trigger.addEventListener('click', (event) => {
                    event.preventDefault();
                    setAiTab(trigger.getAttribute('data-ai-tab-trigger'));
                });
            });

            setTopTab(<?php echo json_encode($hasManualErrors ? 'manual' : 'ai', 15, 512) ?>);
            setAiTab(<?php echo json_encode($groupedDraftBooks->isNotEmpty() ? 'review' : 'scan', 15, 512) ?>);

            const batchList = document.getElementById('ai-batch-list');
            const batchTemplate = document.getElementById('ai-batch-item-template');
            const batchForm = document.getElementById('batch-scan-form');
            const batchScanStatus = document.getElementById('batch-scan-status');
            const batchScanStatusTitle = document.getElementById('batch-scan-status-title');
            const batchScanStatusSubtitle = document.getElementById('batch-scan-status-subtitle');
            const batchScanLiveCount = document.getElementById('batch-scan-live-count');
            const batchScanLiveTotal = document.getElementById('batch-scan-live-total');
            const batchReadyIndicator = document.getElementById('batch-ready-indicator');
            const batchScanSubmit = document.getElementById('batch-scan-submit');
            const batchScanSubmitSpinner = document.getElementById('batch-scan-submit-spinner');
            const batchScanSubmitLabel = document.getElementById('batch-scan-submit-label');
            const updateSlotPreview = (card) => {
                if (!card) return;
                const frontInput = card.querySelector('[data-field="front_image"]');
                const backInput = card.querySelector('[data-field="back_image"]');
                const preview = card.querySelector('[data-slot-front-preview]');
                const placeholder = card.querySelector('[data-slot-placeholder]');
                const frontStatus = card.querySelector('[data-front-status]');
                const backStatus = card.querySelector('[data-back-status]');
                const scanStatus = card.querySelector('[data-scan-status]');

                const hasFront = Boolean(frontInput?.files?.length);
                const hasBack = Boolean(backInput?.files?.length);

                if (frontStatus) {
                    frontStatus.textContent = hasFront ? `Front siap: ${frontInput.files[0].name}` : 'Front belum dipilih';
                    frontStatus.className = `rounded-full px-2.5 py-1 ${hasFront ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-500'}`;
                }
                if (backStatus) {
                    backStatus.textContent = hasBack ? `Back siap: ${backInput.files[0].name}` : 'Back opsional';
                    backStatus.className = `rounded-full px-2.5 py-1 ${hasBack ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500'}`;
                }
                if (scanStatus) {
                    scanStatus.textContent = hasFront ? 'Siap discan' : 'Belum discan';
                    scanStatus.className = `rounded-full px-2.5 py-1 ${hasFront ? 'bg-sky-100 text-sky-700' : 'bg-gray-100 text-gray-500'}`;
                }
                card.classList.toggle('is-scanning', hasFront || hasBack);

                if (preview && placeholder) {
                    if (hasFront) {
                        preview.src = URL.createObjectURL(frontInput.files[0]);
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
                const ready = cards.filter((card) => card.querySelector('[data-field="front_image"]')?.files?.length).length;
                if (batchReadyIndicator) {
                    batchReadyIndicator.textContent = `${ready} buku siap discan`;
                }
                if (batchScanLiveCount) batchScanLiveCount.textContent = String(ready);
                if (batchScanLiveTotal) batchScanLiveTotal.textContent = String(cards.length);
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

            batchForm?.addEventListener('submit', () => {
                const cards = [...batchList.querySelectorAll('[data-ai-book-item]')];
                const total = cards.length;
                const ready = cards.filter((card) => card.querySelector('[data-field="front_image"]')?.files?.length).length;

                if (batchScanStatus) batchScanStatus.classList.remove('hidden');
                if (batchScanStatusTitle) batchScanStatusTitle.textContent = 'Batch scan sedang dijalankan...';
                if (batchScanStatusSubtitle) batchScanStatusSubtitle.textContent = `${ready} dari ${total} buku masuk antrian scan.`;
                if (batchScanLiveCount) batchScanLiveCount.textContent = '0';
                if (batchScanLiveTotal) batchScanLiveTotal.textContent = String(ready);
                if (batchScanSubmit) {
                    batchScanSubmit.disabled = true;
                    batchScanSubmit.classList.add('is-loading');
                }
                if (batchScanSubmitSpinner) {
                    batchScanSubmitSpinner.classList.remove('hidden');
                }
                if (batchScanSubmitLabel) {
                    batchScanSubmitLabel.textContent = 'Scanning...';
                }

                let simulated = 0;
                const timer = setInterval(() => {
                    simulated = Math.min(simulated + 1, ready);
                    if (batchScanLiveCount) batchScanLiveCount.textContent = String(simulated);
                    if (batchScanStatusSubtitle) batchScanStatusSubtitle.textContent = `${simulated} buku selesai dibaca, ${Math.max(ready - simulated, 0)} masih diproses...`;
                    cards.forEach((card, index) => {
                        if (index < simulated) {
                            const scanStatus = card.querySelector('[data-scan-status]');
                            if (scanStatus) {
                                scanStatus.textContent = 'Sudah discan';
                                scanStatus.className = 'rounded-full bg-primary-100 px-2.5 py-1 text-primary-700';
                            }
                        }
                    });
                    if (simulated >= ready) {
                        clearInterval(timer);
                    }
                }, 500);
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
                list.addEventListener('click', (event) => {
                    const removeButton = event.target.closest('.remove-review-book');
                    if (!removeButton) return;
                    event.preventDefault();
                    removeButton.closest('[data-review-book-item]')?.remove();
                    reindexReviewCards();
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
            const csrfToken = '<?php echo e(csrf_token()); ?>';

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
                    const response = await fetch("<?php echo e(route('books.import.isbn-lookup')); ?>", {
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

                    const response = await fetch("<?php echo e(route('books.import.ai-scan')); ?>", {
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
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\renre\Smart_LMS\resources\views\books\import.blade.php ENDPATH**/ ?>