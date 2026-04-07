# Smart Library - AI Book Scan Handover Plan

## 1) Ringkasan Tujuan
Menyediakan pipeline input buku otomatis dari gambar untuk mengisi form admin (Filament atau UI admin lain) dengan output metadata konsisten:

```json
{
  "title": "...",
  "author": "...",
  "description": "...",
  "publisher": "...",
  "published_year": "...",
  "isbn": "...",
  "cover_url": "...",
  "source": "google|openlibrary|openmaic|ai"
}
```

## 2) Arsitektur Final (Yang Sudah Diterapkan)
- Vision extraction:
  - `app/Services/OllamaService.php`
  - Model: `qwen2.5vl:7b`
  - Fungsi: classify `front/back/unknown`, ekstrak `isbn/title/author`
- Orchestrator:
  - `app/Services/AiBookScanPipelineService.php`
  - Prioritas sumber metadata:
    1. Google Books
    2. OpenLibrary
    3. OpenMAIC
    4. AI fallback
- Endpoint API:
  - `POST /api/ai/books/scan`
  - Controller: `app/Http/Controllers/Api/AiBookScanController.php`
  - Request validation: `app/Http/Requests/ScanBookImagesRequest.php`
- Lookup services:
  - `app/Services/IsbnLookupService.php`
  - `app/Services/OpenMaicService.php`
  - `app/Services/SearxngSearchService.php`
  - `app/Services/WebContentExtractorService.php`
  - `app/Services/WebBookDescriptionService.php`
- Config:
  - `config/services.php`
  - `.env.example`

## 3) Status Implementasi Saat Ini
### Done
1. Multi-image upload (max 5 file) untuk scan AI.
2. Detection front/back/unknown dari Ollama.
3. Metadata lookup berjenjang Google -> OpenLibrary -> OpenMAIC.
4. Validasi OpenMAIC:
   - confidence threshold >= 0.7
   - title similarity check.
5. Cover selection rule:
   - front image upload -> cover provider -> upload pertama.
6. Cache layer:
   - hit cache + negative cache untuk Google/OpenLibrary/OpenMAIC.
7. Mode pipeline:
   - `mode=full` (default)
   - `mode=simple` (AI-only, tanpa lookup eksternal).
8. Fallback web-search self-host:
   - `SearXNG` + konten extractor + Ollama JSON extractor.
   - Aktif hanya jika `WEBSEARCH_ENABLED=true` dan `SEARXNG_BASE_URL` terisi.

### Belum Done (Next Priority)
1. Integrasi langsung ke form Filament (autofill realtime).
2. Guardrail observability (metrics + structured log per source).
3. Optional queue mode (scan async) untuk file besar / traffic tinggi.

## 3.1) Progress Update - Tahap A (Web Admin Manual Import)
Sudah diterapkan integrasi autofill realtime pada halaman web admin existing (`books/import`) sebagai jembatan sebelum integrasi Filament penuh.

Yang sudah aktif:
1. Endpoint web scan AI:
   - `POST /books/import/ai-scan`
   - Controller: `BulkImportPageController::scanWithAi()`
2. Manual import UI:
   - input file multi-image (`jpg/jpeg/png/webp`)
   - tombol `Scan with AI`
   - pilihan mode (`full|simple`)
   - status scan + source metadata
3. Autofill field form:
   - `title`, `author`, `isbn`, `description`, `cover_url`
   - `publisher` dan `published_year` ditampilkan di status scan
4. Validasi cover lokal:
   - `cover_url` sekarang menerima:
     - absolute URL (`https://...`)
     - local path (`/storage/...`)
5. Auto-crop cover depan:
   - pipeline mencoba crop cover dari gambar front
   - prioritas menggunakan `cover_box` dari output Ollama
   - jika `cover_box` tidak tersedia/invalid, fallback ke center crop rasio buku (2:3)
6. UI cover preview pada manual import:
   - preview otomatis update saat ISBN fetch / AI scan / edit manual `cover_url`
   - label preview menandai jika hasil berasal dari AI cropped front cover
7. Standardisasi dimensi cover:
   - hasil cover diproses ke ukuran konsisten via GD (default 600x900)
   - berlaku untuk hasil crop front cover dan normalisasi fallback upload

Catatan:
- Ini bukan integrasi Filament final, tapi sudah siap dipakai operasional untuk mempercepat input buku sekarang.

## 4) Kontrak API Endpoint
### Request
- Method: `POST`
- URL: `/api/ai/books/scan`
- Auth: middleware `auth + role:admin,staff`
- Body (`multipart/form-data`):
  - `images[]` (required, min 1, max 5, jpg/jpeg/png/webp, max 10MB)
  - `mode` (optional): `full` | `simple`

### Response (200)
```json
{
  "title": null,
  "author": null,
  "category": null,
  "description": null,
  "publisher": null,
  "published_year": null,
  "isbn": null,
  "cover_url": "/storage/book-scans/xxx.jpg",
  "source": "ai"
}
```

### Error (502)
Jika Ollama gagal/timeout:
```json
{
  "message": "Ollama request failed: ..."
}
```

