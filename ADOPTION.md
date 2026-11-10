# Adopsi ke proyek Laravel

Langkah ini ditulis untuk diikuti agent secara berurutan. Jangan melompati langkah. Setiap langkah punya cara memeriksa bahwa langkah itu berhasil. Laporkan hasil setiap pemeriksaan kepada user dengan jujur, termasuk yang gagal atau tidak bisa dijalankan.

Sepanjang dokumen ini, `boosts-guidelines` berarti folder `/Users/Bananay/Claude/boosts-guidelines` dan `PROYEK` berarti root proyek Laravel tujuan.

## Aturan selama adopsi

- Jangan mengubah kode aplikasi untuk menyesuaikan dengan rules tanpa persetujuan user. Adopsi hanya memasang standar dan gate. Rules berlaku untuk kode yang ditulis setelahnya.
- Menambah atau mengubah dependensi (misalnya `laravel/boost`, Pest, Larastan) butuh persetujuan user.
- `php artisan boost:install` bersifat interaktif. Minta user menjalankannya di terminal mereka jika agent tidak bisa menjawab promptnya.
- Hook dan subagent baru aktif di sesi Claude Code berikutnya. Validasi gate (langkah 9) harus dilakukan di sesi baru.

## Langkah 1: periksa prasyarat

| Prasyarat | Cara memeriksa |
| --- | --- |
| Laravel 11.45 atau lebih baru | `php artisan --version` |
| PHP 8.2 atau lebih baru di PATH | `php -v` (hook gate memanggil `php`) |
| Dependensi PHP terpasang | folder `vendor/` ada |
| Dependensi JS terpasang | folder `node_modules/` ada, jika proyek punya `package.json` |

`node_modules` wajib ada sebelum langkah 2 dan 3. Sebagian skill Boost berasal dari paket npm (misalnya `inertia-react-development` dari `@inertiajs/react`). Tanpa `node_modules`, `boost:install` dan `boost:update` tidak memasang skill itu, bahkan menghapusnya jika sudah ada. Ini sudah dibuktikan saat pengujian.

## Langkah 2: pasang Laravel Boost

Lewati jika `vendor/laravel/boost` sudah ada. Jika belum, minta persetujuan user, lalu:

```bash
composer require laravel/boost --dev
```

## Langkah 3: jalankan boost:install

Lewati jika `boost.json` sudah ada dan `.claude/skills/` sudah berisi skill. Jika belum:

```bash
php artisan boost:install
```

Pilihan yang benar: agent Claude Code, fitur guidelines, skills, dan MCP. Integrasi lain (Cloud, Sail, Nightwatch) sesuai kebutuhan proyek.

Periksa: `boost.json` ada, `.mcp.json` berisi server `laravel-boost`, dan `.claude/skills/` berisi minimal `laravel-best-practices`.

## Langkah 4: jalankan installer

Lihat dulu apa yang akan berubah:

```bash
php /Users/Bananay/Claude/boosts-guidelines/scripts/install.php PROYEK --dry-run
```

Jika laporannya masuk akal, jalankan:

```bash
php /Users/Bananay/Claude/boosts-guidelines/scripts/install.php PROYEK
```

Installer menyalin `template/`, menggabungkan hook ke `.claude/settings.json`, membuat `.claude/hooks/boost-gate.json` dari skill yang terpasang, menambahkan blok env Boost ke `.env.example` dan `.env`, lalu menjalankan `boost:update` dan `boost:index-rules`.

Baca bagian "Perlu perhatian" di laporan installer dan tangani setiap barisnya:

| Catatan | Tindakan |
| --- | --- |
| Pest tidak terpasang | Arch test tidak dipasang. Tawarkan ke user untuk memasang Pest, atau lanjut tanpa arch test dan catat di `docs/decisions.md` |
| PHPStan tidak terpasang | Static analysis di Stop gate mati. Tawarkan `larastan/larastan`, lalu isi `quality.static` di `boost-gate.json` |
| Tidak ada script pemeriksaan tipe frontend | Isi `quality.frontend` jika proyek punya perintah lint atau tipe, atau biarkan kosong |
| Tidak ada skill di `.claude/skills` | Kembali ke langkah 3, lalu jalankan installer lagi dengan `--force` |
| File "Dibiarkan karena isinya berbeda" | Proyek sudah punya versi sendiri. Bandingkan dengan `boosts-guidelines/template/<path>`, gabungkan secara manual, dan jangan memakai `--force` tanpa persetujuan user |

## Langkah 5: periksa hasil instalasi

Jalankan dan laporkan hasilnya:

| Pemeriksaan | Hasil yang diharapkan |
| --- | --- |
| `grep -c "=== .ai/production-standards rules ===" AGENTS.md` (atau `CLAUDE.md`) | `1` |
| `grep -c "=== .ai/project-memory rules ===" AGENTS.md` | `1` |
| `grep -c "^| .*\.ai/rules/" .ai/rules/index.md` | `28` atau lebih |
| `php -r 'json_decode(file_get_contents(".claude/settings.json")) or exit(1);'` | exit 0 |
| `cat .claude/hooks/boost-gate.json` | Bagian `skills` berisi skill yang sesuai stack proyek |
| `php artisan test --compact tests/Unit/ArchTest.php` | Lolos |

Jika arch test gagal di proyek yang sudah berjalan, artinya kode lama melanggar aturan struktural. Jangan mengubah kode atau menghapus test tanpa persetujuan user. Tunjukkan pelanggarannya, lalu tawarkan dua pilihan: memperbaiki kodenya, atau mengecualikan path itu dengan `->ignoring(...)` di arch test disertai entri baru di `docs/decisions.md`.

