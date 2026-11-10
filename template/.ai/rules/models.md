---
paths:
  - 'app/Models/**'
---

# Model

## Atribut model dideklarasikan dengan jelas
- Tentukan atribut yang boleh diisi massal dengan `#[Fillable]` atau `$fillable`, mengikuti pola model lain di proyek. Jangan pernah memakai `$guarded = []`.
- Cast ditulis di method `casts()` (atau `$casts` jika itu pola proyek). Kolom tanggal yang dipakai sebagai tanggal wajib di-cast.
- Kolom dengan pilihan nilai terbatas (status, tipe, role) di-cast ke enum di `app/Enums/`.
- Tulis PHPDoc `@property` untuk kolom agar static analysis dan editor mengenali tipenya.
- Atribut sensitif disembunyikan (`#[Hidden]` atau `$hidden`). Nilai yang harus bisa dibaca kembali memakai cast `encrypted` dengan kolom bertipe `text`.
- Kolom uang berupa integer satuan terkecil. Tidak ada uang bertipe float.

## Relasi dan query di model
- Method relasi punya return type dengan generics, misalnya `HasMany<Post, $this>`.
- Kondisi query yang dipakai berulang dijadikan local scope.
- Global scope hanya untuk batasan yang berlaku di mana pun, seperti soft delete atau multi-tenancy, karena ia diam-diam mengubah setiap query.
- Utamakan query lewat model dan relasi (misalnya `whereBelongsTo()`) daripada query builder dengan nama tabel.

## Identitas dan isolasi data
- ID yang terlihat di URL atau API tidak boleh bisa ditebak. Pilih salah satu: pakai ULID sebagai route key (`HasUlids`), atau setiap pencarian record selalu dibatasi per pemilik (misalnya `$request->user()->orders()->findOrFail($id)`).
- Pada aplikasi multi-tenant, satu mekanisme membatasi setiap query per tenant (global scope atau relasi), dan ada test yang membuktikan tenant A tidak bisa membaca data tenant B.
- Soft delete dan tabel riwayat dipilih dengan sengaja dan dicatat di `docs/decisions.md`. Data yang diandalkan orang diarsipkan atau ditutup, bukan dihapus permanen.
