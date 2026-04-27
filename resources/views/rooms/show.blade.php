@extends('layouts.app')

@section('content')
    @php $accent = $room->accent_classes; @endphp

    {{-- Room Header --}}
    <section class="mb-6 rounded-2xl border border-primary-100 bg-gradient-to-br from-white via-slate-50 to-emerald-50/70 p-6 shadow-sm">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div class="max-w-3xl">
                <a href="{{ route('racks.index') }}" class="inline-flex items-center gap-2 rounded-2xl border border-border bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary-200 hover:text-primary-700">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    Kembali ke Lobby
                </a>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-gray-400">{{ $room->code }}</p>
                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[0.68rem] font-semibold uppercase tracking-[0.18em] {{ $accent['badge'] }}">{{ ucfirst($room->status) }}</span>
                </div>
                <h1 class="mt-2 text-3xl font-black tracking-tight text-gray-900">{{ $room->name }}</h1>
                @if($room->description)
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-gray-600">{{ $room->description }}</p>
                @endif
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.24em] text-gray-400">Rak</p>
                    <p class="mt-2 text-2xl font-black text-gray-900">{{ $room->racks_count }}</p>
                </div>
                <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.24em] text-gray-400">Buku</p>
                    <p class="mt-2 text-2xl font-black text-emerald-700">{{ $room->books_count }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Create Rack Form — TOP position --}}
    <section class="mb-6">
        <x-card>
            <h3 class="section-title">Tambah Rak Baru ke {{ $room->name }}</h3>
            <p class="mt-2 mb-4 text-sm text-gray-500">Rak baru akan otomatis masuk ke ruangan ini.</p>
            <form method="POST" action="{{ route('racks.store') }}" class="grid grid-cols-1 gap-3 xl:grid-cols-[1.5fr_0.6fr_0.6fr_0.6fr_auto]">
                @csrf
                <input type="hidden" name="room_id" value="{{ $room->id }}">
                <input name="name" placeholder="Nama Rak (contoh: Rak A-1)" class="form-input" required>
                <div class="grid grid-cols-2 gap-3">
                    <input name="rows" type="number" min="1" max="26" placeholder="Baris" class="form-input" required>
                    <input name="columns" type="number" min="1" max="10" placeholder="Kolom" class="form-input" required>
                </div>
                <input name="capacity_per_slot" type="number" min="1" max="100" placeholder="Kapasitas/slot" class="form-input" value="50" required>
                <x-button type="submit" variant="success" class="h-full min-h-[3rem] rounded-2xl px-6">Buat Rak</x-button>
            </form>
        </x-card>
    </section>

    {{-- Rack Cards Grid --}}
    <section class="mb-6">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-gray-900">Rak di Ruangan Ini</h2>
        </div>

        @if($racks->isEmpty())
            <div class="rounded-2xl border border-dashed border-border bg-gray-50 px-6 py-12 text-center">
                <p class="text-sm font-semibold text-gray-700">Belum ada rak di ruangan ini.</p>
                <p class="mt-1 text-xs text-gray-500">Gunakan form di atas untuk menambahkan rak pertama.</p>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($racks as $rack)
                    <div class="group relative overflow-hidden rounded-2xl border border-border bg-gradient-to-b from-white to-slate-50 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-primary-200 hover:shadow-lg">
                        {{-- Hover Edit Button --}}
                        <button
                            type="button"
                            x-data
                            @click.stop="$dispatch('open-modal', 'edit-rack-{{ $rack->id }}')"
                            class="absolute right-3 top-3 z-10 flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-white/90 text-gray-400 opacity-0 shadow-sm backdrop-blur-sm transition-all duration-200 group-hover:opacity-100 hover:border-primary-200 hover:text-primary-700"
                            title="Edit Rak"
                        >
                            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                        </button>

                        {{-- Card Header —compact info --}}
                        <div class="flex items-center justify-between gap-3 border-b border-border/50 px-5 py-3">
                            <div class="flex items-center gap-3">
                                <span class="h-2.5 w-2.5 rounded-full {{ $accent['chip'] }}"></span>
                                <h3 class="text-base font-black tracking-tight text-gray-900">{{ $rack->name }}</h3>
                            </div>
                            <div class="flex items-center gap-3 text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-gray-400">
                                <span>{{ $rack->rows }}×{{ $rack->columns }}</span>
                                <span>{{ $rack->books_count }} buku</span>
                            </div>
                        </div>

                        {{-- Tall Grid Preview — click a slot to set its column category --}}
                        <div class="px-4 py-4" style="min-height: 16rem;">
                            @if($rack->grid_preview)
                                <div class="grid gap-1.5 h-full" style="grid-template-columns: repeat({{ $rack->columns }}, minmax(0, 1fr));">
                                    @foreach($rack->grid_preview as $cell)
                                        @php
                                            $slotCategoryId = $cell['slot_category_id'] ?? null;
                                            $slotCategoryName = $slotCategoryId ? ($categories[$slotCategoryId]->name ?? null) : null;
                                        @endphp
                                        <button
                                            type="button"
                                            x-data
                                            @click="$dispatch('open-slot-category', { rack: {{ $rack->id }}, code: '{{ $cell['code'] }}', category: '{{ $slotCategoryId ?? '' }}' })"
                                            data-room-slot
                                            data-rack-id="{{ $rack->id }}"
                                            data-position-code="{{ $cell['code'] }}"
                                            class="flex min-h-[2.85rem] flex-col items-center justify-center rounded-lg border px-1 py-2 text-center transition hover:border-primary-300 hover:bg-primary-50 {{ $cell['occupied'] ? 'border-primary-200 bg-primary-50/80' : 'border-gray-200 bg-gray-50/50' }}"
                                            title="Klik untuk atur kategori slot {{ $cell['code'] }}"
                                        >
                                            <span data-slot-label class="max-w-full truncate text-[8px] font-bold uppercase leading-tight {{ $slotCategoryName ? 'text-primary-700' : 'text-gray-400' }}">{{ $slotCategoryName ?: $cell['code'] }}</span>
                                            <span class="mt-0.5 text-[10px] font-black {{ $cell['occupied'] ? 'text-primary-800' : 'text-gray-400' }}">{{ $cell['count'] }} buku</span>
                                        </button>
                                    @endforeach
                                </div>
                            @else
                                <div class="flex h-full items-center justify-center text-sm text-gray-400">Grid belum tersedia</div>
                            @endif
                        </div>

                        {{-- Footer --}}
                        <div class="flex items-center justify-between border-t border-border bg-gray-50/60 px-5 py-3 text-xs">
                            <span class="text-gray-500">{{ $rack->books_count }} buku terpetakan</span>
                            <a href="{{ route('racks.show', ['rack' => $rack, 'from_room' => $room->id]) }}" class="font-semibold text-primary-700 transition hover:text-primary-800 group-hover:translate-x-1">Buka Rak →</a>
                        </div>
                    </div>

                    {{-- Slot Category Modal (per-rack) --}}
                    <div
                        x-data="{ show: false, code: '', category: '' }"
                        x-show="show"
                        x-on:open-slot-category.window="if ($event.detail.rack === {{ $rack->id }}) { show = true; code = $event.detail.code; category = $event.detail.category || ''; }"
                        x-on:keydown.escape.window="show = false"
                        style="display: none;"
                        class="fixed inset-0 z-50 overflow-y-auto"
                    >
                        <div class="flex min-h-screen items-center justify-center p-4">
                            <div x-show="show" x-transition.opacity class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm" @click="show = false"></div>
                            <div x-show="show" x-transition class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                                <form class="room-slot-category-form" data-rack-id="{{ $rack->id }}">
                                    <h3 class="mb-1 text-lg font-bold text-gray-900">Kategori Slot <span x-text="code"></span></h3>
                                    <p class="mb-5 text-sm text-gray-500">Kategori hanya disimpan untuk slot yang sedang dipilih.</p>
                                    <input type="hidden" name="position_code" :value="code">
                                    <label class="form-label">Kategori</label>
                                    <select name="category_id" class="form-input" x-model="category">
                                        <option value="">Tanpa kategori</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="mt-6 flex justify-end gap-3">
                                        <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                                        <x-button type="submit" variant="success" @click="show = false">Simpan</x-button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Edit Rack Modal (per-rack) --}}
                    <div x-data="{ show: false }" x-show="show" x-on:open-modal.window="if ($event.detail === 'edit-rack-{{ $rack->id }}') show = true" x-on:keydown.escape.window="show = false" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
                        <div class="flex min-h-screen items-center justify-center p-4">
                            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm" @click="show = false"></div>
                            <div x-show="show" x-transition class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                                <form action="{{ route('racks.update', $rack) }}" method="POST">
                                    @csrf @method('PUT')
                                    <h3 class="text-lg font-bold text-gray-900 mb-5">Edit Rak — {{ $rack->name }}</h3>
                                    <div class="space-y-4">
                                        <div><label class="form-label">Nama *</label><input type="text" name="name" value="{{ $rack->name }}" class="form-input" required></div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div><label class="form-label">Baris (A-Z)</label><input name="rows" type="number" min="1" max="26" value="{{ $rack->rows }}" class="form-input" required></div>
                                            <div><label class="form-label">Kolom (1-10)</label><input name="columns" type="number" min="1" max="10" value="{{ $rack->columns }}" class="form-input" required></div>
                                        </div>
                                        <div class="grid grid-cols-1 gap-4">
                                            <div><label class="form-label">Kapasitas/Slot</label><input name="capacity_per_slot" type="number" min="1" max="100" value="{{ $rack->capacity_per_slot ?? 50 }}" class="form-input" required></div>
                                        </div>
                                        <div>
                                            <label class="form-label">Pindah ke Ruangan</label>
                                            <select name="room_id" class="form-input">
                                                @foreach($allRooms as $r)
                                                    <option value="{{ $r->id }}" @selected($rack->room_id === $r->id)>{{ $r->code }} — {{ $r->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-6 flex items-center justify-between">
                                        <button type="button" @click="if(confirm('Yakin hapus rak ini? Semua buku di dalamnya akan menjadi unassigned.')) { $el.closest('form').action='{{ route('racks.destroy', $rack) }}'; $el.closest('form').querySelector('[name=_method]').value='DELETE'; $el.closest('form').submit(); }" class="text-sm font-medium text-red-600 hover:text-red-700 transition">Hapus Rak</button>
                                        <div class="flex gap-3">
                                            <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                                            <x-button type="submit" variant="success">Simpan</x-button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <script>
        (() => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const forms = document.querySelectorAll('.room-slot-category-form');

            forms.forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const rackId = form.dataset.rackId;
                    const payload = {
                        position_code: form.querySelector('[name="position_code"]')?.value || '',
                        category_id: form.querySelector('[name="category_id"]')?.value || '',
                    };

                    const response = await fetch(`/racks/${rackId}/column-category`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();
                    const slotButton = document.querySelector(`[data-room-slot][data-rack-id="${rackId}"][data-position-code="${data.position_code}"]`);
                    if (!slotButton) {
                        return;
                    }

                    const label = slotButton.querySelector('[data-slot-label]');
                    if (label) {
                        label.textContent = data.category_name || data.position_code;
                        label.className = `max-w-full truncate text-[8px] font-bold uppercase leading-tight ${data.category_name ? 'text-primary-700' : 'text-gray-400'}`;
                    }
                });
            });
        })();
    </script>
@endsection
