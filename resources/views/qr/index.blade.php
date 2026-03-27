@extends('layouts.app')

@section('content')
    <h1 class="mb-6 text-3xl font-bold tracking-tight text-slate-900">QR Stickers</h1>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('qr.generate') }}" class="flex flex-wrap gap-2">
            <select name="rack_id" class="rounded-md border border-gray-300 p-2 text-sm">
                <option value="">Select Rack</option>
                @foreach($racks as $rack)
                    <option value="{{ $rack->id }}" @selected((string) $selected_rack_id === (string) $rack->id)>{{ $rack->name }}</option>
                @endforeach
            </select>
            <x-button type="submit">Generate QR</x-button>
            <a href="{{ route('qr.print', ['rack_id' => $selected_rack_id]) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-slate-700 hover:bg-gray-100">Print Layout</a>
        </form>
    </x-card>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
        @foreach($books as $book)
            <x-card class="text-center">
                <img src="{{ $book->qr_code_path }}" alt="QR {{ $book->title }}" class="mx-auto mb-2 h-28 w-28 object-contain">
                <p class="text-xs font-semibold text-slate-800">{{ $book->title }}</p>
                <p class="text-xs text-slate-500">{{ $book->rack->name }} - {{ $book->position_code }}</p>
            </x-card>
        @endforeach
    </div>

    <div class="mt-5">
        {{ $books->links() }}
    </div>
@endsection

