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

unset($__defined_vars); ?>

<?php
    $classes = match ($variant) {
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-300',
        'secondary' => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 focus:ring-gray-300',
        default => 'bg-slate-900 text-white hover:bg-slate-800 focus:ring-slate-300',
    };
?>

<button type="<?php echo e($type); ?>" <?php echo e($attributes->merge(['class' => "rounded-md px-4 py-2 text-sm font-medium transition focus:outline-none focus:ring {$classes}"])); ?>>
    <?php echo e($slot); ?>

</button>
<?php /**PATH C:\Users\renre\Smart_LMS\resources\views\components\button.blade.php ENDPATH**/ ?>