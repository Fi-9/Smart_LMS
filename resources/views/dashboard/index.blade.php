@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Ringkasan koleksi perpustakaan dan distribusi rak.</p>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <div class="animate-slide-up rounded-xl bg-gradient-to-br from-primary-700 to-primary-500 p-5 text-white shadow-md xl:col-span-2">
            <p class="text-sm text-primary-100">Total Books</p>
            <h2 class="mt-2 text-3xl font-bold">{{ $stats['total_books'] }}</h2>
        </div>

        <x-card class="animate-slide-up" style="animation-delay: 50ms">
            <p class="text-sm text-gray-500">Categories</p>
            <h2 class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['total_categories'] }}</h2>
        </x-card>

        <x-card class="animate-slide-up" style="animation-delay: 100ms">
            <p class="text-sm text-gray-500">Racks</p>
            <h2 class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['total_racks'] }}</h2>
        </x-card>

        <div class="animate-slide-up rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm" style="animation-delay: 150ms">
            <p class="text-sm text-amber-600">Active Borrowed</p>
            <h2 class="mt-2 text-3xl font-bold text-amber-700">{{ $stats['total_borrowed_active'] ?? 0 }}</h2>
        </div>

        <div class="animate-slide-up rounded-xl border border-red-200 bg-red-50 p-5 shadow-sm" style="animation-delay: 200ms">
            <p class="text-sm text-red-600">Late Returns</p>
            <h2 class="mt-2 text-3xl font-bold text-red-700">{{ $stats['total_late'] ?? 0 }}</h2>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <x-card>
            <h2 class="font-semibold text-gray-800">Books per Rack</h2>
            <canvas
                id="booksPerRackChart"
                class="mt-4 h-72"
                data-labels='@json(collect($stats["books_per_rack"])->pluck("rack")->all())'
                data-values='@json(collect($stats["books_per_rack"])->pluck("total")->all())'
            ></canvas>
        </x-card>
        <x-card>
            <h2 class="font-semibold text-gray-800">Books per Category</h2>
            <canvas
                id="booksPerCategoryChart"
                class="mt-4 h-72"
                data-labels='@json(collect($stats["books_per_category"])->pluck("category")->all())'
                data-values='@json(collect($stats["books_per_category"])->pluck("total")->all())'
            ></canvas>
        </x-card>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-card>
            <p class="text-sm text-gray-500">AI Scan Today</p>
            <h2 class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['ai_scan_today']['total'] ?? 0 }}</h2>
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500">Success Rate</p>
            <h2 class="mt-2 text-3xl font-bold text-primary-700">{{ $stats['ai_scan_today']['success_rate'] ?? 0 }}%</h2>
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500">Avg Latency</p>
            <h2 class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['ai_scan_today']['avg_latency_ms'] ?? 0 }} ms</h2>
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500">Source (Google/OL)</p>
            <h2 class="mt-2 text-2xl font-bold text-gray-900">
                {{ $stats['ai_scan_today']['source_distribution']['google'] ?? 0 }}
                /
                {{ $stats['ai_scan_today']['source_distribution']['openlibrary'] ?? 0 }}
            </h2>
        </x-card>
        <x-card>
            <p class="text-sm text-gray-500">Source (Web/AI)</p>
            <h2 class="mt-2 text-2xl font-bold text-gray-900">
                {{ $stats['ai_scan_today']['source_distribution']['websearch'] ?? 0 }}
                /
                {{ $stats['ai_scan_today']['source_distribution']['ai'] ?? 0 }}
            </h2>
        </x-card>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/dashboard.js')
@endpush
