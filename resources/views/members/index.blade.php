@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="page-title">Anggota Perpustakaan</h1>
            <p class="page-subtitle">Kelola data siswa, guru, dan staff SMK Mustaqbal.</p>
        </div>
        <x-button type="button" variant="success" x-data @click="$dispatch('open-modal', 'add-member-modal')">
            + Tambah Anggota
        </x-button>
    </div>

    {{-- Stats Cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-card class="bg-gradient-to-br from-white to-primary-50">
            <p class="text-sm font-semibold text-gray-500">Total Anggota</p>
            <h2 class="mt-2 text-3xl font-black text-primary-700">{{ $stats['total'] }}</h2>
        </x-card>
        <x-card>
            <p class="text-sm font-semibold text-gray-500">Status Aktif</p>
            <h2 class="mt-2 text-3xl font-black text-emerald-600">{{ $stats['active'] }}</h2>
        </x-card>
        <x-card>
            <p class="text-sm font-semibold text-gray-500">Siswa</p>
            <h2 class="mt-2 text-3xl font-black text-gray-900">{{ $stats['siswa'] }}</h2>
        </x-card>
        <x-card>
            <p class="text-sm font-semibold text-gray-500">Guru & Staff</p>
            <h2 class="mt-2 text-3xl font-black text-gray-900">{{ $stats['guru'] }}</h2>
        </x-card>
    </div>

    {{-- Filters --}}
    <x-card class="mb-6">
        <form method="GET" action="{{ route('members.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Cari nama, NIS, atau kelas..." class="form-input">
            </div>
            <select name="type" class="form-select w-32">
                <option value="">Semua Tipe</option>
                <option value="siswa" @selected($filters['type'] === 'siswa')>Siswa</option>
                <option value="guru" @selected($filters['type'] === 'guru')>Guru</option>
                <option value="staff" @selected($filters['type'] === 'staff')>Staff</option>
            </select>
            <select name="status" class="form-select w-32">
                <option value="">Status</option>
                <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
                <option value="inactive" @selected($filters['status'] === 'inactive')>Tidak Aktif</option>
            </select>
            <x-button type="submit" variant="secondary">Filter</x-button>
            @if(array_filter($filters))
                <a href="{{ route('members.index') }}" class="text-sm text-gray-500 hover:text-red-500">Reset</a>
            @endif
        </form>
    </x-card>

    {{-- Data Table --}}
    <x-card class="overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50/50 text-xs uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-6 py-4 font-semibold">NIS/Nama</th>
                        <th class="px-6 py-4 font-semibold">Tipe/Kelas</th>
                        <th class="px-6 py-4 font-semibold text-center">Pinjam Aktif</th>
                        <th class="px-6 py-4 font-semibold text-center">Total Pinjam</th>
                        <th class="px-6 py-4 font-semibold text-center">Status</th>
                        <th class="px-6 py-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($members as $member)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900">{{ $member->name }}</div>
                                <div class="text-xs text-gray-500">{{ $member->nis }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-widest text-gray-600">{{ $member->type }}</span>
                                @if($member->class)
                                    <div class="mt-1 text-xs font-medium text-gray-700">{{ $member->class }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($member->active_borrowings_count > 0)
                                    <span class="inline-flex h-6 min-w-[24px] items-center justify-center rounded-full bg-amber-100 px-2 text-xs font-bold text-amber-700">{{ $member->active_borrowings_count }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="font-medium text-gray-700">{{ $member->borrowings_count }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($member->status === 'active')
                                    <span class="rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700">Aktif</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-gray-500">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('members.show', $member) }}" class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium text-primary-700 transition hover:bg-primary-50">
                                    Detail
                                </a>
                                <button type="button" x-data @click="$dispatch('open-modal', 'edit-member-modal-{{ $member->id }}')" class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-100">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                Belum ada data anggota yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-border bg-gray-50/30 px-6 py-3">
            {{ $members->links() }}
        </div>
    </x-card>

    {{-- Modal Add Member --}}
    <div x-data="{ show: false }" x-show="show" x-on:open-modal.window="if ($event.detail === 'add-member-modal') show = true" x-on:close-modal.window="if ($event.detail === 'add-member-modal') show = false" x-on:keydown.escape.window="show = false" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" @click="show = false" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block w-full max-w-lg transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
                <form action="{{ route('members.store') }}" method="POST">
                    @csrf
                    <div class="bg-white px-6 pb-6 pt-5">
                        <div class="mb-5 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-900" id="modal-title">Tambah Anggota Baru</h3>
                            <button type="button" @click="show = false" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-500">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="form-label">NIS / NIP *</label>
                                <input type="text" name="nis" class="form-input" required>
                            </div>
                            <div>
                                <label class="form-label">Nama Lengkap *</label>
                                <input type="text" name="name" class="form-input" required>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Tipe *</label>
                                    <select name="type" class="form-input" required>
                                        <option value="siswa">Siswa</option>
                                        <option value="guru">Guru</option>
                                        <option value="staff">Staff</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Kelas / Jabatan</label>
                                    <input type="text" name="class" class="form-input" placeholder="Contoh: XII RPL 1">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">No. Telepon / WA</label>
                                <input type="text" name="phone" class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-input h-20"></textarea>
                            </div>
                            <input type="hidden" name="status" value="active">
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 rounded-b-2xl">
                        <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                        <x-button type="submit" variant="success">Simpan Anggota</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modals Edit Member --}}
    @foreach($members as $member)
        <div x-data="{ show: false }" x-show="show" x-on:open-modal.window="if ($event.detail === 'edit-member-modal-{{ $member->id }}') show = true" x-on:close-modal.window="if ($event.detail === 'edit-member-modal-{{ $member->id }}') show = false" x-on:keydown.escape.window="show = false" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" @click="show = false" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
                <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block w-full max-w-lg transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
                    <form action="{{ route('members.update', $member) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="bg-white px-6 pb-6 pt-5">
                            <div class="mb-5 flex items-center justify-between">
                                <h3 class="text-lg font-bold text-gray-900" id="modal-title">Edit Anggota</h3>
                                <button type="button" @click="show = false" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-500">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="form-label">NIS / NIP *</label>
                                    <input type="text" name="nis" value="{{ $member->nis }}" class="form-input" required>
                                </div>
                                <div>
                                    <label class="form-label">Nama Lengkap *</label>
                                    <input type="text" name="name" value="{{ $member->name }}" class="form-input" required>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="form-label">Tipe *</label>
                                        <select name="type" class="form-input" required>
                                            <option value="siswa" @selected($member->type === 'siswa')>Siswa</option>
                                            <option value="guru" @selected($member->type === 'guru')>Guru</option>
                                            <option value="staff" @selected($member->type === 'staff')>Staff</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Kelas / Jabatan</label>
                                        <input type="text" name="class" value="{{ $member->class }}" class="form-input" placeholder="Contoh: XII RPL 1">
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label">No. Telepon / WA</label>
                                    <input type="text" name="phone" value="{{ $member->phone }}" class="form-input">
                                </div>
                                <div>
                                    <label class="form-label">Status *</label>
                                    <select name="status" class="form-input" required>
                                        <option value="active" @selected($member->status === 'active')>Aktif</option>
                                        <option value="inactive" @selected($member->status === 'inactive')>Tidak Aktif</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Alamat</label>
                                    <textarea name="address" class="form-input h-20">{{ $member->address }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-4 flex justify-between rounded-b-2xl">
                            <div>
                                @if($member->active_borrowings_count === 0)
                                    <button type="button" class="text-sm font-semibold text-red-600 hover:text-red-700 hover:underline" onclick="if(confirm('Hapus anggota ini?')) document.getElementById('delete-member-{{ $member->id }}').submit();">Hapus Anggota</button>
                                @endif
                            </div>
                            <div class="flex gap-3">
                                <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                                <x-button type="submit" variant="success">Simpan Perubahan</x-button>
                            </div>
                        </div>
                    </form>
                    <form id="delete-member-{{ $member->id }}" action="{{ route('members.destroy', $member) }}" method="POST" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection
