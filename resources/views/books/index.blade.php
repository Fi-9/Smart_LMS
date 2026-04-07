@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="page-title">Books</h1>
            <p class="page-subtitle">Master-detail view — klik buku di panel kiri untuk melihat detail.</p>
        </div>
        <a href="{{ route('books.import') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-800 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-primary-700">
            <span>📥</span> Import
        </a>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-10"
         x-data="{
             selectedBookId: {{ $selected_book?->id ?? 'null' }},
             isLoading: false,
             async loadPanel(url, bookId) {
                 this.selectedBookId = bookId;
                 this.isLoading = true;
                 
                 const newUrl = new URL(window.location);
                 newUrl.searchParams.set('selected_book_id', bookId);
                 window.history.replaceState({}, '', newUrl);

                 try {
                     const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
                     if (!response.ok) throw new Error();
                     document.getElementById('detail-panel').innerHTML = await response.text();
                     
                     // Re-initialize any scripts within the newly loaded HTML
                     Array.from(document.getElementById('detail-panel').querySelectorAll('script')).forEach(oldScript => {
                        const newScript = document.createElement('script');
                        Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                     });
                 } catch (e) {
                     document.getElementById('detail-panel').innerHTML = '<div class=\'rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700\'>Failed to load book detail.</div>';
                 } finally {
                     this.isLoading = false;
                 }
             }
         }"
    >
        {{-- LEFT PANEL: Book List (40%) --}}
        <section class="xl:col-span-4">
            <x-card>
                <form method="GET" action="{{ route('books.index') }}" class="space-y-3">
                    <input name="search" value="{{ $filters['search'] }}" type="text" placeholder="🔍 Search title or author..." class="form-input">
                    <div class="grid grid-cols-3 gap-2">
                        <select name="category_id" class="form-input">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) $filters['category_id'] === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <select name="rack_id" class="form-input">
                            <option value="">All Racks</option>
                            @foreach($racks as $rack)
                                <option value="{{ $rack->id }}" @selected((string) $filters['rack_id'] === (string) $rack->id)>{{ $rack->name }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="form-input">
                            <option value="">All Status</option>
                            <option value="available" @selected($filters['status'] === 'available')>Available</option>
                            <option value="borrowed" @selected($filters['status'] === 'borrowed')>Borrowed</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-button type="submit">Apply</x-button>
                        <a href="{{ route('books.index') }}" class="inline-flex items-center rounded-lg border border-border bg-white px-4 py-2 text-sm text-gray-600 transition hover:bg-gray-50">Reset</a>
                    </div>
                </form>
            </x-card>

            <div class="mt-4 max-h-[calc(100vh-20rem)] space-y-2 overflow-y-auto pr-1">
                @forelse($books as $book)
                    <button
                        type="button"
                        @click="loadPanel('{{ route('books.web.panel', $book) }}', {{ $book->id }})"
                        class="group w-full rounded-xl border bg-white p-3 text-left shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:shadow-md"
                        :class="selectedBookId === {{ $book->id }} ? 'border-primary-400 ring-2 ring-primary-200' : 'border-border hover:border-primary-300'"
                    >
                        <div class="flex items-start gap-3">
                            <img
                                src="{{ $book->cover_url ?: '/images/default-book-cover.svg' }}"
                                alt="{{ $book->title }}"
                                class="h-16 w-12 rounded-lg border border-gray-200 object-cover"
                            >
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-gray-900">{{ $book->title }}</p>
                                <p class="truncate text-xs text-gray-500">{{ $book->author }}</p>
                                <div class="mt-1.5 flex items-center gap-1">
                                    @if($book->rack)
                                        <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-600">{{ $book->rack->name }} {{ $book->position_code }}</span>
                                    @endif
                                </div>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1">
                                    <x-badge :status="$book->status->value" />
                                    @if(!$book->isAssigned())
                                        <x-badge status="unassigned" />
                                    @endif
                                </div>
                            </div>
                            {{-- Quick Actions (always visible, full color on hover) --}}
                            <div class="flex shrink-0 flex-col gap-1 opacity-40 transition-opacity duration-200 group-hover:opacity-100">
                                @if($book->rack_id)
                                    <a href="{{ route('racks.show', $book->rack_id) }}" title="Move to rack" class="rounded-md bg-gray-100 p-1.5 text-xs text-gray-600 transition hover:bg-primary-100 hover:text-primary-700" onclick="event.stopPropagation()">📍</a>
                                @endif
                                <a href="{{ route('qr.print', ['selected_ids' => [$book->id]]) }}" target="_blank" title="Print QR" class="rounded-md bg-gray-100 p-1.5 text-xs text-gray-600 transition hover:bg-primary-100 hover:text-primary-700" onclick="event.stopPropagation()">🔳</a>
                                <a href="{{ route('books.web.show', $book->id) }}" title="Edit" class="rounded-md bg-gray-100 p-1.5 text-xs text-gray-600 transition hover:bg-primary-100 hover:text-primary-700" onclick="event.stopPropagation()">✏️</a>
                            </div>
                        </div>
                    </button>
                @empty
                    <x-card>
                        <div class="py-6 text-center">
                            <p class="text-3xl">📚</p>
                            <p class="mt-2 text-sm font-medium text-gray-700">Belum ada buku</p>
                            <p class="mt-1 text-xs text-gray-500">Upload CSV atau tambah manual untuk mulai.</p>
                            <a href="{{ route('books.import') }}" class="mt-3 inline-flex items-center gap-1 rounded-lg bg-primary-800 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                                <span>📥</span> Import Buku
                            </a>
                        </div>
                    </x-card>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $books->appends(request()->query())->links() }}
            </div>
        </section>

        {{-- RIGHT PANEL: Book Detail (60%) --}}
        <section class="xl:col-span-6">
            <div id="detail-panel" class="sticky top-6 transition-opacity duration-150" :class="isLoading ? 'opacity-50' : 'opacity-100'">
                @if($selected_book)
                    @include('books.partials.detail_panel', [
                        'book' => $selected_book,
                        'rack_mini_map' => $selected_book_rack_mini_map,
                        'compact_description' => true,
                    ])
                @else
                    <x-card>
                        <div class="py-12 text-center">
                            <p class="text-4xl">👈</p>
                            <p class="mt-3 text-sm font-medium text-gray-700">Pilih buku dari panel kiri</p>
                            <p class="mt-1 text-xs text-gray-500">Detail buku akan tampil di sini.</p>
                        </div>
                    </x-card>
                @endif
            </div>
        </section>
    </div>
@endsection

