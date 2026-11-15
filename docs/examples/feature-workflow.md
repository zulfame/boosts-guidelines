# Alur kerja fitur baru (contoh guideline opsional)

Contoh ini berasal dari satu proyek yang memakai boosts-guidelines. Isinya menambah langkah di atas batas bawah gate. Gate hanya menuntut rule dibaca, skill dimuat, dan `search-docs` dipanggil sekali per konteks, jadi pada sesi yang memuat beberapa fitur, fitur kedua dan seterusnya tidak dihitung ulang.

Guideline membuat setiap sesi membaca teks tambahan, jadi pakai hanya jika agent di proyekmu memang sering melewati langkah ini. Pada pengujian kit, agent melakukan langkah-langkah ini tanpa guideline tambahan.

Cara memakai: salin isi di bawah garis ke `.ai/guidelines/feature-workflow.md` di proyek, lalu jalankan `php artisan boost:update`.

---

# Alur kerja fitur baru

## Sebelum menulis kode

- Baca file `.ai/rules` yang cocok dengan path memakai tool Read, bukan `cat` di Bash. Untuk skill `laravel-best-practices` dan `testing-best-practices`, baca juga file di folder `rules/` skill itu yang sesuai topik fitur.
- Panggil `search-docs` untuk setiap API Laravel, Inertia, Pest, atau paket lain yang dipakai fitur. Alasan "polanya sudah ada di kode lain" tidak cukup untuk melewatinya.
- Lihat skema dengan `database-schema` sebelum membuat migration atau model. Lihat data dengan `database-query`, bukan klien `mysql` atau tinker.

## Lebih dari satu fitur dalam satu sesi

- Gate tidak menghitung ulang. Ulangi langkah di atas untuk setiap fitur, atau mulai sesi baru dengan `/clear` setelah fitur selesai.

## Laporan akhir

- Sebut panggilan `search-docs` dan `database-schema` yang dipakai.