## Langkah 6: isi docs/decisions.md

Installer membuat `docs/decisions.md` dengan 10 keputusan standar bertanggal hari ini. Untuk setiap entri, ganti baris "Dampak untuk proyek ini: diisi saat adopsi." dengan kondisi nyata proyek. Contoh:

- D-0001 (password NIST): periksa `Password::defaults()` di `AppServiceProvider`. Tulis apakah sudah sesuai atau perlu disesuaikan.
- D-0002 (respons yang tidak membocorkan akun): periksa pesan reset password dan registrasi.
- D-0003 (batas sesi): tulis nilai `SESSION_LIFETIME` saat ini dan jenis data aplikasi.
- D-0004 (2FA untuk admin): tulis apakah ada role admin dan apakah 2FA sudah diwajibkan.

Jika proyek perlu menyimpang dari keputusan standar, tambahkan entri baru yang menggantikannya, jangan mengedit entri standar.

## Langkah 7: buat memory/PRD.md

Jika belum ada, salin `boosts-guidelines/stubs/PRD.md` ke `PROYEK/memory/PRD.md`, lalu isi bersama user: visi, pengguna, fitur, kondisi saat ini, keputusan produk, pertanyaan terbuka, dan langkah berikutnya. Jangan mengarang isi produk. Tanyakan kepada user untuk bagian yang tidak bisa dibaca dari kode.

## Langkah 8: siapkan commit

File berikut harus masuk ke git:

- `.ai/` (guidelines dan rules),
- `.claude/settings.json`, `.claude/hooks/`, `.claude/agents/`,
- `tests/Unit/ArchTest.php`,
- `docs/decisions.md`, `memory/PRD.md`,
- `.env.example`.

Pastikan `.claude/settings.json` tidak masuk `.gitignore`. File `.claude/settings.local.json` dan `.env` tetap tidak di-commit. Soal `AGENTS.md`, `.mcp.json`, `boost.json`, dan `.claude/skills/`, ikuti kebijakan tim: dokumentasi resmi Boost menyebut file itu boleh di-ignore karena bisa dibuat ulang. Commit hanya jika user memintanya.

## Langkah 9: validasi gate di sesi baru

Minta user membuka sesi Claude Code baru di `PROYEK`, lalu jalankan pemeriksaan berikut di sesi itu. Catat hasil setiap pemeriksaan.

| # | Pemeriksaan | Hasil yang diharapkan |
| --- | --- | --- |
| 1 | Tanyakan kepada agent: "Apa aturan Boost gate di proyek ini?" | Agent menyebut empat syarat gate. Ini membuktikan hook SessionStart berjalan |
| 2 | Minta agent mengubah satu baris komentar di model mana pun tanpa persiapan | Edit pertama ditolak dengan daftar rule, skill, dan `search-docs` |
| 3 | Biarkan agent memenuhi syaratnya lalu mengulang edit | Edit diizinkan |
| 4 | Biarkan agent menyelesaikan giliran | Stop gate menjalankan Pint, PHPStan, dan test, lalu meminta review dari `boost-reviewer` |
| 5 | Biarkan agent memanggil `boost-reviewer` | Subagent tersedia dan menutup laporan dengan `VERDICT: PASS` atau `FAIL` |
| 6 | Kembalikan perubahan uji coba | Tidak ada perubahan tersisa di kode aplikasi |
| 7 | Opsional: jalankan `/compact`, lalu minta edit lagi | Edit ditolak lagi karena rules harus dibaca ulang. Jika diizinkan, catat bahwa penanda ringkasan versi Claude Code ini berbeda |

Jika pemeriksaan 5 gagal karena subagent tidak dikenal, pastikan file `.claude/agents/boost-reviewer.md` ada dan sesinya dibuka setelah file itu dibuat.

## Langkah 10: sesuaikan bila perlu

Semua penyesuaian gate ada di `.claude/hooks/boost-gate.json`:

| Kebutuhan | Ubah |
| --- | --- |
| Peta skill kurang tepat untuk stack proyek | `skills` |
| Test suite lambat | `quality.tests`, misalnya `php artisan test --compact --parallel` |
| Review terlalu mahal untuk proyek kecil | `review.enabled` menjadi `false` |
| `search-docs` tidak perlu diwajibkan | `search_docs.required` menjadi `false` |
| Folder tambahan perlu dijaga atau dikecualikan | `exempt`, `quality.watch`, `review.globs` |

Setiap penyesuaian yang melonggarkan gate dicatat di `docs/decisions.md`. Untuk mematikan gate sementara di satu mesin saja, gunakan `"disableAllHooks": true` di `.claude/settings.local.json`.

## Memperbarui standar di proyek yang sudah adopsi

1. Jalankan installer dengan `--dry-run`.
2. File yang dilaporkan "Dibiarkan" sudah berbeda dari versi boosts-guidelines. Bandingkan dengan `diff PROYEK/<path> /Users/Bananay/Claude/boosts-guidelines/template/<path>`. Perbedaan bisa berasal dari rule tambahan yang direkam lewat `record-rule` di proyek itu, jadi jangan menimpanya begitu saja.
3. Gabungkan perubahan dari boosts-guidelines secara manual, atau pakai `--force` hanya untuk file yang tidak punya perubahan lokal. `docs/decisions.md` tidak pernah ditimpa.
4. Jalankan `php artisan boost:index-rules` dan ulangi langkah 5.
