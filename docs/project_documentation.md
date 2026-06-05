# 📚 Smart Library Management System — Dokumentasi Teknis

> **Versi:** 2.0 — Phase A/B/C | **Laravel:** 13.5.0 | **Database:** PostgreSQL 16 (Pustaka1) | **Last Update:** 4 Juni 2026

---

## 🎯 Visi & Tujuan

**Smart Library** adalah sistem manajemen perpustakaan digital untuk **SMK Mustaqbal** yang mengintegrasikan:

1. **AI-powered book ingestion** — scan cover buku pakai kamera HP, AI membaca metadata otomatis
2. **QR-based tracking** — setiap buku punya QR code untuk pelacakan lokasi rak
3. **Digital borrowing** — peminjaman & pengembalian via scan QR
4. **Separation of concerns** — dashboard admin (manajemen koleksi) terpisah dari dashboard petugas (scanning)

### Masalah yang Diselesaikan

- ❌ Katalog buku manual → ✅ AI membaca cover, auto-fill metadata
- ❌ Buku sulit ditemukan → ✅ QR code + rak map + pencarian teks penuh
- ❌ Admin & petugas campur → ✅ Login 2 jalur: admin dashboard + scanner dashboard
- ❌ Input ISBN lama → ✅ Kamera auto-capture + multi-source enrichment

---

## 🏗️ Arsitektur Teknologi

```
┌──────────────────────────────────────────────────────┐
│                    CLIENT LAYER                       │
│  Blade + Tailwind CSS + Alpine.js + Vanilla JS       │
│  Mobile-first, Camera API, BarcodeDetector API        │
├──────────────────────────────────────────────────────┤
│                    WEB LAYER                           │
│  Laravel 13.5 — Routes, Controllers, Middleware       │
├──────────────────────────────────────────────────────┤
│                    SERVICE LAYER                       │
│  GeminiService, OcrService, IsbnLookupService         │
│  AiBookScanPipelineService, BookService, QrCodeService│
├──────────────┬───────────────────┬───────────────────┤
│   AI ENGINE  │  EXTERNAL APIs    │   LOCAL TOOLS     │
│  Gemini 2.5  │  OpenLibrary      │  Tesseract OCR    │
│  Flash       │  Google Books     │  Python 3.13      │
│  (direct)    │  Tavily Search    │  pytesseract      │
│              │  n8n (optional)   │                   │
├──────────────┴───────────────────┴───────────────────┤
│                    DATA LAYER                         │
│  PostgreSQL 16 + pgvector (vector search)             │
│  Database: Pustaka1 @ 192.168.100.55:5432            │
│  20 tables + TimescaleDB internal tables              │
└──────────────────────────────────────────────────────┘
```

### Stack Detail

| Layer | Teknologi | Versi |
|-------|----------|-------|
| Backend | Laravel | 13.5.0 |
| PHP | PHP | 8.2+ |
| Database | PostgreSQL | 16.14 |
| Vector Search | pgvector | extension |
| Time Series | TimescaleDB | extension |
| Frontend | Blade + Tailwind CSS + Alpine.js | built-in |
| QR Code | simplesoftwareio/simple-qrcode | 4.2 |
| HTTP Client | Guzzle | 7.10 |
| AI Vision | Gemini 2.5 Flash | direct API |
| OCR | Tesseract | 5.4.0 (eng+ind) |
| OCR Wrapper | Python pytesseract + Pillow | 0.3.13 / 12.2.0 |
| Workflow (opt) | n8n | self-hosted |
| PDF/Image | Intervention (built-in) | Laravel default |

---

## 📊 Database Schema

### Tabel Utama (20 user tables)

```sql
-- Core
users               — admin + staff (role: admin|staff)
categories          — klasifikasi buku
rooms               — ruangan perpustakaan
racks               — rak buku (posisi: room + column)
books               — koleksi buku (judul, ISBN, status, QR, lokasi rak)

-- Borrowing
members             — anggota (siswa, guru, staff)
borrowings          — peminjaman (due_date, status)

-- AI Pipeline
book_inbox          — STAGING: buku hasil scan (pending → approved → routed)
ai_scan_results     — hasil scan AI per batch
scan_sessions       — sesi scanning petugas
book_embeddings     — vector embeddings untuk semantic search

-- System
app_settings        — konfigurasi AI (api key, model, timeout)
audit_logs          — log aktivitas
sessions            — Laravel session (database driver)
cache / jobs        — Laravel standard
```

### Key Enums
- `UserRole`: `admin`, `staff`
- `BookStatus`: `available`, `borrowed`
- `BorrowingStatus`: `active`, `returned`, `overdue`
- `BookInbox.status`: `pending`, `approved`, `rejected`, `routed`

---

## 🗺️ Route Architecture

```
/web.php
├── guest                      → /login, POST /login
├── auth (shared)              → /book-scanner/*, /logout
├── auth + role + staff.scanner → /* (admin only, staff auto-redirect)
└── public                     → /book/{id}

/api.php
├── books, categories, racks   → REST API
└── ai/scan, import            → AI pipeline API
```

