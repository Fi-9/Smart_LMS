<?php $__env->startSection('content'); ?>
    <h1 class="page-title mb-1">Racks</h1>
    <p class="page-subtitle mb-6">Kelola rak perpustakaan dan lihat pemetaan visual buku.</p>

    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'mb-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mb-6']); ?>
        <h2 class="section-title mb-3">➕ Create New Rack</h2>
        <form method="POST" action="<?php echo e(route('racks.store')); ?>" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <?php echo csrf_field(); ?>
            <input name="name" placeholder="Rack name (e.g. Rack A)" class="form-input" required>
            <div class="grid grid-cols-2 gap-2">
                <input name="rows" type="number" min="1" max="26" placeholder="Rows (A-Z)" class="form-input" required>
                <input name="columns" type="number" min="1" max="10" placeholder="Cols (1-10)" class="form-input" required>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <input name="capacity_per_slot" type="number" min="1" placeholder="Slot Capacity" class="form-input" value="1" required>
                <input name="column_category" placeholder="Col Category (Optional)" class="form-input">
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
<?php $component->withAttributes(['type' => 'submit','variant' => 'success']); ?>Create Rack <?php echo $__env->renderComponent(); ?>
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

    <?php if(count($rack_cards) === 0): ?>
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
            <div class="py-10 text-center">
                <p class="text-3xl">🗄️</p>
                <p class="mt-2 text-sm font-medium text-gray-700">Belum ada rak dibuat</p>
                <p class="mt-1 text-xs text-gray-500">Buat rak pertama menggunakan form di atas.</p>
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
    <?php else: ?>
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <?php $__currentLoopData = $rack_cards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-base font-bold text-gray-900"><?php echo e($card['rack']->name); ?></h2>
                        <a href="<?php echo e(route('racks.show', $card['rack'])); ?>" class="inline-flex items-center gap-1 rounded-lg bg-primary-100 px-3 py-1.5 text-xs font-semibold text-primary-700 transition hover:bg-primary-200">Manage →</a>
                    </div>

                    <div class="mb-3 flex items-center gap-3 text-xs text-gray-500">
                        <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-gray-200"></span> Empty</span>
                        <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-primary-400"></span> Occupied</span>
                    </div>

                    <div class="grid gap-2 mb-3 max-w-full overflow-x-auto pb-2" <?php echo 'style="grid-template-columns: repeat(' . $card['rack']->columns . ', minmax(60px, 1fr));"'; ?>>
                        <?php $__currentLoopData = $card['grid']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cell): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="rounded-lg border p-2 text-center text-xs transition-all duration-200
                                <?php echo e($cell['occupied']
                                    ? 'border-primary-300 bg-primary-100 text-primary-800 font-semibold hover:bg-primary-200'
                                    : 'border-gray-200 bg-gray-50 text-gray-400 hover:bg-gray-100'); ?>"
                                style="min-height: 5rem;"
                            >
                                <div class="font-bold border-b <?php echo e($cell['occupied'] ? 'border-primary-200' : 'border-gray-200'); ?> w-full pb-0.5 mb-0.5"><?php echo e($cell['code']); ?></div>
                                <?php if($cell['occupied']): ?>
                                    <div class="flex flex-col gap-0.5 w-full max-h-16 overflow-y-auto no-scrollbar">
                                        <?php $__currentLoopData = $cell['books']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="truncate text-[9px] bg-white/50 rounded px-1 py-0.5 w-full"><?php echo e($b['title']); ?></div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-0.5 text-[10px]">Empty</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\renre\Smart_LMS\resources\views/racks/index.blade.php ENDPATH**/ ?>