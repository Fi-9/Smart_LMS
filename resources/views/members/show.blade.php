@extends('layouts.app')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('members.index') }}" class="mb-2 inline-flex items-center text-sm font-medium text-gray-500 hover:text-primary-700">
                <svg viewBox="0 0 24 24" class="mr-1 h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                Kembali ke Daftar
            </a>
            <h1 class="page-title">Profil Anggota</h1>
        </div>
        <button type="button" x-data @click="$dispatch('open-modal', 'edit-member-modal-{{ $member->id }}')" class="rounded-xl border border-border bg-surface px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
            Edit Profil
        </button>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Profile Card --}}
        <div class="xl:col-span-1">
            <x-card class="sticky top-24 relative overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-32 bg-gradient-to-br from-primary-600 to-primary-800"></div>
                <div class="relative pt-16 text-center">
                    <span class="mx-auto flex h-24 w-24 items-center justify-center rounded-full border-4 border-white bg-primary-100 text-3xl font-black text-primary-800 shadow-sm">
                        {{ strtoupper(substr($member->name, 0, 2)) }}
                    </span>
                    <h2 class="mt-4 text-xl font-bold text-gray-900">{{ $member->name }}</h2>
                    <p class="text-sm font-semibold text-gray-500">{{ $member->nis }}</p>
                    <div class="mt-3 flex justify-center gap-2">
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-gray-600">{{ $member->type }}</span>
                        @if($member->class)
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">{{ $member->class }}</span>
                        @endif
                    </div>
                </div>

                <div class="mt-8 space-y-4 border-t border-border pt-6">
                    <div class="flex items-center gap-3 text-sm text-gray-600">
                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        {{ $member->phone ?? 'Tidak ada nomor telepon' }}
                    </div>
                    <div class="flex items-center gap-3 text-sm text-gray-600">
                        <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        {{ $member->email ?? 'Tidak ada email' }}
                    </div>
                    <div class="flex items-start gap-3 text-sm text-gray-600">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span class="leading-relaxed">{{ $member->address ?? 'Tidak ada alamat' }}</span>
                    </div>
                </div>

                <div class="mt-6 border-t border-border pt-6">
                    <p class="text-xs text-gray-500">Terdaftar pada: {{ $member->created_at->format('d M Y') }}</p>
                </div>
            </x-card>
        </div>

        {{-- Right Content --}}
        <div class="xl:col-span-2">
            <div class="grid grid-cols-3 gap-4 mb-6">
                <x-card class="bg-gray-50/50">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">Total Pinjam</p>
                    <p class="mt-2 text-2xl font-black text-gray-900">{{ $member->borrowings_count }}</p>
                </x-card>
                <x-card class="bg-primary-50/50 border-primary-100">
                    <p class="text-xs font-semibold uppercase tracking-widest text-primary-700">Sedang Dipinjam</p>
                    <p class="mt-2 text-2xl font-black text-primary-800">{{ $member->active_borrowings_count }}</p>
                </x-card>
                <x-card class="bg-red-50/50 border-red-100">
                    <p class="text-xs font-semibold uppercase tracking-widest text-red-700">Pernah Terlambat</p>
                    <p class="mt-2 text-2xl font-black text-red-800">{{ $member->late_borrowings_count }}</p>
                </x-card>
            </div>

            <x-card class="p-0">
                <div class="border-b border-border px-6 py-4">
                    <h3 class="font-bold text-gray-900">Riwayat Peminjaman</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50/50 text-xs uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-4 font-semibold">Buku</th>
                                <th class="px-6 py-4 font-semibold">Tgl Pinjam</th>
                                <th class="px-6 py-4 font-semibold">Tgl Kembali</th>
                                <th class="px-6 py-4 font-semibold text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($borrowingHistory as $borrow)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('books.web.show', $borrow->book) }}" class="font-bold text-gray-900 hover:text-primary-700">
                                            {{ $borrow->book->title }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $borrow->borrowed_at->format('d M Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($borrow->returned_at)
                                            {{ $borrow->returned_at->format('d M Y') }}
                                        @else
                                            <span class="text-gray-400">Belum</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($borrow->status->value === 'borrowed')
                                            @if($borrow->due_date->isPast())
                                                <span class="rounded-full bg-red-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-red-700">Late</span>
                                            @else
                                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-blue-700">Active</span>
                                            @endif
                                        @elseif($borrow->status->value === 'late')
                                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-700">Late Return</span>
                                        @elseif($borrow->status->value === 'returned')
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-emerald-700">Returned</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                        Belum ada riwayat peminjaman.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($borrowingHistory->hasPages())
                    <div class="border-t border-border bg-gray-50/30 px-6 py-3">
                        {{ $borrowingHistory->links() }}
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    {{-- Modal Edit (Copied from index for consistency) --}}
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
                            <h3 class="text-lg font-bold text-gray-900" id="modal-title">Edit Profil Anggota</h3>
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
                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 rounded-b-2xl">
                        <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                        <x-button type="submit" variant="success">Simpan Perubahan</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
