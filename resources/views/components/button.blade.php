@props([
    'variant' => 'primary',
    'type' => 'button',
])

@php
    $classes = match ($variant) {
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-300',
        'secondary' => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 focus:ring-gray-300',
        default => 'bg-slate-900 text-white hover:bg-slate-800 focus:ring-slate-300',
    };
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => "rounded-md px-4 py-2 text-sm font-medium transition focus:outline-none focus:ring {$classes}"]) }}>
    {{ $slot }}
</button>
