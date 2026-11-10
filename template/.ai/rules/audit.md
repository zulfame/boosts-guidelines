---
paths:
  - 'app/Models/**'
  - 'app/Observers/**'
  - 'database/migrations/**'
---

# Jejak audit (opsional)

## Kapan aturan ini berlaku
- Aturan ini berlaku hanya jika proyek memutuskan memakai jejak audit, tercatat di `docs/decisions.md` beserta alat yang dipakai. Tanpa keputusan itu, abaikan file ini.
- Memasang paket audit adalah dependensi baru dan butuh persetujuan user.

## Isi dan prinsip jejak audit
- Setiap entri menjawab siapa (ID dan tipe aktor, termasuk job), apa (aksi dari daftar tetap seperti `created`, `updated`, `deleted`, `exported`, `role_changed`), terhadap apa (tipe dan ID subjek), kapan (UTC), perubahannya (nilai lama dan baru, kecuali data pribadi yang hanya ditandai berubah), hasilnya (`succeeded`, `refused`, `failed`), dan request ID.
- Log audit hanya bisa ditambah. Kode aplikasi tidak pernah mengubah atau menghapusnya, kecuali job arsip retensi.
- Utamakan penangkapan otomatis (observer atau trait model) daripada pemanggilan manual, supaya tidak ada jalur yang terlewat.
- Secret dan data pribadi tidak pernah ditulis. Orang dirujuk dengan ID.
- Kolom yang hanya berisi timestamp atau counter (`updated_at`, jumlah view) diabaikan. Aksi massal menulis satu entri ringkasan.

## Model dan fitur baru
- Model baru yang menyimpan data bisnis didaftarkan ke audit, dengan test yang membuktikan perubahannya tercatat.
- Perubahan sensitif (koreksi data, impor, hapus massal, ganti role) menyertakan alasan.
- Membaca dan mengekspor log audit dijaga izin tersendiri, dan ekspornya juga dicatat.
- Retensi default 5 tahun, kecuali proyek menetapkan lain.
