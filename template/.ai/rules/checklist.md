---
paths:
  - 'app/Http/Controllers/**'
  - 'app/Livewire/**'
  - 'app/Actions/**'
  - 'app/Jobs/**'
  - 'app/Policies/**'
  - 'app/Console/**'
  - 'app/Http/Middleware/**'
  - 'routes/**'
  - 'database/migrations/**'
---

# Checklist produksi

## Sembilan pertanyaan sebelum menyebut selesai
Wajib untuk endpoint, job, command, atau migration baru, perubahan autentikasi atau izin, dan review kode yang menyentuhnya. Jawab setiap pertanyaan di laporan akhir dan sebutkan test yang membuktikannya. Untuk pertanyaan yang tidak berlaku, cukup satu kalimat yang menyebut apa yang tidak disentuh. Risiko yang tidak bisa diuji disebut "belum diverifikasi".

1. Siapa boleh melakukan ini, dan terhadap data apa? Bisakah pemanggil mengarahkan aksi ke data milik orang lain?
2. Input apa yang bisa datang? Kosong, tipe salah, terlalu panjang, terlalu banyak, jahat, atau duplikat. Setiap batas dinyatakan: panjang, rentang, ukuran halaman, jumlah baris, serta ukuran dan tipe file.
3. Seperti apa setiap hasilnya? Sukses, tidak ditemukan, ditolak, tidak valid, kena rate limit, terlalu besar, konflik, atau error. Masing-masing punya satu respons tetap (lihat `api.md`).
4. Bagaimana jika terjadi dua kali, atau dua sekaligus? Lock atau constraint mana yang menjaganya, dan apakah pengulangan aman?
5. Apa yang tertinggal jika gagal di tengah jalan? Penulisan yang saling terkait ada dalam satu transaksi pendek, dan file atau record setengah jadi dibersihkan.
6. Berapa biayanya, dan bagaimana tumbuhnya seiring data? Periksa query di dalam loop, daftar tanpa batas, dan pekerjaan berat di dalam request.
7. Apa yang bisa bocor? Secret, data pribadi, atau keberadaan akun di respons, log, pesan error, maupun perbedaan waktu respons.
8. Apakah tercatat? Log dan kejadian keamanan sesuai `logging.md`, dan jejak audit sesuai `audit.md` jika proyek memakainya.
9. Apakah setiap risiko di atas dibuktikan test? Satu test per keputusan dan per jenis kegagalan, termasuk penolakan.

Reviewer memakai pertanyaan yang sama untuk kode orang lain dan mengembalikan pertanyaan pertama yang belum terjawab. Risiko yang sengaja diterima alih-alih diperbaiki dicatat di `docs/decisions.md`.
