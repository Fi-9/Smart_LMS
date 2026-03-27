Nah ini baru mindset **engineer beneran** 🔥
Kalau rules & workflow jelas dari awal, AI (atau dev lain) nggak bakal “halu” dan project lu konsisten.

Gua bikinin **System Rules + Workflow + Guardrails AI** yang siap lu pakai buat:

* Prompt AI builder
* SOP development
* Acuan tim

---

# 📜 1. SYSTEM RULES (WAJIB, NON-NEGOTIABLE)

## 🧱 1.1 Arsitektur Rules

* Backend WAJIB pakai **Laravel**
* Database: MySQL / PostgreSQL
* Frontend: Blade + **Tailwind CSS**
* Tidak boleh pakai framework lain tanpa persetujuan

---

## 📦 1.2 Data Integrity Rules

* ISBN harus unik (nullable allowed)
* Setiap buku WAJIB punya:

  * rack_id
  * position_code
* QR Code WAJIB unique
* Tidak boleh ada buku tanpa lokasi

---

## 🔐 1.3 Security Rules

* Semua endpoint wajib auth
* Role-based access:

  * admin → full access
  * staff → terbatas
* Validasi input WAJIB (Form Request Laravel)

---

## ⚙️ 1.4 Coding Rules

* Gunakan:

  * Service Layer (Business logic)
  * Repository (optional)
* Dilarang:

  * Logic berat di Controller
* Gunakan naming konsisten:

  * snake_case (DB)
  * camelCase (code)

---

## 🧠 1.5 AI Usage Rules

AI hanya boleh digunakan untuk:

* Generate data dari ISBN API
* Suggest kategori (opsional)

AI TIDAK BOLEH:

* Menentukan lokasi rak
* Menyimpan data tanpa validasi user
* Override data existing

---

# 🔄 2. CORE WORKFLOW (FLOW UTAMA SISTEM)

---

## 📚 2.1 Workflow: Tambah Buku (PRIMARY FLOW)

```text
Input ISBN / Manual
        ↓
Call API (Google Books / Open Library)
        ↓
Auto Fill Data
        ↓
User Verifikasi/Edit
        ↓
Pilih Rack & Position
        ↓
Save Book
        ↓
Generate QR Code
```

---

## 📱 2.2 Workflow: Scan QR

```text
Scan QR
    ↓
Open URL /book/{id}
    ↓
Tampilkan:
- Judul
- Penulis
- Lokasi Rak
- Status
```

---

## 🧾 2.3 Workflow: Bulk Import

```text
Upload CSV
    ↓
Preview Data
    ↓
Validasi
    ↓
Assign Rack (manual / default)
    ↓
Import
    ↓
Generate QR (queue)
```

---

## 🧱 2.4 Workflow: Manajemen Rak

```text
Create Rack
    ↓
Set Rows & Columns
    ↓
Auto Generate Position (A1, B2, dll)
    ↓
Digunakan saat assign buku
```

---

# 🚧 3. VALIDATION RULES (BIAR NGGAK BERANTAKAN)

---

## 📚 Buku

* title → required
* author → required
* rack_id → required
* position_code → required
* isbn → nullable tapi unique

---

## 🧱 Position Rules

* Tidak boleh ada:

```text
Rack A → A1 dipakai 2 buku ❌
```

👉 Harus unique per rack

---

## 🔳 QR Rules

* Format:

```text
/domain.com/book/{id}
```

* Tidak boleh regenerate kecuali:

  * book dihapus
  * admin request

---

# 🧠 4. AI GUARDRAILS (ANTI HALU SYSTEM)

Ini penting banget buat lu kalau pakai AI builder 👇

---

## ❌ AI DILARANG:

* Mengubah struktur database
* Menambah field tanpa instruksi
* Menggunakan library random
* Mengganti arsitektur

---

## ✅ AI WAJIB:

* Mengikuti PRD
* Mengikuti migration schema
* Menggunakan Laravel best practice
* Membuat kode modular & scalable

---

## 🔒 AI RESPONSE FORMAT (WAJIB)

Kalau lu pakai AI builder, kasih rule ini:

```text
- Selalu jelaskan sebelum coding
- Jangan langsung generate semua file
- Ikuti step-by-step
- Jangan improvisasi di luar requirement
```

---

# 🧭 5. DEVELOPMENT PHASE (BIAR TERARAH)

---

## 🚀 Phase 1 (Core)

* Auth
* CRUD Buku
* CRUD Rack
* Position system

---

## ⚡ Phase 2

* QR generator
* QR scan page
* Detail buku page

---

## 🤖 Phase 3

* ISBN API integration
* Auto-fill data

---

## 📦 Phase 4

* Bulk import
* Queue system

---

## 🔥 Phase 5 (Optional)

* Borrowing system
* Dashboard analytics

---

# 🧪 6. TESTING RULES

* Test CRUD buku
* Test QR scan
* Test ISBN fetch
* Test posisi rak conflict

---

# 💡 7. NAMING CONVENTION

| Item     | Format     |
| -------- | ---------- |
| Table    | snake_case |
| Column   | snake_case |
| Class    | PascalCase |
| Function | camelCase  |

---

# 🧨 8. COMMON MISTAKES (WAJIB DIHINDARI)

❌ QR generate di frontend
❌ Logic di blade
❌ Hardcode rack
❌ Tidak validasi ISBN
❌ Tidak handle API gagal

---

# 🔥 FINAL SUMMARY

Dengan rules ini:

✅ AI nggak bakal ngaco
✅ Dev flow jelas
✅ System scalable
✅ Minim bug

---

Kalau lu mau makin mantap, next step gua saranin:

👉 🎯 **Bikin "Prompt Master" untuk AI builder (biar auto generate sesuai rules)**
👉 🎨 UI Dashboard Tailwind (biar langsung keliatan keren)

Tinggal bilang:
**"lanjut bang bikin prompt master AI nya"** 🚀
