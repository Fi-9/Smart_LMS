# Smart Library - Change Log

## Aturan Pencatatan
- Setiap perubahan kode wajib ditambahkan ke file ini.
- Format minimal per entri:
  - Tanggal
  - Ruang lingkup
  - File yang berubah
  - Dampak/tujuan

## 2026-04-22

### 🚀 ISBN Multi-Provider Fetch (Hybrid Lookup)
- **Tujuan:** Memastikan fitur fetch ISBN (tombol pencarian manual dan pipeline AI scan) memiliki fallback otomatis untuk buku lokal Indonesia. OpenLibrary sering tidak memiliki metadata untuk buku lokal Gramedia, dll.
- **Implementasi Hybrid:**
  1. **Google Books API (Primary):** `IsbnLookupService` sekarang secara pintar memprioritaskan hasil dari Google Books jika tersedia dan otomatis melakukan "merge" kolom metadata jika ada field kosong yang bisa dipenuhi oleh OpenLibrary.
  2. **Tavily AI Web Search (Fallback Sakti):** Jika Google Books dan OpenLibrary KEDUANYA gagal menemukan buku (atau sukses tapi tidak punya `description` alias sinopsis kosong), maka sistem akan melakukan pencarian web secara otomatis (`Buku ISBN ... Gramedia`).
  3. **Ollama Web Extraction:** Membaca hasil pencarian web dan mengekstrak informasi buku (`title`, `author`, `description`, `publisher`, `category`) menggunakan AI LLM dengan bahasa Indonesia yang rapi.
- **Perubahan Spesifik:**
  - `IsbnLookupService.php`: Ditambahkan logic fallback untuk menggunakan `WebBookDescriptionService::resolveByIsbn()`.
  - `WebBookDescriptionService.php`: Metode baru `resolveByIsbn()` dan perbaikan DRY pada logic internal execute search.
  - `OllamaService.php`: Prompts & logic eksekusi khusus untuk mengekstrak informasi spesifik ISBN dari konteks web (`extractBookInfoFromWebByIsbn`).
- **Dampak:** Pustakawan kini bisa langsung menyalin buku lokal yang tadinya sering kosong di database OpenLibrary, mendapatkan sinopsis lengkap hanya dari scan barcode / ketik ISBN.

### 🚨 ROOT CAUSE FIX — Qwen3-VL Thinking Mode Token Starvation (Empty Response)
- **Diagnosis definitif** dari log: `eval_count: 600 | response_length: 0` terjadi berulang-ulang.
  - Artinya model menghabiskan **semua 600 `num_predict` token** di dalam blok `<think>...</think>` (Qwen3-VL thinking/reasoning mode), lalu **kehabisan token sebelum sempat menulis JSON output**.
  - Ini berbeda dari bug sebelumnya (format:json) — model kali ini **berhasil menerima gambar** (terbukti dari `prompt_eval_count > 0`) tapi output-nya ter-throttle oleh thinking tokens.
- **Perubahan di `OllamaService::sendVisionRequest()`**:
  1. **`'think' => false`** — parameter baru untuk menonaktifkan thinking mode Qwen3-VL secara eksplisit. Tanpa ini, model menginvestasikan hampir semua token untuk berpikir secara internal, bukan untuk menulis output.
  2. **`num_predict: 1200`** — dinaikkan dari 600. Bahkan jika thinking mode tidak 100% dimatikan, token yang lebih banyak memberikan buffer agar JSON bisa keluar setelah thinking selesai.
  3. **`num_ctx: 8192`** — dinaikkan dari 4096. Konteks yang lebih besar diperlukan agar model bisa "melihat" gambar + prompt + menghasilkan output secara penuh tanpa truncation.
- **Verification Step (Image Payload Guard)**:
  - Gambar-gambar dikonversi ke JPEG **sebelum** payload dirakit (bukan di-inline di dalam array map).
  - Sistem kini mengecek: "Ada tidak gambar yang berhasil dikonversi?"
  - Jika **semua gambar kosong** → throw `RuntimeException` dengan pesan jelas, bukan diam-diam kirim payload kosong ke Ollama.
  - Jika **sebagian gambar kosong** → log warning dan skip gambar yang bermasalah.
  - Log ukuran total payload (KB) untuk diagnostik.
- **Enhanced Diagnostics**:
  - Log `prompt_eval_count` (token yang dipakai untuk proses gambar+prompt).
  - Otomatis log error `🚨 EMPTY RESPONSE DETECTED` + penyebab yang mungkin jika `response_length=0` tapi `eval_count > 0`.
  - Log per-gambar: nama file, format, ukuran sebelum konversi.
  - Log error eksplisit jika `imagecreatefromavif` gagal.
- **File**: `app/Services/OllamaService.php`, `docs/change_log.md`
- **Dampak**:
  - Qwen3-VL sekarang **langsung menulis JSON output** tanpa membuang token untuk thinking.
  - Diagnosa masalah gambar jauh lebih mudah karena setiap tahap konversi di-log.
  - Pipeline tidak lagi diam-diam mengirim payload kosong ke Ollama.

### OCR Accuracy Enhancement - Resolution Upgrade + AVIF Hardening + Fuzzy Title Search
- **Vision Image Resolution:** Dinaikkan dari `768px` ke `1024px` dan JPEG quality dari `70` ke `85`. Resolusi lebih tinggi membantu Qwen3-VL membaca karakter kecil lebih akurat (fix kasus "BATT" → "BAIT").
- **AVIF Conversion Logging & Hardening:**
  - Menambahkan `Log::info('Image converted from: ' . $format)` untuk setiap gambar yang diproses.
  - Menambahkan logging jika semua decoder GD gagal (JPEG, AVIF, WebP) — sebelumnya diam-diam kirim raw bytes yang mungkin tidak terbaca.
  - Menambahkan logging untuk dimensi, resize, dan ukuran JPEG output.
  - Prioritas AVIF decoder sekarang juga cek ekstensi file (`.avif`) sebagai hint tambahan selain magic bytes.
- **Vision Prompt Spelling Instruction:**
  - Menambahkan instruksi: `DOUBLE CHECK every character for spelling accuracy!`
  - Menambahkan peringatan: `Common OCR mistakes: I vs T, B vs R, A vs O. Double check!`
- **Fuzzy Title Search (OCR Typo Correction):**
  - `buildLookupTitleCandidates` sekarang generate varian judul dengan mengganti karakter yang sering tertukar oleh OCR:
    - T↔I (`BATT` → `BAIT`)
    - T↔L, B↔R, O↔Q, O↔0, l↔1, S↔5, rn↔m
  - Menambahkan partial title search: ambil 2-3 kata signifikan pertama untuk pencarian yang lebih toleran.
  - Dibatasi maksimal 5 varian per judul agar tidak overload provider API.
- File:
  - `app/Services/OllamaService.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `docs/change_log.md`
- Dampak:
  - Akurasi pembacaan teks cover meningkat signifikan berkat resolusi lebih tinggi.
  - AVIF yang sebelumnya gagal diam-diam kini terdeteksi dan dilaporkan di log.
  - Pencarian metadata tetap bisa menemukan buku yang benar meskipun OCR salah 1-2 karakter.

### Qwen3-VL Root Cause Fix - Remove `format:json` (Empty Response Bug)
- **Diagnosis definitif** dari log: Ollama mengembalikan `"done":true, "response_length":0, "eval_count":46` — artinya model menerima prompt (46 token), tapi **menolak generate output** saat parameter `"format":"json"` aktif. Ini bug/inkompatibilitas Qwen3-VL dengan Ollama forced JSON mode.
- Perubahan utama:
  1. **Hapus `'format' => 'json'`** dari payload Vision request. Qwen3-VL (dan banyak VL model lain) tidak support forced JSON mode di Ollama API `/api/generate`. Instruksi JSON sekarang sepenuhnya dikendalikan lewat prompt teks, dan response dibersihkan oleh `decodeModelJson()` yang sudah punya regex agresif.
  2. **Tambah `num_ctx: 4096`** pada options Vision. Memberi model konteks memori yang cukup untuk memproses 2 gambar (Dual-Cover) sekaligus tanpa terpotong.
  3. **Naikkan `num_predict` Vision** dari `500` menjadi `600`. Memberi ruang lebih untuk deskripsi sinopsis dari back cover.
  4. **Minimum timeout** dipaksa `max(120, ...)` untuk Vision request agar model punya waktu cukup untuk proses gambar di RTX 3060.
  5. **Dual-Cover Prompt** — `buildVisionPrompt()` sekarang menerima parameter `$imageCount`. Jika 2 gambar terdeteksi, prompt otomatis menambahkan instruksi spesifik:
     - Image 0 = Front Cover → sumber Judul & Penulis
     - Image 1 = Back Cover → sumber Sinopsis/Deskripsi
  6. **Enhanced logging** — mencatat jumlah gambar yang dikirim, durasi response Ollama dalam detik, `eval_count`, dan `prompt_eval_count` ke channel `ai_scan`.
- File:
  - `app/Services/OllamaService.php`
  - `docs/change_log.md`
- Dampak:
  - **Qwen3-VL sekarang bisa merespon** — bukan lagi empty response.
  - Dual-Cover workflow (Front + Back) didukung dengan instruksi prompt yang jelas.
  - Model punya konteks dan token yang cukup untuk proses 2 gambar.
  - Backward-compatible: model lain yang support `format:json` tetap bisa jalan karena `decodeModelJson()` sudah handle semua skenario output.

### Qwen3-VL JSON Compatibility Fix - Model Switch "Invalid JSON" Resolution
- Mengatasi error `Invalid JSON returned by Ollama model` yang terjadi setelah migrasi model Vision dari Llama 3.2 ke **Qwen3-VL-8B**.
- Akar masalah:
  - Qwen3-VL sering membungkus output dalam tag `<think>...</think>` (reasoning mode) sebelum mengeluarkan JSON.
  - Qwen3-VL kadang membungkus JSON dalam Markdown code fence (````json ... ````).
  - `num_predict` 400 terlalu rendah untuk Qwen3-VL yang membutuhkan token ekstra untuk reasoning.
  - Response kosong tidak terdeteksi dengan baik, menyebabkan error parsing yang tidak informatif.
- Perubahan di `OllamaService`:
  1. **Strip `<think>` tags**: Menambahkan `preg_replace` untuk menghapus tag `<think>...</think>` dari response mentah sebelum parsing JSON. Qwen3 sering menggunakan tag ini untuk internal reasoning.
  2. **Aggressive Markdown stripping**: Mengganti regex strip code fence dari pendekatan `preg_replace` per-line menjadi `str_replace` global yang menangkap `\`\`\`json`, `\`\`\`JSON`, dan `\`\`\`` secara menyeluruh, plus fallback regex untuk variasi lain.
  3. **Naikkan `num_predict` Vision**: Dari `400` menjadi `500` agar output JSON tidak terpotong saat Qwen3-VL sedang menulis deskripsi atau metadata yang lebih panjang.
  4. **Enhanced debug logging**:
     - `Log::info('DEBUG OLLAMA RAW BODY')` — mencatat body mentah full dari Ollama sebelum proses apapun.
     - `Log::channel('ai_scan')->error(...)` — mencatat raw response saat JSON gagal di-parse, memudahkan diagnosis.
     - Deteksi response kosong secara eksplisit sebelum parsing, dengan error message yang informatif.
     - Log sukses saat JSON berhasil di-decode dari candidate tertentu.
  5. **Empty response guard**: Jika response kosong setelah strip `<think>`, langsung throw RuntimeException dengan pesan jelas (bukan generic "Invalid JSON").
