---
paths:
  - 'database/factories/**'
  - 'database/seeders/**'
---

# Factory dan seeder

## Setiap model punya factory dengan state yang bermakna
- Setiap model baru punya factory yang secara default menghasilkan data valid, ditambah state bernama untuk variasi penting, misalnya `unverified()`, `withTwoFactor()`, `suspended()`.
- Relasi dibuat dengan factory bersarang atau `for()`, bukan ID yang ditulis manual.
- Seeder hanya untuk data local atau demo, dan tidak boleh dijalankan saat deploy production.

## Data yang dibutuhkan production tidak hanya dibuat oleh seeder
- Seeder tidak dijalankan saat deploy production. Data yang dibutuhkan fitur agar berjalan di sana (izin, role dasar, pengaturan awal) dibuat lewat migration data atau command idempoten yang dijalankan saat deploy, misalnya dengan `firstOrCreate`.
- Seeder boleh tetap memanggil logika yang sama untuk data local, tetapi bukan satu-satunya jalan.
- Langkah penyiapan data di production dicatat di dokumentasi deploy atau `memory/PRD.md`. Jika belum ada keputusan tentang caranya, sebutkan sebagai pertanyaan terbuka kepada user.

## Data referensi kecil ditulis di kode, satu sumber untuk seeder dan impor
- Data referensi yang kecil dan jarang berubah (jenis, status, kategori, hak akses dasar) ditulis sebagai konstanta atau array di kelas seeder atau enum, bukan berkas CSV. Kode lebih mudah dibaca, dibandingkan, dan diperbaiki.
- Berkas CSV hanya untuk data yang besar (ribuan baris atau lebih), misalnya daftar wilayah.
- Seeder untuk data local dan command idempoten yang dijalankan saat deploy memuat baris yang sama dari satu sumber, supaya keduanya tidak menyimpang.
