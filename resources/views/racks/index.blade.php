@extends('layouts.app')

@section('content')
    <h1 class="mb-6 text-3xl font-bold tracking-tight text-slate-900">Racks</h1>

    <x-card class="mb-6">
        <form method="POST" action="{{ route('racks.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            @csrf
            <input name="name" placeholder="Rack A" class="rounded-md border border-gray-300 p-2 text-sm" required>
            <input name="rows" type="number" min="1" max="26" placeholder="Rows" class="rounded-md border border-gray-300 p-2 text-sm" required>
            <input name="columns" type="number" min="1" max="6" placeholder="Columns" class="rounded-md border border-gray-300 p-2 text-sm" required>
            <x-button type="submit">Create</x-button>
        </form>
    </x-card>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        @foreach($rack_cards as $card)
            <x-card>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-bold text-slate-900">{{ $card['rack']->name }}</h2>
                    <a href="{{ route('racks.show', $card['rack']) }}" class="text-xs font-medium text-blue-600 hover:text-blue-800">Manage Placement</a>
                </div>

                <div class="{{ $card['grid_class'] }}">
                    @foreach($card['grid'] as $cell)
                        <div class="rounded border p-2 text-center text-xs {{ $cell['occupied'] ? 'border-emerald-300 bg-emerald-100 text-emerald-800' : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                            <div class="font-semibold">{{ $cell['code'] }}</div>
                            <div class="mt-1 truncate">{{ $cell['book_title'] ?? 'Empty' }}</div>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endforeach
    </div>
@endsection

