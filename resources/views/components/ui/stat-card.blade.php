@props([
    'label',
    'value',
])

<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <p class="text-sm text-slate-500">{{ $label }}</p>
    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $value }}</p>
</div>
