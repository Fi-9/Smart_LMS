# Smart Library - Change Log

## Aturan Pencatatan
- Setiap perubahan kode wajib ditambahkan ke file ini.
- Format minimal per entri:
  - Tanggal
  - Ruang lingkup
  - File yang berubah
  - Dampak/tujuan

## 2026-03-28

### Audit & Dokumentasi Status
- Menambahkan audit implementasi terbaru.
- File:
  - `docs/implementation_audit_2026-03-28.md`
  - `docs/change_log.md`
- Dampak:
  - Ada baseline tertulis tentang fitur yang sudah selesai dan yang belum.
  - Ada standar dokumentasi agar setiap perubahan berikutnya tercatat konsisten.

### Konfigurasi DB Dikembalikan ke MySQL (sesuai request user)
- Memindahkan `.env` dari SQLite kembali ke MySQL.
- File:
  - `.env`
- Dampak:
  - Konfigurasi runtime kembali mengikuti keputusan final MySQL.
  - Tetap membutuhkan MySQL service aktif untuk menjalankan aplikasi.

### UI/UX Upgrade: Books, Rack Grid, QR Preview, Dashboard
- Refactor tampilan books dari table ke card grid dengan cover + status + unassigned marker.
- Menambahkan halaman detail buku internal admin.
- Meningkatkan visual rack ke format row/column (seat-map style) dengan legend.
- Menambahkan empty-state informatif dan recent preview pada halaman QR ketika hasil filter kosong.
- Menyegarkan tampilan dashboard agar lebih modern.
- Menambahkan fallback image cover default.
- File:
  - `routes/web.php`
  - `app/Http/Controllers/BookPublicController.php`
  - `app/Http/Controllers/Web/BookPageController.php`
  - `app/Http/Controllers/Web/QrStickerPageController.php`
  - `app/Http/Controllers/Web/RackPageController.php`
  - `app/Services/RackService.php`
  - `resources/views/books/index.blade.php`
  - `resources/views/books/show.blade.php`
  - `resources/views/books/public_show.blade.php`
  - `resources/views/racks/show.blade.php`
  - `resources/views/qr/index.blade.php`
  - `resources/views/dashboard/index.blade.php`
  - `public/images/default-book-cover.svg`
  - `updateUiUx.md`
- Dampak:
  - UI lebih visual, responsif, dan cepat dipahami user.
  - Flow klik buku ke detail sekarang jelas.
  - Rack placement lebih dekat dengan target UX "cinema style".

### Phase 1 - Master Detail Books (No Full Reload)
- Mengubah halaman books menjadi split layout master-detail (kiri list, kanan detail).
- Menambahkan endpoint panel detail agar detail buku dimuat via AJAX tanpa reload halaman.
- Menambahkan partial view detail panel modular untuk dipakai oleh request dinamis.
- File:
  - `app/Http/Controllers/Web/BookPageController.php`
  - `routes/web.php`
  - `resources/views/books/index.blade.php`
  - `resources/views/books/partials/detail_panel.blade.php`
- Dampak:
  - UX list->detail lebih cepat dan modern.
  - Controller tetap tipis, business logic tetap di service.
  - Struktur view lebih modular untuk lanjut ke phase berikutnya.

### Phase 2 - Modern Book Detail Card
- Merapikan detail buku (panel kanan + halaman full detail) menjadi susunan card modern:
  - Hero card: cover kiri, informasi utama kanan.
  - Metadata ringkas (ISBN, category, status, location).
  - Section terpisah: Description, QR Preview, Location, Actions.
- Menyatukan tampilan detail dengan reuse partial agar konsisten dan mudah dirawat.
- File:
  - `resources/views/books/partials/detail_panel.blade.php`
  - `resources/views/books/show.blade.php`
- Dampak:
  - Hierarki informasi buku lebih jelas.
  - UI siap jadi basis untuk fitur berikutnya (rack mini map di phase 3).

### Phase 3 - Rack Mini Map in Book Detail
- Menambahkan mini map posisi rack langsung di panel detail buku.
- Grid mini map dibangun dari `RackService` (bukan generate logic di Blade).
- State warna:
  - empty = gray
  - filled = green
  - current book = blue
- File:
  - `app/Services/RackService.php`
  - `app/Http/Controllers/Web/BookPageController.php`
  - `resources/views/books/index.blade.php`
  - `resources/views/books/show.blade.php`
  - `resources/views/books/partials/detail_panel.blade.php`
- Dampak:
  - User bisa lihat konteks posisi buku dalam rack secara visual tanpa pindah halaman.
  - Fondasi siap untuk ekspansi interaksi lokasi di phase berikutnya.

### Phase 4 - Import Manual + ISBN Autofill
- Menambahkan mode import manual di halaman import dengan tab:
  - `CSV Import` (existing)
  - `Manual Input` (baru)
- Menambahkan endpoint ISBN lookup untuk halaman web import manual (AJAX).
- Menambahkan request validation khusus manual input.
- Menambahkan service flow pembuatan buku manual:
  - rack optional
  - jika rack dipilih, assign ke slot kosong pertama di rack tersebut
  - jika tidak tersedia, fallback ke auto-assign existing flow
  - QR tetap generate via queue
