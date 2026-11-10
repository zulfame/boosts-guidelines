---
paths:
  - 'app/Http/Controllers/**'
  - 'app/Livewire/**'
---

# Controller

## Controller fokus pada urusan HTTP
- Tugas controller: menerima input, otorisasi, validasi, menjalankan operasinya, lalu mengembalikan respons (view, halaman Inertia, redirect, atau JSON).
- Komponen yang berperan seperti controller, misalnya komponen Livewire, mengikuti aturan yang sama.
- Jangan ada query rumit, pemanggilan API luar, atau pengiriman email langsung di controller.

## Pindahkan logika ke Action hanya jika ada alasannya
- Operasi sederhana pada satu model (isi, simpan, hapus) boleh langsung di controller.
- Pindahkan ke Action di `app/Actions/<Domain>/` jika salah satu terpenuhi: dipakai di lebih dari satu tempat (controller, job, command), menyentuh lebih dari satu model atau butuh transaksi, memicu efek samping (email, job, event, API luar), atau perlu diuji terpisah.
- Jangan membuat Action atau Service hanya demi membuat controller lebih pendek.

## Controller disusun per resource
- Satu controller untuk satu resource, dengan nama tunggal (`ProductController`) dan method standar: `index`, `show`, `create`, `store`, `edit`, `update`, `destroy`.
- Aksi khusus seperti `publish` atau `approve` adalah tanda untuk mempertimbangkan resource terpisah (misalnya `PublishedProductController`). Tetap boleh sebagai route eksplisit jika resource terpisah justru membingungkan.
- Gunakan route model binding. Untuk resource bersarang, gunakan scoped binding. Scoped binding membatasi pencarian model, tetapi tidak menggantikan otorisasi.
