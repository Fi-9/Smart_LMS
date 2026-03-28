# UI/UX Upgrade Notes (2026-03-28)

## Analisis Singkat
- Books page sebelumnya berbasis table dan kurang visual.
- Book detail belum masuk ke layout admin utama.
- Rack detail sudah punya logic assign yang kuat, tapi visual grid belum terasa seperti seat map 2D.
- QR page saat filter kosong belum memberi preview alternatif yang membantu user.

## Perubahan yang Diterapkan
1. Books List jadi card view modern.
2. Book detail page modern + cover + preview QR + quick action.
3. Rack visual ditingkatkan ke grid baris/kolom ala cinema.
4. QR page menampilkan empty-state informatif + recent preview fallback.
5. Dashboard card style diperbarui agar lebih modern.

## Dampak UX
- Navigasi buku jadi lebih cepat (card -> click detail).
- Keterbacaan status lokasi buku meningkat (rack/position/unassigned).
- Interaksi assign rack lebih intuitif untuk user non-teknis.
- Halaman QR tidak lagi terasa "mati" saat filter tidak mengembalikan data.

## Backlog Lanjutan yang Direkomendasikan
1. Print selected books (checkbox) di QR page.
2. Export PDF untuk QR print layout.
3. Peta lokasi rack untuk mode siswa dari hasil scan QR.
4. Dark/light theme toggle untuk dashboard admin.