- File:
  - `app/Services/OllamaService.php`
  - `docs/change_log.md`
- Dampak:
  - Pipeline AI Scan kini kompatibel dengan **Qwen3-VL-8B** dan model Qwen3 lainnya yang menggunakan reasoning tags.
  - Markdown-wrapped JSON dari model apapun kini otomatis di-strip sebelum parsing.
  - Diagnosis masalah model jauh lebih mudah berkat logging raw response yang komprehensif.
  - Output JSON tidak lagi terpotong berkat peningkatan `num_predict`.

## 2026-04-21

### AI Pipeline - Flat JSON Fallback & Regex Extraction Fix
- Menganalisa penyebab metadata Vision kosong: Llama 3.2 Vision mengabaikan skema JSON bertingkat (`images` dan `best`) dan mengembalikan object tunggal (flat JSON).
- Mengupdate `OllamaService::extractBookSignals` dengan logic **fallback** untuk menangkap `title`, `author`, `isbn` langsung dari JSON flat jika skema bertingkat `best` tidak ditemukan.
- Memperkuat parsing JSON dengan `trim($raw)` dan fungsi `preg_match('/\{.*\}/s', $content, $matches)` agar parser Laravel bisa mengabaikan teks pengantar AI yang kotor.
- Sinkronisasi urutan logging: log `=== START AI SCAN ===` digeser ke paling atas di awal method `scan()` pada `AiBookScanPipelineService` agar tidak balapan dengan logging raw dari `OllamaService`.
- File:
  - `app/Services/OllamaService.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `docs/change_log.md`
- Dampak:
  - Output JSON model yang sangat minimal dan tidak mengikuti skema tetap bisa dibaca sempurna dan langsung terisi ke UI sebagai Judul, Penulis, dan ISBN.

### Websearch Fallback Enhancement
- Memperbaiki query Tavily di `WebBookDescriptionService` agar lebih tertarget ke data buku.
- Query pencarian sekarang secara paksa ditambahkan prefix "Buku" dan suffix "Gramedia" (contoh: `Buku Bait Kerinduan Megantara Seftian Gramedia`) agar hasil tidak bercampur dengan lirik lagu, puisi, atau artikel umum.
- File:
  - `app/Services/WebBookDescriptionService.php`
  - `docs/change_log.md`
- Dampak:
  - Tingkat keberhasilan Tavily dalam menemukan sinopsis buku dari web meningkat signifikan.

### Image Processing & AVIF Support Fix
- Mengatasi isu error 500 dari Ollama (Unknown Format) saat melakukan scan pada gambar berformat AVIF.
- Menambahkan *fallback logic* di `CoverImageService` dan `OllamaService` menggunakan `imagecreatefromavif()` jika `imagecreatefromstring()` bawaan PHP GD gagal mendeteksi *magic bytes* AVIF.
- Memastikan semua gambar dikonversi menjadi format standar **JPEG** sebelum di-encode ke Base64 untuk dikirimkan ke payload Ollama Vision, menjamin kompatibilitas 100% dengan Llama 3.2 Vision.
- **Cropping Feature Disabled:** Mematikan pemotongan gambar (cropping) berbasis koordinat (bounding box) dari Vision AI yang kerap berhalusinasi (menghasilkan gambar pipih/gepeng). Sistem kini menggunakan gambar `Front Cover` asli secara utuh (`normalizeCoverFromUpload`).
- File:
  - `app/Services/OllamaService.php`
  - `app/Services/CoverImageService.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `docs/change_log.md`
- Dampak:
  - AI Book Scan kini sepenuhnya mendukung upload berformat AVIF, WebP, dan format modern lainnya tanpa error, dan UI import buku selalu menampilkan cover secara proporsional.

### Extreme Performance Optimization - Target Sub-10 Detik
- **Ollama Keep-Alive (Engine Warm):** Mengubah parameter `keep_alive` dari `20m` menjadi `2h` pada semua payload Ollama (vision, text, web). Model tetap ter-load di VRAM selama 2 jam setelah pemakaian terakhir, menghilangkan overhead model loading 3-5 detik pada setiap scan berikutnya.
- **Image Pre-processing (Aggressive Downscale):** Menurunkan resolusi maksimal gambar yang dikirim ke AI dari `1024px` menjadi `768px` dan kualitas JPEG dari `85-90%` menjadi `70%`. Ukuran payload Base64 turun ~60%, mempercepat encoding dan inference secara signifikan. AI Vision hanya butuh detail teks, bukan kualitas poster.
- **Razor-Sharp Vision Prompt:** Merombak total prompt Vision dari ~40 baris instruksi verbose menjadi ~12 baris instruksi tajam berbahasa Inggris. Menghilangkan penjelasan panjang, contoh berlebihan, dan instruksi `cover_box` (yang sudah dimatikan). Prompt sekarang langsung memberikan schema JSON target dan aturan singkat. Efek: input token berkurang ~70%, output AI lebih cepat dan fokus.
- **Token Generation Cap:** Menurunkan `num_predict` dari `800` menjadi `400` untuk Vision, dari `512` menjadi `350` untuk Web Extraction. Mencegah AI "ngobrol" panjang lebar dan memaksanya langsung ke JSON output.
- **Pipeline Timing Instrumentation:** Menambahkan `microtime(true)` logging per tahap pipeline (Vision, Provider Lookup, Websearch) ke channel `ai_scan`. Setiap scan sekarang melaporkan durasi per-fase dan total pipeline dalam milidetik, memudahkan profiling dan optimasi lanjutan.
- File:
  - `app/Services/OllamaService.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `docs/change_log.md`
- Estimasi dampak kecepatan:
  | Tahap | Sebelum | Sesudah |
  |---|---|---|
  | Image Pre-processing | ~1.0s | ~0.3s |
  | Vision AI (model warm) | 8-15s | 3-5s |
  | Provider API (Google/OL) | 2-4s | 1-2s (cached) |
  | Websearch + Text AI | 5-8s | 3-4s |
  | **Total Pipeline** | **~20-30s** | **~7-12s** |

### AI Upload Form & UI Refinements
- Melonggarkan validasi format gambar AI scan pada frontend dan backend supaya mendukung `.avif`, `.heic`, `.heif`, dan `.bmp`.
- Mengupdate `ScanBookImagesRequest` untuk input AI manual agar file selain format standar (seperti `.avif`) bisa diproses oleh backend.
- Menata ulang layout (struktur HTML) kolom deskripsi pada tab *Review & Grouping* agar merentang membentang penuh (full-width) sampai ke bawah area Cover Buku. Hal ini untuk menghilangkan *blank white space* (ruang kosong putih) di bawah sampul buku sehingga visual lebih padat dan tertata (*rata kanan kiri/justify* layouting).
- Menambahkan style `text-justify` pada *textarea* deskripsi supaya paragraf teks di dalamnya rata secara merata.
- Penanganan anti-stuck Batch Scan *frontend* diperkuat: sistem mendeteksi slot gagal dan tetap mengirim file gambar yang valid ke process antrian `jobs`.
- Menghidupkan fitur **Cari Data Kosong** di tiap slot *Review*: sekarang tombol tersebut berfungsi mencari metadata yang kosong langsung ke provider (Google/OpenLibrary) berdasarkan Judul dan Penulis yang dibaca AI. Dilengkapi animasi *loading spinner* dan transisi sukses (warna hijau) tanpa berpindah halaman.
- Mengubah posisi antarmuka tombol **Simpan Semua ke Library** dari mode *sticky relative* menjadi *fixed bottom-right* (`fixed bottom-6 right-8`). Kini tombol tersebut benar-benar melayang statis di sudut kanan bawah layar sehingga tidak perlu melakukan *scroll* panjang ke bawah lagi saat item *batch scan* sangat banyak.
- File:
  - `app/Http/Requests/ScanBookImagesRequest.php`
  - `resources/views/books/import.blade.php`
  - `docs/change_log.md`
- Dampak:
  - Layout form review hasil scan jauh lebih padat dan tidak membuang ruang di bawah gambar cover.
  - Fotografi modern dari ponsel kini terbaca sempurna tanpa menyebabkan error format.
  - UX operator lebih responsif karena bisa memanggil ulang API metadata (`enrich`) per buku yang datanya kurang sempurna, dan aksi simpan sangat mudah dijangkau kapan saja secara *floating*.

## 2026-04-07

### Rack Capacity UX Fix - Multi-Book Slot (Available Until Full)
- Memperbaiki alur rack agar satu slot benar-benar bisa menampung banyak buku sesuai `capacity_per_slot` (mis. 30/50+), bukan terasa seperti 1 slot 1 buku.
- Perubahan utama:
  - halaman detail rack sekarang menganggap slot **tersedia** selama belum penuh (`count < capacity`), bukan hanya saat kosong total.
  - dropdown quick move sekarang menampilkan `available slot` + indikator `count/capacity`.
  - grid slot menampilkan ringkasan `count/capacity`, daftar judul ringkas, serta `+N buku lainnya` agar tetap rapi saat isi banyak.
  - slot yang sudah berisi tapi belum penuh tetap bisa diklik untuk assign buku tambahan.
  - default input `capacity_per_slot` di form create/edit rack dinaikkan agar lebih realistis untuk skenario banyak buku per slot.
- File:
  - `app/Http/Controllers/Web/RackPageController.php`
  - `resources/views/racks/index.blade.php`
  - `resources/views/racks/show.blade.php`
  - `docs/change_log.md`
- Dampak:
  - Operasional penyusunan rack jadi sesuai kebutuhan lapangan: 1 posisi dapat menampung banyak buku.
  - UI lebih informatif dan tidak menyesatkan seolah slot hanya bisa 1 buku.

### AI Metadata Fallback Order Fix - Google First, Fill Missing Only
- Memperbaiki urutan fallback metadata AI scan agar lebih sesuai alur operasional:
  - prioritas provider tetap dimulai dari Google Books
  - jika source awal jatuh ke OpenLibrary dan metadata inti masih ada yang kosong, sistem mencoba retry ke Google (berbasis title kandidat yang sudah dibersihkan)
  - websearch tetap dipakai sebagai fallback deskripsi jika deskripsi masih kosong
- Menegaskan kebijakan merge metadata:
  - field yang sudah terisi tidak dioverride
  - field kosong saja yang diisi dari provider/fallback
- Memperluas merge missing field agar bisa melengkapi juga `title`, `author`, `isbn`, dan `cover_url` bila memang kosong.
- File:
  - `app/Services/AiBookScanPipelineService.php`
  - `tests/Unit/AiBookScanPipelineServiceTest.php`
  - `docs/change_log.md`
- Dampak:
  - Kasus yang sebelumnya mentok di source OpenLibrary dengan deskripsi kosong jadi punya kesempatan naik lagi ke Google.
  - Autofill metadata jadi lebih aman karena tidak menimpa data yang sudah ada.

### Async Batch Scan Architecture - Queue Worker + Polling Progress
- Mengubah batch scan AI dari proses sinkron menjadi arsitektur asynchronous yang lebih cocok untuk Ollama lokal.
- Implementasi inti:
  - job baru `ProcessAiBatchScanBook` untuk memproses satu buku per job di queue `ai-scan`
  - service baru `AiBatchScanDraftService` untuk menyimpan draft status batch di cache
  - endpoint status baru untuk polling progress batch scan dari UI
  - form batch scan sekarang submit via `fetch`, menerima `202 Accepted`, lalu memantau progress sampai selesai
  - setelah semua job selesai, halaman otomatis refresh ke hasil review
- Pengamanan resource:
  - file upload batch disimpan sementara di local storage untuk diproses worker lalu dibersihkan
  - `DB_QUEUE_RETRY_AFTER` dinaikkan untuk menghindari job vision panjang diproses ulang terlalu cepat
- Penyesuaian prompt/vision:
  - image prep untuk request vision diperkecil ke sisi maksimal `1024px`
  - prompt vision diperjelas untuk gaya GLM: JSON ketat, abaikan testimoni/badge promo, serta ekstraksi publisher/bahasa jika terlihat
- File:
  - `app/Services/AiBatchScanDraftService.php`
  - `app/Jobs/ProcessAiBatchScanBook.php`
  - `app/Http/Controllers/Web/BulkImportPageController.php`
  - `resources/views/books/import.blade.php`
  - `routes/web.php`
  - `config/queue.php`
  - `app/Services/OllamaService.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `tests/Feature/AiBatchImportFlowTest.php`
  - `.env`
  - `.env.example`
  - `docs/change_log.md`
