# 🚀 n8n Full Pipeline — Import Guide

File rekomendasi: `docs/n8n_full_pipeline_websearch.json`

File lama yang masih ada:
- `docs/n8n_full_pipeline.json` → baseline awal
- `docs/n8n_full_pipeline_websearch.json` → versi yang sudah menambahkan fallback websearch

## Cara Import

1. Buka n8n di browser: https://n8n.smkmustaqbal.sch.id
2. Login ke n8n dashboard
3. Klik **"Import from File"** di pojok kanan atas
4. Pilih file `docs/n8n_full_pipeline.json`
5. Klik **"Import"**
6. Gunakan workflow `SmartLMS Vision + Websearch Fallback` sebagai workflow aktif

## Konfigurasi Setelah Import

### Environment Variable (wajib)
Tambahkan di n8n Settings → Environment Variables:
```
GEMINI_API_KEY=your_gemini_key
TAVILY_API_KEY=your_tavily_key
```

### Webhook URL
Setelah workflow diaktifkan, webhook URL akan:
```
https://n8n.smkmustaqbal.sch.id/webhook/smartlms-vision
```

## Pipeline Flow

```
📥 Webhook (image upload)
    ↓
🤖 Gemini Vision API
    ↓
📝 Parse title / author / ISBN
    ↓
📚 Google Books lookup
📚 Open Library lookup
🌐 Tavily websearch fallback
    ↓
🔗 Merge metadata
    ↓
✅ Return JSON to caller
```

## Expected Response Format

```json
{
  "found": true,
  "source": "n8n-pipeline",
  "book": {
    "title": "Judul Buku",
    "author": "Nama Penulis",
    "isbn": "9786020332116",
    "publisher": "Gramedia Pustaka Utama",
    "published_year": 2016,
    "description": "...",
    "category": "Fiksi",
    "cover_url": "https://...",
    "language": "id"
  },
  "sources_used": {
    "google_books": true,
    "openlibrary": true,
    "gemini_vision": true,
    "websearch": true
  }
}
```

## Kapan Websearch Dipakai

Fallback websearch dipakai ketika:

- `Google Books` tidak memberi `description`
- `Open Library` juga kosong atau terlalu minim
- hasil scan hanya memberi judul/penulis kasar dan butuh deskripsi tambahan

Prioritas merge:

1. `Google Books`
2. `Open Library`
3. `Websearch`
4. `Gemini Vision`

## Testing

Dari terminal:
```bash
curl -X POST https://n8n.smkmustaqbal.sch.id/webhook/smartlms-vision \
  -H "X-N8N-API-KEY: eyJh..." \
  -F "image=@path/to/cover.jpg"
```
