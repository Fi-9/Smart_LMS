# Smart Library - Implementation Audit (2026-03-28)

## Ringkasan
Audit ini membandingkan implementasi saat ini terhadap PRD, ERD, dan aturan workflow yang sudah disepakati.

## Sudah Selesai
- Arsitektur Laravel 11 + PHP 8.2+, Service Layer, Repository Pattern, Form Request.
- Struktur inti database tersedia: `users`, `categories`, `racks`, `books`.
- Constraint posisi unik sudah diterapkan: `unique(rack_id, position_code)`.
- QR disimpan sebagai path file (`/storage/qrcodes/book-{id}.png`).
- Role `admin` dan `staff` tersedia.
- ISBN lookup dengan fallback provider tersedia.
- Bulk import dengan alur `preview -> commit` tersedia.
- Validasi import tersedia (termasuk duplicate ISBN dan default rack).
- QR generation via queue job tersedia.
- Dashboard statistik tersedia (total + group by rack/category + status).
- UI utama tersedia: dashboard, list buku, import.
- CRUD category dan rack tersedia.
- Visual click assign buku ke slot rack tersedia (modal + AJAX).
- Filter unassigned, prevent double assign di UI, toast feedback tersedia.
- Auto-assign buku unassigned ke slot kosong tersedia.
- QR sticker page + QR print A4 layout tersedia (`/qr/print`).

## Belum / Gap yang Masih Ada
- Environment MySQL lokal belum aktif, sehingga aplikasi gagal jika dipaksa MySQL.
- Borrowing module masih ditunda (sesuai keputusan lock sebelumnya).
- OpenAPI masih disable (sesuai keputusan lock sebelumnya).
- Export PDF QR print (dompdf) belum ada.
- Drag & drop placement belum ada (masih click assign).

## Progress Update (Post Phase 8 + Hardening)
- Print selected books sudah tersedia di halaman QR.
- Generate missing QR sekarang tersedia dari UI QR (queue-based).
- Bulk import sudah di-hardening:
  - limit preview rows
  - commit diproses per-chunk
  - skipped reasons dicatat dan ditampilkan
- Queue QR job sudah di-hardening:
  - retries
  - timeout
  - backoff
- Test suite sudah stabil pada environment testing sqlite.

## Catatan Risiko Teknis
- Jika memakai `DB_CONNECTION=mysql`, MySQL server wajib aktif di `127.0.0.1:3306`.
- Tanpa MySQL aktif, endpoint dashboard dan halaman root akan 500.
- Beberapa file cache/runtime (`storage/*`, `.phpunit.result.cache`) ikut berubah dan sebaiknya tidak dipakai sebagai acuan fitur.

## Rekomendasi Urutan Lanjut
1. Stabilkan environment MySQL (service up + database `slms_db` + migrate/seed).
2. Finalisasi QR print (selected books + opsi export PDF).
3. Hardening bulk import (chunking/retry/monitoring queue).
4. Lanjut ke fitur opsional tingkat lanjut (drag-drop).
