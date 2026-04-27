@extends('layouts.app')

@section('content')
    {{-- Hero Stats --}}
    <section class="mb-6 rounded-2xl border border-primary-100 bg-gradient-to-br from-white via-slate-50 to-emerald-50/70 p-6 shadow-sm">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-primary-700">Library Map</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight text-gray-900">Peta Digital Perpustakaan</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-gray-600">Kelola ruangan, rak, dan slot buku secara visual. Klik ruangan untuk masuk dan melihat rak di dalamnya.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.24em] text-gray-400">Ruangan</p>
                    <p class="mt-2 text-2xl font-black text-gray-900">{{ $stats['rooms'] }}</p>
                </div>
                <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.24em] text-gray-400">Rak</p>
                    <p class="mt-2 text-2xl font-black text-gray-900">{{ $stats['racks'] }}</p>
                </div>
                <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.24em] text-gray-400">Buku Terpetakan</p>
                    <p class="mt-2 text-2xl font-black text-emerald-700">{{ $stats['books_mapped'] }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Room Cards --}}
    <section class="mb-6">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-gray-900">Daftar Ruangan</h2>
            <button type="button" x-data @click="$dispatch('open-modal', 'add-room-modal')" class="rounded-xl bg-primary-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-600">+ Tambah Ruangan</button>
        </div>

        @if($rooms->isEmpty() && $unassigned_racks->isEmpty())
            <x-card class="py-12 text-center">
                <p class="text-sm font-semibold text-gray-700">Belum ada ruangan atau rak.</p>
                <p class="mt-1 text-xs text-gray-500">Buat ruangan dulu, lalu tambahkan rak ke dalamnya.</p>
            </x-card>
        @endif

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($rooms as $room)
                @php $accent = $room->accent_classes; @endphp
                <article class="group relative overflow-hidden rounded-2xl border border-border bg-surface shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                    {{-- Edit Button --}}
                    <button
                        type="button"
                        x-data
                        @click="$dispatch('open-modal', 'edit-room-{{ $room->id }}')"
                        class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-xl border border-border bg-white/90 text-gray-500 opacity-0 shadow-sm backdrop-blur-sm transition group-hover:opacity-100 hover:border-primary-200 hover:text-primary-700"
                        title="Edit Ruangan"
                    >
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                    </button>

                    {{-- Card Content --}}
                    <div class="p-5">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br {{ $accent['soft'] }} shadow-sm">
                                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-gray-400">{{ $room->code }}</p>
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[0.68rem] font-semibold uppercase tracking-[0.18em] {{ $accent['badge'] }}">{{ ucfirst($room->status) }}</span>
                                </div>
                                <h3 class="mt-1 text-xl font-black tracking-tight text-gray-900">{{ $room->name }}</h3>
                            </div>
                        </div>

                        @if($room->description)
                            <p class="mt-3 text-sm leading-6 text-gray-600 line-clamp-2">{{ $room->description }}</p>
                        @endif

                        {{-- Mini Stats --}}
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-gray-50 px-3 py-2.5">
                                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-gray-400">Rak</p>
                                <p class="mt-1 text-lg font-black text-gray-900">{{ $room->racks_count }}</p>
                            </div>
                            <div class="rounded-xl bg-gray-50 px-3 py-2.5">
                                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-gray-400">Buku</p>
                                <p class="mt-1 text-lg font-black text-gray-900">{{ $room->books_count }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Enter Button --}}
                    <a href="{{ route('rooms.show', $room) }}" class="flex items-center justify-center gap-2 border-t border-border bg-gradient-to-r from-gray-50 to-white px-5 py-3.5 text-sm font-semibold text-primary-700 transition hover:from-primary-50 hover:to-emerald-50">
                        Masuk ke Ruangan
                        <svg viewBox="0 0 24 24" class="h-4 w-4 transition group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </article>

                {{-- Edit Room Modal (per-room) --}}
                <div x-data="{ show: false }" x-show="show" x-on:open-modal.window="if ($event.detail === 'edit-room-{{ $room->id }}') show = true" x-on:keydown.escape.window="show = false" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm" @click="show = false"></div>
                        <div x-show="show" x-transition class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                            <form action="{{ route('rooms.update', $room) }}" method="POST">
                                @csrf @method('PUT')
                                <h3 class="text-lg font-bold text-gray-900 mb-5">Edit Ruangan — {{ $room->code }}</h3>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div><label class="form-label">Nama *</label><input type="text" name="name" value="{{ $room->name }}" class="form-input" required></div>
                                        <div><label class="form-label">Kode *</label><input type="text" name="code" value="{{ $room->code }}" class="form-input" required></div>
                                    </div>
                                    <div><label class="form-label">Deskripsi</label><textarea name="description" class="form-input h-20">{{ $room->description }}</textarea></div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="form-label">Status *</label>
                                            <select name="status" class="form-input" required>
                                                <option value="active" @selected($room->status === 'active')>Aktif</option>
                                                <option value="preview" @selected($room->status === 'preview')>Preview</option>
                                                <option value="inactive" @selected($room->status === 'inactive')>Nonaktif</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">Warna Aksen *</label>
                                            <select name="accent" class="form-input" required>
                                                <option value="emerald" @selected($room->accent === 'emerald')>🟢 Emerald</option>
                                                <option value="sky" @selected($room->accent === 'sky')>🔵 Sky</option>
                                                <option value="amber" @selected($room->accent === 'amber')>🟡 Amber</option>
                                                <option value="rose" @selected($room->accent === 'rose')>🔴 Rose</option>
                                                <option value="violet" @selected($room->accent === 'violet')>🟣 Violet</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-6 flex items-center justify-between">
                                    <button type="button" @click="if(confirm('Yakin hapus ruangan ini?')) { $el.closest('form').action='{{ route('rooms.destroy', $room) }}'; $el.closest('form').querySelector('[name=_method]').value='DELETE'; $el.closest('form').submit(); }" class="text-sm font-medium text-red-600 hover:text-red-700 transition">Hapus Ruangan</button>
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

        {{-- Unassigned racks --}}
        @if($unassigned_racks->isNotEmpty())
            <div class="mt-6 rounded-2xl border border-dashed border-amber-300 bg-amber-50/30 p-5 shadow-sm">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Rak Belum Ditugaskan</h3>
                        <p class="text-xs text-gray-600">{{ $unassigned_racks->count() }} rak belum masuk ke ruangan manapun.</p>
                    </div>
                </div>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($unassigned_racks as $rack)
                        <div class="rounded-xl border border-dashed border-amber-300 bg-amber-50 p-4 shadow-sm">
                            <h4 class="text-base font-bold text-gray-900">{{ $rack->name }}</h4>
                            <p class="mt-1 text-xs text-gray-500">{{ $rack->rows }}×{{ $rack->columns }} grid · {{ $rack->books_count }} buku</p>
                            <div class="mt-2 flex items-center justify-between text-xs">
                                <span class="font-semibold text-amber-600">Perlu dimasukkan ke Ruangan</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    {{-- Add Room Modal --}}
    <div x-data="{ show: false }" x-show="show" x-on:open-modal.window="if ($event.detail === 'add-room-modal') show = true" x-on:keydown.escape.window="show = false" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm" @click="show = false"></div>
            <div x-show="show" x-transition class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                <form action="{{ route('rooms.store') }}" method="POST">
                    @csrf
                    <h3 class="text-lg font-bold text-gray-900 mb-5">Tambah Ruangan Baru</h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="form-label">Nama *</label><input type="text" name="name" class="form-input" required placeholder="Ruang Referensi"></div>
                            <div><label class="form-label">Kode *</label><input type="text" name="code" class="form-input" required placeholder="RM-01"></div>
                        </div>
                        <div><label class="form-label">Deskripsi</label><textarea name="description" class="form-input h-20" placeholder="Deskripsi singkat ruangan..."></textarea></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Status *</label>
                                <select name="status" class="form-input" required>
                                    <option value="active">Aktif</option>
                                    <option value="preview">Preview</option>
                                    <option value="inactive">Nonaktif</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Warna Aksen *</label>
                                <select name="accent" class="form-input" required>
                                    <option value="emerald">🟢 Emerald</option>
                                    <option value="sky">🔵 Sky</option>
                                    <option value="amber">🟡 Amber</option>
                                    <option value="rose">🔴 Rose</option>
                                    <option value="violet">🟣 Violet</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                        <x-button type="submit" variant="success">Simpan Ruangan</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
