<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($title ?? 'Smart Library'); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</head>
<body class="bg-gray-100 text-gray-900">
    <div class="flex min-h-screen">
        <aside class="h-screen w-64 shrink-0 bg-slate-900 p-5 text-slate-100 shadow-xl">
            <h1 class="mb-8 text-xl font-bold tracking-wide">Smart Library</h1>
            <nav class="space-y-2 text-sm">
                <a href="<?php echo e(route('dashboard')); ?>" class="block rounded-md px-3 py-2 <?php echo e(request()->routeIs('dashboard') ? 'bg-white text-slate-900' : 'text-slate-200 hover:bg-slate-800'); ?>">Dashboard</a>
                <a href="<?php echo e(route('books.index')); ?>" class="block rounded-md px-3 py-2 <?php echo e(request()->routeIs('books.index') ? 'bg-white text-slate-900' : 'text-slate-200 hover:bg-slate-800'); ?>">Books</a>
                <a href="<?php echo e(route('books.import')); ?>" class="block rounded-md px-3 py-2 <?php echo e(request()->routeIs('books.import') ? 'bg-white text-slate-900' : 'text-slate-200 hover:bg-slate-800'); ?>">Import</a>
                <a href="<?php echo e(route('categories.index')); ?>" class="block rounded-md px-3 py-2 <?php echo e(request()->routeIs('categories.*') ? 'bg-white text-slate-900' : 'text-slate-200 hover:bg-slate-800'); ?>">Categories</a>
                <a href="<?php echo e(route('racks.index')); ?>" class="block rounded-md px-3 py-2 <?php echo e(request()->routeIs('racks.*') ? 'bg-white text-slate-900' : 'text-slate-200 hover:bg-slate-800'); ?>">Racks</a>
                <a href="<?php echo e(route('qr.index')); ?>" class="block rounded-md px-3 py-2 <?php echo e(request()->routeIs('qr.*') ? 'bg-white text-slate-900' : 'text-slate-200 hover:bg-slate-800'); ?>">QR Stickers</a>
                <a href="<?php echo e(route('scanner')); ?>" class="block rounded-md px-3 py-2 text-slate-200 hover:bg-slate-800">Scan QR</a>
            </nav>
        </aside>

        <main class="flex-1 overflow-y-auto p-8">
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
</body>
</html>
<?php /**PATH C:\Users\renre\Smart_LMS\resources\views\layouts\app.blade.php ENDPATH**/ ?>