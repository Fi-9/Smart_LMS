# Queue Worker Setup

Panduan ini dipakai agar worker `ai-scan` selalu hidup otomatis dan batch scan tidak berhenti di status antrian.

## Opsi 1: Supervisor

Template tersedia di:

- `deploy/supervisor/smart-lms-ai-scan.conf`

Sesuaikan dulu:

- path project, contoh: `/var/www/smart_lms`
- user Linux, contoh: `www-data`
- path PHP, contoh: `/usr/bin/php`

Install:

```bash
sudo cp deploy/supervisor/smart-lms-ai-scan.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start smart-lms-ai-scan:*
sudo supervisorctl status
```

## Opsi 2: systemd

Template tersedia di:

- `deploy/systemd/smart-lms-ai-scan.service`

Sesuaikan dulu:

- path project, contoh: `/var/www/smart_lms`
- user Linux, contoh: `www-data`
- path PHP, contoh: `/usr/bin/php`

Install:

```bash
sudo cp deploy/systemd/smart-lms-ai-scan.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable smart-lms-ai-scan
sudo systemctl start smart-lms-ai-scan
sudo systemctl status smart-lms-ai-scan
```

## Command Worker

Command inti yang dipakai:

```bash
php artisan queue:work database --queue=ai-scan --tries=1 --sleep=1 --timeout=600 --backoff=5 --memory=768
```

## Cek Cepat

Kalau worker sehat:

- halaman `Settings` akan menampilkan status queue lebih jelas
- halaman `Import Books` tidak akan berhenti lama di status "Menunggu antrian"
- tabel `jobs` akan berkurang saat worker berjalan

## Catatan

- `numprocs=1` sengaja dipakai agar scan AI tetap satu per satu dan GPU/VRAM Ollama aman.
- Jika server sangat kuat dan nanti ingin eksperimen paralel, lakukan hati-hati karena model vision lokal bisa cepat OOM.
