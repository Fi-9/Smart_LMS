📘 PRODUCT REQUIREMENTS DOCUMENT (PRD)
📌 Smart Library Management System (SLiMS+ QR)
1. 🎯 Overview

Nama Produk: Smart Library System
Platform: Web-based
Target User:

Admin perpustakaan
Petugas
(Opsional) Siswa

Deskripsi:
Aplikasi berbasis web untuk manajemen buku perpustakaan dengan fitur utama:

Tracking lokasi buku berbasis rak
QR Code untuk identifikasi buku
Input buku otomatis via ISBN
Sistem pencarian & monitoring buku
2. 🎯 Objectives
Mempermudah pengelolaan buku dalam jumlah besar
Mengurangi input manual
Mempercepat pencarian lokasi buku
Digitalisasi sistem perpustakaan
3. 🧑‍💻 Tech Stack
Backend
Laravel
Frontend
Blade / Inertia (opsional React)
Tailwind CSS
Database
MySQL / PostgreSQL
Integrasi API
Google Books API
Open Library
Tools Tambahan
QR Generator: Simple QrCode
Scanner: html5-qrcode
4. 🧩 Core Features
📚 4.1 Manajemen Buku

Fitur:

Tambah buku manual
Edit & hapus buku
Upload cover (opsional)
Assign ke rak

Field:

Judul
Penulis
ISBN
Kategori
Lokasi rak
QR Code
📷 4.2 Input Buku via ISBN

Flow:

User scan / input ISBN
Sistem call API
Auto fill data buku
User konfirmasi → simpan

Fallback:

Jika Google Books kosong → Open Library
🧾 4.3 Bulk Import Buku

Fitur:

Upload CSV / Excel
Preview data
Import massal
🔳 4.4 QR Code System

Fitur:

Generate QR otomatis saat buku dibuat
QR berisi URL unik buku

Contoh:

https://domain.com/book/{id}
📱 4.5 QR Scanner

Fitur:

Scan via kamera
Redirect ke detail buku
🧱 4.6 Manajemen Rak

Fitur:

Custom rak (A, B, C)
Struktur:
Rak → Baris → Kolom

Contoh:

Rak A
- Baris 1 → A1, A2, A3
- Baris 2 → B1, B2, B3
🔍 4.7 Search & Filter
Search by judul / penulis
Filter kategori
Filter rak
📊 4.8 Dashboard
Total buku
Buku per kategori
Buku per rak
Buku tersedia / dipinjam
5. 🧠 Smart Features (Optional)
Auto suggestion kategori
Auto assign rak (future AI)
Rekomendasi penempatan buku
6. 👥 User Roles
Admin
Full akses
Petugas
CRUD buku
Scan QR
Viewer (opsional)
Lihat buku saja
7. 🧱 Database Schema (High Level)
books
id
title
author
isbn
category_id
rack_id
qr_code
created_at
racks
id
name
row
column
categories
id
name
users
id
name
role
8. 🎨 UI/UX Guidelines
Style:
Clean, modern dashboard
Minimalist
Mobile friendly
Framework:
Tailwind CSS
Warna (Rekomendasi):
Primary: Biru (education feel)
Secondary: Abu soft
Accent: Hijau (status tersedia)
Komponen:
Card-based layout
Table + pagination
Modal form
Toast notification
9. 🔄 User Flow (Simplified)
➕ Tambah Buku
Input ISBN → Auto Fill → Simpan → QR dibuat
🔍 Cari Buku
Search → Klik → Lihat lokasi
📱 Scan QR
Scan → Redirect → Detail Buku + Lokasi
10. 🚀 Deployment
Environment:
VPS (Ubuntu)
Nginx / Apache
PHP 8+
Storage:
Local / cloud (optional)
11. ⚠️ Constraints
API limit (Google Books)
Data ISBN tidak selalu lengkap
Perlu internet untuk API
12. 📈 Future Roadmap
Sistem peminjaman buku
Mobile app
Integrasi RFID
AI rekomendasi rak
💡 Penutup

Ini PRD udah cukup:

Bisa langsung lu pakai buat development
Bisa lempar ke AI builder
Bisa jadi acuan tim

Kalau lu mau next step, gua bisa bantu:

🧱 bikin ERD + migration Laravel
🎨 desain UI dashboard (Tailwind)
⚙️ struktur folder project Laravel
🔌 flow API ISBN siap pakai