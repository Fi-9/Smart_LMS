<?php $__env->startSection('content'); ?>
    
    <section class="mb-6 rounded-2xl border border-primary-100 bg-gradient-to-br from-white via-slate-50 to-emerald-50/70 p-6 shadow-sm">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-primary-700">Library Map</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight text-gray-900">Peta Digital Perpustakaan</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-gray-600">Kelola ruangan, rak, dan slot buku secara visual. Klik ruangan untuk masuk dan melihat rak di dalamnya.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.24em] text-gray-400">Ruangan</p>
                    <p class="mt-2 text-2xl font-black text-gray-900"><?php echo e($stats['rooms']); ?></p>
                </div>
                <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.24em] text-gray-400">Rak</p>
                    <p class="mt-2 text-2xl font-black text-gray-900"><?php echo e($stats['racks']); ?></p>
                </div>
                <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.24em] text-gray-400">Buku Terpetakan</p>
                    <p class="mt-2 text-2xl font-black text-emerald-700"><?php echo e($stats['books_mapped']); ?></p>
                </div>
            </div>
        </div>
    </section>

    
    <section class="mb-6">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-gray-900">Daftar Ruangan</h2>
            <button type="button" x-data @click="$dispatch('open-modal', 'add-room-modal')" class="rounded-xl bg-primary-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-600">+ Tambah Ruangan</button>
        </div>

        <?php if($rooms->isEmpty() && $unassigned_racks->isEmpty()): ?>
            <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'py-12 text-center']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'py-12 text-center']); ?>
                <p class="text-sm font-semibold text-gray-700">Belum ada ruangan atau rak.</p>
                <p class="mt-1 text-xs text-gray-500">Buat ruangan dulu, lalu tambahkan rak ke dalamnya.</p>
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
        <?php endif; ?>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <?php $__currentLoopData = $rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $accent = $room->accent_classes; ?>
                <article class="group relative overflow-hidden rounded-2xl border border-border bg-surface shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                    
                    <button
                        type="button"
                        x-data
                        @click="$dispatch('open-modal', 'edit-room-<?php echo e($room->id); ?>')"
                        class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-xl border border-border bg-white/90 text-gray-500 opacity-0 shadow-sm backdrop-blur-sm transition group-hover:opacity-100 hover:border-primary-200 hover:text-primary-700"
                        title="Edit Ruangan"
                    >
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                    </button>

                    
                    <div class="p-5">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br <?php echo e($accent['soft']); ?> shadow-sm">
                                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-gray-400"><?php echo e($room->code); ?></p>
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[0.68rem] font-semibold uppercase tracking-[0.18em] <?php echo e($accent['badge']); ?>"><?php echo e(ucfirst($room->status)); ?></span>
                                </div>
                                <h3 class="mt-1 text-xl font-black tracking-tight text-gray-900"><?php echo e($room->name); ?></h3>
                            </div>
                        </div>

                        <?php if($room->description): ?>
                            <p class="mt-3 text-sm leading-6 text-gray-600 line-clamp-2"><?php echo e($room->description); ?></p>
                        <?php endif; ?>

                        
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-gray-50 px-3 py-2.5">
                                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-gray-400">Rak</p>
                                <p class="mt-1 text-lg font-black text-gray-900"><?php echo e($room->racks_count); ?></p>
                            </div>
                            <div class="rounded-xl bg-gray-50 px-3 py-2.5">
                                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-gray-400">Buku</p>
                                <p class="mt-1 text-lg font-black text-gray-900"><?php echo e($room->books_count); ?></p>
                            </div>
                        </div>
                    </div>

                    
                    <a href="<?php echo e(route('rooms.show', $room)); ?>" class="flex items-center justify-center gap-2 border-t border-border bg-gradient-to-r from-gray-50 to-white px-5 py-3.5 text-sm font-semibold text-primary-700 transition hover:from-primary-50 hover:to-emerald-50">
                        Masuk ke Ruangan
                        <svg viewBox="0 0 24 24" class="h-4 w-4 transition group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </article>

                
                <div x-data="{ show: false }" x-show="show" x-on:open-modal.window="if ($event.detail === 'edit-room-<?php echo e($room->id); ?>') show = true" x-on:keydown.escape.window="show = false" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm" @click="show = false"></div>
                        <div x-show="show" x-transition class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                            <form action="<?php echo e(route('rooms.update', $room)); ?>" method="POST">
                                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                                <h3 class="text-lg font-bold text-gray-900 mb-5">Edit Ruangan — <?php echo e($room->code); ?></h3>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div><label class="form-label">Nama *</label><input type="text" name="name" value="<?php echo e($room->name); ?>" class="form-input" required></div>
                                        <div><label class="form-label">Kode *</label><input type="text" name="code" value="<?php echo e($room->code); ?>" class="form-input" required></div>
                                    </div>
                                    <div><label class="form-label">Deskripsi</label><textarea name="description" class="form-input h-20"><?php echo e($room->description); ?></textarea></div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="form-label">Status *</label>
                                            <select name="status" class="form-input" required>
                                                <option value="active" <?php if($room->status === 'active'): echo 'selected'; endif; ?>>Aktif</option>
                                                <option value="preview" <?php if($room->status === 'preview'): echo 'selected'; endif; ?>>Preview</option>
                                                <option value="inactive" <?php if($room->status === 'inactive'): echo 'selected'; endif; ?>>Nonaktif</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">Warna Aksen *</label>
                                            <select name="accent" class="form-input" required>
                                                <option value="emerald" <?php if($room->accent === 'emerald'): echo 'selected'; endif; ?>>🟢 Emerald</option>
                                                <option value="sky" <?php if($room->accent === 'sky'): echo 'selected'; endif; ?>>🔵 Sky</option>
                                                <option value="amber" <?php if($room->accent === 'amber'): echo 'selected'; endif; ?>>🟡 Amber</option>
                                                <option value="rose" <?php if($room->accent === 'rose'): echo 'selected'; endif; ?>>🔴 Rose</option>
                                                <option value="violet" <?php if($room->accent === 'violet'): echo 'selected'; endif; ?>>🟣 Violet</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-6 flex items-center justify-between">
                                    <button type="button" @click="if(confirm('Yakin hapus ruangan ini?')) { $el.closest('form').action='<?php echo e(route('rooms.destroy', $room)); ?>'; $el.closest('form').querySelector('[name=_method]').value='DELETE'; $el.closest('form').submit(); }" class="text-sm font-medium text-red-600 hover:text-red-700 transition">Hapus Ruangan</button>
                                    <div class="flex gap-3">
                                        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'button','variant' => 'secondary','@click' => 'show = false']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','variant' => 'secondary','@click' => 'show = false']); ?>Batal <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
                                        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','variant' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'success']); ?>Simpan <?php echo $__env->renderComponent(); ?>
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
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <?php if($unassigned_racks->isNotEmpty()): ?>
            <div class="mt-6 rounded-2xl border border-dashed border-amber-300 bg-amber-50/30 p-5 shadow-sm">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Rak Belum Ditugaskan</h3>
                        <p class="text-xs text-gray-600"><?php echo e($unassigned_racks->count()); ?> rak belum masuk ke ruangan manapun.</p>
                    </div>
                </div>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <?php $__currentLoopData = $unassigned_racks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rack): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="rounded-xl border border-dashed border-amber-300 bg-amber-50 p-4 shadow-sm">
                            <h4 class="text-base font-bold text-gray-900"><?php echo e($rack->name); ?></h4>
                            <p class="mt-1 text-xs text-gray-500"><?php echo e($rack->rows); ?>×<?php echo e($rack->columns); ?> grid · <?php echo e($rack->books_count); ?> buku</p>
                            <div class="mt-2 flex items-center justify-between text-xs">
                                <span class="font-semibold text-amber-600">Perlu dimasukkan ke Ruangan</span>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    
    <div x-data="{ show: false }" x-show="show" x-on:open-modal.window="if ($event.detail === 'add-room-modal') show = true" x-on:keydown.escape.window="show = false" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm" @click="show = false"></div>
            <div x-show="show" x-transition class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <form action="<?php echo e(route('rooms.store')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <h3 class="text-lg font-bold text-gray-900 mb-5">Tambah Ruangan Baru</h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="form-label">Nama *</label><input type="text" name="name" class="form-input" required placeholder="Ruang Referensi"></div>
                            <div><label class="form-label">Kode *</label><input type="text" name="code" class="form-input" required placeholder="RM-01"></div>
                        </div>
                        <div><label class="form-label">Deskripsi</label><textarea name="description" class="form-input h-20" placeholder="Deskripsi singkat ruangan..."></textarea></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Status *</label>
                                <select name="status" class="form-input" required>
                                    <option value="active">Aktif</option>
                                    <option value="preview">Preview</option>
                                    <option value="inactive">Nonaktif</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Warna Aksen *</label>
                                <select name="accent" class="form-input" required>
                                    <option value="emerald">🟢 Emerald</option>
                                    <option value="sky">🔵 Sky</option>
                                    <option value="amber">🟡 Amber</option>
                                    <option value="rose">🔴 Rose</option>
                                    <option value="violet">🟣 Violet</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'button','variant' => 'secondary','@click' => 'show = false']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','variant' => 'secondary','@click' => 'show = false']); ?>Batal <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','variant' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'success']); ?>Simpan Ruangan <?php echo $__env->renderComponent(); ?>
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
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\renre\Downloads\Smart_LMS\resources\views/racks/index.blade.php ENDPATH**/ ?>