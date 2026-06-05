# 📐 Smart Library v3 — Change Format & Progress Tracker

> **Started:** 2026-06-03 16:30 WIB
> **Status:** IN PROGRESS
> **Based on:** `docs/migration_v3_plan.md`

---

## 🎯 Ringkasan Perubahan

| # | Phase | Deskripsi | Status |
|---|-------|-----------|--------|
| 1 | n8n + Gemini Backend | Koneksi API n8n, `GeminiService.php`, integrate ke pipeline | ✅ Done |
| 2 | Gemini Vision Cover | Vision extraction via n8n, ganti Qwen3-VL (Ollama) di pipeline | ✅ Done |
| 3 | Remove Ollama | Hapus dari service, config, UI settings, env vars, views | ✅ Done |
| 4 | Mobile Scan Dashboard | Route `/book-scanner`, auth + operator name, ISBN looper, cover capture | ✅ Done |
| 5 | Cleanup & Test | Hapus file OllamaService, update docs, testing | ⬜ Pending |

---

## 📋 Format Perubahan (Detail Tiap Phase)

### Phase 1: n8n + Gemini Integration
```
FILES TO CREATE:
  app/Services/GeminiService.php          ← Call n8n webhook
FILES TO UPDATE:
  config/services.php                     ← Add 'gemini' config block
  .env                                    ← Add N8N_WEBHOOK_URL, GEMINI_MODEL, etc
  app/Services/AiBookScanPipelineService.php  ← Add Gemini as metadata source
ROUTES:
  (none — backend service only)
```

### Phase 2: Gemini Vision for Back Cover
```
FILES TO UPDATE:
  app/Services/GeminiService.php          ← Add vision endpoint (multimodal)
  app/Services/AiBookScanPipelineService.php  ← Replace Ollama vision call
FILES TO DELETE/STRIP:
  app/Services/OllamaService.php          ← REMOVE (after pipeline switch)
```

### Phase 3: Remove Ollama
```
FILES TO DELETE:
  app/Services/OllamaService.php          ← ✕
  tests/Unit/OllamaServiceJsonDecodeTest.php ← ✕
FILES TO UPDATE:
  config/services.php                     ← Remove 'ollama' block
  .env                                    ← Remove OLLAMA_* vars
  .env.example                            ← Remove OLLAMA_* vars
  resources/views/settings/index.blade.php ← Remove Ollama settings UI
  app/Services/AiInfrastructureService.php ← Remove resolveOllamaModel()
  app/Http/Controllers/Web/SettingsPageController.php ← Remove Ollama save logic
```

### Phase 4: Mobile Scan Dashboard `/scan`
```
FILES TO CREATE:
  app/Http/Controllers/Web/MobileScanController.php   ← Controller utama
  resources/views/scanner/mobile-scan.blade.php       ← Layout mobile-first
  resources/views/scanner/operator-login.blade.php    ← Input nama operator
  database/migrations/xxxx_create_scan_sessions.php   ← Tabel scan_sessions
  app/Models/ScanSession.php                          ← Model
FILES TO UPDATE:
  routes/web.php                                      ← Tambah route /scan
  resources/views/layouts/navigation.blade.php         ← Tambah menu Scan

DATABASE:
  CREATE TABLE scan_sessions (
    id, user_id, operator_name, book_count,
    started_at, ended_at, created_at, updated_at
  )
```

### Phase 5: Cleanup & Testing
```
FILES TO UPDATE:
  docs/change_log.md
  README.md
FILES TO CREATE:
  tests/Feature/MobileScanTest.php
  tests/Feature/GeminiServiceTest.php
```

---

## 🔌 n8n Connection Details

| Field | Value |
|-------|-------|
| **URL** | http://localhost:5678 |
| **Auth** | JWT Bearer Token (Public API) |
| **Status** | ⚠️ n8n tidak berjalan — perlu dinyalakan |
| **Existing Flow** | Pustaka1 |

---

## 🔑 Credentials Summary

| Service | Key | Status |
|---------|-----|--------|
| n8n API | `eyJh...hnuXs` (JWT) | ✅ Token valid, server ⚠️ off |
| Gemini | Via n8n credential | ⬜ Pending setup |
| Google Books | `.env` | ✅ |
| OpenLibrary | Free | ✅ |
| Ollama | LOCAL | 🔜 TO REMOVE |
| Python OCR | LOCAL | 🔜 TO REMOVE (user request) |

---

## 🗺️ Arsitektur Pipeline Baru

```
ISBN / Cover Upload
        ↓
Google Books API ──→ (cukup? stop)
        ↓
OpenLibrary ──→ (cukup? stop)
        ↓
n8n Webhook ──→ Gemini 2.5 Flash
        ↓            │
        │            ├─ Enrich metadata (title, author, category, description)
        │            ├─ Web search via Gemini grounding
        │            └─ Translate ke Bahasa Indonesia
        ↓
Return ke Smart Library

Cover Scan (Vision):
  Upload front + back cover → n8n → Gemini Vision → metadata extraction
```

---

## 📱 Rute `/scan` — Spesifikasi

