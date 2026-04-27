<?php $__env->startSection('content'); ?>
    <?php
        $totalSlots = $rack->rows * $rack->columns;
        $filledSlots = collect($grid)->where('occupied', true)->count();
        $fullSlots = collect($grid)->where('is_full', true)->count();
        $availableSlots = collect($grid)->where('is_full', false)->count();
        $gridSystemLabel = strtoupper($rack->rows . 'x' . $rack->columns . ' GRID SYSTEM');
        $barPalette = ['#E5E7EB', '#F59E80', '#F6DC7A', '#8EA996', '#5F7866'];
    ?>

    <div x-data="{ viewMode: 'grid' }">
        <section class="mb-6 rounded-[2rem] border border-primary-100 bg-gradient-to-br from-white via-slate-50 to-emerald-50/50 p-6 shadow-sm dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <div class="max-w-3xl">
                    <a href="<?php echo e($rack->room ? route('rooms.show', $rack->room) : route('racks.index')); ?>" class="inline-flex items-center gap-2 rounded-2xl border border-border bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary-200 hover:text-primary-700 dark:bg-slate-900">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m15 18-6-6 6-6"/>
                        </svg>
                        <?php echo e($rack->room ? 'Kembali ke ' . $rack->room->name : 'Kembali ke Peta'); ?>

                    </a>

                    <p class="mt-4 text-xs font-semibold uppercase tracking-[0.28em] text-gray-400">Library Map / <?php echo e($rack->name); ?></p>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-black tracking-tight text-gray-900">Struktur Internal Rack <?php echo e($rack->name); ?></h1>
                        <span class="rounded-2xl bg-emerald-100 px-3 py-1 text-sm font-black uppercase tracking-[0.2em] text-emerald-700"><?php echo e($gridSystemLabel); ?></span>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-gray-600">Klik pada slot untuk melihat detail buku, menambah penempatan baru, atau memindahkan buku antar posisi di rack ini.</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.24em] text-gray-400">Tersedia</p>
                        <p class="mt-2 text-2xl font-black text-gray-900"><?php echo e($availableSlots); ?></p>
                    </div>
                    <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.24em] text-gray-400">Terisi</p>
                        <p class="mt-2 text-2xl font-black text-gray-900"><?php echo e($filledSlots); ?></p>
                    </div>
                    <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.24em] text-gray-400">Kapasitas</p>
                        <p class="mt-2 text-sm font-black uppercase tracking-[0.18em] text-emerald-700"><?php echo e($rack->capacity_per_slot ?? 1); ?>/slot</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="inline-flex rounded-2xl border border-gray-200 bg-white p-1 shadow-sm dark:bg-slate-900">
                <button
                    type="button"
                    @click="viewMode = 'grid'"
                    :class="viewMode === 'grid' ? 'bg-primary-800 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50 dark:hover:bg-slate-800'"
                    class="rounded-2xl px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] transition-all"
                >
                    Rack Explorer
                </button>
                <button
                    type="button"
                    @click="viewMode = 'manual'"
                    :class="viewMode === 'manual' ? 'bg-primary-800 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50 dark:hover:bg-slate-800'"
                    class="rounded-2xl px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] transition-all"
                >
                    Manual Input
                </button>
            </div>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <input
                    id="grid-search"
                    type="text"
                    placeholder="Cari judul di slot rack..."
                    class="form-input min-w-[18rem]"
                    x-show="viewMode === 'grid'"
                >
                <div class="flex flex-wrap gap-2">
                    <button
                        id="auto-assign-btn"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-primary-800 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-700"
                    >
                        ⚡ Auto
                    </button>
                    <a
                        href="<?php echo e(route('qr.print', ['rack_id' => $rack->id, 'layout' => 'tj103'])); ?>"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                    >
                        🖨️ QR
                    </a>
                </div>
            </div>
        </div>

        
        <div x-show="viewMode === 'grid'" x-transition>
            <section class="mb-6 rounded-[2rem] border border-border bg-surface p-6 shadow-sm">
                <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Rack Explorer</h2>
                        <p class="mt-1 text-sm text-gray-500">Slot divisualkan seperti kompartemen rak. Klik slot kosong untuk menambah buku, klik slot penuh untuk melihat daftar isinya.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500">
                        <span class="inline-flex items-center gap-1.5"><span class="h-3.5 w-3.5 rounded-full bg-[#d9e2d7]"></span> Tersedia</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-3.5 w-3.5 rounded-full bg-[#f59e80]"></span> Dipinjam / terisi</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-3.5 w-3.5 rounded-full bg-[#8ea996]"></span> Fokus slot</span>
                    </div>
                </div>

                <?php if(!$has_books_in_rack): ?>
                    <div class="mb-5 rounded-[1.6rem] border border-dashed border-primary-300 bg-primary-50 px-5 py-8 text-center">
                        <p class="text-sm font-semibold text-primary-800">Belum ada buku di rack ini.</p>
                        <p class="mt-1 text-xs text-primary-700">Klik slot mana saja untuk mulai menempatkan buku atau gunakan tombol Auto Assign.</p>
                    </div>
                <?php endif; ?>
                
                <div id="column-category-row" class="hidden mb-3 grid gap-4 overflow-x-auto pb-1" style="grid-template-columns: repeat(<?php echo e($rack->columns); ?>, minmax(7rem, 1fr));">
                    <?php for($col = 1; $col <= $rack->columns; $col++): ?>
                        <?php $catId = $rack->column_categories[(string)$col] ?? null; ?>
                        <div class="relative" x-data="{ open: false }">
                            <button
                                type="button"
                                @click="open = !open"
                                class="w-full rounded-xl border border-dashed border-gray-300 bg-white px-3 py-2 text-center text-[0.7rem] font-semibold uppercase tracking-[0.18em] transition hover:border-primary-300 hover:bg-primary-50"
                                :class="open ? 'border-primary-400 bg-primary-50 text-primary-700' : 'text-gray-500'"
                                id="col-cat-btn-<?php echo e($col); ?>"
                            >
                                <span id="col-cat-label-<?php echo e($col); ?>"><?php echo e($catId ? ($categories->firstWhere('id', $catId)?->name ?? 'Kolom '.$col) : 'Kolom '.$col); ?></span>
                                <svg class="inline h-3 w-3 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div
                                x-show="open"
                                @click.away="open = false"
                                x-transition
                                class="absolute left-0 right-0 top-full z-20 mt-1 max-h-48 overflow-y-auto rounded-xl border border-border bg-white shadow-lg"
                                style="display: none;"
                            >
                                <button type="button" @click="setColumnCategory(<?php echo e($col); ?>, null); open = false" class="w-full px-3 py-2 text-left text-xs text-gray-500 hover:bg-gray-50 transition">— Tanpa Kategori</button>
                                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <button type="button" @click="setColumnCategory(<?php echo e($col); ?>, <?php echo e($cat->id); ?>); open = false" class="w-full px-3 py-2 text-left text-xs text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition <?php echo e($catId == $cat->id ? 'bg-primary-50 font-bold text-primary-700' : ''); ?>"><?php echo e($cat->name); ?></button>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <div id="rack-grid" class="grid gap-4 overflow-x-auto pb-1" style="grid-template-columns: repeat(<?php echo e($rack->columns); ?>, minmax(8rem, 1fr));">
                    <?php $__currentLoopData = $grid; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cell): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $canAssign = ! $cell['is_full'];
                            $cellTitles = implode(', ', array_column($cell['books'], 'title'));
                            $seed = abs(crc32($cell['code']));
                            $activeBars = max(1, min(5, $cell['count']));
                            $footerLabel = $cell['count'] === 0
                                ? 'Klik untuk tambah buku'
                                : ($canAssign ? $cell['count'] . ' buku tersimpan' : 'Detail slot');
                        ?>

                        <button
                            type="button"
                            title="<?php echo e($cell['count'] > 0 ? ($cellTitles ?: 'Detail slot') : 'Klik untuk assign buku'); ?>"
                            data-grid-cell
                            data-position-code="<?php echo e($cell['code']); ?>"
                            data-book-titles="<?php echo e(strtolower(implode(' ', array_column($cell['books'], 'title')))); ?>"
                            data-count="<?php echo e($cell['count']); ?>"
                            data-capacity="<?php echo e($cell['capacity']); ?>"
                            <?php if($canAssign): ?> data-available-target <?php endif; ?>
                            <?php if($cell['count'] > 0): ?> data-filled-cell <?php else: ?> data-empty-slot <?php endif; ?>
                            class="rack-slot-card <?php echo e($cell['count'] > 0 ? 'is-filled' : ''); ?>"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <span class="rack-slot-label">SLOT <?php echo e($cell['code']); ?></span>
                                <span class="rack-slot-meta"><?php echo e($cell['count']); ?>/<?php echo e($cell['capacity']); ?></span>
                            </div>

                            <div class="mt-3 flex h-16 items-end justify-center gap-1.5">
                                <?php for($barIndex = 0; $barIndex < 5; $barIndex++): ?>
                                    <?php
                                        $height = 28 + (($seed + ($barIndex * 19)) % 34);
                                        $barColor = $barPalette[($seed + $barIndex) % count($barPalette)];
                                        if ($cell['count'] === 0 || $barIndex >= $activeBars) {
                                            $barColor = '#E5E7EB';
                                        }
                                    ?>
                                    <span class="slot-book-bar" style="height: <?php echo e($height); ?>px; background-color: <?php echo e($barColor); ?>;"></span>
                                <?php endfor; ?>
                            </div>

                            <div class="mt-5 flex items-center justify-between gap-3">
                                <span class="rack-slot-caption"><?php echo e($footerLabel); ?></span>
                                <span class="text-[0.7rem] font-bold uppercase tracking-[0.2em] text-gray-400"><?php echo e($cell['count'] > 0 ? 'Management' : 'Available'); ?></span>
                            </div>
                        </button>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </section>

        </div> 

    
    <div x-show="viewMode === 'manual'" x-cloak x-transition>
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'mb-6 shadow-md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mb-6 shadow-md']); ?>
            <div class="mb-4">
                <h2 class="text-base font-bold text-gray-900">📝 Manual Book Entry — <?php echo e($rack->name); ?></h2>
                <p class="mt-1 text-sm text-gray-500">Tambah buku baru langsung ke rack ini. Buku akan otomatis ter-assign ke rak yang sedang dibuka.</p>
            </div>

            <form method="POST" action="<?php echo e(route('books.import.manual')); ?>" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="rack_id" value="<?php echo e($rack->id); ?>">

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="manual-isbn-input" class="form-label">ISBN / Scan Input (optional)</label>
                        <div class="mb-2 flex gap-2">
                            <input id="manual-isbn-input" name="isbn" type="text" class="form-input" placeholder="Scan or type ISBN">
                            <button id="manual-isbn-lookup-btn" type="button" class="inline-flex items-center gap-1 rounded-lg border border-border bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-primary-50 hover:text-primary-700">Fetch</button>
                        </div>
                        <p id="manual-isbn-lookup-status" class="mt-1 text-xs text-gray-500"></p>
                    </div>

                    <div>
                        <label for="manual-title-input" class="form-label">Title *</label>
                        <input id="manual-title-input" name="title" type="text" class="form-input" required>
                    </div>
                    <div>
                        <label for="manual-author-input" class="form-label">Author *</label>
                        <input id="manual-author-input" name="author" type="text" class="form-input" required>
                    </div>
                    <div>
                        <label for="manual-category-input" class="form-label">Category *</label>
                        <input list="manual-category-list" id="manual-category-input" name="category_name" type="text" class="form-input" required placeholder="Type or select category">
                        <datalist id="manual-category-list">
                            <?php $__currentLoopData = \App\Models\Category::orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($cat->name); ?>"></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </datalist>
                    </div>
                    <div>
                        <label class="form-label">Rack</label>
                        <input type="text" class="form-input bg-gray-50 cursor-not-allowed" value="<?php echo e($rack->name); ?>" disabled>
                    </div>
                    <div>
                        <label for="manual-cover-url-input" class="form-label">Cover URL (optional)</label>
                        <input id="manual-cover-url-input" name="cover_url" type="text" class="form-input" placeholder="https://... atau /storage/...">
                    </div>
                    <div>
                        <label for="manual-condition-input" class="form-label">Kondisi Buku (optional)</label>
                        <input id="manual-condition-input" name="condition_notes" type="text" class="form-input" placeholder="Baik / Rusak halaman 10 / dll.">
                    </div>
                    <div class="md:col-span-2">
                        <label for="manual-description-input" class="form-label">Description (optional)</label>
                        <textarea id="manual-description-input" name="description" class="form-input h-24"></textarea>
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
<?php $component->withAttributes(['type' => 'submit','variant' => 'success']); ?>Simpan Buku ke <?php echo e($rack->name); ?> <?php echo $__env->renderComponent(); ?>
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

    </div> 


    
    <div id="assign-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
        <div class="w-full max-w-5xl animate-slide-up rounded-2xl bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 id="assign-modal-title" class="text-lg font-bold text-gray-900">Slot - — 0 buku</h3>
                <span id="assign-modal-count" class="text-xs font-medium text-gray-500">0 buku</span>
                <button type="button" data-close-modal class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">✕</button>
            </div>

            <p id="slot-list-subtitle" class="mb-3 text-sm text-gray-600">Pilih buku untuk dimasukkan ke <span id="selected-position" class="rounded bg-primary-100 px-2 py-0.5 font-bold text-primary-700">-</span>.</p>

            <form id="assign-form" class="space-y-4">
                <input type="hidden" id="position-code-input" name="position_code">
                <input type="hidden" id="rack-id-input" name="rack_id" value="<?php echo e($rack->id); ?>">

                <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                    <div>
                    <label for="book-id-input" class="form-label">Dropdown Buku</label>
                    <select id="book-id-input" name="book_id" class="form-input" required>
                        <option value="">Pilih buku...</option>
                        <?php $__currentLoopData = $books; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option
                                value="<?php echo e($book->id); ?>"
                                data-unassigned="<?php echo e($book->isAssigned() ? '0' : '1'); ?>"
                                data-title="<?php echo e($book->title); ?>"
                            >
                                <?php echo e($book->title); ?>

                                <?php if(!$book->rack_id): ?>
                                    (Unassigned)
                                <?php else: ?>
                                    (Assigned <?php echo e($book->position_code); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <p class="mt-1 text-xs text-amber-600">Unassigned = buku belum ditempatkan.</p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" data-close-modal class="rounded-lg border border-border bg-white px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50">Cancel</button>
                        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','variant' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'success']); ?>Assign <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
                    </div>
                </div>
            </form>

            <div class="my-4 border-t border-gray-200"></div>

            <div id="slot-book-list-empty" class="rounded-xl border border-dashed border-gray-300 bg-gray-50 py-8 text-center">
                <p class="text-sm font-medium text-gray-500">Belum ada buku di slot ini.</p>
            </div>

            <div id="slot-management-zone" class="hidden">
                <div id="slot-book-list-table" class="rounded-xl border border-gray-200 bg-white p-2">
                    <div id="slot-book-list-body" class="space-y-2"></div>
                </div>
            </div>
        </div>
    </div>

    
    

    <div id="assign-toast" class="fixed bottom-6 right-6 z-50 hidden rounded-xl border border-primary-200 bg-primary-50 px-5 py-3.5 text-sm font-medium text-primary-800 shadow-lg">
        Book assigned.
    </div>

    <script id="rack-books-data" type="application/json">
        <?php echo json_encode($rack->books->map(fn($b) => ['id' => $b->id, 'title' => $b->title, 'author' => $b->author, 'status' => $b->status?->value ?? (string) $b->status, 'position_code' => $b->position_code])->values()->all()); ?>

    </script>

    <script>
        (() => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const rackId = '<?php echo e($rack->id); ?>';
            const assignUrl = '<?php echo e(route("books.assign")); ?>';
            const autoAssignUrl = '<?php echo e(route("books.auto-assign")); ?>';

            const modal = document.getElementById('assign-modal');
            const assignModalTitle = document.getElementById('assign-modal-title');
            const assignModalCount = document.getElementById('assign-modal-count');
            const selectedPositionLabel = document.getElementById('selected-position');
            const positionCodeInput = document.getElementById('position-code-input');
            const rackIdInput = document.getElementById('rack-id-input');
            const assignForm = document.getElementById('assign-form');
            const gridSearchInput = document.getElementById('grid-search');
            const bookSelect = document.getElementById('book-id-input');
            const autoAssignButton = document.getElementById('auto-assign-btn');
            const toast = document.getElementById('assign-toast');
            const closeButtons = document.querySelectorAll('[data-close-modal]');
            const rackGrid = document.getElementById('rack-grid');
            const bookDetailBaseUrl = '<?php echo e(url('/books')); ?>';
            const bookManageBaseUrl = '<?php echo e(route('books.index')); ?>';
            const qrPrintBaseUrl = '<?php echo e(route('qr.print')); ?>';
            const slotListSubtitle = document.getElementById('slot-list-subtitle');
            const slotBookListEmpty = document.getElementById('slot-book-list-empty');
            const slotManagementZone = document.getElementById('slot-management-zone');
            const slotBookListBody = document.getElementById('slot-book-list-body');

            const baseCellClass = 'rack-slot-card';
            const filledCellClass = 'rack-slot-card is-filled';
            const selectedCellClass = 'rack-slot-card is-selected';

            let activePosition = null;
            let selectedSlotButton = null;
            
            let allRackBooks = JSON.parse(document.getElementById('rack-books-data').textContent);

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const getGridButtons = () => rackGrid ? rackGrid.querySelectorAll('[data-grid-cell]') : [];

            const openSlotOverlay = (button) => {
                const positionCode = button.dataset.positionCode;
                const booksInCell = allRackBooks.filter((b) => b.position_code === positionCode);

                if (selectedSlotButton && selectedSlotButton !== button) {
                    selectedSlotButton.classList.remove('is-active');
                }
                button.classList.add('is-active');
                selectedSlotButton = button;

                activePosition = positionCode;
                assignModalTitle.textContent = 'Slot ' + positionCode + ' — ' + booksInCell.length + ' buku';
                assignModalCount.textContent = booksInCell.length + ' buku';
                slotListSubtitle.textContent = booksInCell.length > 0
                    ? 'Klik judul buku untuk melihat detail lengkap.'
                    : 'Pilih buku dari dropdown untuk menempatkannya ke slot ini.';
                selectedPositionLabel.textContent = positionCode;
                positionCodeInput.value = positionCode;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                bookSelect.value = '';

                if (booksInCell.length === 0) {
                    slotBookListEmpty.classList.remove('hidden');
                    slotBookListEmpty.querySelector('p').textContent = 'Belum ada buku di slot ' + positionCode + '.';
                    slotManagementZone.classList.add('hidden');
                } else {
                    slotBookListEmpty.classList.add('hidden');
                    slotManagementZone.classList.remove('hidden');
                }

                slotBookListBody.innerHTML = '';
                booksInCell.forEach((b) => {
                    const row = document.createElement('div');
                    const status = b.status || 'available';
                    const manageUrl = bookManageBaseUrl + '?selected_book_id=' + encodeURIComponent(b.id);
                    const detailUrl = bookDetailBaseUrl + '/' + encodeURIComponent(b.id);
                    const printUrl = qrPrintBaseUrl + '?selected_ids%5B0%5D=' + encodeURIComponent(b.id);

                    row.className = 'slot-book-row';
                    row.innerHTML = `
                        <div class="min-w-0 flex items-center gap-3">
                            <span class="slot-book-position">${escapeHtml(positionCode)}</span>
                            <div class="min-w-0">
                                <a href="${detailUrl}" class="block truncate text-sm font-bold text-gray-900 transition hover:text-primary-700">${escapeHtml(b.title)}</a>
                                <p class="truncate text-xs text-gray-500">${escapeHtml(b.author || 'Penulis belum diisi')}</p>
                            </div>
                        </div>
                        <div class="slot-book-actions">
                            <span class="slot-book-status">${escapeHtml(status)}</span>
                            <a href="${manageUrl}" title="Edit / CRUD" class="slot-action-btn">✏️</a>
                            <a href="${detailUrl}" title="Lihat detail" class="slot-action-btn">📄</a>
                            <a href="${printUrl}" target="_blank" title="Print QR" class="slot-action-btn">🖨️</a>
                        </div>
                    `;
                    slotBookListBody.appendChild(row);
                });
            };

            const closeModal = () => {
                clearSelectedCell();
                activePosition = null;
                assignModalTitle.textContent = 'Slot - — 0 buku';
                assignModalCount.textContent = '0 buku';
                slotListSubtitle.textContent = 'Pilih buku untuk dimasukkan ke slot yang aktif.';
                selectedPositionLabel.textContent = '-';
                positionCodeInput.value = '';
                slotBookListBody.innerHTML = '';
                slotBookListEmpty.classList.remove('hidden');
                slotBookListEmpty.querySelector('p').textContent = 'Belum ada buku di slot ini.';
                slotManagementZone.classList.add('hidden');
                assignForm.reset();
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            // Column Category Assignment (AJAX)
            window.setColumnCategory = async (column, categoryId) => {
                const url = '<?php echo e(route("racks.set-column-category", $rack)); ?>';
                const res = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ column, category_id: categoryId }),
                });
                const data = await res.json();
                if (data.success) {
                    const label = document.getElementById('col-cat-label-' + column);
                    if (label) label.textContent = data.category_name || 'Kolom ' + column;
                    showToast(data.category_name ? 'Kolom ' + column + ' → ' + data.category_name : 'Kategori kolom ' + column + ' dihapus', false);
                }
            };

            const hashPosition = (positionCode) => {
                return positionCode.split('').reduce((total, char, index) => total + (char.charCodeAt(0) * (index + 3)), 0);
            };

            const buildBarsMarkup = (positionCode, count) => {
                const palette = ['#E5E7EB', '#F59E80', '#F6DC7A', '#8EA996', '#5F7866'];
                const seed = hashPosition(positionCode);
                const activeBars = Math.max(1, Math.min(5, count));

                return Array.from({ length: 5 }, (_, index) => {
                    const height = 28 + ((seed + (index * 19)) % 34);
                    const isMuted = count === 0 || index >= activeBars;
                    const color = isMuted ? '#E5E7EB' : palette[(seed + index) % palette.length];

                    return '<span class="slot-book-bar" style="height:' + height + 'px; background-color:' + color + ';"></span>';
                }).join('');
            };

            const buildSlotMarkup = (positionCode, count, capacity, canAssign) => {
                const footerLabel = count === 0
                    ? 'Klik untuk tambah buku'
                    : (canAssign ? count + ' buku tersimpan' : 'Detail slot');
                const sideLabel = count > 0 ? 'Management' : 'Available';

                return ''
                    + '<div class="flex items-start justify-between gap-3">'
                    + '<span class="rack-slot-label">SLOT ' + positionCode + '</span>'
                    + '<span class="rack-slot-meta">' + count + '/' + capacity + '</span>'
                    + '</div>'
                    + '<div class="mt-3 flex h-16 items-end justify-center gap-1.5">'
                    + buildBarsMarkup(positionCode, count)
                    + '</div>'
                    + '<div class="mt-5 flex items-center justify-between gap-3">'
                    + '<span class="rack-slot-caption">' + footerLabel + '</span>'
                    + '<span class="text-[0.7rem] font-bold uppercase tracking-[0.2em] text-gray-400">' + sideLabel + '</span>'
                    + '</div>';
            };

            const clearSelectedCell = () => {
                if (!selectedSlotButton) return;
                selectedSlotButton.classList.remove('is-active');
                renderCell(selectedSlotButton);
                selectedSlotButton = null;
            };

            const markSelectedCell = (positionCode) => {
                clearSelectedCell();
                const selectedCell = rackGrid.querySelector('[data-position-code="' + positionCode + '"]');
                if (selectedCell) {
                    selectedCell.className = selectedCellClass;
                    selectedSlotButton = selectedCell;
                }
            };

            const showToast = (message, isError) => {
                toast.textContent = message;
                toast.className = isError
                    ? 'fixed bottom-6 right-6 z-50 rounded-xl border border-red-200 bg-red-50 px-5 py-3.5 text-sm font-medium text-red-800 shadow-lg'
                    : 'fixed bottom-6 right-6 z-50 rounded-xl border border-primary-200 bg-primary-50 px-5 py-3.5 text-sm font-medium text-primary-800 shadow-lg';
                toast.classList.remove('hidden');
                window.setTimeout(() => toast.classList.add('hidden'), 3000);
            };

            const bindEvents = () => {
                getGridButtons().forEach((btn) => {
                    btn.onclick = () => openSlotOverlay(btn);
                });
            };

            const renderCell = (target) => {
                if (!target) return;

                const positionCode = target.dataset.positionCode;
                const capacity = Number(target.dataset.capacity || 1);
                const booksInCell = allRackBooks.filter(b => b.position_code === positionCode);
                const count = booksInCell.length;
                const canAssign = count < capacity;

                target.dataset.count = String(count);
                target.setAttribute('data-book-titles', booksInCell.map(b => b.title.toLowerCase()).join(' '));

                if (canAssign) {
                    target.setAttribute('data-available-target', '');
                } else {
                    target.removeAttribute('data-available-target');
                }

                if (count > 0) {
                    target.removeAttribute('data-empty-slot');
                    target.setAttribute('data-filled-cell', '');
                    target.className = filledCellClass;
                    target.title = booksInCell.map(b => b.title).join(', ') || 'Detail slot';
                } else {
                    target.removeAttribute('data-filled-cell');
                    target.setAttribute('data-empty-slot', '');
                    target.className = baseCellClass;
                    target.title = 'Klik untuk assign buku';
                }

                target.onclick = () => openSlotOverlay(target);
                target.innerHTML = buildSlotMarkup(positionCode, count, capacity, canAssign);
            };

            const markCellAssigned = (positionCode, bookId, title) => {
                const target = rackGrid.querySelector('[data-position-code="' + positionCode + '"]');
                if (!target) return;
                
                const existing = allRackBooks.find(b => b.id == bookId);
                if (existing) existing.position_code = positionCode;
                else allRackBooks.push({id: bookId, title: title, position_code: positionCode});
                
                renderCell(target);
            };

            const refreshCell = (positionCode) => {
                const target = rackGrid.querySelector('[data-position-code="' + positionCode + '"]');
                if (!target) return;
                renderCell(target);
            };

            const highlightGrid = () => {
                const keyword = gridSearchInput.value.trim().toLowerCase();
                let firstMatch = null;
                rackGrid.querySelectorAll('[data-grid-cell]').forEach(cell => {
                    cell.classList.remove('is-search-match');
                    const title = cell.dataset.bookTitles || '';
                    if (keyword !== '' && title.includes(keyword)) {
                        cell.classList.add('is-search-match');
                        if (!firstMatch) firstMatch = cell;
                    }
                });
                if (firstMatch) firstMatch.scrollIntoView({ behavior: 'smooth', block: 'center' });
            };

            const postAssign = async (bookId, positionCode) => {
                const response = await fetch(assignUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        book_id: bookId,
                        rack_id: rackIdInput.value || rackId,
                        position_code: positionCode,
                    }),
                });
                if (!response.ok) throw new Error('Assign failed');
                return response.json();
            };

            closeButtons.forEach(btn => btn.onclick = closeModal);
            gridSearchInput.oninput = highlightGrid;

            bindEvents();

            assignForm.onsubmit = async (e) => {
                e.preventDefault();
                const bookId = bookSelect.value;
                if (!bookId || !activePosition) return;
                try {
                    const res = await postAssign(bookId, activePosition);
                    const opt = bookSelect.selectedOptions[0];
                    const title = opt ? opt.dataset.title : res.book.title;
                    markCellAssigned(activePosition, res.book.id, title);
                    if (opt) opt.remove();
                    openSlotOverlay(rackGrid.querySelector('[data-position-code="' + activePosition + '"]'));
                    highlightGrid();
                    showToast('✅ Book assigned to ' + res.book.position_code);
                } catch (err) {
                    showToast('Assign failed.', true);
                }
            };

            autoAssignButton.onclick = async () => {
                autoAssignButton.disabled = true;
                autoAssignButton.innerHTML = '⏳ Assigning...';
                try {
                    const res = await fetch(autoAssignUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ limit: 50 }),
                    });
                    const data = await res.json();
                    if (data.assigned_count > 0) {
                        showToast('✅ ' + data.assigned_count + ' buku ditempatkan.');
                        window.setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showToast('ℹ️ Tidak ada buku unassigned.');
                        autoAssignButton.disabled = false;
                        autoAssignButton.innerHTML = '⚡ Auto Assign';
                    }
                } catch (err) {
                    showToast('Auto assign failed.', true);
                    autoAssignButton.disabled = false;
                    autoAssignButton.innerHTML = '⚡ Auto Assign';
                }
            };

            window.onclick = (e) => {
                if (e.target === modal) closeModal();
            };


            // Manual Input — ISBN Fetch
            const manualIsbnInput = document.getElementById('manual-isbn-input');
            const manualIsbnLookupBtn = document.getElementById('manual-isbn-lookup-btn');
            const manualIsbnStatus = document.getElementById('manual-isbn-lookup-status');
            const isbnLookupUrl = '<?php echo e(route("books.import.isbn-lookup")); ?>';

            if (manualIsbnLookupBtn && manualIsbnInput) {
                const doIsbnFetch = async () => {
                    const isbn = manualIsbnInput.value.trim();
                    if (!isbn) return;
                    manualIsbnLookupBtn.disabled = true;
                    manualIsbnLookupBtn.textContent = '⏳...';
                    manualIsbnStatus.textContent = 'Mencari data ISBN...';

                    try {
                        const res = await fetch(isbnLookupUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                            body: JSON.stringify({ isbn }),
                        });
                        const data = await res.json();
                        if (res.ok && data.title) {
                            const set = (id, val) => { const el = document.getElementById(id); if (el && val) el.value = val; };
                            set('manual-title-input', data.title);
                            set('manual-author-input', data.author);
                            set('manual-category-input', data.category);
                            set('manual-cover-url-input', data.cover_url);
                            set('manual-description-input', data.description);
                            manualIsbnStatus.textContent = '✅ Data ditemukan! Form telah diisi otomatis.';
                            manualIsbnStatus.className = 'mt-1 text-xs text-emerald-600 font-semibold';
                        } else {
                            manualIsbnStatus.textContent = '❌ ' + (data.message || 'ISBN tidak ditemukan.');
                            manualIsbnStatus.className = 'mt-1 text-xs text-red-600';
                        }
                    } catch (err) {
                        manualIsbnStatus.textContent = '❌ Gagal menghubungi server.';
                        manualIsbnStatus.className = 'mt-1 text-xs text-red-600';
                    }
                    manualIsbnLookupBtn.disabled = false;
                    manualIsbnLookupBtn.textContent = 'Fetch';
                };

                manualIsbnLookupBtn.onclick = doIsbnFetch;
                manualIsbnInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); doIsbnFetch(); } });
            }
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /mnt/data/Smart_LMS/resources/views/racks/show.blade.php ENDPATH**/ ?>