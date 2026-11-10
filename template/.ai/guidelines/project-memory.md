# Memori proyek (memory/PRD.md)

- `memory/PRD.md` adalah memori bersama proyek untuk semua agent, supaya konteks tidak hilang saat berpindah agent atau sesi. Isinya konteks produk: tujuan aplikasi, pengguna, fitur beserta statusnya, keputusan produk dan alasannya, serta pekerjaan yang sedang berjalan.
- Baca `memory/PRD.md` di awal setiap sesi, sebelum merencanakan atau mengubah kode. Jika file itu belum ada, tawarkan kepada user untuk membuatnya.
- Perbarui `memory/PRD.md` setelah fitur selesai, ruang lingkup berubah, atau ada keputusan produk baru. Tulis singkat, sertakan tanggal (YYYY-MM-DD), dan hapus informasi yang sudah tidak berlaku.
- Pembagian tempat:
  - Standar penulisan kode dicatat di `.ai/rules` lewat tool `record-rule`, bukan di `memory/PRD.md`.
  - Konteks produk dan progres dicatat di `memory/PRD.md`.
  - Hal yang bisa dibaca langsung dari kode atau riwayat git tidak perlu dicatat.
- Jangan menulis secret, kredensial, atau data pribadi ke `memory/PRD.md`.
- Tulis isi `memory/PRD.md` dalam bahasa Indonesia.
