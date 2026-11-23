---
paths:
  - 'app/**'
---

# Aplikasi (aturan umum backend)

## Performa query
- Eager load relasi yang dipakai di dalam loop atau dikirim ke view (`with()`, `load()`). Untuk jumlah saja, gunakan `withCount()`. Tidak ada query atau panggilan HTTP di dalam loop.
- Daftar yang dilihat pengguna wajib dipaginasi, dengan urutan yang pasti ditambah pembeda unik, misalnya `orderByDesc('created_at')->orderByDesc('id')`. Jangan memakai `->get()` atau `->all()` pada tabel yang terus bertambah di dalam request.
- Pemrosesan massal memakai `chunkById()` atau `lazyById()` jika data diubah saat diiterasi.
- Untuk tabel dengan kolom besar (text, JSON), pilih hanya kolom yang dibutuhkan, termasuk key yang diperlukan relasi.
- Cache pembacaan yang mahal dengan `Cache::remember()`, key yang jelas, dan TTL. Hapus key terkait saat datanya berubah (lihat juga `runtime.md`).

## Konkurensi
- Proses yang tidak boleh berjalan bersamaan dilindungi dengan `Cache::lock()`. Atur masa lock lebih lama dari waktu proses terlama.
- Membaca lalu mengubah baris yang sama (stok, saldo) dilakukan di dalam transaksi dengan `lockForUpdate()`, atau dengan update atomik seperti `decrement()` berkondisi.
- Transaksi dibuat pendek (di bawah 1 detik) dan tidak memanggil layanan luar di dalamnya. Deadlock boleh di-retry dalam batas retry yang ada di guideline standar produksi.

## Quality gate
- Setiap aturan yang bisa dicek mesin ditegakkan oleh alat, bukan ingatan:

  | Aturan | Alat |
  | --- | --- |
  | Format kode PHP | Pint |
  | Tipe dan static analysis | Larastan |
  | Perilaku | Pest atau PHPUnit |
  | Kerentanan dependensi | `composer audit` dan `npm audit` |
  | Format, lint, dan tipe frontend | Alat bawaan stack frontend proyek |

- Jangan menurunkan level analisis, menambah baseline, atau menambah `@phpstan-ignore` tanpa alasan singkat di baris yang sama.
- Utamakan generics dan PHPDoc array shape yang tepat daripada `mixed`.
- Semua pemeriksaan CI harus lolos sebelum merge.
- Aturan yang belum punya alat penegak (misalnya batas ukuran method) diperiksa saat review, dan alatnya diusulkan sekali.

## Waktu, uang, dan nilai domain
- Simpan dan bandingkan waktu dalam UTC (`'timezone' => 'UTC'` di `config/app.php`), dan konversi ke zona waktu pengguna hanya saat ditampilkan.
- Uang disimpan sebagai integer satuan terkecil (misalnya sen), dengan kolom mata uang jika mendukung lebih dari satu mata uang. Format hanya di lapisan tampilan.
- Nilai domain yang berulang atau terbatas (status, tipe, role) dibuat sebagai enum di `app/Enums/`, bukan string yang tersebar.

## Kode khusus satu varian ditaruh di folder per varian
- Alur, aturan, dan integrasi yang hanya berlaku untuk satu varian (produk, tenant, atau mitra) ditaruh di subfolder bernama varian itu: `app/Actions/<Varian>/`, `app/Http/Controllers/<Varian>/`, `app/Http/Requests/<Varian>/`, dan klien integrasinya di `app/Services/<Mitra>/`. Jangan dicampur ke folder yang dipakai semua varian.
- Yang berlaku untuk semua varian tetap di folder bersama. Perbedaan antarvarian diatur lewat parameter (kolom, config, atau enum), bukan percabangan berdasarkan nama varian.
- Kode lama tidak dipindahkan hanya demi aturan ini. Kode yang khusus satu varian ditempatkan di foldernya begitu muncul.

## Path berkas di penyimpanan disusun di satu tempat
- Path berkas yang disimpan ke disk (lokal, S3, SFTP) disusun di satu class atau enum, tidak dirakit di tiap kode yang menyimpan.
- Susunannya memisahkan tingkat akses dan umur data, misalnya `{tingkat}/{tahun}/{folder}/{nama file}`, dengan `tingkat` bernilai `public` atau `private`. Dokumen yang berisi data pribadi atau rahasia masuk `private`. Hanya yang memang boleh diakses umum masuk `public`.
