<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'status',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'status',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php
    $normalizedStatus = strtolower((string) $status);
    $classes = match ($normalizedStatus) {
        'available' => 'bg-emerald-100 text-emerald-800',
        'borrowed' => 'bg-rose-100 text-rose-800',
        'unassigned' => 'bg-amber-100 text-amber-800',
        default => 'bg-slate-100 text-slate-700',
    };
?>

<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium <?php echo e($classes); ?>">
    <?php echo e(strtoupper($normalizedStatus)); ?>

</span>
<?php /**PATH C:\Users\renre\Smart_LMS\resources\views\components\ui\status-badge.blade.php ENDPATH**/ ?>