---
paths:
  - 'app/**'
  - 'routes/**'
  - 'config/**'
  - 'resources/**'
  - bootstrap/app.php
---

# Keamanan

## Secret dan konfigurasi
- Tidak ada secret, kredensial, atau URL khusus environment yang ditulis langsung di kode, test, seeder, dokumen, atau riwayat commit. Secret yang ditemukan di repositori dilaporkan, tidak dipakai, dan tidak disalin.
- Secret, token, password, dan data pribadi tidak pernah masuk ke log, URL, atau pesan error. Satu-satunya pengecualian adalah token sekali pakai di link reset password atau verifikasi (lihat `auth.md`).
- Config yang wajib ada agar aplikasi aman (misalnya kredensial payment gateway) dicek saat boot, dan aplikasi gagal start jika hilang. Pengaturan keamanan yang hilang tidak boleh jatuh ke nilai yang lebih lemah.
- `.env.example` hanya berisi nama variabel dan contoh nilai yang aman. Seeder dan factory hanya berisi data karangan, tidak pernah salinan data production atau password asli.

## Otorisasi
- Lengkapi aturan di `policies.md`. Otorisasi diperiksa sebelum validasi dan sebelum data diproses, sehingga pihak luar tidak bisa mengetahui apa pun, termasuk apakah sebuah ID ada.
- Kepemilikan dan role dicek pada record, bukan hanya pada route.
- Tidak ada yang bisa menaikkan hak aksesnya sendiri, bertindak atas akun yang lebih berkuasa, atau menyetujui permintaannya sendiri.
- Aksi yang ditujukan ke pihak lain (akun lain, penerima pesan) dibatasi juga berdasarkan targetnya, bukan hanya pemanggilnya.

## Logika bisnis
- Harga, diskon, total, dan nilai apa pun yang menentukan uang atau akses dihitung di server dari data tersimpan, tidak pernah diambil dari input browser.
- Alur bertahap (checkout, onboarding, approval) memeriksa di server bahwa langkah sebelumnya sudah selesai.
- Aksi yang mengirim pesan atau menulis storage dibatasi jumlahnya per target, per hari, dan secara keseluruhan.

## Input dan output
- Semua nilai dari luar dianggap jahat: field request, header, nama dan isi file, webhook, pesan antrean, dan argumen command.
- Jangan pernah membangun query, perintah shell, atau template dari teks mentah. Gunakan binding, `Process` dengan argumen berupa array, dan escaping bawaan.
- Jangan pernah memanggil `unserialize()` untuk data yang tidak tepercaya.
- Path dari input dinormalisasi dan dicek tetap berada di dalam folder yang diizinkan.
- Target redirect dari input dicocokkan dengan allow-list. Jangan mengikuti parameter `next` atau `returnUrl` tanpa pengecekan.
- URL absolut di email dan redirect dibuat dari `APP_URL` lewat `route()` atau `url()`, dan host yang diterima dibatasi dengan `trustHosts()` di `bootstrap/app.php`.
- Ekspor CSV atau spreadsheet menetralkan sel yang diawali `=`, `+`, `-`, atau `@`.
- Panggilan HTTP ke URL dari pengguna (webhook keluar, pratinjau link) mengecek scheme dan host terhadap allow-list, menolak alamat internal dan alamat metadata cloud, dan tidak mengikuti redirect tanpa pengecekan yang sama. Verifikasi sertifikat SSL tidak pernah dimatikan, termasuk di test.
- HTML atau Markdown dari pengguna hanya dirender lewat sanitizer yang teruji.

## Header dan browser
- Header keamanan (`X-Content-Type-Options: nosniff`, CSP termasuk `frame-ancestors`, `Referrer-Policy`) dipasang dari satu middleware dan berlaku untuk semua respons, termasuk halaman error. Respons yang memuat data pribadi memakai `Cache-Control: no-store`.
- CORS mati kecuali dibutuhkan, lalu dibuka per path di `config/cors.php` (publish dengan `php artisan config:publish cors`).
- Cookie sesi bersifat `HttpOnly`, `Secure` di production (`SESSION_SECURE_COOKIE=true`), dan `SameSite=Lax` atau `Strict`.
- Script dan style pihak ketiga hanya dari sumber yang diizinkan, dengan atribut `integrity` jika dimuat dari CDN.

## Kriptografi
- Gunakan fasilitas Laravel: `Hash` untuk password, `Crypt` atau cast `encrypted` untuk data yang perlu dibaca kembali, dan `Str::random()` atau `random_bytes()` untuk token. Jangan pernah membuat kriptografi sendiri, atau memakai `md5`, `sha1`, dan `rand()` untuk keperluan keamanan.
- Kolom sensitif (nomor identitas, data pembayaran) dienkripsi. Rotasi `APP_KEY` memakai `APP_PREVIOUS_KEYS` supaya data lama tetap terbaca.

## Dependensi dan rantai pasok
- `composer.lock` dan lockfile npm selalu di-commit.
- Paket baru dicek: namanya persis ada di registry, bukan tiruan nama paket populer, punya rilis dalam 24 bulan terakhir, bukan paket yang baru terbit kurang dari 30 hari, dan install script-nya sudah ditinjau. Paket yang gagal salah satu syarat butuh keputusan user.
- Temuan `composer audit` dan `npm audit` diperbaiki, atau dicatat sebagai risiko yang diterima di `docs/decisions.md` dengan tanggal tinjau ulang. Temuan tidak pernah dibungkam.
