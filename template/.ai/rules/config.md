---
paths:
  - 'config/**'
---

# Konfigurasi

## env() hanya di file config, secret tidak pernah di-commit
- Panggil `env()` hanya di dalam `config/*.php`. Di tempat lain, baca dengan `config()`, karena setelah `config:cache` file `.env` tidak dibaca lagi.
- Setiap key env baru diberi contoh nilai yang aman di `.env.example`.
- Jangan pernah meng-commit secret asli. Di server, gunakan secret store platform atau `.env` terenkripsi (`php artisan env:encrypt`).
- Kredensial layanan pihak ketiga ditaruh di `config/services.php`.
- Cek environment dengan `app()->isProduction()` atau `App::environment()`, bukan membaca `env('APP_ENV')`.

## Pengaturan wajib di production
- `APP_ENV=production` dan `APP_DEBUG=false`. Jika proyek memakai Laravel Boost, juga `BOOST_ENABLED=false`.
- Deploy menjalankan `composer install --no-dev`, `php artisan migrate --force`, dan `php artisan optimize` (cache config, route, event, dan view).
- Pastikan driver yang dipilih mendukung fitur yang dipakai. Contohnya, cache tags tidak didukung driver `file`, `database`, dan `dynamodb`.
