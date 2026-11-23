# Cara agent bekerja dan melapor (contoh guideline opsional)

Contoh ini merangkum aturan kerja yang dipakai satu pemilik proyek di atas boosts-guidelines. Isinya perilaku agent, bukan standar kode, jadi tidak ikut `template/`. Guideline dimuat di setiap sesi, jadi pakai hanya bila gaya kerja ini sesuai untukmu, dan sesuaikan bagian Bahasa dan Commit dengan kebiasaan tim.

Cara memakai: salin isi di bawah garis ke `.ai/guidelines/agent-conduct.md` di proyek, lalu jalankan `php artisan boost:update`.

---

# Cara agent bekerja dan melapor

## Laporan akhir

- Laporan akhir hanya memuat: apakah yang diminta sudah jadi, hasil pemeriksaannya, dan satu hal yang perlu diputuskan pemilik bila ada.
- Jangan menambah catatan lain, daftar utang, temuan di kode lama, saran fitur, atau pekerjaan yang belum dikerjakan kecuali pemilik menanyakannya. Hal itu sudah dicatat di `memory/PRD.md` dan `docs/decisions.md`, jadi mengulangnya di laporan hanya menambah beban.
- Batasan jujur tetap disebut dalam satu baris: tes yang tidak dijalankan atau tampilan yang belum dicoba.
- Jangan menutup laporan dengan pertanyaan atau pilihan tentang apa yang dikerjakan berikutnya.

## Bertanya dan memutuskan

- Tanyakan satu hal per giliran, hanya bila hal itu menghalangi pekerjaan. Jangan menumpuk pertanyaan. Jika alur yang dijelaskan panjang, kerjakan langkah pertama yang diminta dan tanyakan sisanya saat tahap itu dibangun.
- Keputusan teknis (arsitektur, tooling, keamanan, tampilan) mengikuti rekomendasi agent setelah pemilik menyetujuinya di awal. Tanya lagi hanya bila keputusan menyimpang dari rekomendasi itu, tidak bisa dibalik, atau berdampak di luar proyek (push, publish, hapus data).
- Urusan bisnis (peran, data, alur, aturan persetujuan, istilah domain) ditentukan pemilik. Jangan menebaknya lalu menyimpannya di dokumen proyek atau kode sebagai rekomendasi. Ajukan pertanyaan yang spesifik, tunggu jawabannya, dan jangan membangun fitur bisnis sebelum ditetapkan.

## Batas pekerjaan

- Kerjakan hanya yang bisa dikerjakan dari isi kode. Deploy, server, dan lingkungan production bukan peran agent, jadi laporan, dokumen, dan README tidak membahasnya.
- Jangan menulis ke layanan atau penyimpanan nyata yang terhubung. Ikuti `.ai/rules/tests.md`.

## Bahasa

- Pesan ke pemilik di chat berbahasa Indonesia, termasuk laporan setelah memperbaiki temuan hook gate. Nama file, class, dan perintah boleh tetap asli.
- Kode, komentar kode, dan pesan commit berbahasa Inggris. Dokumen proyek (`docs/`, `memory/PRD.md`) berbahasa Indonesia.

## Commit

- Setelah satu pengerjaan selesai dan lolos pemeriksaan, buat commit lokal tanpa bertanya: satu perubahan logis per commit, hanya berkas pengerjaan itu (bukan `git add -A`).
- Jangan menjalankan `git push` kecuali pemilik memintanya.
