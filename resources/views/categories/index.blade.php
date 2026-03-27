@extends('layouts.app')

@section('content')
    <h1 class="mb-6 text-3xl font-bold tracking-tight text-slate-900">Categories</h1>

    <x-card>
        <form method="POST" action="{{ route('categories.store') }}" class="mb-4 flex gap-2">
            @csrf
            <input name="name" placeholder="New Category" class="flex-1 rounded-md border border-gray-300 p-2 text-sm" required>
            <x-button type="submit">Add</x-button>
        </form>

        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-2 text-left font-semibold text-slate-700">Name</th>
                        <th class="p-2 text-right font-semibold text-slate-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $category)
                        <tr class="border-t border-slate-100">
                            <td class="p-2">
                                <form method="POST" action="{{ route('categories.update', $category) }}" class="flex gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input name="name" value="{{ $category->name }}" class="w-full rounded border border-gray-300 px-2 py-1 text-sm">
                                    <x-button type="submit" variant="secondary">Save</x-button>
                                </form>
                            </td>
                            <td class="p-2 text-right">
                                <form method="POST" action="{{ route('categories.destroy', $category) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" variant="secondary">Delete</x-button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
@endsection

