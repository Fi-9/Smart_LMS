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

unset($__defined_vars, $__key, $__value); ?>

<?php
    $normalizedStatus = strtolower((string) $status);
    $classes = match ($normalizedStatus) {
        'available' => 'bg-primary-100 text-primary-700 ring-1 ring-primary-200',
        'borrowed' => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
        'lost' => 'bg-red-100 text-red-700 ring-1 ring-red-200',
        'unassigned' => 'bg-orange-100 text-orange-700 ring-1 ring-orange-200',
        default => 'bg-gray-100 text-gray-600 ring-1 ring-gray-200',
    };
?>

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?php echo e($classes); ?>">
    <?php echo e(ucfirst($normalizedStatus)); ?>

</span>
<?php /**PATH /mnt/data/Smart_LMS/resources/views/components/badge.blade.php ENDPATH**/ ?>