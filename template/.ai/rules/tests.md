---
paths:
  - 'tests/**'
---

# Test

## Cakupan test setiap endpoint
- Setiap perilaku yang bisa dicapai lewat request (termasuk aksi komponen Livewire) punya feature test, dengan Pest atau PHPUnit sesuai proyek. Unit test hanya untuk logika yang tidak memakai framework.
- Untuk setiap endpoint, tulis test untuk kasus yang berlaku: tamu (diarahkan ke login, atau 401 untuk API), user tanpa izin, data milik user atau tenant lain, validasi gagal, kena rate limit, konflik, dan kasus sukses.
- Kasus sukses memeriksa respons dan data yang tersimpan di database.
- Matriks izin lengkap diuji langsung di policy. Endpoint cukup satu test penolakan untuk membuktikan otorisasi dipanggil.
- Validasi diuji dengan mengirim input tidak valid dan memeriksa teks pesan errornya, bukan memeriksa isi array rules.
- Kode yang memeriksa sesuatu sebelum bertindak (stok, saldo, keunikan) punya test untuk request yang sama dikirim dua kali.

## Data dan isolasi test
- Setiap test membuat datanya sendiri dengan factory dan state bernama. Jangan membuat record di `beforeEach()` atau `setUp()`.
- Panggil route lewat `route('nama')`.
- Fake dibuat di dalam test yang membutuhkannya, dengan nama class jika diketahui (`Queue::fake([SendInvoice::class])`).
- HTTP keluar: `Http::preventStrayRequests()` dan fake endpoint yang spesifik. Uji juga jalur gagalnya (timeout, 5xx).
- Waktu dikunci dengan `freezeTime()` atau `travelTo()`. Jangan memakai `Carbon::setTestNow()`.
- Database direset dengan trait yang sudah dipakai proyek (`RefreshDatabase` atau `LazilyRefreshDatabase`). Jangan mock query builder. Jika perilaku bergantung pada database (lock, constraint), jalankan test di engine yang sama dengan production.

## Test yang jujur
- Perbaikan bug dimulai dengan test regresi yang gagal karena alasan yang benar, lalu lolos setelah diperbaiki.
- Jangan pernah melemahkan test agar lolos: tanpa `skip()`, tanpa melonggarkan assertion, tanpa menghapus test atau kasus. Jika test-nya yang salah, perbaiki dan jelaskan alasannya.
- Test yang kadang gagal (flaky) diperbaiki di perubahan yang sama, tidak di-retry, dilewati, atau dihapus diam-diam.
- Test yang tidak dijalankan atau dilewati dilaporkan apa adanya, tidak pernah disebut lolos.
- Target coverage minimal 80% untuk kode baru. Ukur dengan `--coverage` jika pcov atau xdebug tersedia. Jika belum bisa diukur, sebutkan di laporan.