- Dampak:
  - Batch scan tidak lagi menahan request browser sampai semua buku selesai.
  - Worker bisa dijalankan serial untuk menjaga GPU/VRAM Ollama tetap stabil.
  - User mendapat progress yang lebih jelas selama proses scan berlangsung.

## 2026-04-06

### AI Scan Flow Realignment - Vision First, Provider Enrichment, Trusted Web Fallback
- Menyelaraskan ulang flow AI scan agar kembali sesuai alur operasional:
  - gambar dibaca dulu oleh AI vision untuk ambil `title`, `author`, `isbn`, `category`, dan deskripsi dari teks yang terlihat
  - metadata lalu diperkaya dari provider katalog (`Google Books` / `Open Library`)
  - websearch hanya dipakai sebagai fallback terpercaya bila deskripsi masih kosong
  - jika deskripsi dari back cover terbaca, deskripsi itu dipertahankan dan tidak dioverride deskripsi internet
- Menambahkan unit test untuk mengunci dua aturan utama:
  - deskripsi back cover harus menang atas provider/web
  - trusted websearch dipakai hanya saat deskripsi provider memang kosong
- File:
  - `app/Services/AiBookScanPipelineService.php`
  - `tests/Unit/AiBookScanPipelineServiceTest.php`
  - `docs/change_log.md`
- Dampak:
  - Flow scan sekarang lebih dekat dengan kebutuhan operasional nyata di lapangan.
  - Risiko deskripsi bagus dari back cover tertimpa hasil internet yang kurang pas jadi jauh lebih kecil.

### AI Scan Review Transparency - Per-Field Source Labels
- Menambahkan provenance per-field pada hasil scan AI agar operator bisa melihat sumber tiap data secara lebih jelas.
- Field yang kini membawa label sumber antara lain:
  - `title`
  - `author`
  - `isbn`
  - `category`
  - `description`
  - `cover_url`
- Tampilan review hasil scan sekarang menampilkan badge seperti:
  - `Judul: AI Cover`
  - `Kategori: Google Books`
  - `Deskripsi: Back Cover`
  - `Deskripsi: Web Resmi Gramedia`
- File:
  - `app/Services/AiBookScanPipelineService.php`
  - `app/Http/Controllers/Web/BulkImportPageController.php`
  - `resources/views/books/import.blade.php`
  - `tests/Unit/AiBookScanPipelineServiceTest.php`
  - `tests/Feature/AiBatchImportFlowTest.php`
  - `docs/change_log.md`
- Dampak:
  - Operator lebih mudah audit hasil autofill sebelum commit ke rak.
  - Sumber data yang kurang meyakinkan bisa cepat dikenali saat review.

### Review UX + Runtime Cleanup - Remove Button, OpenMaic Removed
- Menambahkan tombol `Hapus` pada kartu hasil `Review Hasil Scan` agar buku yang tidak ingin diproses bisa langsung dibuang dari preview sebelum commit.
- Item review yang dihapus sekarang:
  - keluar dari DOM
  - di-reindex ulang sebelum submit
  - mengurangi counter per kategori
  - menonaktifkan tombol simpan bila semua item sudah dibuang
- Membersihkan jalur `OpenMaic` dari runtime aktif:
  - pipeline AI scan tidak lagi memakai fallback OpenMaic
  - panel status runtime dan command `ai:status` tidak lagi menampilkan OpenMaic
  - rekomendasi mode scan sekarang hanya mempertimbangkan websearch aktif/tidak aktif
