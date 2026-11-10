---
paths:
  - 'database/factories/**'
  - 'database/seeders/**'
---

# Factory dan seeder

## Setiap model punya factory dengan state yang bermakna
- Setiap model baru punya factory yang secara default menghasilkan data valid, ditambah state bernama untuk variasi penting, misalnya `unverified()`, `withTwoFactor()`, `suspended()`.
- Relasi dibuat dengan factory bersarang atau `for()`, bukan ID yang ditulis manual.
- Seeder hanya untuk data local atau demo, dan tidak boleh dijalankan saat deploy production.
