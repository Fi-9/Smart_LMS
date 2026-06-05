# 🚀 Smart Library v3.0 — Migration Plan

> **Tanggal:** 2026-06-03
> **Status:** In Progress
> **Tujuan:** Replace Ollama → Gemini via n8n, tambah Python OCR, buat Mobile Scan Dashboard terpisah

---

## 📋 Masalah Saat Ini

1. **Ollama lokal unreliable** — Qwen3-VL sering empty response, token starvation, format JSON kacau
2. **Tavily websearch** — terlalu banyak false positive untuk buku lokal Indonesia
3. **Back cover extraction** — dual-cover prompt sudah ada tapi hasilnya ga konsisten
4. **Scanning UX** — halaman import sekarang terlalu berat untuk operator yang pegang HP

---

## 🏗️ Arsitektur Baru

### AI Pipeline (Setelah Migrasi)

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
```

### Back Cover Extraction — Dua Jalur

| Jalur | Tools | Kelebihan |
|-------|-------|-----------|
| **A: Gemini Vision** | Upload front + back cover → Gemini Vision API via n8n | Akurat, bisa baca teks Indonesia |
| **B: Python OCR** | `easyocr` / `surya-ocr` via subprocess | Offline, cepat untuk teks jelas |

**Prioritas:** Gemini Vision → Python OCR (backup)

### Ollama: **REMOVED**
- Semua referensi Ollama di kode dihapus
- Setting Ollama di UI dihapus
- Worker `ai-scan` queue tetap dipertahankan (untuk async processing)
- Model lokal diganti n8n + Gemini cloud

---

## 📱 Mobile Scan Dashboard

### Route Terpisah: `/scan`
- **Auth required** — operator wajib login
- **Operator identification** — input nama pustakawan sebelum mulai scanning
- **Session tracking** — setiap scan tercatat siapa operator-nya

### Layout Mobile-First

```
┌─────────────────────────┐
│  📱 Smart Scan          │
│  Operator: [Nama]       │
│                         │
│  ┌───────────────────┐  │
│  │   📷 Kamera       │  │
│  │   (viewport       │  │
│  │    preview)       │  │
│  └───────────────────┘  │
│                         │
│  ┌─ ISBN ─┐ ┌─ Cover ─┐│
│  │ Active │ │  Tab    ││
│  └────────┘ └─────────┘│
│                         │
│  [  Input ISBN...    ]  │
│  [    🔍 FETCH      ]  │
│                         │
│  ┌───────────────────┐  │
│  │ ✅ Buku ditemukan │  │
│  │ Judul: ...        │  │
│  │ Penulis: ...      │  │
│  │ Rak: A3-2         │  │
│  │ [✅ Simpan] [⏭️]   │  │
│  └───────────────────┘  │
│                         │
│  📊 Hari ini: 23 buku  │
└─────────────────────────┘
```

### Fitur Mobile Scanner
- Tab switch: **ISBN mode** (barcode gun) vs **Cover mode** (foto buku)
- ISBN mode: input besar + auto-focus + auto-loop (scan → save → next)
- Cover mode: kamera langsung buka, capture front + back
- Hasil scan langsung muncul di card compact
- Quick action: Simpan & Lanjut (tanpa pindah halaman)
- Counter harian per operator
- PWA-ready (bisa install di home screen)

---

## 🔧 Implementation Phases

### Phase 1: n8n + Gemini Integration (Backend)
- [ ] Setup koneksi n8n lokal (http://localhost:5678)
- [ ] Buat/update workflow Gemini di n8n (cek Pustaka1 yang sudah ada)
- [ ] Buat `GeminiService.php` di Laravel (call n8n webhook)
- [ ] Replace Tavily + Ollama text fallback → Gemini via n8n
- [ ] Keep Google Books + OpenLibrary sebagai primary
- [ ] Test end-to-end: ISBN → Google → OpenLibrary → n8n/Gemini

### Phase 2: Gemini Vision for Back Cover
- [ ] Upgrade `GeminiService` untuk multimodal (image + text prompt)
- [ ] Buat n8n workflow untuk vision endpoint
- [ ] Replace Qwen3-VL vision call → Gemini Vision API via n8n
- [ ] Remove semua kode Ollama
- [ ] Remove Ollama dari Settings UI
- [ ] Test cover scan: front + back → metadata extraction

### Phase 3: Python OCR Setup
- [ ] Setup Python environment (`easyocr` atau `surya-ocr`)
- [ ] Buat script `ocr_extractor.py` — input: image path, output: JSON text
- [ ] Integrasi via `Process` (subprocess) dari Laravel
- [ ] Fallback chain: Gemini Vision → Python OCR
- [ ] Test dengan foto buku nyata

### Phase 4: Mobile Scan Dashboard
- [ ] Route baru `/scan` dengan auth + operator name
- [ ] Database: tabel `scan_sessions` (operator, timestamp, book_count)
- [ ] Layout mobile-first (Tailwind responsive)
- [ ] ISBN looper: input besar + auto-loop + haptic feedback
- [ ] Cover scan: kamera capture via html5-qrcode + camera API
- [ ] Card hasil compact dengan quick save
- [ ] Counter harian per operator
- [ ] PWA manifest + service worker

### Phase 5: Cleanup & Testing
- [ ] Remove semua kode Ollama (service, config, env, UI settings)
- [ ] Update README & change_log
- [ ] Feature tests untuk flow baru
- [ ] Smoke test end-to-end di mobile

---

## 🔑 Credentials & Config

### n8n Local
- **URL:** http://localhost:5678
- **API Key:** Sudah disimpan di environment
- **Existing Workflow:** Pustaka1

### Gemini
- **Provider:** Google AI Studio
- **Model:** gemini-2.5-flash (text), gemini-2.5-flash (vision)
- **API Key:** Via n8n credential

### Python OCR
- **Library:** easyocr (Bahasa: id, en)
- **Runtime:** Python 3.x subprocess

---

## 📝 Catatan

- Ollama di-remove total — ga ada offline fallback lokal lagi
- Jika n8n down, pipeline tetap bisa jalan dengan Google Books + OpenLibrary saja
- Python OCR jadi satu-satunya komponen offline untuk text extraction
- Mobile scanner wajib login + operator identification untuk audit trail
