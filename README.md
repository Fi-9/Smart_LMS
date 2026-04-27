# 📚 Smart Library v2.0 — SMK Mustaqbal

Sistem Manajemen Perpustakaan Pintar berbasis AI untuk SMK Mustaqbal.
Dibangun dengan **Laravel 13**, **Tailwind CSS**, **Alpine.js**, dan **Ollama AI** (Qwen3-VL Vision + Qwen 2.5 Text).

---

## ✨ Fitur Utama

| Menu | Deskripsi |
|------|-----------|
| **Dashboard** | Ringkasan statistik koleksi, rak, peminjaman, dan performa AI pipeline |
| **Search Book** | Katalog buku dengan master-detail layout, cover card, QR preview, dan rack mini map |
| **Library Map** | Peta digital perpustakaan: Lobby (Ruangan) → Hallway (Rak) → Shelf (Grid Slot) — drill-down navigation |
| **Categories** | Manajemen label/kategori buku (CRUD) |
| **Smart Ingest** | 2 jalur automasi: **AI Scan** (Qwen3-VL cover) dan **ISBN Scan** (Continuous Looper barcode gun) → Review & Routing |
| **Borrowing** | Sirkulasi peminjaman & pengembalian buku dengan status tracking |
| **Members** | Manajemen anggota perpustakaan |
| **Settings** | Konfigurasi AI runtime, websearch provider, branding & QR, dan akun |

## 🤖 AI Pipeline

Smart Library menggunakan **Ollama** (local inference) dengan arsitektur pipeline:

```
Upload Cover → Qwen3-VL Vision (extract metadata)
                    ↓
            Google Books API (enrich)
                    ↓
            OpenLibrary (fallback)
                    ↓
            Tavily Web Search (last resort)
                    ↓
            Review & Grouping → Physical Routing → Commit
```

- **Async Batch Scan**: Upload banyak buku sekaligus, diproses via queue worker
- **Hybrid ISBN Lookup**: Google Books → OpenLibrary → Tavily web extraction
- **Per-field Source Labels**: Setiap field metadata menampilkan asal datanya (AI Cover, Google Books, Web Resmi, dll)
- **Fuzzy Title Search**: OCR typo correction (T↔I, B↔R, dst)

## 🖥️ Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 13 (PHP 8.4) |
| Frontend | Blade + Tailwind CSS + Alpine.js |
| AI Vision | Ollama + Qwen3-VL-8B |
| AI Text | Ollama + Qwen 2.5 14B |
| Websearch | Tavily API |
| Book API | Google Books API, OpenLibrary API |
| Database | MySQL |
| Queue | Laravel Queue (database driver) |
| QR Code | endroid/qr-code (PNG/SVG) |
| Build | Vite |

## 🎨 Tema UI

Smart Library v2.0 menggunakan **tema Lumina** dengan dual mode:
- **Light Mode**: Abu terang bersih dengan aksen Emerald
- **Dark Mode**: Slate gelap dengan aksen Emerald

Tema disimpan di `localStorage` dan tersinkron otomatis.

## 🚀 Instalasi

```bash
# Clone repository
git clone <repo-url> smart-library
cd smart-library

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed --class=LibraryDemoSeeder

# Build frontend
npm run build

# Start server
php artisan serve
npm run dev

# Start queue worker (untuk AI batch scan)
php artisan queue:work --queue=ai-scan
```

## ⚙️ Konfigurasi AI

1. Install [Ollama](https://ollama.com/) dan pull model:
   ```bash
   ollama pull qwen3-vl:8b
   ollama pull qwen2.5:14b
   ```
2. Buka menu **Settings** di aplikasi
3. Atur Ollama Base URL, pilih model Vision dan Text
4. (Opsional) Masukkan API key Google Books dan Tavily untuk enrichment

## 📁 Struktur Direktori Penting

```
├── Http/Controllers/Web/    # Controller halaman admin
├── Models/                  # Book, Category, Rack, Room, Borrowing, User, AppSetting
├── Services/                # AI Pipeline, QR, ISBN Lookup, Borrowing
├── Jobs/                    # Async batch scan job
└── Enums/                   # BorrowingStatus, UserRole

resources/views/
├── layouts/app.blade.php    # Shell utama + sidebar Lumina
├── dashboard/               # Halaman dashboard
├── books/                   # Search Book + Smart Ingest (AI Scan + ISBN Looper)
├── racks/                   # Rack Detail (Explorer, Table Mode, Manual Input)
├── rooms/                   # Room Detail (Hallway)
├── categories/              # Categories
├── borrowings/              # Borrowing
├── members/                 # Members
├── settings/                # Settings
└── qr/                      # QR sticker & print

docs/
├── change_log.md            # Log perubahan kode
└── implementation_audit_*.md
```

## 📋 Changelog

Lihat [docs/change_log.md](docs/change_log.md) untuk riwayat perubahan lengkap.

## 📄 License

Proyek internal SMK Mustaqbal.
