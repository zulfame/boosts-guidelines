---
paths:
  - 'app/Services/**'
---

# Service

## Service untuk kemampuan yang dipakai ulang dan untuk layanan luar
- Service berisi kemampuan yang dipakai oleh banyak Action (misalnya `PricingService`, `InventoryService`) atau membungkus layanan luar (payment gateway, API pihak ketiga). Namanya kata benda.
- Service tidak mengatur alur satu use case dari awal sampai akhir. Itu tugas Action.
- Jangan membuat Service yang isinya hanya meneruskan ke Eloquent, misalnya `UserService::create()` yang cuma memanggil `User::create()`.
- Untuk layanan luar yang perlu diganti atau dipalsukan saat test, buat interface dan bind implementasinya di service provider. Jangan membuat interface jika belum ada kebutuhan itu.

## Pemanggilan API luar aman dan terukur
- Setiap integrasi punya satu class client di `app/Services/<Vendor>/` yang membaca kredensial dari `config('services.<vendor>')`.
- Selalu tentukan `timeout()` dan `connectTimeout()` yang sesuai. Ingat, retry melipatgandakan total waktu.
- `retry()` hanya untuk kegagalan sementara (koneksi, 429, 5xx) dan request yang aman diulang. Request yang mengubah data hanya boleh diulang jika API-nya mendukung idempotency key, dan key yang sama dikirim di setiap percobaan.
- Panggil `->throw()` atau periksa status sebelum membaca hasil. HTTP client Laravel tidak melempar error untuk 4xx dan 5xx secara default.
- Kembalikan objek data bertipe, bukan array mentah. Client dipanggil dari Action atau job, bukan dari controller atau view.
