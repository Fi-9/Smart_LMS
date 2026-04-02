@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <h1 class="page-title">QR Stickers</h1>
        <p class="page-subtitle">Filter, preview, lalu print layout A4.</p>
    </div>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('qr.generate') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="form-label">Rack</label>
                <select name="rack_id" class="form-input">
                    <option value="">All Racks</option>
                    @foreach($racks as $rack)
                        <option value="{{ $rack->id }}" @selected((string) $selected_rack_id === (string) $rack->id)>{{ $rack->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Category</label>
                <select name="category_id" class="form-input">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $selected_category_id === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <x-button type="submit">Apply Filter</x-button>
        </form>
    </x-card>

    @if($books->isEmpty())
        <x-card class="mb-5">
            <div class="py-8 text-center">
                <p class="text-3xl">🔳</p>
                <p class="mt-2 text-sm font-medium text-gray-700">Belum ada QR tersedia untuk filter ini.</p>
                <p class="mt-1 text-xs text-gray-500">Missing QR: {{ $missing_count }} buku.</p>
                <form method="POST" action="{{ route('qr.generate-missing') }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="rack_id" value="{{ $selected_rack_id }}">
                    <input type="hidden" name="category_id" value="{{ $selected_category_id }}">
                    <x-button type="submit" variant="success">Generate QR Now</x-button>
                </form>
            </div>
        </x-card>

        @if($preview_books->isNotEmpty())
            <h2 class="section-title mb-3">Recent QR Preview</h2>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                @foreach($preview_books as $book)
                    <x-card class="text-center">
                        <img src="{{ $book->qr_code ?: $book->qr_code_path }}" alt="QR {{ $book->title }}" class="mx-auto mb-2 h-28 w-28 object-contain">
                        <p class="text-xs font-semibold text-gray-800">{{ $book->title }}</p>
                        <p class="text-xs text-gray-500">{{ $book->rack->name ?? '-' }} - {{ $book->position_code ?? '-' }}</p>
                    </x-card>
                @endforeach
            </div>
        @endif
    @else
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <form method="POST" action="{{ route('qr.generate-missing') }}">
                @csrf
                <input type="hidden" name="rack_id" value="{{ $selected_rack_id }}">
                <input type="hidden" name="category_id" value="{{ $selected_category_id }}">
                <x-button type="submit" variant="secondary">Generate Missing QR</x-button>
            </form>

            <div class="flex items-center gap-2">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" id="select-all-qr" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    Select All
                </label>
                <a
                    href="{{ route('qr.print', ['rack_id' => $selected_rack_id, 'category_id' => $selected_category_id]) }}"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary-800 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-primary-700"
                >
                    🖨️ Print All
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('qr.print') }}" target="_blank" id="print-selected-form">
            <input type="hidden" name="rack_id" value="{{ $selected_rack_id }}">
            <input type="hidden" name="category_id" value="{{ $selected_category_id }}">

            <div class="mb-3 flex items-center gap-3">
                <x-button id="print-selected-btn" type="submit" variant="success" disabled>🖨️ Print Selected</x-button>
                <span id="selected-count" class="text-xs font-medium text-gray-500">0 selected</span>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                @foreach($books as $book)
                    <label class="group cursor-pointer">
                        <x-card class="text-center transition-all duration-150 hover:-translate-y-0.5 hover:shadow-md group-has-[:checked]:border-primary-400 group-has-[:checked]:ring-2 group-has-[:checked]:ring-primary-200">
                            <div class="mb-2 flex justify-start">
                                <input type="checkbox" name="selected_ids[]" value="{{ $book->id }}" class="qr-select-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            </div>
                            <img src="{{ $book->qr_code ?: $book->qr_code_path }}" alt="QR {{ $book->title }}" class="mx-auto mb-2 h-28 w-28 object-contain">
                            <p class="text-xs font-semibold text-gray-800">{{ $book->title }}</p>
                            <p class="text-xs text-gray-500">{{ $book->rack->name ?? '-' }} — {{ $book->position_code ?? '-' }}</p>
                        </x-card>
                    </label>
                @endforeach
            </div>
        </form>
    @endif

    <div class="mt-5">
        {{ $books->links() }}
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const checkboxes = document.querySelectorAll('.qr-select-checkbox');
            const printSelectedButton = document.getElementById('print-selected-btn');
            const selectedCountNode = document.getElementById('selected-count');
            const selectAllCheckbox = document.getElementById('select-all-qr');

            if (!checkboxes.length || !printSelectedButton || !selectedCountNode) return;

            const updateSelectedState = () => {
                const selectedCount = Array.from(checkboxes).filter((cb) => cb.checked).length;
                printSelectedButton.disabled = selectedCount === 0;
                selectedCountNode.textContent = `${selectedCount} selected`;
            };

            checkboxes.forEach((cb) => cb.addEventListener('change', updateSelectedState));

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', () => {
                    checkboxes.forEach((cb) => { cb.checked = selectAllCheckbox.checked; });
                    updateSelectedState();
                });
            }
        })();
    </script>
@endpush
