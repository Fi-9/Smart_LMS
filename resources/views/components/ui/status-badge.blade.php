@props([
    'status',
])

@php
    $isAvailable = $status === 'available';
    $classes = $isAvailable
        ? 'bg-emerald-100 text-emerald-800'
        : 'bg-amber-100 text-amber-800';
@endphp

<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $classes }}">
    {{ strtoupper($status) }}
</span>