## 5) Environment Checklist
Isi variabel berikut di `.env`:

```env
GOOGLE_BOOKS_API_KEY=
GOOGLE_BOOKS_CACHE_MINUTES=120
GOOGLE_BOOKS_CACHE_MISS_MINUTES=15

OLLAMA_BASE_URL=http://192.168.100.200:11434
OLLAMA_MODEL=qwen2.5vl:7b
OLLAMA_TIMEOUT=90

OPENMAIC_BASE_URL=
OPENMAIC_API_KEY=
OPENMAIC_MODEL=openmaic-chat
OPENMAIC_TIMEOUT=30
OPENMAIC_CACHE_MINUTES=180
OPENMAIC_CACHE_MISS_MINUTES=20

WEBSEARCH_ENABLED=false
SEARXNG_BASE_URL=
SEARXNG_TIMEOUT=12
WEBSEARCH_MAX_RESULTS=3
WEBSEARCH_ALLOWED_DOMAINS=goodreads.com,gramedia.com,mizanstore.com,bukukita.com,openlibrary.org,books.google.com
WEBSEARCH_CACHE_MINUTES=180
WEBSEARCH_CACHE_MISS_MINUTES=20
AI_COVER_WIDTH=600
AI_COVER_HEIGHT=900
```

Lalu jalankan:
1. `php artisan config:clear`
2. `php artisan cache:clear`
3. pastikan `php artisan storage:link` sudah ada.

## 6) Plan Implementasi Lanjutan (Bertahap)
### Tahap A - Filament Autofill (Prioritas Tinggi)
Status: **In progress** (web-admin bridge done, Filament-native integration pending)

1. Tambah upload component di form Buku.
2. Saat upload selesai, call `/api/ai/books/scan`.
3. Mapping response ke field form:
   - `title`, `author`, `category`, `description`, `publisher`, `published_year`, `isbn`, `cover_url`
4. Tampilkan badge source (`google/openlibrary/openmaic/ai`) di form.
5. Fallback UX:
   - jika AI/provider gagal, form tetap bisa diisi manual.

Acceptance:
- Upload gambar -> field terisi otomatis tanpa refresh halaman.

### Tahap B - Test Otomatis
Status: **Done**

1. Tambah feature tests endpoint scan:
   - valid upload
   - invalid mime
   - mode simple
   - provider gagal
2. Mock HTTP provider:
   - Ollama
   - Google
   - OpenLibrary
   - OpenMAIC

Acceptance:
- Test pipeline lulus konsisten, tidak flaky.

Implementasi:
- `tests/Feature/AiBookScanApiTest.php`
  - auth required (`401`)
  - validation error (`422`) untuk payload invalid
  - default mode fallback ke `full`
  - pass-through mode `simple`
  - handling `RuntimeException` => `502`

### Tahap C - Operasional & Monitoring
Status: **In progress** (structured log + metrics dashboard ringkas sudah aktif)

1. Tambah structured logs per scan:
   - durasi
   - source terpilih
   - hit/miss cache
2. Tambah dashboard sederhana:
   - success rate scan
   - rata-rata latency
   - distribusi source (`google/openlibrary/openmaic/ai`)

Acceptance:
- Tim bisa audit kualitas data dan performa pipeline.

Implementasi berjalan:
- Service observability baru:
  - `app/Services/AiScanObservabilityService.php`
  - menyimpan counter harian scan (`total/success/failed`), avg latency, distribusi source.
- Structured logs sudah aktif:
  - event `ai_scan.completed` dan `ai_scan.failed` (API + Web import)
  - event cache `book_lookup.cache` dan `openmaic_lookup.cache` untuk hit/miss.
- Ringkasan operasional ditampilkan di dashboard:
  - `AI Scan Today`, `Success Rate`, `Avg Latency`, distribusi source.

## 7) Risiko & Mitigasi
1. Google API quota/rate limit:
   - mitigasi: cache + fallback OpenLibrary/OpenMAIC.
2. OCR/vision gagal baca gambar blur:
   - mitigasi: multi-image + mode full.
3. OpenMAIC halusinasi:
   - mitigasi: confidence threshold + title similarity + source fallback.
4. Response lambat:
   - mitigasi: mode `simple`, timeout terukur, rencana async queue.
5. Crop box tidak akurat:
   - mitigasi: fallback center crop 2:3 + tetap simpan original front cover.

## 8) Handover Quick Notes untuk Engineer Pengganti
1. Jangan ubah schema output endpoint tanpa update form mapping.
2. Jangan biarkan OpenMAIC dipanggil sebelum Google/OpenLibrary gagal.
3. Jangan turunkan guardrail `confidence >= 0.7` tanpa evaluasi kualitas.
4. Jika rollback cepat dibutuhkan:
   - gunakan `mode=simple` dari client sementara waktu.
5. Semua progres lanjutan wajib dicatat ke:
   - `docs/change_log.md`
   - file ini (`docs/ai_book_scan_handover_plan.md`) bila ada perubahan arsitektur.