- File:
  - `app/Http/Controllers/Web/BulkImportPageController.php`
  - `app/Http/Requests/StoreManualBookRequest.php`
  - `app/Services/BookService.php`
  - `app/Services/RackService.php`
  - `resources/views/books/import.blade.php`
  - `routes/web.php`
- Dampak:
  - Input buku manual lebih cepat dan realistis untuk operasional.
  - ISBN scan/input sekarang bisa autofill metadata dari provider eksternal tanpa keluar halaman.

### Phase 5 - QR UX Improvement
- Menambahkan empty-state QR dengan tombol `Generate QR Now`.
- Menambahkan backend action untuk queue generate QR yang masih missing berdasarkan filter.
- Menambahkan mode print:
  - `Print All`
  - `Print Selected` (checkbox per item).
- Menjaga konsistensi bahwa hanya buku dengan `qr_code_path` yang tampil di preview/print.
- File:
  - `app/Services/BookService.php`
  - `app/Http/Controllers/Web/QrStickerPageController.php`
  - `resources/views/qr/index.blade.php`
  - `routes/web.php`
- Dampak:
  - UX halaman QR lebih operasional (tidak buntu saat data kosong).
  - Admin bisa print subset QR tanpa perlu filter berulang.

### Phase 6 - Rack UI Improvement
- Menyelaraskan visual rack grid agar konsisten dengan state:
  - empty = gray
  - occupied = green
  - selected = blue (saat slot kosong diklik sebelum assign)
- Menambahkan selected-state interaktif pada slot kosong di halaman `racks.show`.
- Menjaga slot occupied tetap disabled (tidak bisa diklik/buka modal).
- Menyelaraskan legend + warna di `racks.index` dan `racks.show`.
- File:
  - `resources/views/racks/show.blade.php`
  - `resources/views/racks/index.blade.php`
- Dampak:
  - UX pemetaan posisi rak lebih konsisten dan intuitif.
  - Interaksi assign lebih jelas karena ada feedback visual slot terpilih.

### Phase 7 - Global UI Polish
- Standardisasi komponen UI utama untuk konsistensi lintas halaman:
  - card spacing diseragamkan (`p-4`)
  - button radius/hover/focus/disabled state diseragamkan
  - badge status diseragamkan:
    - `available` = green
    - `borrowed` = red
    - `unassigned` = yellow
- Sinkronisasi pemakaian badge unassigned di halaman books.
- Penyempurnaan layout shell:
  - sidebar nav transition/rounded konsisten
  - content padding lebih konsisten responsif (`p-6 lg:p-8`)
- File:
  - `resources/views/components/card.blade.php`
  - `resources/views/components/button.blade.php`
  - `resources/views/components/badge.blade.php`
  - `resources/views/components/ui/status-badge.blade.php`
  - `resources/views/components/ui/stat-card.blade.php`
  - `resources/views/layouts/app.blade.php`
  - `resources/views/books/index.blade.php`
  - `resources/views/books/partials/detail_panel.blade.php`
- Dampak:
  - Tampilan admin panel lebih rapi dan konsisten.
  - Status visual lebih jelas untuk operasional harian.

### Phase 8 - Dummy Data Seeder Upgrade
- Memperluas dummy data seeder agar sesuai kebutuhan pengujian operasional:
  - Categories: Programming, Networking, Database, Robotics
  - Racks: Rack A (3x3), Rack B (2x4), Rack C (3x4)
  - Books: 14 item dengan kombinasi assigned/unassigned
- Menjamin assigned positions tidak bentrok antar buku (mengikuti unique `rack_id + position_code`).
- Menambahkan variasi status buku (`available` dan sebagian `borrowed`) untuk pengujian UI badge/filter.
- File:
  - `database/seeders/LibraryDemoSeeder.php`
- Verifikasi:
  - `php artisan db:seed --class=LibraryDemoSeeder` berhasil
  - `categories=4 books=14 racks=3 assigned=8 unassigned=6`
- Dampak:
  - UI master-detail, rack placement, dashboard, dan QR page punya data realistis untuk demo/testing.

### Hardening Pass - Import, Queue, Testing
- Bulk import hardening:
  - batas maksimum row preview (`MAX_PREVIEW_ROWS`)
  - commit diproses per chunk (`COMMIT_CHUNK_SIZE`)
  - pencatatan skipped reasons + log warning per row gagal
  - summary skipped reasons ditampilkan di UI import
- Queue job hardening:
  - `GenerateBookQrCodeJob` ditambah `tries`, `timeout`, `backoff`
- Testing hardening:
  - `phpunit.xml` diset sqlite in-memory untuk test isolation
  - `tests/Feature/ExampleTest.php` ditambah `RefreshDatabase`
- File:
  - `app/Services/BulkImportService.php`
  - `resources/views/books/import.blade.php`
  - `app/Jobs/GenerateBookQrCodeJob.php`
  - `phpunit.xml`
  - `tests/Feature/ExampleTest.php`
  - `docs/implementation_audit_2026-03-28.md`
- Verifikasi:
  - `php artisan test` => 4 passed
  - `php artisan view:cache` => success
