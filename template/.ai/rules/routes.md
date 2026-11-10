---
paths:
  - 'routes/**'
  - routes/console.php
---

# Route

## Route bernama, dikelompokkan, dan dipisah per domain
- Setiap route punya nama dengan notasi titik (`products.index`). Kode PHP dan frontend selalu membuat URL dari nama route, tidak pernah menulis URL sebagai string.
- Gunakan `Route::resource()` jika aksinya mengikuti method resource standar. Halaman statis boleh memakai `Route::view()` atau padanannya di stack frontend.
- Halaman yang butuh login ada di dalam grup middleware `auth` (dan `verified` jika aplikasi mewajibkan verifikasi email).
- Jika file route mulai panjang, pisahkan per domain ke file sendiri yang di-require dari `routes/web.php`.

## Rate limit untuk endpoint sensitif
- Pasang `throttle` pada endpoint yang rawan disalahgunakan: login, reset password, pengiriman email atau OTP, pencarian berat, ekspor, dan aksi tulis yang bisa di-spam. Paket auth biasanya sudah membatasi login, tetapi tetap periksa.
- Pilih key limiter dengan sadar. IP saja bisa menghukum banyak user di satu jaringan, akun saja bisa dipakai untuk mengunci user tertentu. Gabungan keduanya biasanya lebih adil.

## Task terjadwal aman dan terbatas
- Gunakan `withoutOverlapping()` untuk task yang durasinya tidak tentu dan tidak aman berjalan bersamaan. Angka di dalamnya adalah masa berlaku lock dalam menit, bukan batas waktu task.
- Gunakan `onOneServer()` jika ada lebih dari satu server scheduler. Semua server harus memakai cache store yang sama dan mendukung lock (database, redis, memcached, dynamodb). Beri nama pada closure terjadwal sebelum memakai `onOneServer()`.
- `runInBackground()` hanya untuk task `command()` atau `exec()` yang lama, dan wajib punya log serta pemantauan kegagalan.
- Batasi pekerjaan di dalam task itu sendiri (proses per batch, atau dispatch ke antrean). Scheduler tidak menghentikan task yang terlalu lama.
