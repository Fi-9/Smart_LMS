@props([
    'status',
])

@php
    $normalizedStatus = strtolower((string) $status);
    $classes = match ($normalizedStatus) {
        'available' => 'bg-emerald-100 text-emerald-800',
        'borrowed' => 'bg-rose-100 text-rose-800',
        'unassigned' => 'bg-amber-100 text-amber-800',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $classes }}">
    {{ strtoupper($normalizedStatus) }}
</span>