### Staff Redirect Logic
- Staff login → auto-redirect ke `/book-scanner`
- Staff akses admin URL → middleware `RedirectStaffToScanner` → redirect ke scanner
- Admin bisa akses semua termasuk scanner

---

## 🔄 Fitur & Flow

### 1. 📷 Book Scanner (Staff Dashboard)

**Flow lengkap:**
```
Staff login (tab 📷 Petugas)
    ↓
Input nama operator → Start session
    ↓
Pilih mode: 📷 Kamera | 🔢 ISBN
    ↓
┌─ KAMERA ─────────────────────────────────┐
│ Auto-capture (BarcodeDetector / timer)   │
│ Camera toggle (front/back) · Flash       │
│ Cover depan → preview → cover belakang   │
│ Mode AI: 🤖 Gemini | 🔤 OCR             │
└──────────────────────────────────────────┘
    ↓
🤖 Gemini Vision direct API → extract title + author
    ↓
📚 smartEnrich() — pipeline enrichment:
    ├── Google Books (title+author search)
    ├── OpenLibrary (title+author search)  
    ├── Cross-reference (scoreMatch similarity)
    ├── Completeness check (assessCompleteness)
    ├── Tavily web search (Gramedia, Perpusnas)
    └── Gemini text enrichment (description, category)
    ↓
📋 Result form → Edit → ✅ Save to book_inbox
```

### 2. 🔍 Admin: Task & Routing

```
/books/import
├── Tab 📋 Review    → review AI scan results (book_inbox pending)
├── Tab 📦 Grouping  → kelompokkan buku approved per kategori
├── Tab 📍 Routing   → assign buku ke rak + posisi
└── Tab 📖 ISBN Lookup → cari buku via ISBN
```

### 3. 📚 Koleksi & Pencarian

- **Search Book** (`/books`) — pencarian teks penuh + filtering
- **Library Map** (`/racks`) — visualisasi rak per ruangan
- **Categories** (`/categories`) — CRUD kategori
- **QR Stickers** (`/qr`) — generate + print QR code buku

### 4. 📖 Peminjaman

- **Borrowing** (`/borrowings`) — catat pinjam, return, tracking due date
- **QR Scanner** (`/scan`) — scan QR buku untuk quick borrow/return
- **Members** (`/members`) — manajemen anggota (siswa, guru, staff)

### 5. ⚙️ Settings

- **AI Settings** (`/settings`) — konfigurasi n8n URL, API key, Gemini model
- Profile: `n8n-gemini`

---

## 🤖 AI Pipeline Detail

### GeminiService
```
extractBookSignals(images[])
    └── extractViaDirectGemini()
        ├── base64_encode(image)
        ├── POST generativeLanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent
        ├── Prompt: "Analyze book cover. Extract title, author, publisher, category. JSON only."
        └── Parse response → {best: {title, author, isbn, publisher, category}}
```

### OcrService (Alternative)
```
extract(images[])
    └── proc_open("python scripts/ocr_book_cover.py <image_paths>")
        ├── pytesseract.image_to_string(img, lang='ind+eng')
        ├── Regex: extract_isbn(), extract_title(), extract_author()
        └── Parse JSON → {best: {title, isbn, author, ...}}
```

### Smart Enrichment (MobileScanController::smartEnrich)
```
title + author dari Gemini/OCR
    → Google Books search (intitle: + inauthor:)
    → OpenLibrary search (title + author)
    → pickBestMatch() — Levenshtein + word overlap scoring
    → crossReferenceMerge() — gabung Google + OpenLibrary
    → assessCompleteness() — skor 0-100
    → searchWebForBook() — Tavily / broad Google
    → Gemini text — fill missing description/category
    → isIndonesian() check
    → Return complete data
```

---

## 📁 Struktur File Penting

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/AuthenticatedSessionController.php  ← Login 2 tab redirect
│   │   ├── Web/
│   │   │   ├── MobileScanController.php             ← Scanner (8 endpoint)
│   │   │   ├── BulkImportPageController.php         ← Admin import + routeBooks
│   │   │   ├── DashboardPageController.php          ← Admin dashboard
│   │   │   └── ...                                  ← CRUD controllers
│   ├── Middleware/
│   │   ├── EnsureUserHasRole.php                    ← admin|staff gate
│   │   └── RedirectStaffToScanner.php               ← Staff → scanner redirect
├── Models/
│   ├── BookInbox.php                                ← Staging table model
│   ├── ScanSession.php                              ← Sesi scanning
│   └── ...                                          ← Core models
├── Services/
│   ├── GeminiService.php                            ← Gemini direct API
│   ├── OcrService.php                               ← Python OCR wrapper
│   ├── IsbnLookupService.php                        ← OpenLibrary + Google Books
│   ├── AiBookScanPipelineService.php                ← Legacy pipeline
│   ├── QrCodeService.php                            ← QR generation
│   └── ...
├── Enums/
│   ├── UserRole.php                                 ← admin|staff
│   ├── BookStatus.php                               ← available|borrowed
│   └── BorrowingStatus.php                          ← active|returned|overdue