```
GET  /scan              → Halaman utama scanner (auth required)
POST /scan/operator     → Simpan nama operator, mulai sesi
POST /scan/isbn         → Proses ISBN scan (lookup cascade)
POST /scan/cover        → Upload cover image → Gemini Vision
POST /scan/save         → Simpan buku dari hasil scan
GET  /scan/session/{id} → Lihat riwayat sesi per operator
```

### Flow Mobile Scanner:
1. Pustakawan login ke `/login`
2. Redirect ke `/scan`
3. Input nama operator (auto-fill kalau udah pernah)
4. Mulai scanning: ISBN mode atau Cover mode
5. Hasil scan langsung autofill → save & lanjut
6. Counter harian terlihat di layar
```

---

## 📝 Progress Log

| Tanggal | Phase | Action | Result |
|---------|-------|--------|--------|
| 2026-06-03 16:30 | PREP | Review all existing code & docs | ✅ Context gathered |
| 2026-06-03 16:30 | PREP | Check n8n local connectivity | ⚠️ n8n not running |
| 2026-06-03 16:30 | PREP | Create `format.md` | ✅ Done |
| 2026-06-03 17:00 | PHASE 1 | Create `GeminiService.php` (n8n webhook proxy) | ✅ Done |
| 2026-06-03 17:00 | PHASE 1 | Update `config/services.php` + `config/ai.php` | ✅ Done |
| 2026-06-03 17:00 | PHASE 1 | Update `.env` with n8n + Gemini vars | ✅ Done |
| 2026-06-03 17:00 | PHASE 2 | Replace OllamaService → GeminiService in Pipeline | ✅ Done |
| 2026-06-03 17:00 | PHASE 2 | Replace Ollama → Gemini in WebBookDescriptionService | ✅ Done |
| 2026-06-03 17:00 | PHASE 3 | Update AiInfrastructureService (remove Ollama) | ✅ Done |
| 2026-06-03 17:00 | PHASE 3 | Update SettingsPageController + UpdateAiSettingsRequest | ✅ Done |
| 2026-06-03 17:00 | PHASE 3 | Update AppSettingsService (n8n keys) | ✅ Done |
| 2026-06-03 17:00 | PHASE 3 | Update settings view (Ollama→n8n) + import view | ✅ Done |
| 2026-06-03 17:00 | PHASE 3 | Update BulkImportPageController (model name) | ✅ Done |
| 2026-06-03 17:00 | PHASE 4 | Create migration `scan_sessions` table | ✅ Done |
| 2026-06-03 17:00 | PHASE 4 | Create `ScanSession` model | ✅ Done |
| 2026-06-03 17:00 | PHASE 4 | Create `MobileScanController` (7 endpoints) | ✅ Done |
| 2026-06-03 17:00 | PHASE 4 | Add `/book-scanner` routes | ✅ Done |
| 2026-06-03 17:00 | PHASE 4 | Create `mobile-scan.blade.php` (mobile-first UI) | ✅ Done |
| 2026-06-03 19:33 | N8N | Connect to n8n.smkmustaqbal.sch.id | ✅ Connected |
| 2026-06-03 19:40 | N8N | Create SmartLMS Vision workflow (webhook: gemini-vision) | ✅ Active |
| 2026-06-03 19:42 | N8N | Create SmartLMS Text workflow (webhook: gemini-text) | ⚠️ Active but 500 error |
| 2026-06-03 19:45 | CODE | Update GeminiService: multipart vision upload, n8n URL, response parsing | ✅ Done |
| 2026-06-03 19:45 | CODE | Update .env: N8N_BASE_URL → n8n.smkmustaqbal.sch.id | ✅ Done |
| 2026-06-03 19:45 | N8N | Delete SmartLMS Echo Test workflow (debug only) | ✅ Done |
| 2026-06-03 19:50 | GEMINI | API key user dimasukkan, test direct — valid ✅ | ✅ Tested |
| 2026-06-03 19:52 | CODE | GeminiService: text → direct Gemini API (no n8n), vision tetap via n8n | ✅ Done |
| 2026-06-03 19:52 | CONFIG | .env + services.php: +GEMINI_API_KEY, +gemini.api_key | ✅ Done |
| 2026-06-03 20:19 | DB | Audit: migration sudah ke 192.168.100.55 | ✅ Verified |
| 2026-06-03 20:28 | DB | Buat database Pustaka1 + user adminpustaka1 | ✅ Created |
| 2026-06-03 20:33 | DB | Migrasi 28 tabel ke Pustaka1 + seed admin/staff | ✅ Done |
| 2026-06-03 20:53 | CLEANUP | Rename 3 files ke PascalCase (Linux compat) | ✅ Done |
| 2026-06-03 20:53 | CLEANUP | Delete OllamaService.php + Ollama config block | ✅ Done |
| 2026-06-03 20:53 | CLEANUP | Clean .env dari sisa Ollama commented | ✅ Done |
| 2026-06-03 20:53 | HEALTH | composer dump-autoload + route verify | ✅ OK |

| 2026-06-04 07:00 | PHASE B | Multi-step scanner: front?back?process?inbox | ? Done |
| 2026-06-04 07:00 | PHASE B | Google Books + OpenLibrary title lookup | ? Done |
| 2026-06-04 07:00 | PHASE B | saveToInbox: pending ? admin Review | ? Done |