# Changelog

Semua perubahan penting pada proyek ini dicatat di file ini. Formatnya mengikuti [Keep a Changelog 1.1.0](https://keepachangelog.com/en/1.1.0/), dan penomoran versinya mengikuti [Semantic Versioning 2.0.0](https://semver.org/spec/v2.0.0.html).

Arti nomor versi di proyek ini:

- **Major:** proyek yang sudah adopsi harus bertindak, misalnya format `boost-gate.json` berubah atau rule dihapus.
- **Minor:** rule, fitur gate, atau opsi installer baru yang kompatibel.
- **Patch:** perbaikan bug tanpa perubahan perilaku yang disengaja.

## [Unreleased]

### Changed

- Rule `documentation.md` kini menyebut format changelog: mengikuti Keep a Changelog dan nomor versi mengikuti Semantic Versioning.
- Changelog ini disusun ulang mengikuti format tersebut. Isinya tidak berubah.

## [1.1.0] - 2026-11-11

Perbaikan dari uji adopsi dan pembuatan fitur oleh agent di proyek Laravel 12 yang belum memakai Boost (beberapa sesi Claude Code nyata, dinilai oleh penilai independen).

### Added

- Rule di `factories.md` (kini 80 rules): data yang dibutuhkan fitur agar berjalan di production (izin, role dasar, pengaturan awal) tidak hanya dibuat oleh seeder, karena seeder tidak dijalankan saat deploy. Temuan dari uji pembuatan fitur.
- Aturan di `production-standards.md`: agent tidak boleh menerima risiko atau menyimpang dari rule atas nama pemilik (status `diusulkan`), dan tidak mengubah kode lama di luar permintaan. Template `docs/decisions.md` mendukung status `diusulkan`.
- `ADOPTION.md`: jalur `boost:install` non-interaktif beserta jebakannya, prasyarat repositori git dengan satu commit, dan langkah 9 dibagi menjadi Tahap A (perubahan uji dibiarkan) dan Tahap B (dikembalikan di pesan berikutnya).
- Panduan skill kustom di `docs/laravel-boost.md`: agent memilih skill dari `description`, dan skill yang wajib dimuat didaftarkan di `boost-gate.json`.
- Lisensi MIT.

### Changed

- **Reviewer menilai perubahan, bukan seluruh file.** `FAIL` hanya untuk baris yang berubah dan file baru. Pelanggaran lama dilaporkan sebagai catatan warisan yang tidak memblokir. Sebelumnya, satu komentar di file lama memicu `FAIL` karena pelanggaran lama, dan agent terdorong memperbaiki kode lama serta menulis entri keputusan atas nama pemilik.
- Reviewer membaca ulang kode di setiap review, mengabaikan klaim di prompt, memakai `docs/decisions.md` sebagai satu-satunya sumber keputusan, menjalankan `wc -l` untuk batas 400 baris, dan tidak memblokir karena saran atau konsistensi dokumen pada review ulang.
- **Stop gate mendeteksi perubahan lewat git** bila tersedia: file yang isinya sama dengan commit sebelum giliran dimulai tidak dihitung, dan commit di tengah giliran tidak menyembunyikan pekerjaan. Tanpa git tetap memakai waktu modifikasi.
- Vonis diambil dari `VERDICT:` terakhir di laporan reviewer. Pesan penahanan melarang mengubah kode di luar permintaan dan menyertakan klaim di prompt reviewer.
- Instruksi di Stop gate dan konteks SessionStart menyebut agar reviewer dijalankan di latar depan.
- Skill Tailwind kini wajib juga untuk `resources/views/**`.
- Dokumentasi memakai penunjuk `/path/ke/boosts-guidelines` alih-alih path absolut, dan menyebut platform serta versi Laravel yang diuji.

### Fixed

- Installer mengisi `agents: ["claude_code"]` di `boost.json` bila kosong. `boost:install --no-interaction` tidak menyimpannya, sehingga `boost:update` dan `composer update` gagal.
- Installer mempertahankan entri skill kustom di `boost-gate.json` saat dijalankan ulang dengan `--force`. Sebelumnya entri itu hilang karena peta dibuat ulang hanya dari skill bawaan Boost.
- Agent kadang menjalankan reviewer di latar belakang, laporannya tidak terbaca, dan satu putaran review terbuang.

### Hasil uji

- **Pembuatan fitur** (prompt dan kondisi awal sama): durasi sesi turun dari 11 menit 37 detik menjadi 7 menit 23 detik, siklus review dan perbaikan dari 7 menit 40 detik menjadi 3 menit 29 detik, dan putaran review dari 6 menjadi 4. Agent tidak mengubah `bootstrap/app.php` dan tidak menulis keputusan berstatus `berlaku` atas nama pemilik. Satu kali jalan per versi. Reviewer yang lebih sempit tidak memblokir izin yang dibuat lewat seeder, tetapi agent mengungkapkannya sebagai pertanyaan terbuka.
- **Skill kustom** (satu skill di `.ai/skills`, tidak ada di peta gate): agent memuat skill itu sebagai langkah pertama dari prompt yang tidak menyebutnya, mengikuti semua konvensinya, dan sesi selesai dalam 4 menit 5 detik dengan 42 test lolos dan reviewer `PASS` setelah dua putaran `FAIL` yang diperbaiki. Gate menolak edit di path yang dipetakan ke skill kustom sampai skill dimuat (diuji dengan simulasi hook).
- **Validasi gate:** turun dari 32 menjadi 21 turn, reviewer memblokir hanya penanda uji di baris yang berubah, dua pelanggaran lama dilaporkan tanpa diperbaiki, `docs/decisions.md` tidak berubah, dan pengembalian perubahan uji lolos tanpa review baru.
- Hook SessionStart dan subagent `boost-reviewer` berjalan di sesi nyata. Setelah `/compact`, gate mengandalkan penanda `compact_boundary`. Jika penandanya berbeda, gate menjadi lebih longgar, bukan salah menolak.

### Memperbarui proyek yang sudah adopsi

Salin `template/.claude/hooks/*.php`, `template/.claude/agents/boost-reviewer.md`, dan `template/.ai/guidelines/production-standards.md` ke proyek itu, lalu jalankan `php artisan boost:update`. Jika proyek tidak punya perubahan lokal pada rules, `install.php --force` juga bisa dipakai. Peta skill Tailwind untuk view ada di `boost-gate.json`: tambahkan `resources/views/**` ke skill `tailwindcss-development` secara manual jika file itu sudah disesuaikan.

### Batasan yang diketahui

Tulis file lewat skrip Bash (`python3`, `php -r`) dan `artisan make:*` tidak terdeteksi gate edit, tetapi Stop gate dan reviewer tetap menangkap hasilnya. Pint di Stop gate bisa merapikan baris lama di file yang disentuh.

## [1.0.1] - 2026-11-11

### Fixed

- Stop gate hanya menghitung file sumber. Sebelumnya, database SQLite yang dipakai untuk session dan cache ikut terdeteksi sebagai perubahan dan memicu permintaan review. Daftar ekstensi diatur lewat `quality.source_extensions` di `boost-gate.json`.
- Deteksi tulis lewat Bash tidak lagi menganggap operator `=>` dan `->` di dalam perintah sebagai redirect.

Memperbarui proyek yang sudah adopsi: salin ulang `template/.claude/hooks/boost-gate.php` dan `template/.claude/hooks/boost-quality.php` ke `.claude/hooks/` di proyek itu. Jangan memakai `--force` hanya untuk ini, karena opsi itu juga menimpa rule yang sudah diubah di proyek. `boost-gate.json` lama tetap berfungsi karena `source_extensions` punya nilai default.

## [1.0.0] - 2026-11-09

Versi pertama. Diuji dengan Laravel 13.40, Laravel Boost 2.10.4, Laravel MCP 1.1, Pest 5.3, Larastan 3.14, Pint 1.32, PHP 8.5, dan Claude Code 2.1.

### Added

- 2 guideline: `production-standards.md` dan `project-memory.md`.
- 79 rules di 28 file, berlaku untuk semua stack Laravel, isi berbahasa Indonesia.
- Gate Claude Code: hook SessionStart, PreToolUse, dan Stop, beserta subagent `boost-reviewer`.
- 10 arch test Pest.
- Template `docs/decisions.md` dengan 10 keputusan standar (D-0001 sampai D-0010).
- Panduan lengkap Laravel Boost di `docs/laravel-boost.md`.
- Installer `scripts/install.php` dengan deteksi skill, PHPStan, Pest, Pint, dan package manager.

[Unreleased]: https://github.com/zulfame/boosts-guidelines/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/zulfame/boosts-guidelines/releases/tag/v1.1.0
[1.0.1]: https://github.com/zulfame/boosts-guidelines/commit/43cfd85
[1.0.0]: https://github.com/zulfame/boosts-guidelines/commit/78007ed
