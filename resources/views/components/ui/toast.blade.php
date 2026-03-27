@php
    $toast = session('toast');
@endphp

@if($toast)
    @php
        $type = $toast['type'] ?? 'info';
        $message = $toast['message'] ?? '';
        $classes = match ($type) {
            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            'error' => 'border-rose-200 bg-rose-50 text-rose-800',
            default => 'border-blue-200 bg-blue-50 text-blue-800',
        };
    @endphp
    <div class="mb-4 rounded-lg border px-4 py-3 text-sm {{ $classes }}">
        {{ $message }}
    </div>
@endif