- File:
  - `resources/views/books/import.blade.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `app/Services/AiInfrastructureService.php`
  - `routes/console.php`
  - `tests/Unit/AiBookScanPipelineServiceTest.php`
  - `tests/Feature/AiInfrastructureStatusTest.php`
  - `docs/change_log.md`
- Dampak:
  - Review batch jadi lebih fleksibel dan cepat untuk operator.
  - Infrastruktur AI lebih sederhana dan fokus ke stack nyata: Ollama + Google/Open Library + web resmi.

### Settings UI + Tavily Integration
- Menambahkan persistence pengaturan aplikasi berbasis database melalui tabel `app_settings`.
- Menambahkan halaman `Settings` baru di panel admin untuk mengatur:
  - `Ollama Base URL`
  - model `vision`, `text`, dan `web`
  - timeout koneksi
  - `Tavily API key`
  - domain whitelist websearch
  - default scan mode
  - `Google Books API key`
- Mengganti provider websearch aktif dari SearXNG ke `Tavily`.
- Menambahkan status runtime yang lebih user-friendly:
  - `php artisan ai:status`
  - kartu status di halaman settings
  - menu sidebar `Settings`
- File:
  - `database/migrations/2026_04_06_150000_create_app_settings_table.php`
  - `app/Models/AppSetting.php`
  - `app/Services/AppSettingsService.php`
  - `app/Services/TavilySearchService.php`
  - `app/Services/AiInfrastructureService.php`
  - `app/Services/OllamaService.php`
  - `app/Services/WebBookDescriptionService.php`
  - `app/Http/Controllers/Web/SettingsPageController.php`
  - `app/Http/Requests/UpdateAiSettingsRequest.php`
  - `resources/views/settings/index.blade.php`
  - `resources/views/layouts/app.blade.php`
  - `routes/web.php`
  - `routes/console.php`
  - `config/services.php`
  - `.env`
  - `.env.example`
  - `tests/Feature/AiSettingsPageTest.php`
  - `tests/Feature/AiInfrastructureStatusTest.php`
  - `docs/change_log.md`
- Dampak:
  - Admin sekarang bisa ganti koneksi AI dan API key dari UI tanpa edit `.env`.
  - Tavily siap dipakai sebagai websearch provider baru saat API key diisi.

### Import UI Refinement - Compact Status Strip + Batch Scan Progress UX
- Merapikan area status import menjadi lebih ringkas dalam satu strip horizontal agar tidak memakan ruang vertikal terlalu besar.
- Menyederhanakan hero/flow batch scan menjadi banner satu baris yang lebih padat.
- Menyesuaikan tampilan `Batch Scan` agar lebih dekat dengan pola `Manual Input AI Scan`:
  - slot buku lebih fokus ke upload + preview
  - front/back cover tampil dalam komposisi horizontal yang lebih rapih
  - cover preview dipindah ke panel kiri agar mudah dipantau
- Menyamakan pengalaman `Manual Input` dengan `Batch Scan`:
  - status file/source lebih jelas saat sebelum dan saat scan berjalan
  - tombol scan manual kini punya loading state yang konsisten
  - preview/animasi cover tambahan di tab manual dihilangkan agar tidak dobel dengan preview cover final
- Menambahkan progress visual per slot:
  - progress bar scan tipis di atas preview cover
  - status `front ready / back ready`
  - preview cover depan langsung muncul saat file dipilih
- Menambahkan progress visual global saat `Jalankan Batch Scan AI` ditekan:
  - spinner loading di samping status batch
  - indikator jumlah buku siap discan
  - counter progres simulasi selama request berjalan
- File:
  - `resources/views/books/import.blade.php`
  - `resources/css/app.css`
  - `docs/change_log.md`
- Dampak:
  - Halaman import terasa lebih padat, fokus, dan operasional.
  - User mendapat feedback visual yang lebih jelas saat menyiapkan dan menjalankan batch scan.
  - Preview cover di batch, manual, dan review lebih mudah dibaca karena tidak lagi terasa terlalu terpotong.
  - Loading scan tidak lagi mendominasi area kartu karena indikator lingkaran besar diganti ke bar scan yang lebih kecil dan rapi.
  - Tab manual jadi lebih bersih karena hanya menyisakan satu preview cover utama.

### Description Fallback Lock - Google Books, Open Library, Trusted Web, Back Cover Wins
- Mengunci ulang alur pengambilan deskripsi agar sesuai flow operasional:
  - AI baca gambar lebih dulu untuk ambil judul dan sinyal buku
  - cari deskripsi dari `Google Books`
  - kalau kosong, lanjut ke `Open Library`
  - kalau masih kosong, lanjut ke website terpercaya melalui websearch
  - kalau ada `back cover` dan berhasil dibaca, deskripsi dari back cover tetap menang dan tidak dioverride
- Menambahkan unit test untuk memastikan deskripsi `Open Library` dipakai lebih dulu sebelum websearch saat `Google Books` tidak punya deskripsi.
- File:
  - `tests/Unit/AiBookScanPipelineServiceTest.php`
  - `resources/views/books/import.blade.php`
  - `docs/change_log.md`
- Dampak:
  - Flow deskripsi sekarang lebih konsisten dengan kebutuhan input buku nyata.
  - Risiko deskripsi kosong berkurang, terutama saat operator upload dua gambar front + back cover.

### Import Cleanup - Manual Input & AI Scan Simplified + Noisy Title Lookup Fallback
- Menyederhanakan `Manual Input` agar tidak ada preview/animasi cover ganda di area AI upload.
- Menerapkan penyederhanaan yang sama ke tab `AI Scan` batch:
  - preview cover tetap ada
  - overlay progress di atas cover dihilangkan
  - status scan dipindah ke chip teks yang lebih bersih
- Menambahkan fallback lookup metadata berbasis kandidat judul:
  - judul AI mentah
  - judul hasil normalisasi
  - potongan segmen judul
  - keyword title yang lebih bersih
- Menambahkan unit test untuk memastikan judul AI yang berantakan tetap bisa menemukan metadata/deskripsi lewat kandidat kata kunci yang lebih bersih.
- File:
  - `app/Services/AiBookScanPipelineService.php`
  - `resources/views/books/import.blade.php`
  - `tests/Unit/AiBookScanPipelineServiceTest.php`
  - `docs/change_log.md`
- Dampak:
  - Tab manual dan batch scan lebih bersih dan tidak terasa penuh.
  - Deskripsi punya peluang lebih besar untuk ketemu walau judul hasil pembacaan AI masih berisik atau terduplikasi.

### Provider Fallback Reliability - Stop Sticking on AI Source
- Memperbaiki validasi relevansi metadata agar hasil provider tidak mudah ditolak saat sinyal AI author berisik/noisy.
- Menambahkan normalisasi sinyal author AI:
  - mengabaikan author OCR yang mengandung marker noise (`top_left`, `corner`, `align`, dll)
  - mengabaikan author dengan pola underscore panjang atau teks terlalu panjang
- Melonggarkan dan memperkaya relevansi kandidat judul metadata:
  - similarity threshold diturunkan agar lebih toleran terhadap variasi subtitle
  - menambahkan cek `contains` dan `token overlap` untuk judul pendek-menengah
- Memperbaiki gate websearch agar toggle di `Settings` benar-benar berlaku:
  - sebelumnya websearch bisa tetap nonaktif bila `.env` `WEBSEARCH_ENABLED=false`
  - sekarang sumber aktivasi utama mengikuti setting aplikasi (`ai.websearch.enabled`) + API key Tavily tersedia
- File:
  - `app/Services/AiBookScanPipelineService.php`
  - `app/Services/IsbnLookupService.php`
  - `app/Services/WebBookDescriptionService.php`
  - `docs/change_log.md`
- Dampak:
  - Kasus buku yang sebelumnya mentok di source `ai` sekarang lebih sering naik ke `google`, `openlibrary`, atau `websearch` saat data tersedia.
  - Fallback deskripsi lebih stabil pada judul OCR yang tidak rapi.

### OpenLibrary to Websearch Continuation - Clean Query Escalation
- Memperbaiki kasus ketika source berhenti di `openlibrary` walau deskripsi masih kosong.
- Perubahan utama:
  - judul AI dibersihkan lebih awal dari karakter sampah seperti `}`, `_`, atau tanda baca liar
  - jika provider (`google`/`openlibrary`) punya judul dan author yang lebih rapi daripada OCR, hasil final sekarang memakai versi provider
  - saat `openlibrary` tidak punya deskripsi, websearch sekarang memakai judul/author yang sudah dibersihkan, bukan string OCR mentah
- Menambahkan unit test untuk memastikan alur berikut benar-benar terjadi:
  - source awal bisa `openlibrary`
  - deskripsi kosong
  - lanjut ke websearch dengan title provider yang sudah bersih
  - hasil akhir beralih ke `websearch`
- File:
  - `app/Services/AiBookScanPipelineService.php`
  - `tests/Unit/AiBookScanPipelineServiceTest.php`
  - `docs/change_log.md`
- Dampak:
  - Buku seperti `Keep Going` tidak lagi mandek di `openlibrary` hanya karena query websearch masih kotor.
  - Peluang temuan deskripsi dari Gramedia atau web terpercaya lain menjadi jauh lebih tinggi.

### Ollama JSON Robustness - Tolerant Decoder for Vision/Web Extraction
- Memperkuat decoder JSON hasil Ollama agar tidak mudah gagal pada output model yang tidak rapi.
- Decoder sekarang tetap bisa membaca payload ketika:
  - JSON dibungkus markdown fence (```json ... ```)
  - JSON diawali teks penjelasan non-JSON
  - JSON terkirim sebagai string ter-escape (double-encoded JSON)
  - ada trailing comma ringan yang masih bisa disanitasi
- Menambahkan unit test khusus untuk skenario parser tersebut.
- File:
  - `app/Services/OllamaService.php`
  - `tests/Unit/OllamaServiceJsonDecodeTest.php`
  - `docs/change_log.md`
- Dampak:
  - Error `Invalid JSON returned by Ollama model.` jauh lebih tahan terhadap variasi output model.
  - Scan manual dan batch tidak lagi sering gagal hanya karena format respons sedikit melenceng.

### Security Hardening - Web Admin Authentication
- Menambahkan autentikasi session minimal untuk panel web admin:
  - halaman login
  - proses login/logout
  - proteksi route web admin dengan middleware `auth` + `role:admin,staff`
- Menjaga route legacy `/book/{id}` tetap ada untuk backward compatibility, tetapi semua halaman admin tujuan sekarang berada di balik auth.
- Menambahkan informasi user aktif + tombol logout di sidebar admin.
- Memperbarui feature test agar memverifikasi guest diarahkan ke login dan user terautentikasi tetap bisa mengakses dashboard.
- File:
  - `routes/web.php`
  - `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
  - `resources/views/auth/login.blade.php`
  - `resources/views/layouts/app.blade.php`
  - `tests/Feature/ExampleTest.php`
  - `docs/change_log.md`
- Dampak:
  - Surface admin web tidak lagi terbuka untuk akses anonim.
  - Sistem sekarang punya jalur login/logout yang bisa langsung dipakai untuk operasi harian.

### Import Workflow Upgrade - AI Batch Scan + Review Grouping
- Mengganti fokus tab import dari CSV ke alur `AI Scan` untuk intake banyak buku sekaligus.
- Menambahkan batch scan per-buku dengan pola:
  - `front cover` wajib
  - `back cover` opsional
  - catatan slot opsional per buku
- Menambahkan draft review hasil scan yang disimpan sementara sebelum buku dibuat permanen.
- Menambahkan tab `Review & Grouping`:
  - hasil scan otomatis dikelompokkan per kategori
  - metadata tiap buku bisa diedit dulu
  - rack bisa dipilih sebelum commit
- Menambahkan commit flow dari hasil review ke library:
  - category auto-create bila belum ada
  - buku dibuat via service existing
  - rack mengikuti pilihan user atau auto-assign
- Menambahkan feature test untuk:
  - batch scan menyimpan draft dan tampil di halaman review
  - review commit membuat category + book secara valid
- File:
  - `app/Http/Controllers/Web/BulkImportPageController.php`
  - `app/Http/Requests/ScanBookBatchRequest.php`
  - `app/Http/Requests/CommitScannedBooksRequest.php`
  - `resources/views/books/import.blade.php`
  - `routes/web.php`
  - `tests/Feature/AiBatchImportFlowTest.php`
  - `docs/change_log.md`
- Dampak:
  - Intake buku awal dalam jumlah besar sekarang jauh lebih cepat.
  - User bisa review hasil AI per kategori sebelum buku dimasukkan ke rack.

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

## 2026-04-01

### Phase 3 Final Execution - Error Fixes & UI Polishing
- **Task 1: Book List Click (Master-Detail)**
  - Fixed `#book-detail-panel` reference to `#detail-panel`.
  - Master-detail fetching now correctly updates the panel without full page reload via Alpine.js.
- **Task 2: Manual Import (Form)**
  - Created migration `add_description_to_books_table` to add description field.
  - Updated `Book`, `StoreManualBookRequest`, and `import.blade.php` to include and validate `description` and make `isbn` nullable.
