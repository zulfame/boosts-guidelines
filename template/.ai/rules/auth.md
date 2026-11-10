---
paths:
  - 'app/Actions/Fortify/**'
  - 'app/Http/Controllers/Auth/**'
  - 'app/Livewire/Auth/**'
  - app/Providers/FortifyServiceProvider.php
  - config/auth.php
  - config/session.php
  - config/fortify.php
  - config/sanctum.php
  - config/hashing.php
---

# Autentikasi, sesi, dan upload

## Password
- Password di-hash dengan `Hash` Laravel. Utamakan argon2id (`HASH_DRIVER=argon2id`) jika PHP mendukungnya, karena bcrypt hanya membaca 72 byte pertama.
- Kebijakan mengikuti NIST SP 800-63B: minimal 15 karakter jika password menjadi satu-satunya faktor, atau minimal 8 jika 2FA diwajibkan. Password sampai minimal 64 karakter diterima, semua karakter termasuk spasi diizinkan, dan password dicek terhadap daftar password bocor (`Password::min(15)->uncompromised()`).
- Tidak ada aturan komposisi (wajib huruf besar, angka, simbol) dan tidak ada penggantian berkala paksa. Password hanya wajib diganti jika ada bukti kebocoran.
- Paste dan password manager tidak pernah diblokir di field password.

## Login
- Pesan gagal login sama untuk akun yang ada maupun tidak.
- Percobaan gagal dibatasi per akun dan per IP (default 5 per menit) dengan jeda yang bertambah. Lockout permanen hanya untuk akun berhak istimewa dan selalu punya jalur pemulihan, supaya orang lain tidak bisa sengaja mengunci akun.
- 2FA wajib untuk akun berhak istimewa (admin, operator). Untuk user biasa 2FA opsional, kecuali proyek menetapkan lain. Kode 2FA dibatasi percobaannya, hanya berlaku sekali, dan recovery code disimpan terenkripsi atau sebagai hash.
- Jalur login baru (token API, magic link, SSO) melewati pengecekan, rate limit, dan pencatatan yang sama dengan login utama.
- Login lewat OAuth atau OIDC memeriksa `state`, mencocokkan redirect URI secara persis, dan tidak menautkan akun hanya berdasarkan email yang belum diverifikasi provider.

## Registrasi dan pemulihan akun
- Registrasi, reset password, dan pemulihan akun memberi respons yang sama baik akun ada maupun tidak, dan dibatasi per target. Pesan bawaan Laravel untuk email yang tidak terdaftar membocorkan keberadaan akun, jadi ganti dengan pesan umum, misalnya "Jika email terdaftar, link reset sudah dikirim."

## Sesi dan token
- Untuk aplikasi yang menyimpan data sensitif: sesi berakhir setelah 15 menit tidak aktif (`SESSION_LIFETIME=15`) dan maksimal berumur 12 jam. Laravel tidak punya batas umur absolut bawaan, jadi tegakkan lewat middleware. Aplikasi lain boleh melonggarkan dengan keputusan tercatat di `docs/decisions.md`.
- Session ID dibuat ulang saat login dan saat hak akses berubah. Logout mengakhiri sesi di server.
- Aksi sensitif (ganti password, email, 2FA, atau role, membuat token API, mengekspor data pribadi) memakai middleware `password.confirm`, dengan `AUTH_PASSWORD_TIMEOUT=900` (15 menit).
- Mengganti password atau 2FA, menonaktifkan akun, atau menghapus akun mengakhiri sesi di perangkat lain (`Auth::logoutOtherDevices()`) dan mencabut token API-nya.
- Token API dan kode sekali pakai berumur pendek, hanya berlaku sekali jika memungkinkan, disimpan sebagai hash (Sanctum sudah melakukannya), dan dibandingkan dengan `hash_equals()`.
- Token di URL hanya boleh untuk link sekali pakai yang berumur pendek (reset password, verifikasi email, signed URL), dan tidak pernah dicatat ke log.

## Upload
- Tipe file dicek dari isinya (`mimes` atau `mimetypes`), bukan dari ekstensinya. Ukuran dibatasi (default 10 MB), dan dimensi gambar dibatasi dengan aturan `dimensions`.
- Nama file disimpan secara acak oleh Laravel (`store()`). Nama asli dari pengguna tidak pernah dipakai di path.
- File dari pengguna disimpan di disk privat dan disajikan lewat route yang diotorisasi atau `temporaryUrl()`. File yang memang harus publik tidak boleh bisa dieksekusi, dan gambar sebaiknya di-encode ulang.
- File dari pihak luar dipindai malware jika proyek punya scanner.
