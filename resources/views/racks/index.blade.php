@extends('layouts.app')

@section('content')
    <h1 class="page-title mb-1">Racks</h1>
    <p class="page-subtitle mb-6">Kelola rak perpustakaan dan lihat pemetaan visual buku.</p>

    <x-card class="mb-6">
        <h2 class="section-title mb-3">➕ Create New Rack</h2>
        <form method="POST" action="{{ route('racks.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            @csrf
            <input name="name" placeholder="Rack name (e.g. Rack A)" class="form-input" required>
            <div class="grid grid-cols-2 gap-2">
                <input name="rows" type="number" min="1" max="26" placeholder="Rows (A-Z)" class="form-input" required>
                <input name="columns" type="number" min="1" max="10" placeholder="Cols (1-10)" class="form-input" required>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <input name="capacity_per_slot" type="number" min="1" placeholder="Slot Capacity" class="form-input" value="1" required>
                <input name="column_category" placeholder="Col Category (Optional)" class="form-input">
            </div>
            <x-button type="submit" variant="success">Create Rack</x-button>
        </form>
    </x-card>

    @if(count($rack_cards) === 0)
        <x-card>
            <div class="py-10 text-center">
                <p class="text-3xl">🗄️</p>
                <p class="mt-2 text-sm font-medium text-gray-700">Belum ada rak dibuat</p>
                <p class="mt-1 text-xs text-gray-500">Buat rak pertama menggunakan form di atas.</p>
            </div>
        </x-card>
    @else
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            @foreach($rack_cards as $card)
                <x-card class="shadow-md transition-shadow hover:shadow-lg">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-base font-bold text-gray-900">{{ $card['rack']->name }}</h2>
                        <a href="{{ route('racks.show', $card['rack']) }}" class="inline-flex items-center gap-1 rounded-lg bg-primary-100 px-3 py-1.5 text-xs font-semibold text-primary-700 transition hover:bg-primary-200">Manage →</a>
                    </div>

                    <div class="mb-3 flex items-center gap-3 text-xs text-gray-500">
                        <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-gray-200"></span> Empty</span>
                        <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded bg-primary-400"></span> Occupied</span>
                    </div>

                    <div class="grid gap-2 mb-3 max-w-full overflow-x-auto pb-2" {!! 'style="grid-template-columns: repeat(' . $card['rack']->columns . ', minmax(60px, 1fr));"' !!}>
                        @foreach($card['grid'] as $cell)
                            <div class="rounded-lg border p-2 text-center text-xs transition-all duration-200
                                {{ $cell['occupied']
                                    ? 'border-primary-300 bg-primary-100 text-primary-800 font-semibold hover:bg-primary-200'
                                    : 'border-gray-200 bg-gray-50 text-gray-400 hover:bg-gray-100' }}"
                                style="min-height: 5rem;"
                            >
                                <div class="font-bold border-b {{ $cell['occupied'] ? 'border-primary-200' : 'border-gray-200' }} w-full pb-0.5 mb-0.5">{{ $cell['code'] }}</div>
                                @if($cell['occupied'])
                                    <div class="flex flex-col gap-0.5 w-full max-h-16 overflow-y-auto no-scrollbar">
                                        @foreach($cell['books'] as $b)
                                            <div class="truncate text-[9px] bg-white/50 rounded px-1 py-0.5 w-full">{{ $b['title'] }}</div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="mt-0.5 text-[10px]">Empty</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
@endsection