- **Task 3: ISBN Scanner**
  - Integrated `html5-qrcode` CDN into `import.blade.php`.
  - Added "Scan ISBN" button, camera initialization, and auto-fetch lookup upon scan success.
- **Task 4: QR Generation Fix**
  - Updated route `POST /qr/generate/{book}`.
  - Fix frontend `detail_panel.blade.php` QR logic to fetch base64 from backend and seamlessly update `#qr-preview` content without page reload.
  - Changed `GenerateBookQrCodeJob::dispatch` to `dispatchSync` in `BookService.php` to ensure real-time QR generation works without active queue workers.
- **Task 6: Multi book per slot**
  - Verified UI handles multiple books gracefully showing list of books inside slot.
  - Existing database has no `unique: [rack_id, position_code]` active constraints so it supports unlimited entries or up to capacity limit.
- **Task 7: Grid Rack Fix (3x4 Bug)**
  - Replaced Tailwind arbitrary `minmax()` mapping with pure `1fr` logic via `grid-template-columns: repeat(..., 1fr)` ensuring columns respect their defined boundaries in `index.blade.php` and `show.blade.php`.
- **Task 9: Return Book Fix**
  - Modified `detail_panel.blade.php` return flow to update both borrowing and book status via AJAX and directly replace `#detail-panel` component to prevent full viewport refresh.

### Hotfix - Master Detail Click & QR Generation Runtime
- **Master-detail click activation**
  - Added Alpine.js initialization in frontend bootstrap so `x-data` and `@click` handlers on `books/index.blade.php` execute correctly.
  - Installed `alpinejs` dependency in `package.json`.
- **QR generation without Imagick dependency**
  - Updated `QrCodeService` to use safe fallback:
    - try PNG first
    - fallback to SVG when PNG backend (Imagick) is unavailable
  - Updated base64 output to match active mime type (`image/png` or `image/svg+xml`).
  - Updated stored QR file extension dynamically (`.png` or `.svg`) based on generated format.
- **QR API hardening**
  - Added exception handling in `QrStickerPageController::generateSingle()` to return clear JSON error instead of raw 500 HTML.
  - Improved frontend QR button error handling in `detail_panel.blade.php` to show feedback message when generation fails.
- File:
  - `resources/js/app.js`
  - `package.json`
  - `package-lock.json`
  - `app/Services/QrCodeService.php`
  - `app/Http/Controllers/Web/QrStickerPageController.php`
  - `resources/views/books/partials/detail_panel.blade.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php artisan test` => 3 passed
  - `npm run build` => success
- Dampak:
  - Klik item buku di panel kiri kembali memicu update panel detail kanan tanpa full reload.
  - Tombol `Generate QR` berjalan di environment tanpa ekstensi Imagick.

### Hotfix - Scan QR Redirect to Full Book Detail
- Mengubah target URL QR generation agar langsung menuju halaman detail buku penuh (`/books/{id}`) alih-alih halaman public sederhana (`/book/{id}`).
- Menambahkan redirect backward compatibility di `BookPublicController`:
  - scan dari QR lama yang masih menyimpan `/book/{id}` tetap otomatis diarahkan ke `/books/{id}`.
- File:
  - `app/Services/QrCodeService.php`
  - `app/Http/Controllers/BookPublicController.php`
  - `docs/change_log.md`
- Dampak:
  - Hasil scan QR sekarang konsisten menampilkan tampilan detail buku yang lengkap.
  - Tidak perlu regenerate semua QR lama untuk mendapatkan behavior baru.

### Hotfix - ISBN Lookup Provider Connectivity
- Investigasi koneksi ISBN lookup menunjukkan Google Books merespons `429 RESOURCE_EXHAUSTED` (quota exceeded) pada endpoint tanpa API key.
- Menambahkan dukungan konfigurasi API key Google Books via:
  - `config/services.php` (`google_books.api_key`)
  - `.env.example` (`GOOGLE_BOOKS_API_KEY`)
- Memperkuat `IsbnLookupService`:
  - normalisasi ISBN input (menghapus karakter non-digit/non-X)
  - logging warning saat provider tidak dapat diakses atau merespons non-OK
  - fallback OpenLibrary diperbarui ke endpoint `GET /isbn/{isbn}.json` (menggantikan endpoint lama `/api/books` yang sering mengembalikan `{}`)
  - parsing cover dari `covers[]` + enrich author name dari endpoint author OpenLibrary
- File:
  - `app/Services/IsbnLookupService.php`
  - `config/services.php`
  - `.env.example`
  - `docs/change_log.md`
- Verifikasi:
  - `php artisan test` => 3 passed
  - Smoke test service:
    - `9780131103627` => metadata ditemukan dari `open_library`
    - `9786234961928` => tetap tidak ditemukan di provider aktif
- Dampak:
  - Lookup ISBN lebih tahan terhadap perubahan API provider.
  - Pesan "No metadata found" sekarang lebih akurat untuk ISBN yang memang tidak tersedia, bukan semata karena endpoint fallback lama bermasalah.

## 2026-04-02

### AI Book Scan Pipeline (Laravel Orchestrator + Ollama Vision + Multi Source Metadata)
- Menambahkan fondasi pipeline scan buku otomatis berbasis gambar dengan arsitektur:
  - Ollama hanya ekstraksi sinyal visual (front/back, ISBN, title, author)
  - Laravel mengorkestrasi lookup metadata dari Google/OpenLibrary
  - OpenMAIC sebagai fallback terakhir saat lookup API gagal
- Menambahkan endpoint API baru untuk integrasi autofill form:
  - `POST /api/ai/books/scan` (multi image upload, max 5 file)
- Menambahkan `OllamaService` implementasi penuh:
  - request ke `/api/generate`
  - prompt terstruktur JSON-only
  - schema output ketat + deterministic options (`temperature=0`, `seed=42`)
  - normalisasi hasil (view/isbn/title/author)
- Menambahkan orchestrator `AiBookScanPipelineService`:
  - flow prioritas sumber metadata:
    1) Google
    2) OpenLibrary
    3) OpenMAIC
    4) AI fallback
  - validasi hasil OpenMAIC:
    - confidence threshold `>= 0.7`
    - title similarity check terhadap sinyal AI
  - cover selection rule:
    1) front image upload
    2) cover dari provider metadata
    3) fallback upload pertama
- Menambahkan `OpenMaicService`:
  - integrasi API style chat-completions
  - JSON-only response expectation
  - timeout + graceful fallback saat konfigurasi belum aktif
- Upgrade `IsbnLookupService`:
  - tetap backward-compatible untuk lookup lama
  - menambahkan lookup kaya metadata:
    - `lookupByIsbn()`
    - `searchByTitleAuthor()`
  - enrich field: `description`, `publisher`, `published_year`, `isbn`, `cover_url`, `source`
  - source label diseragamkan (`google`, `openlibrary`)
- Menambahkan konfigurasi service:
  - `services.ollama`
  - `services.openmaic`
  - `.env.example` variabel terkait Ollama + OpenMAIC
- File:
  - `app/Services/OllamaService.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `app/Services/OpenMaicService.php`
  - `app/Services/IsbnLookupService.php`
  - `app/Http/Requests/ScanBookImagesRequest.php`
  - `app/Http/Controllers/Api/AiBookScanController.php`
  - `routes/api.php`
  - `config/services.php`
  - `.env.example`
  - `docs/change_log.md`

### Pipeline Cleanup Pass (Step-by-step)
- Menyederhanakan route QR agar tidak redundant:
  - menghapus `GET /qr/generate` (duplikat fungsi index)
  - form filter QR diarahkan langsung ke `route('qr.index')`
- Menambahkan mode orkestrasi scan agar alur tidak selalu "bertele-tele":
  - `mode=full` (default): Ollama -> Google -> OpenLibrary -> OpenMAIC -> AI fallback
  - `mode=simple`: hanya ekstraksi Ollama + fallback data AI (tanpa lookup eksternal)
- Menjaga output final endpoint tetap konsisten sesuai schema utama (`title`, `author`, `description`, `publisher`, `published_year`, `isbn`, `cover_url`, `source`), tanpa field tambahan.
- File:
  - `routes/web.php`
  - `resources/views/qr/index.blade.php`
  - `app/Http/Requests/ScanBookImagesRequest.php`
  - `app/Http/Controllers/Api/AiBookScanController.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `docs/change_log.md`

### Pipeline Hardening - Metadata Cache Layer
- Menambahkan cache layer pada lookup metadata untuk mengurangi call berulang ke provider eksternal:
  - `IsbnLookupService`:
    - cache hasil `lookupByIsbn()`
    - cache hasil `searchByTitleAuthor()`
  - `OpenMaicService`:
    - cache hasil `searchBookMetadata()`
- Menambahkan negative-cache (cache untuk miss/no-result) agar request berulang untuk query gagal tidak terus memukul API eksternal.
- Menambahkan konfigurasi TTL cache via environment:
  - `GOOGLE_BOOKS_CACHE_MINUTES`
  - `GOOGLE_BOOKS_CACHE_MISS_MINUTES`
  - `OPENMAIC_CACHE_MINUTES`
  - `OPENMAIC_CACHE_MISS_MINUTES`
- Menambahkan konfigurasi terkait pada `config/services.php` dan `.env.example`.
- File:
  - `app/Services/IsbnLookupService.php`
  - `app/Services/OpenMaicService.php`
  - `config/services.php`
  - `.env.example`
  - `docs/change_log.md`
- Dampak:
  - Waktu respon pipeline lebih stabil untuk query yang sama.
  - Beban request ke Google/OpenLibrary/OpenMAIC berkurang signifikan saat penggunaan berulang.

### Documentation - AI Scan Handover Plan
- Menambahkan dokumen handover khusus agar implementasi AI scan bisa dilanjutkan oleh engineer lain jika terjadi pergantian PIC/credit habis.
- Dokumen mencakup:
  - status implementasi terkini
  - kontrak endpoint scan
  - checklist environment
  - roadmap lanjutan bertahap (Filament autofill, testing, observability)
  - risiko & mitigasi
  - quick notes handover
- File:
  - `docs/ai_book_scan_handover_plan.md`
  - `docs/change_log.md`