resources/views/
├── auth/login.blade.php                             ← Login 2-tab (admin|petugas)
├── layouts/
│   ├── app.blade.php                                ← Admin layout (sidebar)
│   └── scanner.blade.php                            ← Scanner layout (mobile)
├── scanner/
│   └── mobile-scan.blade.php                        ← Scanner UI (6-screen flow)
├── books/import.blade.php                           ← Admin: Task & Routing tabs
└── ...

scripts/
└── ocr_book_cover.py                                ← Python Tesseract OCR

docs/
├── n8n_full_pipeline.json                           ← n8n workflow (importable)
├── n8n_import_guide.md                              ← Panduan import n8n
└── migration_v3_plan.md                             ← Rencana migrasi v3
```

---

## 🔐 Keamanan & Akses

| Role | Akses |
|------|-------|
| **admin** | Semua: dashboard, koleksi, QR, peminjaman, settings, scanner |
| **staff** | HANYA: `/book-scanner`, `/logout` — semua URL admin lain auto-redirect |
| **guest** | HANYA: `/login` |

Middleware chain: `auth` → `role:admin,staff` → `staff.scanner` (redirect staff)

---

## 🔧 Environment Config (.env)

```env
DB_CONNECTION=pgsql
DB_HOST=192.168.100.55
DB_PORT=5432
DB_DATABASE=Pustaka1
DB_USERNAME=adminpustaka1
DB_PASSWORD=pustaka1db2026

GEMINI_MODEL=gemini-2.5-flash
GEMINI_API_KEY=AQ.Ab8RN6L-Yf...(redacted)

N8N_BASE_URL=https://n8n.smkmustaqbal.sch.id
N8N_API_KEY=eyJhbGci...(redacted)

GOOGLE_BOOKS_API_KEY=          # kosong = free tier
AI_RUNTIME_PROFILE=n8n-gemini
```

---

## 🧪 Test Credentials

| Akun | Email | Password | Role |
|------|-------|----------|------|
| Admin | admin@mustaqbal.sch.id | admin123 | admin |
| Staff | staff@mustaqbal.sch.id | staff123 | staff |

- **Database:** Pustaka1 @ 192.168.100.55:5432 (user: adminpustaka1)
- **pgAdmin:** http://192.168.100.55:8086/ (admin@mustaqbal.sch.id)
- **Tesseract:** C:\Program Files\Tesseract-OCR\tesseract.exe (eng+ind)

---

## 🚀 Roadmap Pengembangan

### ✅ Selesai
- [x] Migrasi Ollama → Gemini (direct API)
- [x] Book scanner mobile-first (camera + auto-capture)
- [x] Login 2-tab (admin + petugas)
- [x] Staff dashboard terpisah + middleware redirect
- [x] AI enrichment pipeline (Google Books + OpenLibrary + Tavily + Gemini)
- [x] OCR Tesseract sebagai alternatif
- [x] Admin: Task & Routing tabs (book_inbox staging)
- [x] Database Pustaka1 terpisah dari SAMS

### 🔜 Mendatang
- [ ] Google Books API key (untuk hilangkan rate limit)
- [ ] n8n full pipeline integration (Gemini → Google → OL → merge)
- [ ] n8n MCP connection untuk monitoring workflow
- [ ] Indonesian description priority (translate non-ID descriptions)
- [ ] Cover cropping & enhancement otomatis
- [ ] Batch scan mode (multi-buku sekaligus)
- [ ] Semantic search (pgvector embeddings)
- [ ] Dashboard analytics & reports

### 💡 Ide
- [ ] Mobile PWA untuk scanner
- [ ] Barcode ISBN scan via kamera (sudah ada BarcodeDetector, perlu polish)
- [ ] Auto-suggest rack position berdasarkan kategori
- [ ] Notification ke admin saat buku baru masuk inbox
- [ ] Import dari Excel/CSV

---

## 🛠️ Development Notes

### Local Server
```bash
cd C:\Users\renre\Downloads\Smart_LMS
php artisan serve
```

### Cache Gotcha
- Setelah ubah `.env`, wajib restart `php artisan serve` dan `php artisan config:clear`
- PowerShell: `$env:DB_DATABASE="Pustaka1"` sebelum artisan command
- `composer dump-autoload` exit code 1 di Windows = non-critical

### File Casing
- Semua file PHP baru wajib **PascalCase** untuk production Linux
- Windows case-insensitive — rename via 2-step (temp name dulu)

### PostgreSQL Notes
- Sequence tidak auto-reset setelah rollback → `TRUNCATE ... RESTART IDENTITY CASCADE`
- Index naming: gunakan suffix `_idx` untuk hindari konflik nama

### Prod Deployment
- Target: Linux server
- File case matters
- `php artisan config:cache` untuk production
- Queue worker untuk Tavily/report jobs
