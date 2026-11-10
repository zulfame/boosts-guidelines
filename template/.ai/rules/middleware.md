---
paths:
  - 'app/Http/Middleware/**'
---

# Middleware

## Middleware ringan dan data bersama tetap kecil
- Middleware berjalan di setiap request, jadi hindari query berat atau pemanggilan API luar di dalamnya. Cache hasilnya jika memang perlu.
- Data yang dibagikan ke semua halaman (shared props Inertia, `View::share()`, view composer) hanya berisi yang benar-benar dibutuhkan semua halaman, misalnya sebagian data user yang login, flash message, dan hak akses.
- Jangan membagikan model utuh, atribut tersembunyi, atau secret. Pilih kolom secara eksplisit.
- Data bersama yang mahal dihitung secara lazy (closure atau fitur lazy dari stack frontend) supaya tidak dihitung di setiap request.
