---
paths:
  - 'app/Exceptions/**'
  - bootstrap/app.php
---

# Exception

## Error tidak boleh ditelan diam-diam
- Lempar exception domain yang bernama jelas dari `app/Exceptions/`, bukan mengembalikan `false` atau `null` saat gagal.
- Jangan menangkap `Throwable` hanya agar program lanjut diam-diam. Tangani dengan benar, atau laporkan dengan `report($e)` lalu lempar ulang.
- Tambahkan data pendukung lewat method `context()` di class exception, supaya ikut tercatat di log.

## Kebijakan exception di satu tempat
- Kumpulkan aturan report dan render di `withExceptions()` pada `bootstrap/app.php`. Jika proyek sudah memakai method `report()` dan `render()` di class exception, ikuti pola itu. Jangan campur keduanya.
- Exception yang memang bagian dari alur normal (misalnya stok habis) mengimplementasikan `ShouldntReport` supaya tidak membanjiri log.
- Integrasi yang sering gagal dibatasi laporannya dengan `throttle()`.
- Pengguna melihat pesan yang sudah diterjemahkan, tidak pernah stack trace (`APP_DEBUG=false`).