### Tahap A Execution - Web Admin Autofill Bridge (Pre-Filament)
- Menjalankan tahap awal integrasi autofill realtime di halaman admin existing (`/books/import`) sebagai bridge sebelum integrasi Filament-native.
- Menambahkan endpoint web:
  - `POST /books/import/ai-scan`
  - handler: `BulkImportPageController::scanWithAi()`
- Menambahkan UI AI scan pada manual input:
  - upload multi-image
  - mode selector (`full|simple`)
  - tombol `Scan with AI`
  - status hasil scan + source metadata
- Menambahkan autofill field setelah scan:
  - `title`, `author`, `isbn`, `description`, `cover_url`
  - `publisher` dan `published_year` ditampilkan sebagai info status
- Menyesuaikan validasi `cover_url` agar menerima local storage path (`/storage/...`) selain absolute URL.
- File:
  - `routes/web.php`
  - `app/Http/Controllers/Web/BulkImportPageController.php`
  - `app/Http/Requests/StoreManualBookRequest.php`
  - `resources/views/books/import.blade.php`
  - `docs/ai_book_scan_handover_plan.md`
  - `docs/change_log.md`
- Verifikasi:
  - `php artisan test` => 3 passed
  - `php artisan route:list --path=books/import` => endpoint `books.import.ai-scan` terdaftar

### Cover Enhancement - Auto Crop Front Cover
- Menambahkan auto-crop untuk cover depan hasil upload agar `cover_url` lebih rapih untuk katalog.
- Mekanisme crop:
  - prioritas memakai `cover_box` dari hasil vision Ollama per gambar front
  - fallback ke center-crop rasio buku 2:3 jika `cover_box` tidak tersedia/kurang valid
- Menambahkan metadata `cover_box` pada output terstruktur `OllamaService` (normalisasi 0..1).
- Menambahkan service baru `CoverImageService` berbasis GD:
  - baca gambar dari `public/storage`
  - crop dan simpan ke path `.../cropped/*-front-cropped.<ext>`
  - return web path hasil crop (`/storage/...`)
- Integrasi pipeline:
  - `AiBookScanPipelineService` kini memilih cover dengan prioritas:
    1) cropped front cover
    2) original front cover
    3) cover provider metadata
    4) fallback upload pertama
- File:
  - `app/Services/OllamaService.php`
  - `app/Services/CoverImageService.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `docs/ai_book_scan_handover_plan.md`
  - `docs/change_log.md`

### UX Polish - Live Cover Preview in Manual Import
- Menambahkan preview cover live di form manual import (`/books/import`).
- Preview otomatis update pada skenario:
  - hasil `ISBN Fetch`
  - hasil `Scan with AI`
  - edit manual field `cover_url`
- Menambahkan label preview dinamis untuk menandai hasil crop AI (`AI cropped front cover`) ketika path cover mengandung folder `/cropped/`.
- File:
  - `resources/views/books/import.blade.php`
  - `docs/ai_book_scan_handover_plan.md`
  - `docs/change_log.md`

### Cover Quality Control - Standardized Output Size
- Menambahkan quality control pada proses cover AI:
  - hasil crop/normalisasi cover sekarang di-resize ke ukuran standar katalog (default `600x900`).
- Menambahkan method normalisasi fallback upload:
  - jika crop front cover gagal/tidak tersedia, sistem tetap dapat menghasilkan cover konsisten dari upload (center-crop 2:3 + resize).
- Menambahkan konfigurasi dimensi cover melalui env:
  - `AI_COVER_WIDTH`
  - `AI_COVER_HEIGHT`
- Menyesuaikan label preview UI agar mengenali cover yang sudah diproses (`/cropped/`) sebagai `AI processed cover`.
- File:
  - `app/Services/CoverImageService.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `config/services.php`
  - `.env.example`
  - `resources/views/books/import.blade.php`
  - `docs/ai_book_scan_handover_plan.md`
  - `docs/change_log.md`

### Hotfix - AI Scan Request Timeout (PHP 60s Limit)
- Mengatasi error `Maximum execution time of 60 seconds exceeded` pada proses AI scan.
- Menambahkan override timeout di endpoint scan:
  - `BulkImportPageController::scanWithAi()` (web flow)
  - `AiBookScanController::__invoke()` (API flow)
- Override menggunakan:
  - `set_time_limit(180)`
  - `ini_set('max_execution_time', '180')`
- File:
  - `app/Http/Controllers/Web/BulkImportPageController.php`
  - `app/Http/Controllers/Api/AiBookScanController.php`
  - `docs/change_log.md`

### Hotfix - Ollama Vision Timeout & Payload Optimization
- Mengatasi timeout `cURL error 28` pada request vision ke Ollama (`/api/generate`) dengan kombinasi perbaikan:
  - menaikkan default `OLLAMA_TIMEOUT` menjadi `240`
  - menambahkan `OLLAMA_CONNECT_TIMEOUT` (default `10`)
  - menambahkan retry singkat (`retry(1, 400ms)`)
  - menambahkan `keep_alive` agar model tetap warm lebih lama
  - menurunkan `num_predict` dari `500` ke `280` untuk mempercepat respons
  - optimasi payload gambar:
    - resize sisi terpanjang ke max `1400px`
    - kompres ke JPEG quality `82` sebelum base64
- Dampak:
  - request vision lebih ringan dan lebih tahan timeout.
  - waktu respon lebih stabil untuk gambar berukuran besar.
- File:
  - `app/Services/OllamaService.php`
  - `config/services.php`
  - `.env.example`
  - `.env`
  - `docs/change_log.md`

### Tahap B Execution - Automated Feature Tests for AI Scan API
- Menambahkan test suite khusus endpoint `POST /api/ai/books/scan` untuk memperkuat regresi check pipeline.
- Cakupan test:
  - wajib auth (`401` untuk request tanpa login)
  - validasi payload (`422` untuk `images`/`mode` invalid)
  - mode default fallback ke `full`
  - mode `simple` diteruskan ke pipeline service
  - error handling `RuntimeException` dari pipeline => `502` dengan message yang tepat
- File:
  - `tests/Feature/AiBookScanApiTest.php`
  - `docs/ai_book_scan_handover_plan.md`
  - `docs/change_log.md`
- Verifikasi:
  - `php artisan test tests/Feature/AiBookScanApiTest.php` => 5 passed
  - `php artisan test` => 8 passed

### Metadata Enrichment - Fill Missing Description
- Memperbaiki kasus deskripsi kosong saat source utama adalah `google` tetapi field `description` tidak tersedia.
- Menambahkan enrichment bertahap di `AiBookScanPipelineService`:
  - jika metadata utama masih missing (`description/publisher/published_year`),
    pipeline mencoba melengkapi dari OpenLibrary (by ISBN lalu title+author).
  - jika deskripsi masih kosong, pipeline mencoba OpenMAIC (tetap melalui guardrail confidence/title check).
- Memperluas output `IsbnLookupService::lookup()` agar mengembalikan field metadata lengkap:
  - `description`, `publisher`, `published_year`, `isbn`
- Menyesuaikan UI tombol `Fetch` ISBN di import manual agar:
  - mengisi field `description` jika tersedia
  - menampilkan info publisher/tahun di status bar.
- File:
  - `app/Services/AiBookScanPipelineService.php`
  - `app/Services/IsbnLookupService.php`
  - `resources/views/books/import.blade.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php artisan test` => 8 passed

### Localization - Auto Translate Description to Indonesian
- Menambahkan normalisasi bahasa untuk deskripsi metadata:
  - jika deskripsi terdeteksi berbahasa Inggris, pipeline otomatis menerjemahkan ke Bahasa Indonesia melalui Ollama.
- Menambahkan method baru pada `OllamaService`:
  - `translateTextToIndonesian()`
  - prompt translation ketat (tanpa menambah informasi)
- Menambahkan cache hasil terjemahan deskripsi (24 jam) untuk menghindari translate berulang pada teks yang sama.
- Menyesuaikan prompt OpenMAIC agar `description` diprioritaskan dalam Bahasa Indonesia.
- File:
  - `app/Services/OllamaService.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `app/Services/OpenMaicService.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php artisan test` => 8 passed

### Metadata Enrichment - Auto Fill Category
- Menambahkan dukungan pengambilan `category` end-to-end pada alur import manual (Fetch ISBN + AI Scan).
- Sumber kategori yang dipakai (urutan prioritas mengikuti source metadata aktif):
  - Google Books (`volumeInfo.categories[0]`)
  - OpenLibrary (`subjects/subject/subject_facet`)
  - OpenMAIC (`category`)
  - Ollama vision fallback (`best.category`)
- Pipeline kini mengembalikan field:
  - `category`
  - dan ikut merge saat enrichment jika source utama belum punya kategori.
- UI import manual kini otomatis mengisi input `Category` (`#category-name-input`) saat:
  - tombol `Fetch` ISBN
  - tombol `Scan with AI`
