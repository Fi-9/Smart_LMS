@props([
    'status',
])

@php
    $isAvailable = $status === 'available';
@endphp

<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $isAvailable ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
    {{ $status }}
</span>
