@props([
    'variant' => 'primary',
    'type' => 'button',
])

@php
    $classes = match ($variant) {
        'success' => 'bg-primary-500 text-white hover:bg-primary-600 focus:ring-primary-300',
        'secondary' => 'border border-border bg-white text-gray-700 hover:bg-gray-50 focus:ring-gray-300',
        'danger' => 'bg-danger text-white hover:bg-red-700 focus:ring-red-300',
        default => 'bg-primary-800 text-white hover:bg-primary-700 focus:ring-primary-300',
    };
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition-all duration-150 ease-out focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 {$classes}"]) }}>
    {{ $slot }}
</button>
