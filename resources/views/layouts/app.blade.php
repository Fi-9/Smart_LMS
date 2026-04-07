<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Smart Library' }}</title>
    <meta name="description" content="Smart Library Management System - Kelola koleksi perpustakaan dengan mudah.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-gray-900">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-30 flex h-screen w-60 shrink-0 flex-col border-r border-primary-700 bg-primary-800 text-white shadow-xl">
            <div class="flex items-center gap-2.5 px-5 py-5">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/20 text-sm font-bold">📚</span>
                <h1 class="text-base font-bold tracking-wide">Smart Library</h1>
            </div>

            <nav class="mt-2 flex-1 space-y-0.5 px-3 text-sm">
                @php
                    $navItems = [
                        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => '📊', 'match' => 'dashboard'],
                        ['route' => 'books.index', 'label' => 'Books', 'icon' => '📚', 'match' => 'books.index'],
                        ['route' => 'books.import', 'label' => 'Import', 'icon' => '📥', 'match' => 'books.import'],
                        ['route' => 'categories.index', 'label' => 'Categories', 'icon' => '🏷️', 'match' => 'categories.*'],
                        ['route' => 'racks.index', 'label' => 'Racks', 'icon' => '🗄️', 'match' => 'racks.*'],
                        ['route' => 'qr.index', 'label' => 'QR Stickers', 'icon' => '🔳', 'match' => 'qr.*'],
                        ['route' => 'borrowings.index', 'label' => 'Borrowings', 'icon' => '📋', 'match' => 'borrowings.*'],
                        ['route' => 'scanner', 'label' => 'Scan QR', 'icon' => '📱', 'match' => 'scanner'],
                    ];
                @endphp

                @foreach($navItems as $item)
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 transition-all duration-150
                              {{ request()->routeIs($item['match']) ? 'bg-white font-semibold text-primary-800 shadow-sm' : 'text-primary-100 hover:bg-white/10' }}"
                    >
                        <span class="text-sm">{{ $item['icon'] }}</span>
                        {{ $item['label'] }}
                    </a>
                @endforeach

                <a href="{{ route('settings.index') }}"
                   class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 transition-all duration-150
                          {{ request()->routeIs('settings.*') ? 'bg-white font-semibold text-primary-800 shadow-sm' : 'text-primary-100 hover:bg-white/10' }}"
                >
                    <span class="text-sm">[ ]</span>
                    Settings
                </a>
            </nav>

            <div class="border-t border-primary-700 px-5 py-4">
                <div class="space-y-3">
                    <div>
                        <p class="text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                        <p class="text-xs uppercase tracking-wide text-primary-300">{{ auth()->user()->role->value }}</p>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg border border-white/15 px-3 py-2 text-sm font-medium text-primary-100 transition hover:bg-white/10">
                            Logout
                        </button>
                    </form>

                    <p class="text-xs text-primary-300">SLiMS+ QR v1.0</p>
                </div>
            </div>
        </aside>

        {{-- Main Content --}}
        <main class="ml-60 flex-1 overflow-y-auto p-6 lg:p-8">
            <div class="mx-auto max-w-7xl">
                <x-ui.toast />
                @yield('content')
            </div>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
