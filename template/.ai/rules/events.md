---
paths:
  - 'app/Events/**'
  - 'app/Listeners/**'
---

# Event dan listener

## Event aman terhadap transaksi, listener lambat masuk antrean
- Listener ditemukan otomatis lewat type-hint di method `handle()`. Daftarkan manual hanya jika memang perlu.
- Event yang di-dispatch di dalam transaksi mengimplementasikan `ShouldDispatchAfterCommit`, supaya event dibuang jika transaksi batal.
- Listener yang lambat atau memanggil layanan luar mengimplementasikan `ShouldQueue` dan mengikuti aturan di `jobs.md`.
- Event berisi data yang dibutuhkan listener (model atau ID), bukan logika.
- Deploy production menjalankan `php artisan optimize` (atau `event:cache`) agar penemuan listener di-cache.
