---
paths:
  - 'app/**'
  - config/logging.php
  - bootstrap/app.php
---

# Logging dan observabilitas

## Cara mencatat log
- Gunakan facade `Log` dengan pesan tetap dan data di array context, misalnya `Log::warning('Pembayaran ditolak', ['order_id' => $order->id])`. Jangan menyisipkan data ke dalam teks pesan.
- Arti level: `error` untuk kegagalan yang harus dilihat seseorang, `warning` untuk kondisi menurun yang masih tertangani, `info` untuk kejadian bisnis penting, dan `debug` tidak aktif di production (`LOG_LEVEL`).
- Kegagalan dicatat sekali, di tempat error itu ditangani, dengan ID yang memungkinkan penelusuran. Jangan mencatat lalu melempar ulang error yang sama di setiap lapisan.
- Hal yang tidak boleh dicatat ada di `security.md`. Orang dirujuk dengan ID, bukan nama atau email.
- Kode aplikasi tidak menulis file log sendiri. Tujuan log (file, stderr, layanan eksternal) diatur di `config/logging.php`. Di production berbasis container, log dikirim ke stderr dalam format JSON.

## Request ID
- Setiap request mendapat satu ID dari middleware paling luar. ID dari header klien tidak dipercaya.
- ID itu dimasukkan dengan `Context::add('request_id', ...)` supaya otomatis ikut di setiap baris log dan di job yang di-dispatch dari request itu, lalu dikirim kembali di header respons (misalnya `X-Request-Id`).

## Kejadian keamanan
- Kejadian berikut dicatat dengan ID aktor, subjek, request ID, dan hasil, tanpa nilai rahasia: login berhasil dan gagal, lockout, reset dan ganti password, perubahan 2FA, akses ditolak (403), permintaan konfirmasi password, perubahan role atau izin, serta ekspor atau pembukaan data pribadi.
- Catat lewat listener untuk event bawaan Laravel (`Login`, `Failed`, `Lockout`, `PasswordReset`, dan sejenisnya), supaya tidak ada jalur yang terlewat.
- Penolakan dari pengguna anonim dihitung dan dibatasi, bukan dicatat satu per satu.

## Health check dan metrik
- Endpoint liveness (`/up` bawaan Laravel) hanya menyatakan bahwa proses hidup.
- Jika ada endpoint readiness, ia hanya mengecek dependensi wajib (misalnya database utama) dengan timeout 2 detik per cek, menjawab 503 jika gagal, dan menulis penyebabnya hanya ke log. Dependensi opsional dilaporkan sebagai "degraded" tanpa mengubah status 200.
- Jika proyek memakai metrik atau tracing, label metrik berasal dari himpunan tetap (nama route, kelas status), tidak pernah dari input pengguna atau ID.
- Kegagalan fitur sampingan (notifikasi, analitik) dilaporkan, tetapi tidak menghentikan alur utama.
