<?php $__env->startSection('content'); ?>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="page-title">Rack <?php echo e($rack->name); ?></h1>
            <p class="page-subtitle">Cinema-style 2D grid — klik slot kosong untuk assign, klik buku untuk detail.</p>
        </div>
        <div class="flex gap-2">
            <button
                type="button"
                onclick="document.getElementById('edit-rack-modal').classList.remove('hidden'); document.getElementById('edit-rack-modal').classList.add('flex');"
                class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
            >
                ✏️ Edit Rack
            </button>
            <button
                id="auto-assign-btn"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-800 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-primary-700"
            >
                ⚡ Auto Assign
            </button>
        </div>
    </div>

    <div class="mb-4">
        <input
            id="grid-search"
            type="text"
            placeholder="🔍 Cari buku di grid…"
            class="form-input max-w-md"
        >
    </div>

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
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base font-bold text-gray-900">Visual Rack Grid</h2>
            <div class="flex items-center gap-4 text-xs text-gray-500">
                <span class="inline-flex items-center gap-1.5"><span class="h-3.5 w-3.5 rounded bg-gray-200 border border-gray-300"></span> Empty</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-3.5 w-3.5 rounded bg-primary-400 border border-primary-500"></span> Filled</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-3.5 w-3.5 rounded bg-primary-800 border border-primary-900"></span> Selected</span>
            </div>
        </div>

        <?php if(!$has_books_in_rack): ?>
            <div class="mb-4 rounded-xl border border-dashed border-primary-300 bg-primary-50 px-4 py-6 text-center">
                <p class="text-3xl">🗄️</p>
                <p class="mt-2 text-sm font-medium text-primary-800">Belum ada buku di rack ini</p>
                <p class="mt-1 text-xs text-primary-600">Klik slot kosong untuk mulai assign, atau gunakan tombol Auto Assign.</p>
            </div>
        <?php endif; ?>

        <div class="inline-block rounded-xl border border-gray-200 p-4 shadow-sm bg-white overflow-x-auto max-w-full">
            
            <div class="mb-2 flex items-center gap-3">
                <div class="w-8"></div>
                <div class="grid gap-2 flex-1 min-w-max" <?php echo 'style="grid-template-columns: repeat(' . $rack->columns . ', minmax(0, 1fr));"'; ?>>
                    <?php for($col = 1; $col <= $rack->columns; $col++): ?>
                        <div class="text-center">
                            <div class="text-[10px] font-bold text-gray-400"><?php echo e($col); ?></div>
                            <?php if($rack->column_category): ?>
                                <div class="text-[8px] text-gray-300 uppercase tracking-tighter"><?php echo e($rack->column_category); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div id="rack-grid" class="space-y-2">
                <?php $__currentLoopData = $grid_matrix; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="flex items-center gap-3">
                        <div class="w-8 text-center text-sm font-bold text-gray-500"><?php echo e($row['label']); ?></div>
                        <div class="grid gap-2 flex-1 min-w-max" <?php echo 'style="grid-template-columns: repeat(' . $rack->columns . ', minmax(60px, 1fr));"'; ?>>
                        <?php $__currentLoopData = $row['cells']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cell): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if($cell['occupied']): ?>
                                <button
                                    type="button"
                                    title="<?php echo e(implode(', ', array_column($cell['books'], 'title'))); ?>"
                                    data-grid-cell
                                    data-filled-cell
                                    data-position-code="<?php echo e($cell['code']); ?>"
                                    data-book-titles="<?php echo e(strtolower(implode(' ', array_column($cell['books'], 'title')))); ?>"
                                    class="rounded-lg border border-primary-300 bg-primary-100 p-2 text-center text-xs text-primary-800 transition-all duration-200 hover:-translate-y-0.5 hover:border-primary-500 hover:bg-primary-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-300 flex flex-col items-center justify-start gap-1 h-full min-h-[5rem]"
                                >
                                    <div class="font-bold border-b border-primary-200 w-full pb-0.5 mb-0.5"><?php echo e($cell['code']); ?></div>
                                    <div class="flex flex-col gap-0.5 w-full max-h-16 overflow-y-auto no-scrollbar">
                                        <?php $__currentLoopData = $cell['books']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="truncate text-[9px] bg-white/50 rounded px-1 py-0.5 w-full"><?php echo e($b['title']); ?></div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </button>
                            <?php else: ?>
                                <button
                                    type="button"
                                    title="Klik untuk assign buku"
                                    data-slot-button
                                    data-grid-cell
                                    data-position-code="<?php echo e($cell['code']); ?>"
                                    data-is-partially-filled="<?php echo e($cell['count'] > 0 ? '1' : '0'); ?>"
                                    class="rounded-lg border border-gray-200 bg-gray-50 p-2 text-center text-xs text-gray-400 transition-all duration-200 hover:-translate-y-0.5 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-200 flex flex-col items-center justify-start gap-1 h-full min-h-[5rem]"
                                >
                                    <div class="font-bold border-b border-gray-200 w-full pb-0.5 mb-0.5"><?php echo e($cell['code']); ?></div>
                                    <div class="text-[10px]">
                                        <?php echo e($cell['count'] > 0 ? $cell['count'] . '/' . $cell['capacity'] : 'Empty'); ?>

                                    </div>
                                    <?php if($cell['count'] > 0): ?>
                                        <div class="mt-1 flex flex-col gap-0.5 w-full max-h-16 overflow-y-auto no-scrollbar pointer-events-none">
                                            <?php $__currentLoopData = $cell['books']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <div class="truncate text-[8px] bg-white/50 rounded px-1 py-0.5 w-full"><?php echo e($b['title']); ?></div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    <?php endif; ?>
                                </button>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
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
        <h2 class="section-title mb-3">🔄 Quick Move</h2>
        <form id="quick-move-form" class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <select id="move-book-id" class="form-input">
                <option value="">Select assigned book</option>
                <?php $__currentLoopData = $assigned_books_in_rack; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($book->id); ?>" data-title="<?php echo e($book->title); ?>"><?php echo e($book->title); ?> (<?php echo e($book->position_code); ?>)</option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

            <select id="move-position-code" class="form-input">
                <option value="">Select target empty slot</option>
                <?php $__currentLoopData = $empty_positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $position): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($position); ?>"><?php echo e($position); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

            <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit']); ?>Move <?php echo $__env->renderComponent(); ?>
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

    
    <div id="edit-rack-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md animate-slide-up rounded-2xl bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900">Edit Rack Parameters</h3>
                <button type="button" onclick="document.getElementById('edit-rack-modal').classList.add('hidden');" class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">✕</button>
            </div>
            
            <form action="<?php echo e(route('racks.update', $rack)); ?>" method="POST" class="space-y-4">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <div>
                    <label class="form-label">Rack Name</label>
                    <input name="name" value="<?php echo e($rack->name); ?>" class="form-input" required>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Rows (A-Z)</label>
                        <input name="rows" type="number" min="1" max="26" value="<?php echo e($rack->rows); ?>" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Columns (1-10)</label>
                        <input name="columns" type="number" min="1" max="10" value="<?php echo e($rack->columns); ?>" class="form-input" required>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Slot Capacity</label>
                        <input name="capacity_per_slot" type="number" min="1" value="<?php echo e($rack->capacity_per_slot ?? 1); ?>" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Col Category</label>
                        <input name="column_category" value="<?php echo e($rack->column_category); ?>" class="form-input">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('edit-rack-modal').classList.add('hidden');" class="rounded-lg border border-border bg-white px-4 py-2 text-sm text-gray-700">Cancel</button>
                    <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','variant' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'success']); ?>Update Rack <?php echo $__env->renderComponent(); ?>
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

    
    <div id="assign-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md animate-slide-up rounded-2xl bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900">Assign Book to Position</h3>
                <button type="button" data-close-modal class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">✕</button>
            </div>

            <p class="mb-3 text-sm text-gray-600">Selected Position: <span id="selected-position" class="rounded bg-primary-100 px-2 py-0.5 font-bold text-primary-700">-</span></p>

            <form id="assign-form" class="space-y-4">
                <input type="hidden" id="position-code-input" name="position_code">
                <input type="hidden" id="rack-id-input" name="rack_id" value="<?php echo e($rack->id); ?>">

                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" id="unassigned-only-toggle" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    Show only unassigned books
                </label>

                <div>
                    <label for="book-id-input" class="form-label">Book</label>
                    <select id="book-id-input" name="book_id" class="form-input" required>
                        <option value="">Select book</option>
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
            </form>
        </div>
    </div>

    
    <div id="book-info-popup" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
        <div class="w-full max-w-sm animate-slide-up rounded-2xl bg-white p-6 shadow-2xl">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900" id="popup-book-title">-</h3>
                <button type="button" data-close-book-popup class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">✕</button>
            </div>

            <div class="space-y-2 text-sm text-gray-600">
                <p><span class="section-title">Position:</span> <span id="popup-position" class="ml-1 font-semibold text-gray-900">-</span></p>
                <p><span class="section-title">Rack:</span> <span class="ml-1 font-semibold text-gray-900"><?php echo e($rack->name); ?></span></p>
            </div>

            <div class="mt-4 flex flex-col gap-2">
                <a id="popup-view-link" href="#" class="inline-flex justify-center items-center gap-1.5 rounded-lg bg-primary-800 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-primary-700 hidden">📄 View Detail</a>
                <div id="popup-books-list" class="hidden mt-2 space-y-2 max-h-48 overflow-y-auto pr-2 custom-scrollbar"></div>
            </div>
        </div>
    </div>

    <div id="assign-toast" class="fixed bottom-6 right-6 z-50 hidden rounded-xl border border-primary-200 bg-primary-50 px-5 py-3.5 text-sm font-medium text-primary-800 shadow-lg">
        Book assigned.
    </div>

    <script id="rack-books-data" type="application/json">
        <?php echo json_encode($rack->books->map(fn($b) => ['id' => $b->id, 'title' => $b->title, 'position_code' => $b->position_code])->values()->all()); ?>

    </script>

    <script>
        (() => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const rackId = '<?php echo e($rack->id); ?>';
            const assignUrl = '<?php echo e(route("books.assign")); ?>';
            const autoAssignUrl = '<?php echo e(route("books.auto-assign")); ?>';

            const modal = document.getElementById('assign-modal');
            const selectedPositionLabel = document.getElementById('selected-position');
            const positionCodeInput = document.getElementById('position-code-input');
            const rackIdInput = document.getElementById('rack-id-input');
            const assignForm = document.getElementById('assign-form');
            const quickMoveForm = document.getElementById('quick-move-form');
            const moveBookSelect = document.getElementById('move-book-id');
            const movePositionSelect = document.getElementById('move-position-code');
            const gridSearchInput = document.getElementById('grid-search');
            const bookSelect = document.getElementById('book-id-input');
            const unassignedOnlyToggle = document.getElementById('unassigned-only-toggle');
            const autoAssignButton = document.getElementById('auto-assign-btn');
            const toast = document.getElementById('assign-toast');
            const closeButtons = document.querySelectorAll('[data-close-modal]');
            const rackGrid = document.getElementById('rack-grid');

            const bookInfoPopup = document.getElementById('book-info-popup');
            const popupBookTitle = document.getElementById('popup-book-title');
            const popupPosition = document.getElementById('popup-position');
            const popupViewLink = document.getElementById('popup-view-link');
            const closeBookPopupButtons = document.querySelectorAll('[data-close-book-popup]');

            const emptyCellClass = 'rounded-lg border border-gray-200 bg-gray-50 p-2 text-center text-xs text-gray-400 transition-all duration-200 hover:-translate-y-0.5 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-200 flex flex-col items-center justify-start gap-1 h-full min-h-[5rem]';
            const selectedCellClass = 'rounded-lg border-2 border-primary-600 bg-primary-800 p-2 text-center text-xs text-white transition-all duration-200 shadow-md scale-105 focus:outline-none flex flex-col items-center justify-start gap-1 h-full min-h-[5rem]';
            const occupiedCellClass = 'rounded-lg border border-primary-300 bg-primary-100 p-2 text-center text-xs text-primary-800 transition-all duration-200 hover:-translate-y-0.5 hover:border-primary-500 hover:bg-primary-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-300 flex flex-col items-center justify-start gap-1 h-full min-h-[5rem]';

            let activePosition = null;
            let selectedSlotButton = null;
            
            let allRackBooks = JSON.parse(document.getElementById('rack-books-data').textContent);

            const getEmptySlotButtons = () => rackGrid ? rackGrid.querySelectorAll('[data-slot-button]') : [];
            const getFilledButtons = () => rackGrid ? rackGrid.querySelectorAll('[data-filled-cell]') : [];

            const openModal = (positionCode) => {
                markSelectedCell(positionCode);
                activePosition = positionCode;
                selectedPositionLabel.textContent = positionCode;
                positionCodeInput.value = positionCode;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                applyBookFilter();
            };

            const closeModal = () => {
                clearSelectedCell();
                activePosition = null;
                selectedPositionLabel.textContent = '-';
                positionCodeInput.value = '';
                assignForm.reset();
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                applyBookFilter();
            };

            const openBookPopup = (button) => {
                const position = button.dataset.positionCode;
                const booksInCell = allRackBooks.filter(b => b.position_code === position);

                popupPosition.textContent = position;
                const listContainer = document.getElementById('popup-books-list');
                listContainer.innerHTML = '';
                
                if (booksInCell.length === 1) {
                    popupBookTitle.textContent = booksInCell[0].title;
                    popupViewLink.href = '/books/' + booksInCell[0].id;
                    popupViewLink.classList.remove('hidden');
                    listContainer.classList.add('hidden');
                } else {
                    popupBookTitle.textContent = booksInCell.length + ' Books inside';
                    popupViewLink.classList.add('hidden');
                    listContainer.classList.remove('hidden');
                    booksInCell.forEach(b => {
                        const item = document.createElement('a');
                        item.href = '/books/' + b.id;
                        item.className = 'block rounded-lg border border-gray-100 bg-gray-50 p-2 text-sm text-gray-700 hover:bg-primary-50 hover:border-primary-200 transition';
                        item.textContent = b.title;
                        listContainer.appendChild(item);
                    });
                }

                bookInfoPopup.classList.remove('hidden');
                bookInfoPopup.classList.add('flex');
            };

            const closeBookPopup = () => {
                bookInfoPopup.classList.add('hidden');
                bookInfoPopup.classList.remove('flex');
            };

            const clearSelectedCell = () => {
                if (!selectedSlotButton) return;
                selectedSlotButton.className = emptyCellClass;
                selectedSlotButton = null;
            };

            const markSelectedCell = (positionCode) => {
                clearSelectedCell();
                const selectedCell = rackGrid.querySelector('[data-position-code="' + positionCode + '"][data-slot-button]');
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
                getEmptySlotButtons().forEach(btn => btn.onclick = () => openModal(btn.dataset.positionCode));
                getFilledButtons().forEach(btn => btn.onclick = () => openBookPopup(btn));
            };

            const markCellAssigned = (positionCode, bookId, title) => {
                const target = rackGrid.querySelector('[data-position-code="' + positionCode + '"]');
                if (!target) return;
                
                const existing = allRackBooks.find(b => b.id == bookId);
                if (existing) existing.position_code = positionCode;
                else allRackBooks.push({id: bookId, title: title, position_code: positionCode});
                
                const booksInCell = allRackBooks.filter(b => b.position_code === positionCode);
                
                target.removeAttribute('data-slot-button');
                target.setAttribute('data-filled-cell', '');
                target.setAttribute('data-book-titles', booksInCell.map(b => b.title.toLowerCase()).join(' '));
                target.title = booksInCell.map(b => b.title).join(', ');
                target.className = occupiedCellClass;
                
                let booksHtml = booksInCell.map(b => '<div class="truncate text-[9px] bg-white/50 rounded px-1 py-0.5 w-full">' + b.title + '</div>').join('');
                target.innerHTML = '<div class="font-bold border-b border-primary-200 w-full pb-0.5 mb-0.5">' + positionCode + '</div><div class="flex flex-col gap-0.5 w-full">' + booksHtml + '</div>';
                
                target.onclick = () => openBookPopup(target);
            };

            const markCellEmpty = (positionCode) => {
                const target = rackGrid.querySelector('[data-position-code="' + positionCode + '"]');
                if (!target) return;
                
                const booksInCell = allRackBooks.filter(b => b.position_code === positionCode);
                if (booksInCell.length > 0) return;
                
                target.removeAttribute('data-book-titles');
                target.removeAttribute('data-filled-cell');
                target.setAttribute('data-slot-button', '');
                target.title = 'Klik untuk assign buku';
                target.className = emptyCellClass;
                target.innerHTML = '<div class="font-bold border-b border-gray-200 w-full pb-0.5 mb-0.5">' + positionCode + '</div><div class="text-[10px]">Empty</div>';
                target.onclick = () => openModal(positionCode);
            };

            const applyBookFilter = () => {
                const onlyUnassigned = unassignedOnlyToggle.checked;
                Array.from(bookSelect.options).forEach(opt => {
                    if (opt.value === '') return;
                    opt.hidden = onlyUnassigned && opt.dataset.unassigned === '0';
                });
                if (bookSelect.selectedOptions[0]?.hidden) bookSelect.value = '';
            };

            const updateMoveOptions = () => {
                movePositionSelect.innerHTML = '<option value="">Select target empty slot</option>';
                const slots = getEmptySlotButtons();
                slots.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = opt.textContent = s.dataset.positionCode;
                    movePositionSelect.appendChild(opt);
                });

                moveBookSelect.innerHTML = '<option value="">Select assigned book</option>';
                allRackBooks.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.dataset.title = b.title;
                    opt.dataset.positionCode = b.position_code;
                    opt.textContent = b.title + ' (' + b.position_code + ')';
                    moveBookSelect.appendChild(opt);
                });
            };

            const highlightGrid = () => {
                const keyword = gridSearchInput.value.trim().toLowerCase();
                let firstMatch = null;
                rackGrid.querySelectorAll('[data-grid-cell]').forEach(cell => {
                    cell.classList.remove('ring-2', 'ring-amber-400', 'shadow-lg', 'scale-105');
                    const title = cell.dataset.bookTitles || '';
                    if (keyword !== '' && title.includes(keyword)) {
                        cell.classList.add('ring-2', 'ring-amber-400', 'shadow-lg', 'scale-105');
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
            closeBookPopupButtons.forEach(btn => btn.onclick = closeBookPopup);
            unassignedOnlyToggle.onchange = applyBookFilter;
            gridSearchInput.oninput = highlightGrid;

            bindEvents();
            applyBookFilter();
            updateMoveOptions();

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
                    closeModal();
                    updateMoveOptions();
                    highlightGrid();
                    showToast('✅ Book assigned to ' + res.book.position_code);
                } catch (err) {
                    showToast('Assign failed.', true);
                }
            };

            quickMoveForm.onsubmit = async (e) => {
                e.preventDefault();
                const bookId = moveBookSelect.value;
                const target = movePositionSelect.value;
                const opt = moveBookSelect.selectedOptions[0];
                if (!bookId || !target || !opt) return;
                const old = opt.dataset.positionCode;
                const title = opt.dataset.title;
                try {
                    await postAssign(bookId, target);
                    markCellEmpty(old);
                    markCellAssigned(target, Number(bookId), title);
                    updateMoveOptions();
                    highlightGrid();
                    showToast('✅ Book moved to ' + target);
                } catch (err) {
                    showToast('Move failed.', true);
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
                if (e.target === bookInfoPopup) closeBookPopup();
            };
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\renre\Smart_LMS\resources\views/racks/show.blade.php ENDPATH**/ ?>