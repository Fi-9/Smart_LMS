<?php
    $toast = session('toast');
?>

<?php if($toast): ?>
    <?php
        $type = $toast['type'] ?? 'info';
        $message = $toast['message'] ?? '';
        $classes = match ($type) {
            'success' => 'border-primary-200 bg-primary-50 text-primary-800',
            'error' => 'border-red-200 bg-red-50 text-red-800',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
            default => 'border-blue-200 bg-blue-50 text-blue-800',
        };
        $icons = match ($type) {
            'success' => '✅',
            'error' => '❌',
            'warning' => '⚠️',
            default => 'ℹ️',
        };
    ?>
    <div class="animate-slide-up mb-5 flex items-center gap-3 rounded-xl border px-4 py-3 text-sm shadow-sm <?php echo e($classes); ?>">
        <span class="text-base"><?php echo e($icons); ?></span>
        <span><?php echo e($message); ?></span>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\renre\Smart_LMS\resources\views/components/ui/toast.blade.php ENDPATH**/ ?>