---
paths:
  - 'docs/**'
  - 'memory/**'
  - 'README*'
  - 'CHANGELOG*'
---

# Dokumentasi

## Tempat setiap jenis dokumen

| Dokumen | Isi |
| --- | --- |
| `AGENTS.md` | Instruksi untuk agent. Bagian Boost dibuat otomatis dari `.ai/guidelines`, jadi edit sumbernya, bukan file ini |
| `.ai/rules/*.md` | Standar kode per folder |
| `README.md` | Untuk manusia: apa proyeknya, cara setup, menjalankan, dan menguji, serta nama variabel env yang ada |
| `memory/PRD.md` | Konteks produk: visi, kondisi saat ini, pertanyaan terbuka, dan langkah berikutnya. Ditulis ulang di tempat, tidak ditambah seperti log |
| `docs/decisions.md` | Keputusan, risiko yang diterima, pengecualian aturan, dan utang teknis |
| `CHANGELOG.md` | Perubahan yang terasa bagi pengguna dan pemanggil API |
| `docs/` lainnya | Kontrak dan catatan desain yang menjelaskan kode |

## Aturan menulis dokumentasi
- Keputusan yang mengubah aturan, menerima risiko, atau mengambil utang dicatat saat diambil: apa, mengapa, siapa yang memutuskan, kapan, dan kapan ditinjau ulang.
- Dokumen menjelaskan kode apa adanya. Perubahan perilaku belum selesai sampai dokumennya ikut diperbarui.
- Changelog menyebut apa yang berubah, apa yang tidak kompatibel, dan cara kembali untuk perubahan yang tidak bisa dibatalkan.
- Format changelog mengikuti Keep a Changelog: bagian `Unreleased` di paling atas, lalu setiap versi dengan tanggal absolut, dikelompokkan per jenis (`Added`, `Changed`, `Deprecated`, `Removed`, `Fixed`, `Security`). Nomor versi mengikuti Semantic Versioning.
- Dokumentasi API dibuat dari kode atau skema jika memungkinkan, bukan ditulis tangan terpisah.
- Tulis lugas dengan tanggal absolut (YYYY-MM-DD), tanpa angka karangan.
