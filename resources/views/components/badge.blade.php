@props([
    'status',
])

@php
    $normalizedStatus = strtolower((string) $status);
    $classes = match ($normalizedStatus) {
        'available' => 'bg-primary-100 text-primary-700 ring-1 ring-primary-200',
        'borrowed' => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
        'lost' => 'bg-red-100 text-red-700 ring-1 ring-red-200',
        'unassigned' => 'bg-orange-100 text-orange-700 ring-1 ring-orange-200',
        default => 'bg-gray-100 text-gray-600 ring-1 ring-gray-200',
    };
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $classes }}">
    {{ ucfirst($normalizedStatus) }}
</span>
