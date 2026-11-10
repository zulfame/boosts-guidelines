# Standar produksi

## Produksi, bukan prototipe
- Tidak ada mock, stub, data palsu, atau placeholder diam-diam di kode aplikasi. Bagian yang belum selesai ditandai `TODO: <alasan>` dan disebutkan di laporan akhir.
- Jangan pernah melewati pengecekan: tanpa `--no-verify`, tanpa menurunkan level atau mematikan aturan lint dan static analysis, tanpa melewati atau menghapus test, tanpa menambah baseline. Perbaiki akar masalahnya.
- Utang teknis, risiko yang diterima, dan pengecualian aturan dicatat di `docs/decisions.md`, tidak pernah dibiarkan implisit.
- Agent tidak boleh menerima risiko atau menyimpang dari rule atas nama pemilik. Penyimpangan dicatat dengan status `diusulkan` dan `Diputuskan oleh: menunggu pemilik`, lalu disebut di laporan akhir agar user memutuskan. Entri berstatus `berlaku` baru ditulis setelah user menyetujuinya di percakapan.
- Jangan mengubah kode lama di luar permintaan demi memenuhi rules atau lolos review. Rules berlaku untuk kode yang ditulis atau diubah. Pelanggaran yang ditemukan di kode lama dilaporkan kepada user di laporan akhir, bukan diperbaiki diam-diam.
- Laporkan dengan jujur. Test yang tidak dijalankan tidak disebut lolos, dan hal yang hanya dibaca tanpa diuji disebut "belum diverifikasi".

## Batas default
Berlaku kecuali proyek menetapkan nilai lain di `docs/decisions.md`:

| Hal | Batas |
| --- | --- |
| Panggilan HTTP keluar | Timeout total 10 detik, connect 3 detik |
| Retry | Maksimal 3 percobaan dengan jeda bertambah, hanya untuk operasi yang aman diulang, dan tidak lebih dari 3 di semua lapisan |
| Ukuran halaman | Maksimal 100 item |
| Body request dan upload | 1 MB untuk body, 10 MB untuk file |
| Rate limit umum | 60 request per menit per user atau IP |
| Ekspor | Maksimal 50.000 baris |
| Konfirmasi password untuk aksi sensitif | Berlaku 15 menit |
| Transaksi database | Selesai di bawah 1 detik |

## Kualitas desain
- Method melakukan satu hal: targetkan 40 baris, maksimal 80, dengan nesting maksimal 4 tingkat. File maksimal 400 baris, kecuali migration, file hasil generate, dan fixture. Batas ini belum ditegakkan alat, jadi diperiksa saat review.
- Nama menyebut apa sesuatu itu atau apa yang dilakukannya. Class atau file yang namanya hanya kata generik (`Helper`, `Utils`, `Common`, `Manager`, `Misc`) tidak diizinkan. Kata generik di dalam nama yang tepat (`ConnectionPoolManager`) boleh.
- Early return lebih baik daripada kondisi bersarang. Parameter boolean yang mengubah perilaku method berarti seharusnya ada dua method. Himpunan nilai tertutup memakai enum. Tidak ada dead code.
- Kasus baru dari keluarga yang sama ditambahkan di satu tempat (kasus enum, baris config, atau registry). Jika kasus ketiga butuh mengedit banyak file, buat registry dulu.
- Komentar menjelaskan mengapa, bukan apa. Ekstrak kode di salinan ketiga, atau di salinan kedua jika keduanya mewakili satu aturan bisnis yang harus berubah bersama.
- Nilai yang bisa diubah operator berasal dari config. Nilai kebijakan (batas, aturan) ditulis sekali, di config atau konstanta bernama.

## Perbaikan bug
- Perbaiki akar masalahnya, bukan gejalanya. Tulis test regresi yang gagal lebih dulu, baru perbaiki.
- Cari defek yang sama dengan bug yang sedang diperbaiki di seluruh repositori. Perbaiki kemunculannya di file yang sudah kamu ubah, dan sebutkan sisanya di laporan.

## Library dan dependensi
- Sebelum memakai API library, pastikan API itu ada di versi yang terpasang (lewat `search-docs` atau source di `vendor/` dan `node_modules/`). Jangan memakai API yang diingat dari versi lain.
- Dependensi baru atau kenaikan versi mayor butuh persetujuan user. Ajukan dengan kebutuhan, alternatif, lisensi, status pemeliharaan, dan dampak keamanan, serta pastikan nama paketnya persis ada di Packagist atau npm.
- Kenaikan versi patch atau minor untuk menutup temuan `composer audit` atau `npm audit` boleh tanpa persetujuan baru, selama test lolos.
- Jangan pernah menjalankan script dari sumber tak dikenal (`curl | sh`).
