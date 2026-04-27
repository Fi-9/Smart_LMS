<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'variant' => 'primary',
    'type' => 'button',
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
    'variant' => 'primary',
    'type' => 'button',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $classes = match ($variant) {
        'success' => 'bg-primary-500 text-white hover:bg-primary-600 focus:ring-primary-300',
        'secondary' => 'border border-border bg-white text-gray-700 hover:bg-gray-50 focus:ring-gray-300',
        'danger' => 'bg-danger text-white hover:bg-red-700 focus:ring-red-300',
        default => 'bg-primary-800 text-white hover:bg-primary-700 focus:ring-primary-300',
    };
?>

<button type="<?php echo e($type); ?>" <?php echo e($attributes->merge(['class' => "inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition-all duration-150 ease-out focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 {$classes}"])); ?>>
    <?php echo e($slot); ?>

</button>
<?php /**PATH /mnt/data/Smart_LMS/resources/views/components/button.blade.php ENDPATH**/ ?>