# 🚀 Panduan Instalasi Smart_LMS di Local (Menggunakan XAMPP)

Panduan ini berisi langkah-langkah detail untuk menginstal dan menjalankan aplikasi **Smart_LMS (Smart Library Management System)** pada komputer lokal Anda.

## 📦 1. Persiapan Kebutuhan (Prerequisites)
Sebelum mulai, pastikan spesifikasi dasar berikut sudah ter-install di komputer Anda:
1. **XAMPP** (Mendukung PHP 8.2 ke atas). Berfungsi sebagai Apache Web Server dan MySQL Database. [Download XAMPP](https://www.apachefriends.org/index.html)
2. **Composer**. Berfungsi sebagai *package manager* untuk PHP/Laravel. [Download Composer](https://getcomposer.org/)
3. **Node.js** (Beserta NPM). Berfungsi untuk *build tool* frontend seperti Vite & Tailwind CSS. [Download Node.js](https://nodejs.org/)
4. Teks Editor, sangat disarankan menggunakan **Visual Studio Code (VS Code)**.

---

## ⚙️ 2. Menjalankan XAMPP & Membuat Database
1. Buka aplikasi **XAMPP Control Panel**.
2. Klik tombol **Start** pada modul **Apache** dan **MySQL**. Tunggu hingga tulisan berwarna latar hijau.
3. Buka browser dan pergi ke **phpMyAdmin**: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
4. Buat Database untuk aplikasi ini:
   * Klik tab **Databases** atau menu **Baru (New)**.
   * Masukkan nama database: `smart_lms_db` (tanpa tanda kutip, dan pisahkan pakai _).
   * Klik tombol **Buat (Create)**.

---

## 🛠️ 3. Konfigurasi Project Smart_LMS
1. Pindahkan folder project `Smart_LMS` Anda ke tempat yang diinginkan (secara default di Windows bisa menaruhnya bebas, tidak wajib di dalam *htdocs* karena menggunakan Laravel Server bawaan).
2. Buka folder `Smart_LMS` di **VS Code**.
3. Buka Terminal pada VS Code (Pilih tab *Terminal* -> *New Terminal*).
4. Instal semua paket PHP (Backend) menggunakan perintah:
   ```bash
   composer install
   ```
5. Instal semua paket *Node Modules* (Frontend UI, dll) menggunakan perintah:
   ```bash
   npm install
   ```

---

## 🔑 4. Setup File `.env` (Lingkungan Konfigurasi)
1. Di panel *file explorer* sebelah kiri, cari file `.env.example`.
2. Duplikat (*copy-paste*) file tersebut lalu ubah namanya menjadi `.env` saja.
3. Buka file `.env` yang baru digandakan, lalu cari blok **DB_CONNECTION** dan perbarui datanya seperti ini:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=smart_lms_db   # <- Samakan dengan nama database di langkah 2
   DB_USERNAME=root           # <- Secara bawaan akun XAMPP menggunakan username root
   DB_PASSWORD=               # <- Secara bawaan XAMPP tidak memiliki password (kosongkan)
   ```
4. Kembali ke terminal, masukkan *Application Key* ke dalam `.env` secara otomatis dengan perintah:
   ```bash
   php artisan key:generate
   ```

---

## 🗄️ 5. Menjalankan Migrasi Database
Sekarang kita perlu memasukkan tabel-tabel project (books, categories, racks, dll) ke dalam Database `smart_lms_db` yang masih kosong.
Di terminal jalankan instruksi berikut:
```bash
php artisan migrate
```
*(Apabila ada pertanyaan konfirmasi untuk membuat ulang atau migrasi, tekan "Y" / Yes).*

---

## 🚀 6. Jalankan Server Lokal!
Untuk akses aplikasi ini, Laravel 11 dengan *Vite tooling* mensyaratkan dua terminal untuk aktif bersamaan. Buka dua tab terminal di VS Code.

**Terminal 1 (Menjalankan server backend PHP):**
```bash
php artisan serve
```

**Terminal 2 (Menkalankan server asset Frontend CSS/JS):**
Secara berdampingan jalankan:
```bash
npm run dev
```

🎉 **Selesai!**
Buka browser favorit Anda (Google Chrome / Edge) dan arahkan ke tab:
👉 **[http://localhost:8000](http://localhost:8000)**

Sistem Smart_LMS akan berjalan secara independen dan bisa langsung Anda gunakan. Masing-masing fitur AI scanner atau QR code juga bisa digunakan secara langsung asalkan komputer terkoneksi di jaringan internet.
