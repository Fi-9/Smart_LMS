@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <h1 class="page-title">Import Books</h1>
        <p class="page-subtitle">CSV import atau manual input dengan ISBN autofill.</p>
    </div>

    @php
        $manualFieldNames = ['title', 'author', 'isbn', 'category_id', 'rack_id', 'cover_url'];
        $hasManualErrors = collect($manualFieldNames)->contains(fn ($name) => $errors->has($name));
    @endphp

    {{-- Tab Buttons --}}
    <div class="mb-5 flex gap-1 rounded-xl bg-gray-100 p-1" style="width: fit-content">
        <button type="button" data-tab-trigger="csv" class="rounded-lg px-5 py-2 text-sm font-medium transition-all duration-150">📄 CSV Import</button>
        <button type="button" data-tab-trigger="manual" class="rounded-lg px-5 py-2 text-sm font-medium transition-all duration-150">✏️ Manual Input</button>
    </div>

    {{-- CSV Tab --}}
    <div data-tab-panel="csv" class="{{ $hasManualErrors ? 'hidden' : '' }}">
        <x-card>
            <h2 class="section-title mb-3">📄 Upload CSV File</h2>
            <form method="POST" action="{{ route('books.import.preview') }}" enctype="multipart/form-data" class="space-y-3" data-loading-form>
                @csrf
                <input type="file" name="file" class="form-input" required>
                <progress class="hidden h-2 w-full overflow-hidden rounded bg-gray-100 [&::-webkit-progress-bar]:bg-gray-100 [&::-webkit-progress-value]:bg-primary-600 [&::-moz-progress-bar]:bg-primary-600" max="100"></progress>
                <x-button type="submit">Upload & Preview</x-button>
            </form>
        </x-card>

        @if($import_summary)
            <div class="mt-6 animate-slide-up rounded-xl border border-primary-200 bg-primary-50 p-4 text-sm text-primary-800">
                ✅ Imported: {{ $import_summary['imported'] }} | Skipped: {{ $import_summary['skipped'] }}
                @if(!empty($import_summary['skipped_reasons']))
                    <ul class="mt-2 list-inside list-disc text-xs text-primary-900">
                        @foreach($import_summary['skipped_reasons'] as $reason => $count)
                            <li>{{ $reason }} ({{ $count }})</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        @if($preview)
            <x-card class="mt-6">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-bold text-gray-900">Preview</h2>
                    <p class="text-xs text-gray-500">
                        Total: {{ $preview['summary']['total_rows'] }} |
                        Valid: <span class="text-primary-700">{{ $preview['summary']['valid_rows'] }}</span> |
                        Invalid: <span class="text-red-600">{{ $preview['summary']['invalid_rows'] }}</span>
                    </p>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="p-2.5 text-left text-xs font-semibold text-gray-500">Row</th>
                                <th class="p-2.5 text-left text-xs font-semibold text-gray-500">Title</th>
                                <th class="p-2.5 text-left text-xs font-semibold text-gray-500">Author</th>
                                <th class="p-2.5 text-left text-xs font-semibold text-gray-500">Status</th>
                                <th class="p-2.5 text-left text-xs font-semibold text-gray-500">Errors</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preview['analyzed_rows'] as $row)
                                <tr class="border-t border-gray-100 {{ $row['is_valid'] ? 'bg-white' : 'bg-red-50' }}">
                                    <td class="p-2.5">{{ $row['row'] }}</td>
                                    <td class="p-2.5 font-medium">{{ $row['data']['title'] }}</td>
                                    <td class="p-2.5">{{ $row['data']['author'] }}</td>
                                    <td class="p-2.5">
                                        @if($row['is_valid'])
                                            <span class="inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-700">Valid</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Error</span>
                                        @endif
                                    </td>
                                    <td class="p-2.5 text-xs text-red-600">{{ $row['errors'] ? implode('; ', $row['errors']) : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <form method="POST" action="{{ route('books.import.commit') }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="preview_token" value="{{ $preview['preview_token'] }}">
                    <x-button type="submit" variant="success" :disabled="$preview['summary']['valid_rows'] === 0">Confirm Import ({{ $preview['summary']['valid_rows'] }} rows)</x-button>
                </form>
            </x-card>
        @endif
    </div>

    {{-- Manual Tab --}}
    <div data-tab-panel="manual" class="{{ $hasManualErrors ? '' : 'hidden' }}">
        <x-card>
            <h2 class="section-title mb-4">✏️ Manual Book Entry</h2>
            <form method="POST" action="{{ route('books.import.manual') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="isbn-input" class="form-label">ISBN / Scan Input (optional)</label>
                        <div class="flex gap-2">
                            <input id="isbn-input" name="isbn" value="{{ old('isbn') }}" type="text" class="form-input" placeholder="Scan or type ISBN">
                            <button id="isbn-lookup-btn" type="button" class="inline-flex items-center gap-1 rounded-lg border border-border bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-primary-50 hover:text-primary-700">
                                🔍 Fetch
                            </button>
                        </div>
                        @error('isbn')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        <p id="isbn-lookup-status" class="mt-1 text-xs text-gray-500"></p>
                    </div>

                    <div>
                        <label for="title-input" class="form-label">Title</label>
                        <input id="title-input" name="title" value="{{ old('title') }}" type="text" class="form-input" required>
                        @error('title')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="author-input" class="form-label">Author</label>
                        <input id="author-input" name="author" value="{{ old('author') }}" type="text" class="form-input" required>
                        @error('author')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="category-name-input" class="form-label">Category</label>
                        <input list="category-list" id="category-name-input" name="category_name" value="{{ old('category_name') }}" type="text" class="form-input" required placeholder="Type or select category">
                        <datalist id="category-list">
                            @foreach($categories as $category)
                                <option value="{{ $category->name }}"></option>
                            @endforeach
                        </datalist>
                        @error('category_name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="rack-id-input" class="form-label">Rack (optional)</label>
                        <select id="rack-id-input" name="rack_id" class="form-input">
                            <option value="">Auto Assign</option>
                            @foreach($racks as $rack)
                                <option value="{{ $rack->id }}" @selected((string) old('rack_id') === (string) $rack->id)>{{ $rack->name }}</option>
                            @endforeach
                        </select>
                        @error('rack_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="cover-url-input" class="form-label">Cover URL (optional)</label>
                        <input id="cover-url-input" name="cover_url" value="{{ old('cover_url') }}" type="url" class="form-input" placeholder="https://...">
                        @error('cover_url')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <x-button type="submit" variant="success">💾 Save Book</x-button>
            </form>
        </x-card>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const tabTriggers = document.querySelectorAll('[data-tab-trigger]');
            const tabPanels = document.querySelectorAll('[data-tab-panel]');

            const activeTabClass = ['bg-white', 'text-primary-800', 'shadow-sm', 'font-semibold'];
            const inactiveTabClass = ['text-gray-500', 'hover:text-gray-700'];

            const setActiveTab = (name) => {
                tabPanels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.tabPanel !== name);
                });

                tabTriggers.forEach((trigger) => {
                    const isActive = trigger.dataset.tabTrigger === name;
                    activeTabClass.forEach(c => trigger.classList.toggle(c, isActive));
                    inactiveTabClass.forEach(c => trigger.classList.toggle(c, !isActive));
                });
            };

            tabTriggers.forEach((trigger) => {
                trigger.addEventListener('click', () => setActiveTab(trigger.dataset.tabTrigger));
            });

            const manualPanel = document.querySelector('[data-tab-panel="manual"]');
            setActiveTab(manualPanel.classList.contains('hidden') ? 'csv' : 'manual');

            // ISBN Lookup
            const lookupButton = document.getElementById('isbn-lookup-btn');
            const statusNode = document.getElementById('isbn-lookup-status');
            const isbnInput = document.getElementById('isbn-input');
            const titleInput = document.getElementById('title-input');
            const authorInput = document.getElementById('author-input');
            const coverUrlInput = document.getElementById('cover-url-input');
            const csrfToken = '{{ csrf_token() }}';
            const lookupUrl = "{{ route('books.import.isbn-lookup') }}";

            if (!lookupButton) return;

            lookupButton.addEventListener('click', async () => {
                const isbn = isbnInput.value.trim();
                if (isbn === '') {
                    statusNode.textContent = 'ISBN is required for lookup.';
                    statusNode.className = 'mt-1 text-xs text-red-600';
                    return;
                }

                lookupButton.disabled = true;
                lookupButton.innerHTML = '⏳ Fetching...';
                statusNode.textContent = 'Fetching metadata...';
                statusNode.className = 'mt-1 text-xs text-gray-500';

                try {
                    const response = await fetch(lookupUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ isbn }),
                    });

                    if (!response.ok) {
                        const error = await response.json();
                        throw new Error(error.message || 'Lookup failed');
                    }

                    const data = await response.json();
                    titleInput.value = data.title ?? titleInput.value;
                    authorInput.value = data.author ?? authorInput.value;
                    coverUrlInput.value = data.cover_url ?? coverUrlInput.value;

                    statusNode.textContent = `✅ Metadata loaded from ${data.source ?? 'provider'}.`;
                    statusNode.className = 'mt-1 text-xs text-primary-700 font-medium';
                } catch (error) {
                    statusNode.textContent = error.message;
                    statusNode.className = 'mt-1 text-xs text-red-600';
                } finally {
                    lookupButton.disabled = false;
                    lookupButton.innerHTML = '🔍 Fetch';
                }
            });
        })();
    </script>
@endpush
