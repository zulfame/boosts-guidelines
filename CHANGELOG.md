# Changelog

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
