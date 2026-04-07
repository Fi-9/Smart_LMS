<?php $__env->startSection('content'); ?>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="page-title">Books</h1>
            <p class="page-subtitle">Master-detail view — klik buku di panel kiri untuk melihat detail.</p>
        </div>
        <a href="<?php echo e(route('books.import')); ?>" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-800 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-primary-700">
            <span>📥</span> Import
        </a>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-10"
         x-data="{
             selectedBookId: <?php echo e($selected_book?->id ?? 'null'); ?>,
             isLoading: false,
             async loadPanel(url, bookId) {
                 this.selectedBookId = bookId;
                 this.isLoading = true;
                 
                 const newUrl = new URL(window.location);
                 newUrl.searchParams.set('selected_book_id', bookId);
                 window.history.replaceState({}, '', newUrl);

                 try {
                     const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
                     if (!response.ok) throw new Error();
                     document.getElementById('detail-panel').innerHTML = await response.text();
                     
                     // Re-initialize any scripts within the newly loaded HTML
                     Array.from(document.getElementById('detail-panel').querySelectorAll('script')).forEach(oldScript => {
                        const newScript = document.createElement('script');
                        Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                     });
                 } catch (e) {
                     document.getElementById('detail-panel').innerHTML = '<div class=\'rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700\'>Failed to load book detail.</div>';
                 } finally {
                     this.isLoading = false;
                 }
             }
         }"
    >
        
        <section class="xl:col-span-4">
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
                <form method="GET" action="<?php echo e(route('books.index')); ?>" class="space-y-3">
                    <input name="search" value="<?php echo e($filters['search']); ?>" type="text" placeholder="🔍 Search title or author..." class="form-input">
                    <div class="grid grid-cols-3 gap-2">
                        <select name="category_id" class="form-input">
                            <option value="">All Categories</option>
                            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($category->id); ?>" <?php if((string) $filters['category_id'] === (string) $category->id): echo 'selected'; endif; ?>><?php echo e($category->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <select name="rack_id" class="form-input">
                            <option value="">All Racks</option>
                            <?php $__currentLoopData = $racks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rack): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($rack->id); ?>" <?php if((string) $filters['rack_id'] === (string) $rack->id): echo 'selected'; endif; ?>><?php echo e($rack->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <select name="status" class="form-input">
                            <option value="">All Status</option>
                            <option value="available" <?php if($filters['status'] === 'available'): echo 'selected'; endif; ?>>Available</option>
                            <option value="borrowed" <?php if($filters['status'] === 'borrowed'): echo 'selected'; endif; ?>>Borrowed</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?>Apply <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
                        <a href="<?php echo e(route('books.index')); ?>" class="inline-flex items-center rounded-lg border border-border bg-white px-4 py-2 text-sm text-gray-600 transition hover:bg-gray-50">Reset</a>
                    </div>
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

            <div class="mt-4 max-h-[calc(100vh-20rem)] space-y-2 overflow-y-auto pr-1">
                <?php $__empty_1 = true; $__currentLoopData = $books; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <button
                        type="button"
                        @click="loadPanel('<?php echo e(route('books.web.panel', $book)); ?>', <?php echo e($book->id); ?>)"
                        class="group w-full rounded-xl border bg-white p-3 text-left shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:shadow-md"
                        :class="selectedBookId === <?php echo e($book->id); ?> ? 'border-primary-400 ring-2 ring-primary-200' : 'border-border hover:border-primary-300'"
                    >
                        <div class="flex items-start gap-3">
                            <img
                                src="<?php echo e($book->cover_url ?: '/images/default-book-cover.svg'); ?>"
                                alt="<?php echo e($book->title); ?>"
                                class="h-16 w-12 rounded-lg border border-gray-200 object-cover"
                            >
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-gray-900"><?php echo e($book->title); ?></p>
                                <p class="truncate text-xs text-gray-500"><?php echo e($book->author); ?></p>
                                <div class="mt-1.5 flex items-center gap-1">
                                    <?php if($book->rack): ?>
                                        <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-600"><?php echo e($book->rack->name); ?> <?php echo e($book->position_code); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1">
                                    <?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['status' => $book->status->value]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($book->status->value)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $attributes = $__attributesOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $component = $__componentOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__componentOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
                                    <?php if(!$book->isAssigned()): ?>
                                        <?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['status' => 'unassigned']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => 'unassigned']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $attributes = $__attributesOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $component = $__componentOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__componentOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="flex shrink-0 flex-col gap-1 opacity-40 transition-opacity duration-200 group-hover:opacity-100">
                                <?php if($book->rack_id): ?>
                                    <a href="<?php echo e(route('racks.show', $book->rack_id)); ?>" title="Move to rack" class="rounded-md bg-gray-100 p-1.5 text-xs text-gray-600 transition hover:bg-primary-100 hover:text-primary-700" onclick="event.stopPropagation()">📍</a>
                                <?php endif; ?>
                                <a href="<?php echo e(route('qr.print', ['selected_ids' => [$book->id]])); ?>" target="_blank" title="Print QR" class="rounded-md bg-gray-100 p-1.5 text-xs text-gray-600 transition hover:bg-primary-100 hover:text-primary-700" onclick="event.stopPropagation()">🔳</a>
                                <a href="<?php echo e(route('books.web.show', $book->id)); ?>" title="Edit" class="rounded-md bg-gray-100 p-1.5 text-xs text-gray-600 transition hover:bg-primary-100 hover:text-primary-700" onclick="event.stopPropagation()">✏️</a>
                            </div>
                        </div>
                    </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
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
                        <div class="py-6 text-center">
                            <p class="text-3xl">📚</p>
                            <p class="mt-2 text-sm font-medium text-gray-700">Belum ada buku</p>
                            <p class="mt-1 text-xs text-gray-500">Upload CSV atau tambah manual untuk mulai.</p>
                            <a href="<?php echo e(route('books.import')); ?>" class="mt-3 inline-flex items-center gap-1 rounded-lg bg-primary-800 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                                <span>📥</span> Import Buku
                            </a>
                        </div>
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
            </div>

            <div class="mt-4">
                <?php echo e($books->appends(request()->query())->links()); ?>

            </div>
        </section>

        
        <section class="xl:col-span-6">
            <div id="detail-panel" class="sticky top-6 transition-opacity duration-150" :class="isLoading ? 'opacity-50' : 'opacity-100'">
                <?php if($selected_book): ?>
                    <?php echo $__env->make('books.partials.detail_panel', [
                        'book' => $selected_book,
                        'rack_mini_map' => $selected_book_rack_mini_map,
                        'compact_description' => true,
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php else: ?>
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
                        <div class="py-12 text-center">
                            <p class="text-4xl">👈</p>
                            <p class="mt-3 text-sm font-medium text-gray-700">Pilih buku dari panel kiri</p>
                            <p class="mt-1 text-xs text-gray-500">Detail buku akan tampil di sini.</p>
                        </div>
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
            </div>
        </section>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\renre\Smart_LMS\resources\views\books\index.blade.php ENDPATH**/ ?>