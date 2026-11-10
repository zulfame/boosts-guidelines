---
paths:
  - 'app/Jobs/**'
---

# Job antrean

## Job aman dijalankan ulang
- Pekerjaan lambat atau yang melibatkan layanan luar (email, HTTP, file, laporan) dijalankan sebagai job `ShouldQueue`.
- Job harus aman dijalankan dua kali: periksa kondisi data sebelum bertindak. Worker bisa mati setelah melakukan efek samping tetapi sebelum job ditandai selesai.
- Kirim model atau ID, bukan data besar atau secret. Data dimuat ulang saat job berjalan.
- Payload job harus tetap terbaca oleh versi kode sebelum dan sesudah deploy (lihat `runtime.md`).
- Job yang bergantung pada data transaksi di-dispatch dengan `->afterCommit()`.

## Waktu, retry, dan kegagalan diatur dengan sengaja
- `$timeout` job dan `--timeout` worker harus lebih pendek beberapa detik dari `retry_after` koneksi antrean di `config/queue.php` (default 90 detik). Jika tidak, job yang sama bisa diambil dua worker.
- Gunakan `$backoff` yang makin lama untuk kegagalan sementara. Jangan mengulang kegagalan permanen seperti data tidak valid.
- `ShouldBeUnique` untuk mencegah dispatch ganda. Ini memakai cache lock dan tidak menggantikan pemrosesan yang aman diulang atau constraint database.
- Gunakan middleware `RateLimited` jika beberapa job berbagi kuota API pihak ketiga.
- Buat method `failed()` hanya jika aplikasi perlu memperbarui status atau memberi tahu seseorang setelah semua percobaan habis. Jangan hanya untuk mencatat log, karena sistem antrean sudah mencatatnya.
