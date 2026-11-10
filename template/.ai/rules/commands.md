---
paths:
  - 'app/Console/**'
  - routes/console.php
---

# Artisan command

## Command yang bisa diandalkan
- Kembalikan `self::SUCCESS`, `self::FAILURE`, atau `self::INVALID` untuk input yang salah. Jangan pernah mengembalikan sukses saat ada yang gagal.
- Hasil ditulis dengan `$this->info()` atau `$this->line()`, dan error dengan `$this->error()`. Command yang output-nya dibaca program lain menyediakan opsi `--json`.
- Signature dan description diisi lengkap, supaya `--help` menjelaskan semua argumen dan opsi.
- Secret tidak pernah dikirim sebagai argumen, karena terlihat di daftar proses. Baca dari config atau dari input tersembunyi (`$this->secret()`).
- Command yang menghapus atau menimpa data menyediakan `--dry-run` atau meminta konfirmasi. Di production, gunakan `ConfirmableTrait` (`$this->confirmToProceed()`) sehingga command butuh `--force`.
- Command yang berjalan terjadwal atau di CI tidak boleh menunggu input. Jika butuh konfirmasi, command gagal dengan pesan yang jelas.
- Logika bisnis ada di Action. Command hanya membaca input dan memanggilnya, sama seperti controller.
- Pekerjaan besar diproses per batch dengan batas yang dinyatakan, dan aman dijalankan ulang.
