@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Books</h1>
        <p class="mt-1 text-sm text-slate-500">Daftar buku, pencarian, dan filter rak</p>
    </div>

    <x-card class="mb-5">
        <form method="GET" action="{{ route('books.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-5">
            <input name="search" value="{{ $filters['search'] }}" type="text" placeholder="Search title/author" class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
            <select name="category_id" class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $filters['category_id'] === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="rack_id" class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                <option value="">All Racks</option>
                @foreach($racks as $rack)
                    <option value="{{ $rack->id }}" @selected((string) $filters['rack_id'] === (string) $rack->id)>{{ $rack->name }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                <option value="">All Status</option>
                <option value="available" @selected($filters['status'] === 'available')>Available</option>
                <option value="borrowed" @selected($filters['status'] === 'borrowed')>Borrowed</option>
            </select>
            <div class="flex gap-2">
                <x-button type="submit">Search</x-button>
                <a href="{{ route('books.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-slate-700 hover:bg-gray-100">Reset</a>
            </div>
        </form>
    </x-card>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left font-semibold text-slate-700">Title</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Author</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Rack</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Position</th>
                    <th class="p-3 text-left font-semibold text-slate-700">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($books as $book)
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="p-3 font-medium text-slate-900">{{ $book->title }}</td>
                        <td class="p-3 text-slate-700">{{ $book->author }}</td>
                        <td class="p-3 text-slate-700">{{ $book->rack?->name ?? '-' }}</td>
                        <td class="p-3 text-slate-700">
                            @if(!$book->rack_id)
                                <span class="text-yellow-600">Unassigned</span>
                            @else
                                {{ $book->position_code }}
                            @endif
                        </td>
                        <td class="p-3"><x-badge :status="$book->status->value" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-6 text-center text-slate-500">No books found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $books->appends(request()->query())->links() }}
    </div>
@endsection