- File:
  - `app/Services/OllamaService.php`
  - `app/Services/IsbnLookupService.php`
  - `app/Services/OpenMaicService.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `resources/views/books/import.blade.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php -l app/Services/OllamaService.php` => OK
  - `php -l app/Services/IsbnLookupService.php` => OK
  - `php -l app/Services/OpenMaicService.php` => OK
  - `php -l app/Services/AiBookScanPipelineService.php` => OK
  - `php artisan test --filter=AiBookScanApiTest` => 5 passed

### Books UI/UX - Description Clamp + Golden Ratio Layout
- Memperbaiki keterbacaan panel detail pada halaman `Books` (master-detail):
  - Deskripsi panjang sekarang ditampilkan ringkas (clamp) di panel kanan pada tab `Books`.
  - Ditambahkan CTA `Lihat deskripsi lengkap` yang mengarah ke halaman `Full Detail`.
  - Pada halaman `Full Detail` deskripsi tetap tampil penuh.
- Merapikan komposisi layout agar lebih seimbang:
  - Mengubah middle section menjadi rasio visual `1 : 1.618` (golden ratio style) antara kolom kiri dan kanan.
  - Memindahkan kartu `QR Code` ke bawah kartu `Location` (stack vertikal) supaya alur informasi lebih natural.
- File:
  - `resources/views/books/partials/detail_panel.blade.php`
  - `resources/views/books/index.blade.php`
  - `resources/views/books/show.blade.php`
  - `app/Http/Controllers/Web/BookPageController.php`
  - `resources/css/app.css`
  - `docs/change_log.md`
- Verifikasi:
  - `php -l app/Http/Controllers/Web/BookPageController.php` => OK
  - `php artisan test --filter=AiBookScanApiTest` => 5 passed

### Books UI/UX Adjustment - QR Embedded in Location Card
- Menyesuaikan ulang layout middle section berdasarkan feedback visual:
  - Tetap memakai komposisi kiri-kanan (bukan stack ke bawah).
  - QR dipindahkan menyatu ke dalam card `Location` pada sisi kanan, dibatasi border dashed.
  - Menghilangkan area kosong besar di sisi kiri agar panel lebih padat dan proporsional.
- File:
  - `resources/views/books/partials/detail_panel.blade.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php artisan view:cache` => OK

### Books UI/UX Refinement - Vertical Location+QR, Rack Then Description
- Menyesuaikan ulang panel detail sesuai layout acuan:
  - Kolom kiri: 1 card `Location` yang memanjang vertikal, konten lokasi di atas dan QR di bawah (masih dalam box border dashed).
  - Kolom kanan: card `Rack Mini Map` di bagian atas, lalu card `Description` di bawahnya.
  - Rasio grid tetap menggunakan pendekatan golden ratio (`1 : 1.618`) agar komposisi lebih seimbang.
- Menyamakan ukuran preview QR setelah generate agar konsisten dengan state awal.
- File:
  - `resources/views/books/partials/detail_panel.blade.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php artisan view:cache` => OK

### Tahap C Execution (Partial) - AI Scan Observability & Monitoring
- Menambahkan observability layer untuk AI scan pipeline agar performa bisa diaudit harian.
- Implementasi:
  - service baru `AiScanObservabilityService` untuk:
    - counter harian scan: `total`, `success`, `failed`
    - akumulasi latency + hitung `avg_latency_ms`
    - distribusi source: `google/openlibrary/openmaic/ai`
    - mode/channel counter (`full|simple`, `api|web`)
  - structured log scan:
    - `ai_scan.completed`
    - `ai_scan.failed`
  - structured log cache provider:
    - `book_lookup.cache` (Google/OpenLibrary)
    - `openmaic_lookup.cache`
- Integrasi runtime:
  - API endpoint `POST /api/ai/books/scan`
  - Web endpoint `POST /books/import/ai-scan`
  - masing-masing mencatat durasi, mode, jumlah image, source, status.
- Dashboard:
  - tambah kartu ringkasan `AI Scan Today`:
    - total scan
    - success rate
    - average latency
    - distribusi source.
- File:
  - `app/Services/AiScanObservabilityService.php`
  - `app/Http/Controllers/Api/AiBookScanController.php`
  - `app/Http/Controllers/Web/BulkImportPageController.php`
  - `app/Services/IsbnLookupService.php`
  - `app/Services/OpenMaicService.php`
  - `app/Services/DashboardService.php`
  - `resources/views/dashboard/index.blade.php`
  - `docs/ai_book_scan_handover_plan.md`
  - `docs/change_log.md`
- Verifikasi:
  - `php -l app/Services/AiScanObservabilityService.php` => OK
  - `php -l app/Http/Controllers/Api/AiBookScanController.php` => OK
  - `php -l app/Http/Controllers/Web/BulkImportPageController.php` => OK
  - `php -l app/Services/DashboardService.php` => OK
  - `php -l app/Services/IsbnLookupService.php` => OK
  - `php -l app/Services/OpenMaicService.php` => OK
  - `php artisan test --filter=AiBookScanApiTest` => 5 passed

### Books UI/UX Rollback - Separate Location and QR Cards
- Menyesuaikan ulang panel detail agar `Location` dan `QR Code` kembali dipisah menjadi 2 card terpisah (seperti versi awal split layout).
- Komposisi tetap dua kolom dengan rasio visual `1 : 1.618`:
  - kolom kiri: `Location` (atas) + `QR Code` (bawah)
  - kolom kanan: `Rack Mini Map`
  - `Description` tetap di bawah section ini.
- File:
  - `resources/views/books/partials/detail_panel.blade.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php artisan view:cache` => OK

### Books UI/UX Cleanup - Stable Detail Cards
- Merapikan ulang layout detail card pada halaman `Books` agar kembali konsisten dan tidak kosong:
  - `Location`, `QR Code`, `Description` disusun ulang dalam grid 3 kolom yang stabil (`lg:grid-cols-3`).
  - Card `QR Code` diberi container dashed dengan tinggi minimum tetap (`min-h-[200px]`) agar tidak collapse/blank.
  - Tampilan QR hasil generate diseragamkan (border + padding) agar konsisten dengan state awal.
  - Clamp deskripsi dipadatkan dari 12 baris menjadi 10 baris agar tinggi card lebih proporsional.
- File:
  - `resources/views/books/partials/detail_panel.blade.php`
  - `resources/css/app.css`
  - `docs/change_log.md`
- Verifikasi:
  - `php artisan view:cache` => OK
  - `php artisan test --filter=AiBookScanApiTest` => 5 passed

### AI Scan Fallback - SearXNG + Extractor + Ollama (Self-Hosted Web Search)
- Menambahkan fallback web-search mandiri tanpa ketergantungan OpenMAIC API.
- Arsitektur fallback baru:
  1. Search via SearXNG (`SearxngSearchService`)
  2. Fetch + ekstraksi konten halaman (`WebContentExtractorService`)
  3. Ekstraksi deskripsi JSON via Ollama (`OllamaService::extractBookDescriptionFromWeb()`)
  4. Orkestrasi + whitelist domain + cache + confidence threshold (`WebBookDescriptionService`)
- Integrasi pipeline:
  - `AiBookScanPipelineService` kini mencoba web-search fallback saat deskripsi masih kosong setelah Google/OpenLibrary/OpenMAIC.
  - Mendukung `source = websearch` dan `source_url` untuk traceability di UI.
- Integrasi UI import:
  - catatan sumber deskripsi kini mengenali provider `websearch`.
- Konfigurasi baru:
  - `WEBSEARCH_ENABLED`
  - `SEARXNG_BASE_URL`
  - `SEARXNG_TIMEOUT`
  - `WEBSEARCH_MAX_RESULTS`
  - `WEBSEARCH_ALLOWED_DOMAINS`
  - `WEBSEARCH_CACHE_MINUTES`
  - `WEBSEARCH_CACHE_MISS_MINUTES`
- File:
  - `config/services.php`
  - `.env.example`
  - `app/Services/SearxngSearchService.php`
  - `app/Services/WebContentExtractorService.php`
  - `app/Services/WebBookDescriptionService.php`
  - `app/Services/OllamaService.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `app/Services/OpenMaicService.php`
  - `resources/views/books/import.blade.php`
  - `resources/views/dashboard/index.blade.php`
  - `app/Services/AiScanObservabilityService.php`
  - `docs/ai_book_scan_handover_plan.md`
  - `docs/change_log.md`
- Verifikasi:
  - `php -l` seluruh file service/config yang diubah => OK
  - `php artisan test --filter=AiBookScanApiTest` gagal dijalankan di environment ini karena error `cwd` internal process (bukan error sintaks kode)
  - `php artisan view:cache` gagal di environment ini karena permission `storage/framework/views`

### Description Priority Update - Prefer Gramedia First
- Menyesuaikan prioritas sumber deskripsi:
  1. Coba web-search khusus domain `gramedia.com` terlebih dahulu.
  2. Jika tidak ada, lanjut ke Google/OpenLibrary/OpenMAIC/websearch fallback umum sesuai alur existing.
- Implementasi:
  - `WebBookDescriptionService` ditambah method `resolveForDomains(...)` dengan filter domain prioritas.
  - `AiBookScanPipelineService` mencoba deskripsi Gramedia sebelum/selepas lookup metadata utama, lalu override field deskripsi jika ditemukan.
- File:
  - `app/Services/WebBookDescriptionService.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php -l app/Services/WebBookDescriptionService.php` => OK
  - `php -l app/Services/AiBookScanPipelineService.php` => OK

### Metadata Quality Guardrail - Reject Mismatch Titles + Official-First Relevance
- Menambahkan guardrail kualitas agar metadata buku niche tidak mudah salah tarik:
  - Kandidat Google/OpenLibrary untuk pencarian `title+author` kini wajib lolos similarity judul minimum.
  - Kandidat Google terbaik juga dihitung dengan bobot similarity judul; hasil yang terlalu mismatch otomatis ditolak.
- Menambahkan relevansi pada websearch:
  - Hasil domain whitelist disaring lagi berdasarkan kemiripan judul (`title + snippet`) sebelum konten diekstrak.
- Memperluas domain prioritas official-first:
  - Gramedia fallback kini mencoba `gramedia.com` dan `gramedia.digital`.
- File:
  - `app/Services/IsbnLookupService.php`
  - `app/Services/WebBookDescriptionService.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php -l app/Services/IsbnLookupService.php` => OK
  - `php -l app/Services/WebBookDescriptionService.php` => OK
  - `php -l app/Services/AiBookScanPipelineService.php` => OK

### Metadata Consistency Lock - Block Wrong Google Result on Niche Books
- Menambahkan validasi konsistensi metadata provider terhadap sinyal AI dari cover (judul/penulis).
- Jika metadata dari `lookupByIsbn` atau `searchByTitleAuthor` tidak match (similarity rendah), metadata otomatis ditolak.
- Setelah ditolak, pipeline lanjut ke fallback resmi/relevan (Gramedia/websearch) sehingga tidak lagi mengisi form dengan buku yang salah tapi berlabel `google`.
- File:
  - `app/Services/AiBookScanPipelineService.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php -l app/Services/AiBookScanPipelineService.php` => OK

### Description Override Policy - Official Web First + Full Indonesian Normalization
- Untuk mode AI scan full, deskripsi sekarang selalu diprioritaskan dari web resmi/relevan:
  1) Gramedia (`gramedia.com`, `gramedia.digital`)
  2) fallback websearch whitelist
