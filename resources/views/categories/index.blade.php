@extends('layouts.app')

@section('content')
    <h1 class="page-title mb-1">Categories</h1>
    <p class="page-subtitle mb-6">Kelola kategori buku perpustakaan.</p>

    <x-card>
        <form method="POST" action="{{ route('categories.store') }}" class="mb-5 flex gap-2">
            @csrf
            <input name="name" placeholder="New Category name..." class="form-input flex-1" required>
            <x-button type="submit" variant="success">➕ Add</x-button>
        </form>

        @if($categories->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 py-10 text-center">
                <p class="text-3xl">🏷️</p>
                <p class="mt-2 text-sm font-medium text-gray-700">Belum ada kategori</p>
                <p class="mt-1 text-xs text-gray-500">Buat kategori pertama menggunakan form di atas.</p>
            </div>
        @else
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-3 text-left text-xs font-semibold text-gray-500">Name</th>
                            <th class="p-3 text-right text-xs font-semibold text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                            <tr class="border-t border-gray-100 transition hover:bg-gray-50">
                                <td class="p-3">
                                    <form method="POST" action="{{ route('categories.update', $category) }}" class="flex gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input name="name" value="{{ $category->name }}" class="form-input flex-1">
                                        <x-button type="submit" variant="secondary">Save</x-button>
                                    </form>
                                </td>
                                <td class="p-3 text-right">
                                    <form method="POST" action="{{ route('categories.destroy', $category) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="danger">Delete</x-button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
@endsection
