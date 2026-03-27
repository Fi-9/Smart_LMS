<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Smart Library' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('scripts')
</head>
<body class="bg-gray-100 text-gray-900">
    <div class="flex min-h-screen">
        <aside class="h-screen w-64 shrink-0 bg-slate-900 p-5 text-slate-100 shadow-xl">
            <h1 class="mb-8 text-xl font-bold tracking-wide">Smart Library</h1>
            <nav class="space-y-2 text-sm">
                <a href="{{ route('dashboard') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('dashboard') ? 'bg-white text-slate-900' : 'text-slate-200 hover:bg-slate-800' }}">Dashboard</a>
                <a href="{{ route('books.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('books.index') ? 'bg-white text-slate-900' : 'text-slate-200 hover:bg-slate-800' }}">Books</a>
                <a href="{{ route('books.import') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('books.import') ? 'bg-white text-slate-900' : 'text-slate-200 hover:bg-slate-800' }}">Import</a>
                <a href="{{ route('categories.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('categories.*') ? 'bg-white text-slate-900' : 'text-slate-200 hover:bg-slate-800' }}">Categories</a>
                <a href="{{ route('racks.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('racks.*') ? 'bg-white text-slate-900' : 'text-slate-200 hover:bg-slate-800' }}">Racks</a>
                <a href="{{ route('qr.index') }}" class="block rounded-md px-3 py-2 {{ request()->routeIs('qr.*') ? 'bg-white text-slate-900' : 'text-slate-200 hover:bg-slate-800' }}">QR Stickers</a>
                <a href="{{ route('scanner') }}" class="block rounded-md px-3 py-2 text-slate-200 hover:bg-slate-800">Scan QR</a>
            </nav>
        </aside>

        <main class="flex-1 overflow-y-auto p-8">
            <div class="mx-auto max-w-7xl">
                <x-ui.toast />
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
