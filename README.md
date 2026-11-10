# boosts-guidelines

Standar dan perangkat untuk memakai Laravel Boost di proyek Laravel dengan standar produksi. Isinya dikemas supaya bisa dipasang ke proyek mana pun dengan satu perintah dan dipahami agent tanpa penjelasan tambahan.

Standar ini dibuat untuk tim yang bekerja dengan Claude Code. Aturan kodenya berlaku untuk semua stack Laravel (Blade, Livewire, Inertia dengan React, Vue, atau Svelte), sedangkan gate penegaknya memakai hook Claude Code.

## Daftar isi

- [Masalah yang diselesaikan](#masalah-yang-diselesaikan)
- [Isi repositori](#isi-repositori)
- [Mulai cepat](#mulai-cepat)
- [Cara kerjanya](#cara-kerjanya)
- [Dukungan stack](#dukungan-stack)
- [Status verifikasi](#status-verifikasi)
- [Memelihara boosts-guidelines](#memelihara-boosts-guidelines)

## Masalah yang diselesaikan

Laravel Boost memberi agent guidelines, skill, tool MCP, dan project rules. Semuanya hanya instruksi teks. Boost tidak punya mekanisme yang memaksa agent mematuhinya, sehingga di proyek nyata agent sering melewati rules, tidak memuat skill, dan lupa memanggil `search-docs`.

Repositori ini menambahkan dua hal di atas Boost:

1. **Standar kode produksi** yang berlaku umum: 2 guideline dan 80 rules di 28 file, dalam bahasa Indonesia.
2. **Gate penegak** yang memaksa agent membaca rules, memuat skill, dan memanggil `search-docs` sebelum mengedit, serta memastikan format, static analysis, test, dan review lolos sebelum giliran selesai.

## Isi repositori

| Path | Isi |
| --- | --- |
| `ADOPTION.md` | Langkah adopsi ke proyek Laravel, ditulis untuk diikuti agent secara berurutan |
| `AGENTS.md` | Instruksi untuk agent yang bekerja di repositori ini |
| `CHANGELOG.md` | Riwayat versi standar dan versi paket yang dipakai saat pengujian |
| `scripts/install.php` | Installer yang menyalin template, mendeteksi stack, dan menggabungkan konfigurasi |
| `template/` | File yang disalin ke root proyek tujuan, dengan path yang sama |
| `template/.ai/guidelines/` | `production-standards.md` (standar umum, selalu dibaca agent) dan `project-memory.md` (instruksi `memory/PRD.md`) |
| `template/.ai/rules/` | 28 file rule per folder Laravel |
| `template/.claude/settings.json` | Pendaftaran hook gate |
| `template/.claude/hooks/` | Script gate: `boost-context.php`, `boost-gate.php`, `boost-quality.php`, dan `lib.php` |
| `template/.claude/agents/boost-reviewer.md` | Subagent reviewer yang wajib memberi `VERDICT: PASS` |
| `template/tests/Unit/ArchTest.php` | 10 arch test Pest untuk aturan struktural |
| `template/docs/decisions.md` | Catatan keputusan berisi 10 keputusan standar |
| `stubs/boost-gate.base.json` | Konfigurasi gate dasar. Peta skill diisi installer sesuai proyek |
| `stubs/env-boost.txt` | Blok variabel env Boost |
| `stubs/PRD.md` | Kerangka `memory/PRD.md` |
| `docs/laravel-boost.md` | Panduan lengkap Laravel Boost: pemakaian, kustomisasi, cara kerja, dan temuan pengujian |
| `docs/examples/boost-gate.inertia-react.json` | Contoh konfigurasi gate hasil installer untuk proyek Inertia React |

## Mulai cepat

Di proyek Laravel tujuan, setelah `composer install` dan `npm install`:

```bash
composer require laravel/boost --dev
```

```bash
php artisan boost:install
```

Pilih Claude Code, lalu aktifkan guidelines, skills, dan MCP. Setelah itu pasang standar ini. Sesuaikan `/path/ke/boosts-guidelines` dengan lokasi folder ini di mesinmu (hasil `git clone` repositori ini), dan `/path/ke/proyek` dengan root proyek tujuan:

```bash
php /path/ke/boosts-guidelines/scripts/install.php /path/ke/proyek --dry-run
```

```bash
php /path/ke/boosts-guidelines/scripts/install.php /path/ke/proyek
```

Lanjutkan dengan langkah verifikasi di [ADOPTION.md](ADOPTION.md), lalu buka sesi Claude Code baru di proyek itu. Hook dan subagent hanya dimuat saat sesi dimulai.

Installer aman dijalankan berulang kali. Ia tidak pernah menghapus file, tidak menimpa file yang sudah diubah kecuali dengan `--force`, dan tidak pernah menimpa `docs/decisions.md`.

## Cara kerjanya

Ada empat bagian yang saling melengkapi:

| Bagian | Dibaca kapan | Fungsi |
| --- | --- | --- |
| Guidelines (`.ai/guidelines`) | Selalu, lewat `AGENTS.md` atau `CLAUDE.md` yang dibuat Boost | Standar umum (batas default, kualitas desain, perbaikan bug, dependensi) dan instruksi memori proyek |
| Rules (`.ai/rules`) | Saat agent menyentuh folder yang cocok | Standar per folder: controller, model, migration, keamanan, API, frontend, test, dan lainnya |
| Gate (`.claude/`) | Otomatis oleh Claude Code | Memaksa rules dibaca, skill dimuat, `search-docs` dipanggil, lalu format, PHPStan, test, dan review lolos |
| Catatan (`docs/decisions.md`, `memory/PRD.md`) | Saat ada keputusan atau perubahan produk | Keputusan teknis dan konteks produk yang dibagi ke semua agent |

Gate terdiri dari tiga hook dan satu subagent:

| Komponen | Kapan berjalan | Yang dilakukan |
| --- | --- | --- |
| `boost-context.php` (SessionStart) | Sesi baru, dilanjutkan, `/clear`, dan setelah percakapan diringkas | Memasukkan aturan gate, peta skill, index rules, dan `memory/PRD.md` ke konteks |
| `boost-gate.php` (PreToolUse) | Sebelum edit file dan perintah Bash yang menulis file | Menolak perubahan sampai syaratnya terpenuhi, dengan pesan berisi langkah persis |
| `boost-quality.php` (Stop) | Saat agent hendak menyelesaikan giliran | Pint, PHPStan, seluruh test, pemeriksaan frontend, lalu review. Menahan paling banyak 3 kali per giliran |
| `boost-reviewer` (subagent) | Dipanggil agent karena diminta Stop gate | Membandingkan perubahan dengan rules dan checklist produksi, lalu memberi `VERDICT: PASS` atau `FAIL` |

Penjelasan lengkap tentang Boost sendiri (cara guidelines disusun, cara skill dipilih, cara tool MCP dijalankan) ada di [docs/laravel-boost.md](docs/laravel-boost.md).

## Dukungan stack

Rules berlaku untuk semua stack. Yang berbeda per stack hanya peta skill wajib di gate, dan installer membuatnya otomatis dari skill yang dipasang Boost di `.claude/skills`:

| Skill Boost | Wajib dimuat sebelum mengedit |
| --- | --- |
| `laravel-best-practices` | `app/**/*.php`, `routes/**/*.php`, `database/**/*.php`, `config/**/*.php`, `bootstrap/app.php` |
| `testing-best-practices` atau `pest-testing` | `tests/**` |
| `fortify-development` | `app/Actions/Fortify/**` |
| `inertia-react-development`, `inertia-vue-development`, `inertia-svelte-development` | `resources/js/**` |
| `livewire-development` | `app/Livewire/**`, `resources/views/livewire/**`, `resources/views/components/**` |
| `volt-development` | `resources/views/livewire/**`, `resources/views/pages/**` |
| `fluxui-development` | `resources/views/**` |
| `folio-routing` | `resources/views/pages/**` |
| `tailwindcss-development` | `resources/css/**`, `resources/views/**` |
| `pennant-development` | `app/Features/**` |
| `mcp-development` | `app/Mcp/**` |

Installer juga menyesuaikan pemeriksaan dengan alat yang ada di proyek. Static analysis dimatikan jika PHPStan tidak terpasang, arch test dilewati jika proyek tidak memakai Pest, dan pemeriksaan frontend memakai script `types:check`, `typecheck`, atau `type-check` dengan package manager yang terdeteksi.

## Status verifikasi

Diuji di macOS dengan Laravel 13.40 dan 12.72, Laravel Boost 2.10.4, Pest 5.3, Larastan 3.14, PHP 8.5, Claude Code 2.1, dan agent berbasis Sonnet 5.5.

Sudah terbukti, termasuk di sesi Claude Code nyata (headless) pada proyek Laravel 12 yang belum memakai Boost:

- **Adopsi oleh agent:** agent mengikuti `ADOPTION.md` dari awal, menangkap `node_modules` yang belum ada, berhenti meminta persetujuan dependensi, memasang Boost dan standar, mengisi `docs/decisions.md` dari kondisi kode nyata, dan tidak mengubah kode aplikasi. Sekitar 25 turn dan 2,7 menit.
- **Hook SessionStart dan gate edit:** edit ke file model ditolak dengan daftar rule, skill, dan `search-docs`, lalu diizinkan setelah dipenuhi. Pada permintaan fitur, agent menyiapkan semuanya sendiri sebelum menulis (16 rule, 2 skill, dan `search-docs` sebelum edit pertama) sehingga gate tidak perlu menolak.
- **Stop gate dan reviewer:** berjalan di alur nyata, termasuk batas penahanan dan penguraian vonis `PASS` dan `FAIL`.
- **Fitur nyata:** agent membangun CRUD kategori produk memakai paket yang sudah terpasang (spatie permission dan activitylog), tanpa dependensi baru, dengan 35 test lolos.
- **Perbaikan versi 1.1.0:** pada uji ulang validasi gate, reviewer hanya memblokir perubahan di baris yang berubah dan melaporkan pelanggaran lama sebagai catatan warisan. Agent tidak memperbaiki kode lama, `docs/decisions.md` tidak berubah, dan pengembalian perubahan uji lolos tanpa review baru. Dibanding versi 1.0.1, validasi itu turun dari 32 menjadi 21 turn.
- **Pembuatan fitur diulang dengan versi 1.1.0** (prompt dan kondisi awal sama): durasi sesi turun dari 11 menit 37 detik menjadi 7 menit 23 detik. Siklus review dan perbaikan turun dari 7 menit 40 detik (66%) menjadi 3 menit 29 detik (47%), dan putaran review dari 6 menjadi 4 (satu putaran terbuang karena reviewer dijalankan di latar belakang). Review hanya memblokir hal yang bisa ditunjuk ke rule: test rate limit yang hilang dan file test di atas 400 baris. Agent tidak mengubah `bootstrap/app.php`, menulis dua entri keputusan berstatus `diusulkan`, dan tidak menyentuh entri standar. Perkiraan pemakaian turun dari USD 2,8 menjadi USD 1,9 (setara tarif API, bukan tagihan).
- **Installer:** menghasilkan konfigurasi yang sama dengan yang disusun manual, aman dijalankan ulang, menggabungkan `.claude/settings.json` tanpa menghapus isinya, dan memperbaiki `boost.json` yang kehilangan `agents`.
- **Arch test** menangkap pelanggaran yang disengaja.

Belum terbukti, dan perlu diketahui:

- Reset hitungan gate setelah percakapan diringkas (`/compact`). Gate mengenali penanda `compact_boundary`. Jika penandanya berbeda, gate menjadi lebih longgar, bukan salah menolak.
- Hasil pembuatan fitur adalah satu kali jalan per versi, jadi sebagian selisihnya bisa berupa variasi antar-jalan. Jumlah turn agent bahkan naik (65 menjadi 86), tampaknya karena gaya kerjanya berbeda (lebih banyak edit kecil), jadi ukuran yang dipakai adalah durasi.
- Reviewer versi 1.1.0 menilai lebih sempit. Pada fitur yang sama, izin dibuat lewat seeder yang tidak dijalankan saat deploy production. Reviewer versi 1.0.1 mengangkatnya sebagai cacat nyata, sedangkan versi 1.1.0 tidak memblokirnya. Agent mengungkapkannya sendiri sebagai pertanyaan terbuka di laporan dan `memory/PRD.md`, tetapi celah seperti ini bisa lolos dari review.
- Proyek tanpa git hanya diuji dengan simulasi.
- Linux dan Windows. Hook memakai shell POSIX (`cd`, `&&`) dan `git`, jadi Linux seharusnya berjalan, tetapi Windows tanpa WSL belum didukung.
- Laravel 11. Boost mendukungnya, tetapi standar ini baru diuji di Laravel 12 dan 13.
- Setiap skenario nyata dijalankan satu kali dengan satu model, jadi perilaku bisa bervariasi.
- MySQL, request paralel, dan tampilan di browser.

Batasan yang diketahui ada di bagian "Batasan yang perlu kamu tahu" di [docs/laravel-boost.md](docs/laravel-boost.md#batasan-yang-perlu-kamu-tahu).

## Memelihara boosts-guidelines

- **Mengubah standar:** edit file di `template/`. Isi tetap bahasa Indonesia, nama file bahasa Inggris, dan berlaku umum untuk semua stack Laravel, tanpa menyebut file milik proyek tertentu. Catat perubahan di `CHANGELOG.md`.
- **Menguji perubahan:** jalankan installer ke salinan proyek Laravel di folder sementara, lalu pastikan arch test dan gate berfungsi. Cara pengujian yang sudah dipakai ada di `AGENTS.md`.
- **Boost versi baru:** cek ulang hal yang bergantung pada versi, yaitu daftar tool MCP, nama skill, dan format `.ai/rules/index.md` yang dibaca gate. Perbarui tabel dukungan stack jika ada skill baru.
- **Memperbarui proyek yang sudah adopsi:** jalankan installer lagi. File yang sudah diubah di proyek itu akan dilaporkan "Dibiarkan". Bandingkan dulu dengan versi di `template/`, gabungkan secara manual, baru pakai `--force` jika perlu.
