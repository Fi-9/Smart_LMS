<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($title ?? 'Smart Library'); ?></title>
    <meta name="description" content="Smart Library Management System - Kelola koleksi perpustakaan dengan mudah.">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="bg-background text-gray-900">
    <div class="flex min-h-screen">
        
        <aside class="fixed inset-y-0 left-0 z-30 flex h-screen w-60 shrink-0 flex-col border-r border-primary-700 bg-primary-800 text-white shadow-xl">
            <div class="flex items-center gap-2.5 px-5 py-5">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/20 text-sm font-bold">📚</span>
                <h1 class="text-base font-bold tracking-wide">Smart Library</h1>
            </div>

            <nav class="mt-2 flex-1 space-y-0.5 px-3 text-sm">
                <?php
                    $navItems = [
                        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => '📊', 'match' => 'dashboard'],
                        ['route' => 'books.index', 'label' => 'Books', 'icon' => '📚', 'match' => 'books.index'],
                        ['route' => 'books.import', 'label' => 'Import', 'icon' => '📥', 'match' => 'books.import'],
                        ['route' => 'categories.index', 'label' => 'Categories', 'icon' => '🏷️', 'match' => 'categories.*'],
                        ['route' => 'racks.index', 'label' => 'Racks', 'icon' => '🗄️', 'match' => 'racks.*'],
                        ['route' => 'qr.index', 'label' => 'QR Stickers', 'icon' => '🔳', 'match' => 'qr.*'],
                        ['route' => 'borrowings.index', 'label' => 'Borrowings', 'icon' => '📋', 'match' => 'borrowings.*'],
                        ['route' => 'scanner', 'label' => 'Scan QR', 'icon' => '📱', 'match' => 'scanner'],
                    ];
                ?>

                <?php $__currentLoopData = $navItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route($item['route'])); ?>"
                       class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 transition-all duration-150
                              <?php echo e(request()->routeIs($item['match']) ? 'bg-white font-semibold text-primary-800 shadow-sm' : 'text-primary-100 hover:bg-white/10'); ?>"
                    >
                        <span class="text-sm"><?php echo e($item['icon']); ?></span>
                        <?php echo e($item['label']); ?>

                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </nav>

            <div class="border-t border-primary-700 px-5 py-4">
                <p class="text-xs text-primary-300">SLiMS+ QR v1.0</p>
            </div>
        </aside>

        
        <main class="ml-60 flex-1 overflow-y-auto p-6 lg:p-8">
            <div class="mx-auto max-w-7xl">
                <?php if (isset($component)) { $__componentOriginal339c7fedf680433726dbafc2f156956f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal339c7fedf680433726dbafc2f156956f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.toast','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.toast'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal339c7fedf680433726dbafc2f156956f)): ?>
<?php $attributes = $__attributesOriginal339c7fedf680433726dbafc2f156956f; ?>
<?php unset($__attributesOriginal339c7fedf680433726dbafc2f156956f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal339c7fedf680433726dbafc2f156956f)): ?>
<?php $component = $__componentOriginal339c7fedf680433726dbafc2f156956f; ?>
<?php unset($__componentOriginal339c7fedf680433726dbafc2f156956f); ?>
<?php endif; ?>
                <?php echo $__env->yieldContent('content'); ?>
            </div>
        </main>
    </div>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\renre\Smart_LMS\resources\views/layouts/app.blade.php ENDPATH**/ ?>