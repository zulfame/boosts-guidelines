---
paths:
  - 'app/**'
  - 'config/**'
---

# Perilaku runtime

## Server tanpa state lokal
- Aplikasi harus bisa berjalan di lebih dari satu server. Di production, file upload memakai disk bersama (S3 atau sejenisnya), sedangkan sesi, cache, antrean, dan lock memakai store bersama (database atau Redis), bukan `file` atau `array`.
- Kode tidak bercabang berdasarkan nama environment. Perbedaan perilaku datang dari config. Satu-satunya pengecualian adalah pengaturan global di service provider, seperti dijelaskan di `providers.md`.

## Cache
- Setiap key cache punya satu pemilik di kode, TTL, dan jalur invalidasi yang diuji.
- Data per user atau per tenant tidak pernah disimpan di key bersama. Key memuat ID user, tenant, atau scope izin.
- Counter rate limit dan lockout disimpan di store bersama dengan TTL, tidak pernah di array statis di memori.
- Memoization di memori (array statis, `once()`) yang key-nya berasal dari input dibatasi jumlahnya.

## File dan data besar
- Upload dan unduhan besar di-stream (`Storage::readStream()`, `response()->streamDownload()`), tidak dimuat utuh ke memori.
- Arsip dari pengguna dicek jumlah entri, total ukuran setelah diekstrak, dan path yang keluar dari folder tujuan, sebelum diekstrak.
- File, koneksi, dan file sementara yang dibuka selalu ditutup di semua jalur, termasuk saat error.

## Kompatibilitas saat deploy
- Selama deploy, kode lama dan baru berjalan bersamaan. Payload job, payload event, entri cache, dan key config harus bisa dibaca kedua versi selama satu rilis. Menambah field boleh, menghapus atau mengganti nama dilakukan bertahap.
- Job yang sudah ada di antrean tetap harus bisa diproses setelah class-nya berubah. Jangan mengganti nama class job atau menghapus properti konstruktornya dalam satu rilis.
- Setiap perubahan yang tidak kompatibel dicatat di changelog.

## Feature flag dan script sekali jalan
- Feature flag dibaca dari satu tempat (config, atau Laravel Pennant jika dipasang), default mati, punya pemilik dan tanggal penghapusan di `docs/decisions.md`, dan kedua kondisinya diuji.
- Script data sekali jalan (backfill, perbaikan data) dibuat sebagai Artisan command yang aman diulang, mencatat hasilnya, dan disimpan di repositori. Perbaikan data tidak pernah dijalankan lewat tinker di production.

## Waktu
- Ambil waktu saat ini lewat `now()` atau facade `Date`, supaya bisa dikunci di test.
- Tanggal bisnis tanpa jam (tanggal lahir, jatuh tempo) disimpan sebagai kolom `date`, terpisah dari timestamp. Kejadian yang ditentukan dalam waktu lokal (jadwal rapat) menyimpan nama zona waktunya, bukan offset.
