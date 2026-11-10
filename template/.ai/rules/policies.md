---
paths:
  - 'app/Policies/**'
---

# Policy

## Otorisasi ada di policy, tolak secara default
- Setiap model yang bisa dilihat atau diubah pengguna punya policy (ditemukan otomatis, dengan nama `ModelPolicy`).
- Policy dipanggil dari `authorize()` di Form Request, `Gate` atau `$this->authorize()` di controller dan komponen, atau middleware `can:`. Jangan menulis pengecekan role atau kepemilikan langsung di controller, komponen, atau view.
- Kembalikan `false` kecuali akses memang diberikan secara eksplisit. Login saja tidak berarti punya izin.
- Aksi yang memang publik untuk semua orang tidak perlu pengecekan tambahan.
- Jika data satu pemilik atau tenant tidak boleh diketahui keberadaannya oleh pihak lain, kembalikan 404, bukan 403.
- Pengecekan izin di frontend (menyembunyikan tombol) hanya untuk tampilan. Server tetap wajib memeriksa.
