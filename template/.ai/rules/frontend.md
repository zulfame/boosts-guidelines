---
paths:
  - 'resources/**'
---

# Frontend (Blade, Livewire, Inertia, Vue, React, Svelte)

## Prinsip yang berlaku untuk semua stack frontend
- Ikuti skill dan guideline untuk stack yang dipakai proyek. Aturan di sini hanya prinsip umum.
- Tidak ada query database atau logika bisnis di view dan komponen. Data disiapkan di controller, Action, atau komponen server.
- URL selalu dibuat dari nama route dengan helper yang tersedia di stack itu (`route()`, Wayfinder, atau sejenisnya), tidak pernah ditulis sebagai string.
- Pakai ulang komponen yang sudah ada sebelum membuat yang baru atau menambah dependensi UI baru. Perubahan tampilan dilakukan di komponen bersama, bukan dengan style sekali pakai di satu halaman.
- Warna, jarak, ukuran huruf, dan radius berasal dari design token di satu tempat (tema CSS proyek), bukan nilai literal yang tersebar.
- File yang dibuat otomatis oleh tool (misalnya hasil generate route atau tipe) tidak diedit manual. Generate ulang.

## Data yang sampai ke browser bisa dilihat dan diubah pengguna
- Props halaman, public property Livewire, dan data yang di-embed ke HTML atau JavaScript terlihat oleh pengguna. Jangan kirim model utuh, atribut tersembunyi, atau secret. Kirim kolom yang dibutuhkan saja.
- Nilai yang datang kembali dari browser selalu divalidasi dan diotorisasi ulang di server, termasuk ID yang tersimpan di state komponen.
- Tampilkan data pengguna dengan escaping bawaan (`{{ }}` di Blade, interpolasi biasa di Vue, React, dan Svelte). Fitur HTML mentah (`{!! !!}`, `v-html`, `dangerouslySetInnerHTML`, `{@html}`) hanya untuk konten yang sudah disanitasi.
- Secret dan token tidak pernah disimpan di local storage, session storage, atau URL.

## Form dan interaksi
- Input menegakkan aturan yang sama dengan server (panjang maksimum, hanya angka, format), supaya karakter yang ditolak server tidak bisa diketik. Server tetap menjadi penentu dan memeriksa ulang. Jangan memblokir paste atau password manager.
- Setiap form menampilkan error validasi di dekat field-nya. Tombol aksi dinonaktifkan selama request berjalan, supaya klik ganda hanya mengirim satu request.
- Aksi yang tidak bisa dibatalkan (hapus, keluarkan anggota) meminta konfirmasi yang menjelaskan akibatnya.
- 401 mengarahkan ke halaman login, 403 karena butuh konfirmasi password membuka dialog konfirmasi, dan 422 mengisi error per field.

## State dan tampilan data
- Setiap daftar dan tampilan data punya state loading, kosong, dan error. Setiap aksi memberi umpan balik yang terlihat.
- Tanggal, waktu, angka, dan mata uang diformat di satu tempat per lapisan (satu helper), sesuai locale pengguna. Nilai untuk mesin tetap ISO dan angka polos.
- Teks untuk pengguna memakai sistem terjemahan proyek jika aplikasi mendukung lebih dari satu bahasa. Tidak ada teks placeholder atau data palsu di halaman yang sudah jadi.
- Halaman error (404, 500, 503) menjelaskan apa yang terjadi dengan kata-kata, bukan hanya angka, dan memberi jalan keluar.
- Jika memakai TypeScript, jangan memakai `any` atau non-null assertion (`!`) untuk membungkam error.

## Aksesibilitas dan performa
- Targetnya WCAG 2.2 AA. Setiap kontrol punya nama yang bisa dibaca screen reader (tombol ikon memakai `aria-label`), setiap input terhubung dengan label dan pesan errornya, dan kontras teks minimal 4.5:1. Warna tidak pernah menjadi satu-satunya penanda.
- Semua yang bisa diklik adalah `button` atau link. Navigasi keyboard mengikuti urutan baca, fokus selalu terlihat, dan dialog mengurung fokus, bisa ditutup dengan Esc, serta mengembalikan fokus ke pemicunya.
- Halaman punya atribut `lang`, judul yang deskriptif, satu `h1`, dan bisa dipakai di lebar 320 px serta zoom 200% tanpa scroll ke samping.
- Gambar punya dimensi eksplisit dan teks alternatif (atau ditandai dekoratif), dan gambar di bawah layar pertama dimuat lazy.
