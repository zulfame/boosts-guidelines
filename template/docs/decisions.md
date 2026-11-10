# Catatan keputusan

Keputusan D-0001 sampai D-0010 berasal dari standar boosts-guidelines dan berlaku di proyek ini sejak diadopsi. Jika proyek ini perlu menyimpang, tambahkan entri baru yang menggantikannya. Isi baris "Dampak untuk proyek ini" saat adopsi.

Tempat mencatat keputusan, risiko yang sengaja diterima, pengecualian aturan, dan utang teknis. Setiap entri menyebut apa yang diputuskan, alasannya, siapa yang memutuskan, kapan, dan kapan ditinjau ulang. Entri tidak dihapus. Jika keputusan berubah, tambahkan entri baru yang merujuk entri lama, lalu ubah status entri lama menjadi "digantikan".

Format entri:

```markdown
## D-NNNN: Judul singkat

- Tanggal: YYYY-MM-DD
- Status: berlaku | digantikan oleh D-NNNN
- Diputuskan oleh: ...
- Keputusan: ...
- Alasan: ...
- Dampak untuk proyek ini: diisi saat adopsi.
- Tinjau ulang: YYYY-MM-DD
```

## D-0001: Kebijakan password mengikuti NIST SP 800-63B

- Tanggal: {{ADOPTED_AT}}
- Status: berlaku
- Diputuskan oleh: standar boosts-guidelines, diadopsi oleh pemilik proyek
- Keputusan: Minimal 15 karakter jika password satu-satunya faktor (8 jika 2FA diwajibkan), tanpa aturan komposisi, dan dicek terhadap daftar password bocor. Aturan ada di `.ai/rules/auth.md`.
- Alasan: Aturan komposisi (wajib huruf besar, angka, simbol) mendorong password yang mudah ditebak. Panjang password lebih menentukan keamanan.
- Dampak untuk proyek ini: diisi saat adopsi.
- Tinjau ulang: {{REVIEW_AT}}

## D-0002: Respons sama untuk akun yang ada dan tidak ada

- Tanggal: {{ADOPTED_AT}}
- Status: berlaku
- Diputuskan oleh: standar boosts-guidelines, diadopsi oleh pemilik proyek
- Keputusan: Login, registrasi, reset password, dan pemulihan akun tidak membocorkan apakah sebuah email terdaftar.
- Alasan: Mencegah pihak luar mengumpulkan daftar akun yang valid.
- Dampak untuk proyek ini: diisi saat adopsi.
- Tinjau ulang: {{REVIEW_AT}}

## D-0003: Batas sesi untuk aplikasi berdata sensitif

- Tanggal: {{ADOPTED_AT}}
- Status: berlaku
- Diputuskan oleh: standar boosts-guidelines, diadopsi oleh pemilik proyek
- Keputusan: Aplikasi yang menyimpan data sensitif memakai idle timeout 15 menit dan umur sesi maksimal 12 jam. Aplikasi lain boleh melonggarkan dengan entri keputusan tersendiri.
- Alasan: Membatasi dampak sesi yang tertinggal terbuka atau dicuri.
- Dampak untuk proyek ini: diisi saat adopsi.
- Tinjau ulang: {{REVIEW_AT}}

## D-0004: 2FA wajib untuk akun berhak istimewa

- Tanggal: {{ADOPTED_AT}}
- Status: berlaku
- Diputuskan oleh: standar boosts-guidelines, diadopsi oleh pemilik proyek
- Keputusan: 2FA wajib untuk admin dan operator, dan opsional untuk user biasa kecuali proyek menetapkan lain.
- Alasan: Akun berhak istimewa paling berbahaya jika diambil alih, sedangkan mewajibkan 2FA untuk semua user menambah hambatan yang belum tentu perlu.
- Dampak untuk proyek ini: diisi saat adopsi.
- Tinjau ulang: {{REVIEW_AT}}

## D-0005: Error API membawa field `code`

- Tanggal: {{ADOPTED_AT}}
- Status: berlaku
- Diputuskan oleh: standar boosts-guidelines, diadopsi oleh pemilik proyek
- Keputusan: Setiap body error API memakai format bawaan Laravel (`message`, `errors`) ditambah `code` yang stabil dari satu katalog. Aturan ada di `.ai/rules/api.md`.
- Alasan: Klien (aplikasi mobile, integrasi) butuh kode yang tidak berubah walaupun teks pesan berubah atau diterjemahkan.
- Dampak untuk proyek ini: diisi saat adopsi.
- Tinjau ulang: {{REVIEW_AT}}

## D-0006: Jejak audit bersifat opsional

- Tanggal: {{ADOPTED_AT}}
- Status: berlaku
- Diputuskan oleh: standar boosts-guidelines, diadopsi oleh pemilik proyek
- Keputusan: Jejak audit tidak wajib secara default. Proyek yang membutuhkannya menambahkan entri keputusan beserta alat yang dipakai, lalu mengikuti `.ai/rules/audit.md`.
- Alasan: Jejak audit penuh butuh paket baru atau sistem sendiri, dan kebutuhannya bergantung pada jenis aplikasi.
- Dampak untuk proyek ini: diisi saat adopsi.
- Tinjau ulang: {{REVIEW_AT}}

## D-0007: Batas ukuran kode sebagai pedoman

- Tanggal: {{ADOPTED_AT}}
- Status: berlaku
- Diputuskan oleh: standar boosts-guidelines, diadopsi oleh pemilik proyek
- Keputusan: Batas method 80 baris, nesting 4 tingkat, dan file 400 baris berlaku sebagai pedoman yang diperiksa saat review.
- Alasan: Belum ada alat penegak yang terpasang, dan memasangnya berarti menambah dependensi.
- Dampak untuk proyek ini: diisi saat adopsi.
- Tinjau ulang: {{REVIEW_AT}}

## D-0008: Coverage 80% sebagai target

- Tanggal: {{ADOPTED_AT}}
- Status: berlaku
- Diputuskan oleh: standar boosts-guidelines, diadopsi oleh pemilik proyek
- Keputusan: Kode baru menargetkan coverage minimal 80%. Pengukuran di CI diaktifkan belakangan.
- Alasan: Pengukuran coverage butuh pcov atau xdebug di CI.
- Dampak untuk proyek ini: diisi saat adopsi.
- Tinjau ulang: {{REVIEW_AT}}

## D-0009: Percabangan berdasarkan environment hanya di service provider

- Tanggal: {{ADOPTED_AT}}
- Status: berlaku
- Diputuskan oleh: standar boosts-guidelines, diadopsi oleh pemilik proyek
- Keputusan: `app()->isProduction()` dan sejenisnya hanya dipakai di service provider untuk pengaturan global. Di tempat lain, perbedaan perilaku datang dari config.
- Alasan: Mengikuti konvensi Laravel untuk pengaturan global, sambil menjaga kode aplikasi tetap bisa diuji tanpa bergantung pada nama environment.
- Dampak untuk proyek ini: diisi saat adopsi.
- Tinjau ulang: {{REVIEW_AT}}

## D-0010: ID publik tidak bisa ditebak

- Tanggal: {{ADOPTED_AT}}
- Status: berlaku
- Diputuskan oleh: standar boosts-guidelines, diadopsi oleh pemilik proyek
- Keputusan: ID yang terlihat di URL atau API memakai ULID sebagai route key, atau setiap pencarian record selalu dibatasi per pemilik.
- Alasan: ID berurutan memudahkan pihak luar menebak dan mencoba mengakses data orang lain.
- Dampak untuk proyek ini: diisi saat adopsi.
- Tinjau ulang: {{REVIEW_AT}}
