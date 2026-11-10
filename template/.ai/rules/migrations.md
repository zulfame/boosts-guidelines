---
paths:
  - 'database/migrations/**'
---

# Migration

## Migration yang sudah dibagikan tidak diubah
- Buat migration dengan `php artisan make:migration`.
- Migration yang sudah di-merge atau sudah dijalankan di lingkungan bersama (termasuk database local developer lain) tidak boleh diedit. Buat migration baru. Migration yang dibuat di sesi ini dan belum dibagikan boleh diedit.
- Jangan memakai model aplikasi di dalam migration. Tulis nama tabel secara eksplisit, karena model bisa berubah setelah migration dijalankan.
- Satu migration untuk satu perubahan yang bisa dipahami dan dibatalkan.
- Migration harus berhasil dijalankan dari database kosong (`migrate:fresh`) maupun sebagai upgrade dari versi sebelumnya.

## Foreign key, constraint, dan index dibuat dengan sengaja
- Gunakan `foreignId()->constrained()` dan tentukan perilaku hapus secara sadar (`cascadeOnDelete()`, `restrictOnDelete()`, atau `nullOnDelete()`).
- Aturan data dijaga di database, tidak hanya di kode: `NOT NULL`, `unique`, foreign key, dan check constraint, supaya dua proses yang menulis bersamaan tidak bisa melanggarnya.
- Index dibuat untuk pola query yang nyata dan sering dipakai, bukan untuk setiap kolom yang muncul di `where` atau `orderBy`. Untuk query seperti `where status = ? order by created_at`, buat index gabungan sesuai urutannya.
- Foreign key perlu index. MySQL membuatnya otomatis, sedangkan PostgreSQL dan SQLite tidak. Cek dulu agar tidak ada index ganda.
- Uang memakai `bigInteger` satuan terkecil (bertanda, agar refund atau penyesuaian negatif bisa disimpan). Tidak pernah `float` atau `double`.

## Perubahan pada tabel berisi data dilakukan bertahap
- Menambah kolom wajib atau unik pada tabel yang sudah berisi: tambahkan sebagai nullable, deploy kode yang bisa menangani kedua kondisi, isi data lama secara bertahap, lalu baru tambahkan constraint.
- Mengganti nama atau tipe kolom memakai expand-contract: tambah kolom baru, tulis ke keduanya, pindahkan data, alihkan pembacaan, lalu hapus kolom lama di rilis berikutnya.
- Untuk tabel besar (lebih dari 1 juta baris atau 1 GB), isi data per batch paling banyak 1.000 baris, dan buat index tanpa mengunci tabel jika database mendukungnya. Pengisian data besar dijalankan lewat command atau job yang bisa diulang dan dipantau, bukan di dalam migration skema, dan hasilnya diperiksa (jumlah baris).

## Migration destruktif dibahas dulu
- Migration yang menghapus data atau tidak bisa dibatalkan (drop tabel atau kolom, truncate, memperkecil kolom) dibahas dengan user sebelum ditulis. Changelog menjelaskan cara kembali.
- Tulis `down()` jika perubahan memang bisa dibatalkan dengan aman. Jika `down()` akan menghapus data, beri komentar. Di production, perbaikan dilakukan dengan migration baru, bukan rollback.
