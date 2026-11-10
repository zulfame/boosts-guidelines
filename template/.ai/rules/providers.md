---
paths:
  - 'app/Providers/**'
---

# Service provider

## Pengaturan global untuk production di AppServiceProvider
- Kumpulkan pengaturan global di `boot()` milik `AppServiceProvider`, atau method khusus yang dipanggil dari sana (starter kit Laravel memakai `configureDefaults()`).
- Pengaturan minimum:
  - `Model::shouldBeStrict(! app()->isProduction())`, supaya lazy loading (N+1), atribut yang tidak fillable, dan atribut yang tidak ada langsung ketahuan saat development tanpa mengganggu production.
  - `DB::prohibitDestructiveCommands(app()->isProduction())`, supaya perintah seperti `migrate:fresh` tidak bisa dijalankan di production.
  - `Password::defaults()` mengikuti kebijakan di `auth.md`, misalnya `Password::min(15)->uncompromised()` di production.
  - `URL::forceHttps(app()->isProduction())` jika aplikasi berjalan di belakang HTTPS.
- Pemeriksaan config wajib (lihat `security.md`) juga dilakukan di sini, supaya aplikasi gagal start dengan pesan jelas jika config penting hilang.
- Service provider adalah satu-satunya tempat yang boleh bercabang berdasarkan environment (`app()->isProduction()`). Di tempat lain, perbedaan perilaku datang dari config.
- Jangan pernah melemahkan pengaturan ini hanya agar test atau pekerjaan local berhasil.
- Binding interface ke implementasi (misalnya payment gateway) ditaruh di `register()`.
