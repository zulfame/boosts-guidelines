---
paths:
  - 'app/Http/Requests/**'
---

# Validasi dan Form Request

## Validasi dipisahkan dari controller
- Endpoint tulis memakai Form Request di `app/Http/Requests/<Domain>/` dengan nama `<Aksi><Resource>Request`, misalnya `StoreProductRequest`. Jika proyek sudah konsisten memakai pola lain, ikuti pola itu.
- Komponen yang punya mekanisme validasi sendiri (misalnya Livewire) memakai mekanisme tersebut dengan prinsip yang sama.
- `authorize()` berisi pengecekan sungguhan (policy atau gate). Validasi bukan otorisasi. Kembalikan `true` hanya jika route memang publik atau sudah diotorisasi di tempat lain.
- Aturan yang dipakai di banyak tempat dikumpulkan dalam trait atau Rule class, bukan disalin.
- Tulis aturan dalam bentuk array, bukan string dengan pemisah `|`.

## Hanya data yang divalidasi yang diteruskan
- Teruskan data dengan `validated()` atau `safe()->only([...])`. Jangan pernah memakai `$request->all()` atau `input()` untuk menyimpan data.
- Data yang lolos validasi belum tentu aman untuk mass assignment. Atribut sensitif (role, status, harga) jangan dibuat fillable hanya agar praktis.
- Validasi yang melibatkan beberapa field atau kondisi aplikasi ditaruh di method `after()`.
- Validasi tidak mencegah race condition. Aturan seperti stok atau keunikan wajib dijaga juga oleh constraint database, update atomik, atau transaksi.
