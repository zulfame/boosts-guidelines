# Changelog

## 1.0.1 (2026-11-11)

Perbaikan dua false positive di gate yang ditemukan saat dipakai di sesi nyata:

- Stop gate hanya menghitung file sumber. Sebelumnya, database SQLite yang dipakai untuk session dan cache ikut terdeteksi sebagai perubahan dan memicu permintaan review. Daftar ekstensi diatur lewat `quality.source_extensions` di `boost-gate.json`.
- Deteksi tulis lewat Bash tidak lagi menganggap operator `=>` dan `->` di dalam perintah sebagai redirect.

Proyek yang sudah adopsi: salin ulang `template/.claude/hooks/boost-gate.php` dan `template/.claude/hooks/boost-quality.php` ke `.claude/hooks/` di proyek itu. Jangan memakai `--force` hanya untuk ini, karena opsi itu juga menimpa rule yang sudah diubah di proyek. `boost-gate.json` lama tetap berfungsi karena `source_extensions` punya nilai default.

## 1.0.0 (2026-11-09)

Versi pertama.

Isi:

- 2 guideline: `production-standards.md` dan `project-memory.md`.
- 79 rules di 28 file, berlaku untuk semua stack Laravel, isi berbahasa Indonesia.
- Gate Claude Code: hook SessionStart, PreToolUse, dan Stop, beserta subagent `boost-reviewer`.
- 10 arch test Pest.
- Template `docs/decisions.md` dengan 10 keputusan standar (D-0001 sampai D-0010).
- Panduan lengkap Laravel Boost di `docs/laravel-boost.md`.
- Installer `scripts/install.php` dengan deteksi skill, PHPStan, Pest, Pint, dan package manager.

Diuji dengan Laravel 13.40, Laravel Boost 2.10.4, Laravel MCP 1.1, Pest 5.3, Larastan 3.14, Pint 1.32, PHP 8.5, dan Claude Code 2.1.

Belum terbukti di sesi nyata: hook SessionStart di sesi baru, subagent `boost-reviewer` dalam alur nyata, dan reset hitungan gate setelah percakapan diringkas. Ketiganya divalidasi di langkah 9 `ADOPTION.md`.
