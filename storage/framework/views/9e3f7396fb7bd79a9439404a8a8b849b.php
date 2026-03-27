<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Print Layout</title>
    <?php echo app('Illuminate\Foundation\Vite')('resources/css/app.css'); ?>
</head>
<body class="bg-white p-6">
    <h1 class="mb-6 text-xl font-bold">QR Print Layout</h1>

    <div class="grid grid-cols-3 gap-4">
        <?php $__currentLoopData = $books; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="rounded border border-slate-300 p-3 text-center">
                <img src="<?php echo e($book->qr_code_path); ?>" alt="QR <?php echo e($book->title); ?>" class="mx-auto mb-2 h-24 w-24 object-contain">
                <p class="text-xs font-semibold"><?php echo e($book->title); ?></p>
                <p class="text-xs text-slate-500"><?php echo e($book->rack->name); ?> - <?php echo e($book->position_code); ?></p>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</body>
</html>

<?php /**PATH C:\Users\renre\Smart_LMS\resources\views\qr\print.blade.php ENDPATH**/ ?>