<?php $__env->startSection('content'); ?>
    <div class="mb-5 flex items-center justify-between">
        <a href="<?php echo e(route('books.index')); ?>" class="text-sm font-medium text-slate-600 hover:text-slate-900">Back to Books</a>
        <a href="<?php echo e(route('books.public.show', $book->id)); ?>" target="_blank" class="text-sm font-medium text-emerald-700 hover:text-emerald-800">Open Public View</a>
    </div>

    <?php echo $__env->make('books.partials.detail_panel', [
        'book' => $book,
        'rack_mini_map' => $rack_mini_map ?? null,
        'compact_description' => $compact_description ?? false,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\renre\Downloads\Smart_LMS\resources\views/books/show.blade.php ENDPATH**/ ?>