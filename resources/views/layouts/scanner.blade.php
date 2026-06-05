<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Smart Scanner' }} | Smart Library</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">

    {{-- Top Bar — Simpel, mobile-first --}}
    <header class="sticky top-0 z-50 border-b border-gray-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-14 max-w-lg items-center justify-between px-4">
            <div class="flex items-center gap-2">
                <span class="text-lg">📚</span>
                <span class="text-sm font-bold text-gray-900">Smart Scanner</span>
            </div>
            <div class="flex items-center gap-3">
                {{-- User Menu --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700">
                        {{ strtoupper(auth()->user()->name[0] ?? 'U') }}
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 rounded-xl border bg-white py-1 shadow-lg z-50">
                        <div class="border-b px-4 py-2">
                            <p class="text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                        </div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">🚪 Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Main Content — Mobile-first cards --}}
    <main class="mx-auto max-w-lg px-4 py-4">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