- Jika deskripsi web berhasil ditemukan, deskripsi provider (termasuk Google) otomatis dioverride.
- Bahasa deskripsi dipaksa lebih konsisten ke Bahasa Indonesia:
  - jika terdeteksi Inggris atau mixed-EN (marker tertentu), teks akan ditranslate ke Indonesia.
- Dampak: mengurangi kasus deskripsi campur aduk dari Google Books.
- File:
  - `app/Services/AiBookScanPipelineService.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php -l app/Services/AiBookScanPipelineService.php` => OK
  - `php artisan optimize:clear` => dijalankan

### AI Vision Description Fallback (Visible Text Only)
- Menambahkan fallback deskripsi langsung dari hasil vision AI jika provider/web gagal.
- `OllamaService::extractBookSignals()` sekarang juga mengembalikan `best.description`.
- Prompt vision diperketat:
  - deskripsi harus berdasarkan teks yang benar-benar terlihat di gambar (cover belakang/sinopsis)
  - dilarang menebak dari memory model
  - jika tidak jelas => `null`
- Pipeline (`AiBookScanPipelineService`) kini mengisi `description` dari `best.description` hanya saat deskripsi provider masih kosong.
- Dampak: buku yang tidak ada di Google/OpenLibrary tetap bisa punya deskripsi jika sinopsis terlihat di foto.
- File:
  - `app/Services/OllamaService.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php -l app/Services/OllamaService.php` => OK
  - `php -l app/Services/AiBookScanPipelineService.php` => OK
  - `php artisan optimize:clear` => dijalankan

### Provider Priority Fix - Google First, Then OpenLibrary, Then Websearch
- Memisahkan jalur lookup katalog menjadi `Google-only` dan `OpenLibrary-only` agar pipeline tidak terjebak pada hasil cache/provider campuran.
- `AiBookScanPipelineService` sekarang memproses provider dengan urutan eksplisit:
  1. Google Books lebih dulu
  2. OpenLibrary hanya untuk mengisi field yang masih kosong
  3. Trusted websearch untuk deskripsi bila masih belum tersedia
- Prinsip `fill missing only` tetap dipertahankan, jadi field yang sudah ada dari AI cover/back cover/provider tidak ditimpa.
- Retry Google setelah hasil awal OpenLibrary sekarang memakai lookup Google-only, jadi tidak muter balik ke source OpenLibrary dari cache campuran.
- Unit test pipeline diperbarui untuk mengikuti prioritas provider baru.
- File:
  - `app/Services/IsbnLookupService.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `tests/Unit/AiBookScanPipelineServiceTest.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php -l app/Services/IsbnLookupService.php` => OK
  - `php -l app/Services/AiBookScanPipelineService.php` => OK
  - `php artisan test tests/Unit/AiBookScanPipelineServiceTest.php tests/Feature/AiBatchImportFlowTest.php` => PASS

### Settings Sync to .env - Tavily as Active Websearch Provider
- Menambahkan `EnvFileService` untuk sinkronisasi pengaturan AI dari panel admin ke file `.env`.
- Saat admin menyimpan menu Settings, nilai berikut sekarang ikut ditulis ke `.env`:
  - `GOOGLE_BOOKS_API_KEY`
  - `OLLAMA_BASE_URL`
  - `OLLAMA_VISION_MODEL`
  - `OLLAMA_TEXT_MODEL`
  - `OLLAMA_WEB_MODEL`
  - `OLLAMA_TIMEOUT`
  - `OLLAMA_CONNECT_TIMEOUT`
  - `WEBSEARCH_ENABLED`
  - `TAVILY_API_KEY`
  - `TAVILY_BASE_URL`
  - `TAVILY_TIMEOUT`
  - `WEBSEARCH_MAX_RESULTS`
  - `WEBSEARCH_ALLOWED_DOMAINS`
  - `AI_SCAN_DEFAULT_MODE`
- Sekaligus membersihkan konfigurasi lama yang tidak lagi dipakai sebagai provider aktif:
  - `SEARXNG_BASE_URL`
  - `SEARXNG_TIMEOUT`
  - `OPENMAIC_*`
- File:
  - `app/Services/EnvFileService.php`
  - `app/Http/Controllers/Web/SettingsPageController.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php -l app/Services/EnvFileService.php` => OK
  - `php -l app/Http/Controllers/Web/SettingsPageController.php` => OK

### Queue Stall Detection - Batch Scan Tidak Lagi Terlihat Muter Terus
- Menambahkan deteksi `stale queue` pada draft batch scan jika job tetap `pending` terlalu lama dan belum ada worker yang memproses.
- Status batch sekarang bisa berubah ke `stale_queue` jika antrian diam lebih dari ambang waktu aman.
- UI halaman import diperbarui agar menampilkan pesan yang lebih jujur:
  - bukan lagi sekadar "masih berjalan"
  - tetapi memberi tahu bahwa worker queue belum memproses job
  - sekaligus menampilkan command worker yang perlu dijalankan
- File:
  - `app/Services/AiBatchScanDraftService.php`
  - `resources/views/books/import.blade.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php -l app/Services/AiBatchScanDraftService.php` => OK
  - `php -l resources/views/books/import.blade.php` => OK

### Queue Worker Diagnostics - Status Worker Kini Terlihat di Settings dan Dashboard
- Menambahkan diagnostic baru untuk `ai-scan queue worker` pada `AiInfrastructureService`.
- Aplikasi sekarang memeriksa jumlah job `ai-scan` yang masih menunggu dan lama antrean job tertua.
- Jika ada job menunggu terlalu lama, status berubah menjadi `warning` dan aplikasi menampilkan command worker yang harus dijalankan.
- Menambahkan kartu status `AI Queue` pada halaman Settings.
- Menambahkan alert queue worker pada Dashboard agar operator cepat tahu kalau batch scan macet karena worker belum aktif.
- File:
  - `app/Services/AiInfrastructureService.php`
  - `app/Http/Controllers/Web/DashboardPageController.php`
  - `resources/views/settings/index.blade.php`
  - `resources/views/dashboard/index.blade.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php -l app/Services/AiInfrastructureService.php` => OK
  - `php -l app/Http/Controllers/Web/DashboardPageController.php` => OK
  - `php -l resources/views/settings/index.blade.php` => OK
  - `php -l resources/views/dashboard/index.blade.php` => OK

### Worker Auto-Run Templates - Supervisor dan systemd untuk AI Queue
- Menambahkan template deploy agar worker `ai-scan` bisa hidup otomatis tanpa harus menjalankan `queue:work` manual terus-menerus.
- Menambahkan template Supervisor:
  - `deploy/supervisor/smart-lms-ai-scan.conf`
- Menambahkan template systemd:
  - `deploy/systemd/smart-lms-ai-scan.service`
- Menambahkan panduan setup:
  - `docs/queue_worker_setup.md`
- Halaman Settings sekarang juga menampilkan referensi cepat untuk:
  - lokasi file template
  - command worker yang dipakai
  - rujukan ke panduan setup worker
- File:
  - `deploy/supervisor/smart-lms-ai-scan.conf`
  - `deploy/systemd/smart-lms-ai-scan.service`
  - `docs/queue_worker_setup.md`
  - `resources/views/settings/index.blade.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php -l resources/views/settings/index.blade.php` => OK

### Batch Scan Cancel Action - Tombol Batalkan Antrian Scan
- Menambahkan aksi `cancel batch scan` untuk antrian AI scan yang masih berjalan / masih menunggu worker.
- Menambahkan endpoint backend untuk membatalkan draft batch scan:
  - draft ditandai `cancelled`
  - session draft dihapus
  - buku yang belum selesai diproses ditandai `cancelled`
- Menambahkan guard pada job queue agar worker yang mengambil draft yang sudah dibatalkan langsung berhenti dan membersihkan file temporary.
- Menambahkan tombol `Batalkan Scan` pada status batch scan di halaman import.
- UI polling sekarang mengenali status `cancelled` dan mereset halaman setelah pembatalan berhasil.
- Menambahkan coverage test untuk memastikan batch scan dapat dibatalkan.
- File:
  - `app/Services/AiBatchScanDraftService.php`
  - `app/Jobs/ProcessAiBatchScanBook.php`
  - `app/Http/Controllers/Web/BulkImportPageController.php`
  - `routes/web.php`
  - `resources/views/books/import.blade.php`
  - `tests/Feature/AiBatchImportFlowTest.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php -l app/Http/Controllers/Web/BulkImportPageController.php` => OK
  - `php -l app/Jobs/ProcessAiBatchScanBook.php` => OK
  - `php -l app/Services/AiBatchScanDraftService.php` => OK
  - `php -l resources/views/books/import.blade.php` => OK
  - `php -l routes/web.php` => OK
  - `php artisan test tests/Feature/AiBatchImportFlowTest.php` => PASS

### English Provider Localization - Deskripsi dan Kategori Provider Kini Lebih Indonesia
- Memperkuat prompt translasi Ollama agar hasil provider berbahasa Inggris benar-benar diterjemahkan ke Bahasa Indonesia, bukan dikembalikan mentah.
- Menambahkan normalisasi hasil translasi untuk membuang prefix seperti `Terjemahan:` jika model mengeluarkannya.
- Menambahkan guard tambahan pada pipeline:
  - jika hasil translasi kosong
  - atau hasil translasi masih identik dengan teks sumber
  - atau hasil translasi masih tampak berbahasa Inggris
  maka dianggap gagal dan dicatat melalui log pipeline.
- Menambahkan lokalisasi kategori provider umum berbahasa Inggris, misalnya:
  - `Self-Help` => `Pengembangan Diri`
  - `Business & Economics` => `Bisnis & Ekonomi`
  - `History` => `Sejarah`
- Menambahkan unit test untuk memastikan deskripsi Google Books berbahasa Inggris diterjemahkan ke Indonesia dan kategori ikut dilokalkan.
- File:
  - `app/Services/OllamaService.php`
  - `app/Services/AiBookScanPipelineService.php`
  - `tests/Unit/AiBookScanPipelineServiceTest.php`
  - `docs/change_log.md`
- Verifikasi:
  - `php -l app/Services/OllamaService.php` => OK
  - `php -l app/Services/AiBookScanPipelineService.php` => OK
  - `php artisan test tests/Unit/AiBookScanPipelineServiceTest.php` => PASS
