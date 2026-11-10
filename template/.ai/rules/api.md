---
paths:
  - routes/api.php
  - 'app/Http/Controllers/Api/**'
  - 'app/Http/Controllers/Webhooks/**'
  - 'app/Http/Resources/**'
---

# API dan pertukaran data

## Bentuk API
- Route API ada di `routes/api.php` dengan prefix versi (`/v1`), didaftarkan dengan `Route::apiResource()` bila sesuai, dan diautentikasi dengan Sanctum atau Passport.
- Respons memakai Eloquent API Resource. Jangan mengembalikan model mentah, karena bisa membocorkan kolom yang lupa disembunyikan.
- Setiap endpoint punya kontrak yang jelas: Form Request untuk input dan API Resource untuk output, atau skema OpenAPI jika proyek memakainya. Kontrak berubah di commit yang sama dengan endpoint-nya.
- Endpoint daftar wajib dipaginasi (maksimal 100 item per halaman) dan diberi rate limit.
- Perubahan yang tidak kompatibel membutuhkan versi baru. Versi lama diumumkan sebelum dihapus.

## Format error
- Body error mengikuti format bawaan Laravel (`message`, ditambah `errors` untuk validasi) dengan tambahan field `code`: kode stabil yang bisa dibaca mesin, dari satu katalog (misalnya enum `ErrorCode`). Contoh: `{"code": "insufficient_balance", "message": "Saldo Rp50.000, transaksi butuh Rp80.000."}`.
- Pesan tidak pernah memuat stack trace, teks exception, SQL, nama class, atau path file.
- Error di route API selalu dirender sebagai JSON, apa pun header `Accept`-nya (`shouldRenderJsonWhen()` di `bootstrap/app.php`).

## Status untuk setiap situasi
Situasi yang sama mendapat status dan `code` yang sama di setiap route.

| Situasi | Status |
| --- | --- |
| Method tidak didukung | 405 |
| Kena rate limit | 429, dengan header `Retry-After` |
| Belum login, atau token tidak valid | 401 |
| Butuh konfirmasi password ulang | 403, dengan `code: reauth_required` |
| Sudah login dan boleh tahu record ada, tetapi tidak diizinkan | 403 |
| Data tidak ada, atau tidak punya akses baca ke induknya | 404 |
| Request terlalu besar | 413 |
| Content type tidak didukung | 415 |
| Body rusak | 400 |
| Input tidak valid | 422, dengan `errors` per field |
| Konflik versi atau data duplikat | 409 |
| Precondition `If-Match` gagal | 412 |
| Error server | 500, tanpa detail |
| Dependensi tidak tersedia | 503 |

## Data di JSON
- Uang dan integer 64-bit dikirim sebagai string. Waktu dikirim sebagai ISO-8601 UTC (misalnya `2026-11-09T07:30:00Z`), tidak pernah sebagai epoch atau float.
- Field input yang tidak dikenal diabaikan, karena hanya data `validated()` yang dipakai, kecuali proyek menetapkan untuk menolaknya.
- Payload event, webhook, dan job membawa versi skema. Penerima tetap menerima jika ada field tambahan.

## Daftar dan ekspor
- Sort hanya untuk kolom di allow-list, dengan pembeda unik (ID) supaya paging tidak mengulang atau melewati baris. Setiap kolom yang bisa diurutkan diuji di kedua arah.
- Filter hanya menerima nilai pendek dengan format benar (batas panjang, enum, rentang tanggal yang valid). Pencarian teks yang tidak bisa memakai index dibatasi jendela waktunya (default 90 hari), dan respons menyebutkannya.
- Ekspor dibatasi jumlah barisnya (default 50.000). Ekspor kecil di-stream langsung tanpa file tersimpan. Ekspor besar berjalan sebagai job, filenya disimpan privat, terikat pada pemesannya, dihapus setelah 24 jam, dan diunduh lewat signed URL. Ekspor data pribadi dicatat sebagai kejadian keamanan.

## Pengulangan, konkurensi, dan rate limit
- Endpoint yang membuat transaksi atau menagih menerima header `Idempotency-Key` dan mengembalikan hasil pertama saat request diulang.
- Record yang bisa diedit bersamaan membawa versi (kolom `version` atau `updated_at`). Versi usang di body dijawab 409, dan `If-Match` yang gagal dijawab 412.
- Setiap aksi punya limiter bernama sendiri (`RateLimiter::for('export', ...)`), supaya satu aksi tidak menghabiskan jatah aksi lain.
- Menulis ke database dan mengirim pesan ke sistem lain tidak dilakukan sebagai dua langkah terpisah. Gunakan job dengan `afterCommit()` atau pola outbox.

## Webhook dan integrasi antar sistem
- Webhook masuk memverifikasi signature atas body mentah dengan `hash_equals()`, mengecek timestamp untuk mencegah replay, dan memakai ID event untuk mencegah proses ganda. Verifikasi yang gagal dijawab 401 dan dicatat.
- Event bisa datang terlambat, dua kali, atau tidak berurutan. Perubahan status hanya diterapkan jika valid dari status saat ini, dan jumlah serta mata uang dicocokkan dengan data tersimpan.
- Route webhook boleh dikecualikan dari CSRF hanya karena punya verifikasi signature sendiri. Request-nya dicatat tanpa token atau payload.
- Scope token untuk integrasi dibuat sesempit mungkin.
