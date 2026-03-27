@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Ringkasan koleksi perpustakaan</p>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-card>
            <p class="text-sm text-slate-500">Total Books</p>
            <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['total_books'] }}</h2>
        </x-card>

        <x-card>
            <p class="text-sm text-slate-500">Categories</p>
            <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['total_categories'] }}</h2>
        </x-card>

        <x-card>
            <p class="text-sm text-slate-500">Racks</p>
            <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['total_racks'] }}</h2>
        </x-card>

        <x-card>
            <p class="text-sm text-slate-500">Available</p>
            <h2 class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['available_books'] }}</h2>
        </x-card>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <x-card>
            <h2 class="font-semibold text-slate-800">Books per Rack</h2>
            <canvas
                id="booksPerRackChart"
                class="mt-4 h-72"
                data-labels='@json(collect($stats["books_per_rack"])->pluck("rack")->all())'
                data-values='@json(collect($stats["books_per_rack"])->pluck("total")->all())'
            ></canvas>
        </x-card>
        <x-card>
            <h2 class="font-semibold text-slate-800">Books per Category</h2>
            <canvas
                id="booksPerCategoryChart"
                class="mt-4 h-72"
                data-labels='@json(collect($stats["books_per_category"])->pluck("category")->all())'
                data-values='@json(collect($stats["books_per_category"])->pluck("total")->all())'
            ></canvas>
        </x-card>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/dashboard.js')
@endpush
