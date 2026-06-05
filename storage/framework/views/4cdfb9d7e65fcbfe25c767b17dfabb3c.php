<?php
    $isCompactDescription = (bool) ($compact_description ?? true);
    $descriptionText = $book->description ?? 'Deskripsi belum tersedia. Lengkapi melalui import ISBN untuk mengisi metadata otomatis.';
?>

<div class="animate-fade-in space-y-5">
    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'shadow-md transition-shadow hover:shadow-lg']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'shadow-md transition-shadow hover:shadow-lg']); ?>
        <div class="flex flex-col gap-5 lg:flex-row">
            <img
                src="<?php echo e($book->cover_url ?: '/images/default-book-cover.svg'); ?>"
                alt="<?php echo e($book->title); ?>"
                class="h-56 w-40 rounded-xl border border-gray-200 object-cover shadow-sm"
            >
            <div class="flex-1 space-y-3">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-gray-900"><?php echo e($book->title); ?></h2>
                    <p class="mt-1 text-sm text-gray-500"><?php echo e($book->author); ?></p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-lg bg-primary-100 px-2.5 py-1 text-xs font-semibold text-primary-700 ring-1 ring-primary-200"><?php echo e($book->category->name); ?></span>
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

                <dl class="grid grid-cols-2 gap-4 rounded-xl border border-gray-100 bg-gray-50/50 p-4 text-sm">
                    <div>
                        <dt class="section-title">ISBN</dt>
                        <dd class="mt-1 font-medium text-gray-800"><?php echo e($book->isbn ?? '-'); ?></dd>
                    </div>
                    <div>
                        <dt class="section-title">Category</dt>
                        <dd class="mt-1 font-medium text-gray-800"><?php echo e($book->category->name); ?></dd>
                    </div>
                    <div>
                        <dt class="section-title">Status</dt>
                        <dd class="mt-1">
                            <?php
                                $statusColor = match(strtolower($book->status->value)) {
                                    'available' => 'text-primary-700',
                                    'borrowed' => 'text-amber-600',
                                    'lost' => 'text-red-600',
                                    default => 'text-gray-800',
                                };
                            ?>
                            <span class="font-semibold <?php echo e($statusColor); ?>"><?php echo e(ucfirst($book->status->value)); ?></span>
                        </dd>
                    </div>
                    <div>
                        <dt class="section-title">Location</dt>
                        <dd class="mt-1 font-medium text-gray-800">
                            <?php if($book->rack && $book->position_code): ?>
                                <?php
                                    $row = substr($book->position_code, 0, 1);
                                    $col = substr($book->position_code, 1);
                                ?>
                                <?php echo e($book->rack->name); ?> -> Row <?php echo e($row); ?> -> Column <?php echo e($col); ?>

                            <?php else: ?>
                                <span class="text-gray-400">Unassigned</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>
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

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'shadow-md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'shadow-md']); ?>
            <h3 class="section-title">Location</h3>
            <p class="mt-2 text-lg font-bold text-gray-900"><?php echo e($book->rack?->name ?? 'Unassigned'); ?></p>
            <p class="text-2xl font-black tracking-wide text-primary-600"><?php echo e($book->position_code ?? '-'); ?></p>
            <?php if($book->rack && $book->position_code): ?>
                <?php
                    $row = substr($book->position_code, 0, 1);
                    $col = substr($book->position_code, 1);
                ?>
                <p class="mt-1 text-xs text-gray-500"><?php echo e($book->rack->name); ?> -> Row <?php echo e($row); ?> -> Column <?php echo e($col); ?></p>
            <?php else: ?>
                <p class="mt-1 text-xs text-gray-500">Buku belum ditempatkan di rak.</p>
            <?php endif; ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'shadow-md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'shadow-md']); ?>
            <h3 class="section-title">QR Code</h3>
            <div id="qr-preview" class="mt-4 flex min-h-[200px] flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/40 p-4">
                <?php if($book->qr_code || $book->qr_code_path): ?>
                    <img src="<?php echo e($book->qr_code ?: $book->qr_code_path); ?>" alt="QR Code" class="h-40 w-40 rounded-md border border-gray-100 bg-white p-1 object-contain">
                    <p class="mt-2 text-[10px] font-medium uppercase tracking-wider text-gray-400">Scan to View</p>
                <?php else: ?>
                    <div class="flex h-28 w-28 items-center justify-center rounded-lg bg-gray-100 text-gray-400">
                        <span class="text-xs font-semibold">No QR</span>
                    </div>
                    <button
                        type="button"
                        id="generate-qr-btn"
                        data-generate-url="<?php echo e(route('qr.generate-single', $book->id)); ?>"
                        class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-primary-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                    >
                        + Generate QR
                    </button>
                <?php endif; ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'shadow-md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'shadow-md']); ?>
            <h3 class="section-title">Description</h3>
            <?php if($isCompactDescription): ?>
                <p class="description-clamp mt-2 text-sm leading-relaxed text-gray-600">
                    <?php echo e($descriptionText); ?>

                </p>
                <div class="mt-3">
                    <a href="<?php echo e(route('books.web.show', $book->id)); ?>" class="inline-flex items-center gap-1 text-xs font-semibold text-primary-700 transition hover:text-primary-800">
                        Lihat deskripsi lengkap
                    </a>
                </div>
            <?php else: ?>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-gray-600">
                    <?php echo e($descriptionText); ?>

                </p>
            <?php endif; ?>
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

    <!-- ROW 2: Rack Mini Map -->
    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'flex flex-col shadow-sm transition-shadow hover:shadow-md border border-gray-100']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'flex flex-col shadow-sm transition-shadow hover:shadow-md border border-gray-100']); ?>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 pb-3">
            <div class="flex items-center gap-2">
                <svg class="h-3.5 w-3.5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Rack Mini Map</h3>
            </div>
            <div class="flex items-center gap-3 text-[10px] font-medium text-gray-500">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded bg-gray-200"></span> Empty</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-primary-300"></span> Filled</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-primary-800"></span> Current</span>
            </div>
        </div>

        <div class="flex-1 rounded-xl flex flex-col justify-center min-h-[12rem] bg-white">
            <?php if(!$rack_mini_map): ?>
                <div class="flex flex-col items-center justify-center py-8 text-center bg-gray-50/50 rounded-xl border border-dashed border-gray-200">
                    <p class="text-sm font-medium text-gray-700">Buku belum ditempatkan</p>
                    <p class="mt-1 text-xs text-gray-500">Assign buku ke rack untuk melihat mini map.</p>
                    <a href="<?php echo e(route('racks.index')); ?>" class="mt-3 inline-flex items-center gap-1 rounded-lg bg-primary-800 px-4 py-1.5 text-[11px] font-medium text-white hover:bg-primary-700 shadow-sm hover:shadow">Assign ke Rack</a>
                </div>
            <?php else: ?>
                <p class="mb-5 text-xs font-bold text-gray-800"><?php echo e($rack_mini_map['rack_name']); ?></p>

                <?php if(isset($rack_mini_map['matrix'][0])): ?>
                    <div class="mb-1 flex items-center gap-2">
                        <div class="w-6"></div>
                        <div class="grid gap-2 flex-1" <?php echo 'style="grid-template-columns: repeat(' . count($rack_mini_map['matrix'][0]['cells']) . ', 1fr);"'; ?>>
                            <?php $__currentLoopData = $rack_mini_map['matrix'][0]['cells']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cell): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $colNum = substr($cell['code'], 1); ?>
                                <div class="flex items-center justify-center text-[10px] font-bold text-gray-400"><?php echo e($colNum); ?></div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="space-y-2">
                    <?php $__currentLoopData = $rack_mini_map['matrix']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="flex items-center gap-2">
                            <div class="w-6 text-center text-xs font-bold text-gray-500"><?php echo e($row['label']); ?></div>
                            <div class="grid gap-2 flex-1" <?php echo 'style="grid-template-columns: repeat(' . count($row['cells']) . ', 1fr);"'; ?>>
                                <?php $__currentLoopData = $row['cells']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cell): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div
                                        title="<?php echo e(implode(', ', array_column($cell['books'], 'title')) ?: 'Empty'); ?>"
                                        class="flex h-9 items-center justify-center rounded-md border text-[10px] font-bold shadow-sm transition-all duration-200
                                            <?php echo e($cell['state'] === 'current' ? 'border-primary-600 bg-primary-800 text-white scale-110 z-10 ring-2 ring-primary-500/30 ring-offset-1' : ''); ?>

                                            <?php echo e($cell['state'] === 'filled' ? 'border-primary-300 bg-primary-100 text-primary-800 hover:bg-primary-200 cursor-help' : ''); ?>

                                            <?php echo e($cell['state'] === 'empty' ? 'border-dashed border-gray-300 bg-gray-50 text-gray-400 hover:bg-gray-100' : ''); ?>

                                        "
                                    >
                                        <?php echo e($cell['code']); ?>

                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'shadow-md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'shadow-md']); ?>
        <h3 class="section-title">Actions</h3>
        <div class="mt-3 flex flex-wrap gap-2">
            <?php if($book->isAvailable()): ?>
                <button
                    type="button"
                    data-open-borrow-modal
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-800 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-primary-700"
                >
                    Borrow Book
                </button>
            <?php elseif($book->isBorrowed() && $book->activeBorrowing): ?>
                <button
                    type="button"
                    data-return-book-btn
                    data-return-url="<?php echo e(route('borrowings.return', $book->activeBorrowing->id)); ?>"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-amber-700"
                >
                    Return Book
                </button>
            <?php endif; ?>

            <?php if($book->rack_id): ?>
                <a href="<?php echo e(route('rooms.show', $book->rack->room_id)); ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Lihat Rak</a>
            <?php else: ?>
                <a href="<?php echo e(route('racks.index')); ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Assign</a>
            <?php endif; ?>
            <?php if($book->qr_code || $book->qr_code_path): ?>
                <a href="<?php echo e(route('qr.print', ['selected_ids' => [$book->id]])); ?>" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Print QR</a>
            <?php endif; ?>
            <a href="<?php echo e(route('books.web.show', $book->id)); ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">Full Detail</a>
            <button
                type="button"
                data-delete-book-btn
                data-delete-url="<?php echo e(route('books.destroy', $book->id)); ?>"
                data-book-title="<?php echo e($book->title); ?>"
                class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 hover:border-red-300"
            >
                🗑️ Hapus
            </button>
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

    <div id="borrow-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md animate-slide-up rounded-2xl bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900">Pinjamkan Buku</h3>
                <button type="button" data-close-borrow-modal class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">x</button>
            </div>

            <div class="mb-4 rounded-lg bg-gray-50 p-3">
                <p class="text-sm font-semibold text-gray-900"><?php echo e($book->title); ?></p>
            </div>

            <form id="borrow-form" class="space-y-4" data-borrow-url="<?php echo e(route('borrowings.store')); ?>">
                <input type="hidden" name="book_id" value="<?php echo e($book->id); ?>">

                <div x-data="memberAutocomplete()" class="relative">
                    <label class="form-label">Nama Peminjam *</label>
                    <input type="hidden" name="member_id" x-model="memberId">
                    <input type="text" name="borrower_name" x-model="search" @input.debounce.300ms="fetchMembers" @focus="showDropdown = true" @click.away="showDropdown = false" class="form-input" required placeholder="Cari nama atau NIS siswa/guru..." autocomplete="off">
                    <p id="error-borrower_name" class="mt-1 hidden text-xs text-red-600"></p>

                    <div x-show="showDropdown && members.length > 0" class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-xl border border-border bg-white py-1 shadow-lg" style="display: none;">
                        <template x-for="member in members" :key="member.id">
                            <button type="button" @click="selectMember(member)" class="flex w-full flex-col items-start px-4 py-2 text-left hover:bg-primary-50 focus:bg-primary-50 focus:outline-none">
                                <span class="font-semibold text-gray-900" x-text="member.name"></span>
                                <span class="text-xs text-gray-500" x-text="member.nis + (member.class ? ' - ' + member.class : '')"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div>
                    <label for="due_date" class="form-label">Tenggat Waktu (Due Date)</label>
                    <input type="date" id="due_date" name="due_date" class="form-input" required value="<?php echo e(now()->addDays(7)->format('Y-m-d')); ?>" min="<?php echo e(now()->addDay()->format('Y-m-d')); ?>">
                    <p id="error-due_date" class="mt-1 hidden text-xs text-red-600"></p>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" data-close-borrow-modal class="rounded-lg border border-border bg-white px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50">Cancel</button>
                    <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','variant' => 'success','id' => 'submit-borrow-btn']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'success','id' => 'submit-borrow-btn']); ?>Pinjamkan <?php echo $__env->renderComponent(); ?>
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

