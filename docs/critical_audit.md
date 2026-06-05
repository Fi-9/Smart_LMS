# 🔴 Smart Library — Critical Audit Report
**Date:** 5 June 2026 | **Auditor:** AutoClaw (Bambung) | **Severity:** 🔴🔴🟡

---

## ⚠️ EXECUTIVE SUMMARY

Project telah berevolusi dari sync scanning ke **queue-based async scanning**. Ada 2 arsitektur berdampingan yang tidak sinkron — kode lama (sync) dan kode baru (async). **Queue worker HARUS berjalan** atau scanning tidak akan memproses buku.

---

## 🔴 CRITICAL (must fix)

### 1. Queue Worker Tidak Berjalan — SCAN GAGAL
**File:** `app/Jobs/ProcessBookScanJob.php`
**Impact:** Semua scan akan masuk antrean tapi tidak pernah diproses.

Frontend sekarang kirim ke `POST /book-scanner/enqueue` → buat `ScanJob` → dispatch `ProcessBookScanJob` ke queue `ai-scan` → **tapi tidak ada worker yang consume queue**.

**Fix:**
```bash
php artisan queue:work --queue=ai-scan
```

### 2. scanCover Legacy Code — Dead Path
**File:** `app/Http/Controllers/Web/MobileScanController.php:174-288`

- `$mode = 'gemini'` — hardcoded, tidak menerima parameter dari frontend
- `$ocrService = null` — tidak pernah diinstansiasi → OCR path dead code
- Frontend tidak pernah panggil `/book-scanner/cover` — panggil `/book-scanner/enqueue`
- Method ini **TIDAK DIGUNAKAN** oleh flow saat ini

**Fix:** Hapus atau refactor scanCover untuk kompatibilitas.

### 3. Mode Toggle (Gemini/OCR) Hilang dari Frontend
**File:** `resources/views/scanner/mobile-scan.blade.php`

UI toggle ada di HTML tapi **JS handler `setScanMode()` sudah dihapus**. Tidak ada `<form>.append('mode', ...)` di kode JS. User tidak bisa pilih OCR vs Gemini.

**Fix:** Kembalikan mode toggle ke frontend `enqueueScan` flow, atau tambahkan `mode` parameter di `enqueueScan` controller.

### 4. GeminiService Selalu Coba n8n Dulu — Latency
**File:** `app/Services/GeminiService.php:43`

```php
$profile = config('services.ai_runtime.profile', 'n8n-gemini');
if ($profile === 'n8n-gemini') {
    // coba n8n dulu → gagal → baru direct Gemini
}
```

Setiap scan = ~5-10 detik wasted mencoba n8n yang workflow-nya belum terkonfigurasi. `config('services.ai_runtime.profile')` tidak ada di `config/services.php` → selalu default ke `n8n-gemini`.

**Fix:** Set `AI_RUNTIME_PROFILE=direct-gemini` di `.env` dan tambahkan config block di `config/services.php`:
```php
'ai_runtime' => [
    'profile' => env('AI_RUNTIME_PROFILE', 'direct-gemini'),
],
```

---

## 🟡 WARNING (should fix)

### 5. Duplicate Scan Detection — False Positives
**File:** `app/Http/Controllers/Web/MobileScanController.php:316`

`enqueueScan` cek `ScanJob::query()->where('duplicate_check_hash', $hash)` — hash dibuat dari gambar. Kalau user ambil 2 foto buku yang sama (retake), dianggap duplicate.

### 6. retryJob Route Model Binding Risk
**File:** `routes/web.php:37`
```php
Route::post('/book-scanner/retry/{job}', [MobileScanController::class, 'retryJob'])
    ->name('book-scanner.retry');
```

`{job}` pakai implicit route model binding → `ScanJob $job`. Kalau job tidak ditemukan → 404. Tidak ada error handling di controller.

### 7. New Services Belum Ada di .env Config
**Services baru tanpa config:**
- `BookIdentificationService` 
- `CatalogLookupService`
- `MetadataEnrichmentService`
- `FallbackEngineService`

Masing-masing mungkin butuh API key atau endpoint sendiri.

### 8. Multiple Unused Services
**File:** `app/Services/`

Services yang ada tapi mungkin tidak digunakan:
- `AiBookScanPipelineService` — legacy sync pipeline
- `AiScanObservabilityService` — monitoring
- `AiInfrastructureService` — Ollama diagnostic (deprecated)
- `SearxngSearchService` — SearXNG search
- `WebBookDescriptionService` — web description
- `WebContentExtractorService` — web content

---

## 🟢 OK / VERIFIED

| # | Item | Status |
|---|------|--------|
| 1 | PHP syntax (all files) | ✅ Clean |
| 2 | Routes (8 scanner + admin) | ✅ OK |
| 3 | Database Pustaka1 (49 tables) | ✅ Connected |
| 4 | `book_inbox` table (26 cols) | ✅ Schema OK |
| 5 | `scan_jobs` table (20 cols) | ✅ Exists |
| 6 | `scan_pipeline_logs` table | ✅ Exists |
| 7 | `book_lookup_cache` table | ✅ Exists |
| 8 | Tesseract OCR v5.4.0 (eng+ind) | ✅ Working |
| 9 | OpenLibrary API | ✅ Working |
| 10 | Gemini Direct API | ✅ Working |
| 11 | n8n webhook (responds 200) | ✅ Online |
| 12 | Login 2-tab (admin+petugas) | ✅ Working |
| 13 | Staff middleware redirect | ✅ Working |
| 14 | All new service files exist | ✅ Present |
| 15 | ProcessBookScanJob compiles | ✅ Syntax OK |

---

## 📊 ARSITEKTUR SAAT INI (ACTUAL)

```
📷 Kamera → Auto-capture
    ↓
POST /book-scanner/enqueue (multipart: front + back images)
    ↓
MobileScanController::enqueueScan()
    ├── Duplicate check (hash)
    ├── ScanJob::create(status='queued')
    └── ProcessBookScanJob::dispatch(jobId) → queue 'ai-scan'
    ↓ (ASYNC)
ProcessBookScanJob::handle()
    ├── GeminiService::extractBookSignals(images)
    │   ├── n8n pipeline (attempt, likely fails)
    │   └── fallback: direct Gemini API
    ├── BookIdentificationService (identify book)
    ├── CatalogLookupService (Google + OpenLibrary)
    ├── MetadataEnrichmentService (enrich data)
    ├── FallbackEngineService (Tavily + Gemini text)
    ├── BookInbox::create(status='pending')
    └── BookLookupCache::updateOrCreate()
    ↓
Frontend poll: GET /book-scanner/queue-status
    ↓
Hasil muncul di screen-result
    ↓
User edit → saveToInbox()
```

### Queue Status Flow
```
User submit scan → dapat queue_number (1, 2, 3...)
    → Poll GET /book-scanner/queue-status setiap 3 detik
    → Ketika job selesai → showResult()
```

---

## 🔧 PRIORITY FIXES

### Immediate (hari ini)
1. **Jalankan queue worker**: `php artisan queue:work --queue=ai-scan`
2. **Set profile direct**: tambahkan `AI_RUNTIME_PROFILE=direct-gemini` di `.env`
3. **Tambah config services.php**: `ai_runtime.profile` config block

### Short-term
4. **Clean up dead code**: hapus `scanCover` atau refactor
5. **Restore mode toggle**: OCR vs Gemini di frontend
6. **Add error handling**: retryJob route model binding

### Medium-term
7. Audit semua service baru untuk config dependencies
8. Clean up unused services
9. Tambahkan `php artisan queue:work` ke startup script
