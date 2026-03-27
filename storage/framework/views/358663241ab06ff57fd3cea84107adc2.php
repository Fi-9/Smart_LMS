<?php
    $toast = session('toast');
?>

<?php if($toast): ?>
    <?php
        $type = $toast['type'] ?? 'info';
        $message = $toast['message'] ?? '';
        $classes = match ($type) {
            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            'error' => 'border-rose-200 bg-rose-50 text-rose-800',
            default => 'border-blue-200 bg-blue-50 text-blue-800',
        };
    ?>
    <div class="mb-4 rounded-lg border px-4 py-3 text-sm <?php echo e($classes); ?>">
        <?php echo e($message); ?>

    </div>
<?php endif; ?>

<?php /**PATH C:\Users\renre\Smart_LMS\resources\views/components/ui/toast.blade.php ENDPATH**/ ?>