<script>
(() => {
    const panel = document.getElementById('detail-panel');
    if (!panel) return;

    const generateBtn = panel.querySelector('#generate-qr-btn');
    if (generateBtn) {
        generateBtn.addEventListener('click', async () => {
            const url = generateBtn.dataset.generateUrl;
            generateBtn.disabled = true;
            generateBtn.innerHTML = 'Generating...';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });

                const data = await response.json();
                if (!response.ok || !data?.qr_code) throw new Error(data?.message || 'Generate failed');

                const qrPreview = panel.querySelector('#qr-preview');
                if (qrPreview) {
                    qrPreview.innerHTML = `
                        <img src="${data.qr_code}" alt="QR Code" class="h-40 w-40 rounded-md border border-gray-100 bg-white p-1 object-contain">
                        <p class="mt-2 text-[10px] font-medium uppercase tracking-wider text-gray-400">Scan to View</p>
                    `;
                }

                if (window.showGlobalToast) window.showGlobalToast('OK ' + data.message);
            } catch (error) {
                generateBtn.innerHTML = 'Failed - Retry';
                generateBtn.disabled = false;
                if (window.showGlobalToast) window.showGlobalToast('Error ' + (error.message || 'Failed generating QR code.'));
            }
        });
    }

    const borrowModal = panel.querySelector('#borrow-modal');
    const openBorrowBtn = panel.querySelector('[data-open-borrow-modal]');
    const closeBorrowBtns = panel.querySelectorAll('[data-close-borrow-modal]');
    const borrowForm = panel.querySelector('#borrow-form');
    const submitBorrowBtn = panel.querySelector('#submit-borrow-btn');

    if (openBorrowBtn) {
        openBorrowBtn.addEventListener('click', () => {
            borrowModal.classList.remove('hidden');
            borrowModal.classList.add('flex');
            document.querySelector('[name="borrower_name"]')?.focus();
        });
    }

    const closeBorrowModal = () => {
        borrowModal.classList.add('hidden');
        borrowModal.classList.remove('flex');
        borrowForm.reset();
        document.querySelectorAll('[id^="error-"]').forEach(el => el.classList.add('hidden'));
    };

    closeBorrowBtns.forEach(btn => btn.addEventListener('click', closeBorrowModal));
    borrowModal?.addEventListener('click', (e) => { if (e.target === borrowModal) closeBorrowModal(); });

    borrowForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        submitBorrowBtn.disabled = true;
        submitBorrowBtn.innerHTML = 'Processing...';
        document.querySelectorAll('[id^="error-"]').forEach(el => el.classList.add('hidden'));

        try {
            const formData = new FormData(borrowForm);
            const data = Object.fromEntries(formData.entries());

            const response = await fetch(borrowForm.dataset.borrowUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            });

            const result = await response.json();

            if (!response.ok) {
                if (response.status === 422 && result.errors) {
                    Object.entries(result.errors).forEach(([key, messages]) => {
                        const errorEl = document.getElementById('error-' + key);
                        if (errorEl) {
                            errorEl.textContent = messages[0];
                            errorEl.classList.remove('hidden');
                        }
                    });
                } else {
                    alert(result.message || 'Gagal memproses peminjaman.');
                }
                submitBorrowBtn.disabled = false;
                submitBorrowBtn.innerHTML = 'Pinjamkan';
                return;
            }

            closeBorrowModal();
            if (window.showGlobalToast) window.showGlobalToast('OK ' + result.message);
            window.setTimeout(() => window.location.reload(), 800);
        } catch (error) {
            alert('Terjadi kesalahan jaringan.');
            submitBorrowBtn.disabled = false;
            submitBorrowBtn.innerHTML = 'Pinjamkan';
        }
    });

    const returnBtn = panel.querySelector('[data-return-book-btn]');
    if (returnBtn) {
        returnBtn.addEventListener('click', async () => {
            if (!confirm('Konfirmasi pengembalian buku ini?')) return;

            returnBtn.disabled = true;
            returnBtn.innerHTML = 'Returning...';

            try {
                const response = await fetch(returnBtn.dataset.returnUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });

                const result = await response.json();

                if (!response.ok) throw new Error(result.message || 'Gagal mengembalikan buku');

                if (window.showGlobalToast) window.showGlobalToast('OK ' + result.message);
                else alert(result.message);

                const panelUrl = '<?php echo e(route('books.web.panel', $book->id)); ?>';
                const panelResponse = await fetch(panelUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
                if (panelResponse.ok) {
                    document.getElementById('detail-panel').innerHTML = await panelResponse.text();

                    Array.from(document.getElementById('detail-panel').querySelectorAll('script')).forEach(oldScript => {
                        const newScript = document.createElement('script');
                        Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    });
                }
            } catch (error) {
                alert(error.message);
                returnBtn.disabled = false;
                returnBtn.innerHTML = 'Return Book';
            }
        });
    }
    const deleteBtn = panel.querySelector('[data-delete-book-btn]');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', async () => {
            const title = deleteBtn.dataset.bookTitle;
            const confirmed = confirm(`⚠️ Yakin mau hapus buku "${title}" dari SMK Mustaqbal?\n\nData buku, QR code, dan riwayat peminjaman akan dihapus permanen.`);
            if (!confirmed) return;

            deleteBtn.disabled = true;
            deleteBtn.innerHTML = '⏳ Menghapus...';

            try {
                const response = await fetch(deleteBtn.dataset.deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });

                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Gagal menghapus buku');

                if (window.showGlobalToast) window.showGlobalToast('OK ' + result.message);
                window.setTimeout(() => window.location.reload(), 800);
            } catch (error) {
                alert(error.message);
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = '🗑️ Hapus';
            }
        });
    }
})();

document.addEventListener('alpine:init', () => {
    Alpine.data('memberAutocomplete', () => ({
        search: '',
        memberId: '',
        members: [],
        showDropdown: false,

        async fetchMembers() {
            if (this.search.length < 2) {
                this.members = [];
                this.memberId = '';
                return;
            }

            try {
                const res = await fetch(`/members/search?q=${encodeURIComponent(this.search)}`);
                if (res.ok) {
                    this.members = await res.json();
                    this.showDropdown = true;
                }
            } catch (e) {
                console.error('Failed to fetch members', e);
            }
        },

        selectMember(member) {
            this.search = member.name;
            this.memberId = member.id;
            this.showDropdown = false;
        }
    }));
});
</script>
<?php /**PATH C:\Users\renre\Downloads\Smart_LMS\resources\views/books/partials/detail_panel.blade.php ENDPATH**/ ?>