# Panduan Laravel Boost

Panduan memakai Laravel Boost di proyek Laravel, ditulis untuk developer yang bekerja bersama AI agent seperti Claude Code atau Cursor. Isinya mulai dari cara pakai harian sampai cara kerja di dalamnya.

Semua yang tertulis di sini dicek ke source code `vendor/laravel/boost` dan dokumentasi resmi di <https://laravel.com/framework/docs/boost>. Kalau keduanya berbeda, panduan ini mengikuti source code dan menyebutkan bedanya. Perilaku yang disebut "terbukti" sudah dicoba langsung saat pengujian dengan Laravel 13.40, Laravel Boost 2.10.4, Pest 5.3, dan Claude Code 2.1. Versi Boost lain bisa berbeda di detail.

Cara membaca: kalau baru mengenal Boost, baca [Mulai cepat](#mulai-cepat) dan [Pemakaian sehari-hari](#pemakaian-sehari-hari). Bagian [Kustomisasi](#kustomisasi) dan [Bekerja dalam tim](#bekerja-dalam-tim) dibaca saat dibutuhkan. [Troubleshooting](#troubleshooting) untuk saat ada yang tidak jalan, dan [Di balik layar](#di-balik-layar) untuk yang ingin tahu cara kerjanya.

## Daftar isi

- [Mulai cepat](#mulai-cepat)
- [Pemakaian sehari-hari](#pemakaian-sehari-hari)
- [Rules dan memori proyek](#rules-dan-memori-proyek)
- [Kustomisasi](#kustomisasi)
- [Bekerja dalam tim](#bekerja-dalam-tim)
- [Menegakkan Boost](#menegakkan-boost)
- [Troubleshooting](#troubleshooting)
- [Di balik layar](#di-balik-layar)
- [Lampiran](#lampiran)

## Mulai cepat

### Boost itu apa

AI agent pintar menulis kode, tetapi ia tidak tahu versi paket yang kamu pakai, isi database-mu, error yang baru saja terjadi, atau aturan yang disepakati timmu. Akibatnya agent sering menulis kode untuk versi Laravel yang salah atau mengabaikan pola yang sudah ada di proyek.

Boost menutup celah itu dengan empat hal:

| Bagian | Isinya | Kapan dibaca agent | Lokasi |
| --- | --- | --- | --- |
| Guidelines | Aturan umum Laravel dan paket yang terpasang, disesuaikan dengan versinya | Selalu, di awal setiap sesi | `AGENTS.md` atau `CLAUDE.md` |
| Skills | Panduan rinci per topik (Inertia, Livewire, Tailwind, testing, dan lainnya) | Hanya saat topiknya relevan | `.claude/skills/` |
| MCP server | Tool yang bisa dipanggil agent untuk melihat kondisi aplikasi: skema DB, log, error terakhir, dokumentasi sesuai versi | Saat agent butuh data | `.mcp.json` |
| Project rules | Keputusan dan standar tim, dikelompokkan per folder | Sebelum agent mengedit file di folder terkait | `.ai/rules/` |

Guidelines dan skills menjelaskan cara menulis Laravel secara umum. Project rules menjelaskan cara menulis aplikasi tertentu.

### File yang dibuat Boost

`php artisan boost:install` membuat atau mengubah file berikut (lokasi untuk Claude Code):

| File | Fungsinya |
| --- | --- |
| `boost.json` | Mencatat pilihan saat instalasi: agent, fitur, dan daftar skill |
| `.mcp.json` | Memberi tahu Claude Code cara menyalakan server MCP (`php artisan boost:mcp`) |
| `AGENTS.md` atau `CLAUDE.md` | Guidelines. Bagian milik Boost ada di antara tag `<laravel-boost-guidelines>` |
| `.claude/skills/` | Skill yang dipilih sesuai paket yang terpasang |
| `composer.json` | Script `post-update-cmd` yang menjalankan `boost:update` setiap kali `composer update` |

Boost menulis guidelines ke `CLAUDE.md` hanya jika file itu sudah ada. Jika belum, ia memakai `AGENTS.md`.

### Coba sekarang

Buka Claude Code di folder proyek, lalu coba prompt berikut. Kolom kanan menunjukkan apa yang akan dipakai agent.

| Prompt | Yang dipakai agent |
| --- | --- |
| "Tabel apa saja yang ada di database dan kolom tabel users?" | Tool `database-schema` |
| "Berapa jumlah user yang sudah verifikasi email?" | Tool `database-query` (hanya baca) |
| "Apa error terakhir di aplikasi ini?" | Tool `last-error` dan `read-log-entries` |
| "Bagaimana cara membuat deferred props di Inertia?" | Skill Inertia yang terpasang, lalu tool `search-docs` |
| "Buatkan halaman daftar produk dengan CRUD" | Guidelines, skill terkait, dan rules di `.ai/rules` |

### Kapan Boost aktif

Boost hanya menyala di mesin developer. Ia aktif jika `APP_ENV=local` atau `APP_DEBUG=true`, dan mati otomatis saat:

- test berjalan (`php artisan test`),
- environment bukan local dan debug mati (kondisi production yang benar),
- `BOOST_ENABLED=false`.

Saat mati, Boost tidak mendaftarkan apa pun: tidak ada command, route, maupun middleware. Semua saklar Boost bisa diatur lewat `.env` (lihat [Variabel env Boost](#variabel-env-boost)).

## Pemakaian sehari-hari

### Alur kerja dan contoh prompt

Kamu tidak perlu menyebut nama tool. Agent memilih sendiri berdasarkan instruksi di guidelines. Tetapi menyebutnya bisa membantu kalau agent mulai menebak.

| Tugas | Contoh prompt | Yang sebaiknya terjadi |
| --- | --- | --- |
| Membuat fitur baru | "Buat fitur kategori produk: migration, model, CRUD, halaman, dan test" | Agent membaca rules untuk controller, model, migration, frontend, dan test, cek skema dengan `database-schema`, lalu menulis kode dan test |
| Mengubah skema | "Tambahkan kolom `phone` ke users" | Agent membuat migration baru, bukan mengedit migration lama |
| Error 500 | "Halaman dashboard error 500, cari penyebabnya" | `last-error`, lalu `read-log-entries` |
| Error di browser | "Tombol simpan di halaman profil tidak bereaksi" | `browser-logs` untuk membaca error JavaScript |
| Cek data | "Tampilkan 5 user terbaru" | `database-query` |
| Pertanyaan API paket | "Bagaimana cara memakai rate limiter bernama?" | Skill terkait dulu, lalu `search-docs` |
| Membuat link atau URL | "Berikan URL halaman profil" | `get-absolute-url` |
| Merapikan kode | Panggil prompt `/mcp__laravel-boost__laravel-code-simplifier` | Agent merapikan kode yang baru diubah tanpa mengubah perilakunya |
| Mencatat keputusan | "Ingat aturan ini: semua tanggal ditampilkan dalam zona Asia/Jakarta" | Tool `record-rule` menulis ke `.ai/rules` |

### Tool MCP yang tersedia

Tool bawaan Boost 2.10.4:

| Tool | Gunanya | Perlu diketahui |
| --- | --- | --- |
| `search-docs` | Mencari dokumentasi sesuai versi paket yang terpasang | Query dan daftar paket dikirim ke `boost.laravel.com`. Jangan tulis data rahasia di query |
| `database-schema` | Melihat tabel, kolom, index, dan foreign key | Paling efektif dengan mode ringkas dulu, lalu filter per tabel |
| `database-query` | Menjalankan query SQL yang hanya membaca | Menolak INSERT, UPDATE, DELETE, multi-statement, dan `SELECT ... INTO` |
| `database-connections` | Melihat koneksi database yang dikonfigurasi | Termasuk koneksi default |
| `last-error` | Membaca error backend terakhir | Membaca file log. Lihat Troubleshooting jika log tidak ditemukan |
| `read-log-entries` | Membaca beberapa entri log terakhir | Paham log multi-baris dan log JSON |
| `browser-logs` | Membaca error dan console dari browser | Lihat [Browser logs](#browser-logs) |
| `get-absolute-url` | Membuat URL lengkap dari path atau nama route | Memakai scheme, domain, dan port dari konfigurasi aplikasi |
| `record-rule` | Menyimpan aturan tim ke `.ai/rules` | Satu-satunya tool yang menulis. Dipakai hanya saat kamu memintanya |

Ada satu tool lagi, `tinker`, yang mati secara default karena bisa menjalankan kode PHP apa pun, termasuk menghapus data. Nyalakan hanya jika benar-benar perlu, lewat `'tinker_tool_enabled' => true` di `config/boost.php`.

### Prompt MCP

Selain tool, Boost menyediakan prompt siap pakai. Di Claude Code, ketik `/` lalu pilih yang berawalan `mcp__laravel-boost__`. Prompt hanya muncul jika syaratnya terpenuhi:

| Prompt | Gunanya | Muncul jika |
| --- | --- | --- |
| `laravel-code-simplifier` | Merapikan kode yang baru diubah tanpa mengubah perilaku | Selalu |
| `upgrade-ai-sdk-v1` | Langkah upgrade Laravel AI SDK 0.11 ke 1.0 | Selalu |
| `upgrade-inertia-v3` | Langkah upgrade Inertia 2 ke 3 | Ada paket Inertia (Laravel, React, Vue, atau Svelte) |
| `upgrade-laravel-v13` | Langkah upgrade Laravel 12 ke 13 | Proyek masih Laravel 12 |
| `upgrade-livewire-v4` | Langkah upgrade Livewire 3 ke 4 | Ada paket Livewire |

### Kebiasaan yang disarankan

Minta agent memakai `search-docs` sebelum menulis kode yang bergantung pada API paket, tetapi perhatikan cakupannya. Menurut dokumentasi resmi, Documentation API mencakup Laravel 10 sampai 13, Inertia 1 dan 2, Pest 3 dan 4, serta Livewire, Filament, Flux, Nova, dan Tailwind. Versi yang lebih baru dari daftar itu (misalnya Inertia 3 atau Pest 5) belum tentu tercakup. Untuk versi seperti itu, skill dan guideline yang terpasang lebih bisa dipercaya. Kalau hasil `search-docs` terlihat seperti API lama, curigai.

Sebelum membuat migration atau model, minta agent melihat skema dengan `database-schema`. Untuk melihat data, `database-query` lebih aman daripada tinker karena tidak bisa menulis.

Saat debugging, urutan yang efektif adalah `last-error`, lalu `read-log-entries` untuk backend, dan `browser-logs` untuk frontend.

Simpan keputusan tim sebagai rule, bukan sebagai pesan di chat. Rule tersimpan di repo dan berlaku untuk semua developer dan semua agent. Instruksi untuk satu perbaikan ("hapus typo ini") bukan rule.

Saat kode proyek sudah banyak, jalankan skill `infer-conventions` dengan prompt "Use the infer-conventions skill". Skill ini menyapu kode, menemukan pola yang benar-benar dipakai, menunjukkannya kepadamu, lalu merekam yang kamu setujui sebagai rules. Skill ini tidak pernah berjalan sendiri tanpa diminta.

## Rules dan memori proyek

### Cara kerja project rules

Setiap file di `.ai/rules/` diawali frontmatter `paths:` berisi glob. Boost membuat `.ai/rules/index.md` yang memetakan glob ke file rule, dan guidelines Boost menyuruh agent membaca index itu, lalu membaca semua rule yang glob-nya cocok, sebelum mengedit file. Satu file bisa cocok dengan beberapa rule sekaligus.

Standar rules yang dipakai boosts-guidelines ada di `template/.ai/rules/`, dengan ringkasannya di `README.md`. Rules itu berlaku untuk semua stack Laravel dan tidak menyebut file milik proyek tertentu. Detail stack (Inertia, Livewire, Wayfinder) diserahkan ke skill Boost.

Hal yang perlu diketahui saat menulis rule:

- Boost tidak memeriksa bahasa isi rule. Yang wajib hanya frontmatter `paths:`. Judul dan kalimat pengantar di `index.md` selalu berbahasa Inggris karena file itu dibuat ulang oleh Boost.
- Cara termudah menambah aturan adalah meminta agent, misalnya "Ingat aturan ini: ...". Agent memanggil `record-rule`, yang menulis ke file area yang sesuai (berdasarkan glob) dan memperbarui index sekaligus.
- Mengubah kalimat di file yang sudah ada boleh dilakukan langsung. Tetapi kalau kamu membuat file baru atau mengubah `paths:` secara manual, jalankan `php artisan boost:index-rules`. Tanpa itu, agent tidak akan menemukan rule tersebut.

### Memori proyek (memory/PRD.md)

Rules menjelaskan cara menulis kode, tetapi tidak menjelaskan apa yang sedang dibangun. Untuk itu boosts-guidelines memakai `memory/PRD.md` sebagai memori bersama: tujuan aplikasi, pengguna, fitur beserta statusnya, keputusan produk, dan pekerjaan yang sedang berjalan. Dengan begitu agent mana pun langsung tahu konteksnya, walaupun kamu baru berpindah agent atau memulai sesi baru.

Instruksinya ditulis sebagai guideline di `.ai/guidelines/project-memory.md`, sehingga Boost menyalinnya ke bagian paling atas `AGENTS.md`.

| Jenis informasi | Tempatnya |
| --- | --- |
| Cara menulis kode (standar, konvensi) | `.ai/rules` |
| Apa yang dibangun, kenapa, dan sampai mana | `memory/PRD.md` |
| Keputusan teknis, risiko yang diterima, utang teknis | `docs/decisions.md` |
| Detail implementasi dan riwayat perubahan | Kode dan git |

## Kustomisasi

Semua kustomisasi disimpan di folder `.ai/`. Boost membaca folder itu setiap kali `boost:install` atau `boost:update` dijalankan, lalu menyalin hasilnya ke `AGENTS.md` dan `.claude/skills/`.

Jangan mengedit isi tag `<laravel-boost-guidelines>` di `AGENTS.md` secara langsung, karena isinya ditimpa setiap update. Teks di luar tag itu aman.

### Menambah guideline sendiri

Buat file Markdown di `.ai/guidelines/`, misalnya `.ai/guidelines/domain.md`:

```markdown
# Domain aplikasi

- Aplikasi ini menjual langganan. Invoice dibuat oleh `App\Actions\Billing\CreateInvoice`.
```

Lalu jalankan:

```bash
php artisan boost:update
```

Guideline milikmu ditaruh paling atas di blok Boost, dengan judul `=== .ai/domain rules ===`.

Guideline bisa juga ditulis sebagai Blade (`.blade.php`) supaya isinya menyesuaikan proyek. Di dalamnya tersedia variabel `$assist`:

```blade
# Perintah proyek

- Jalankan migration dengan `{{ $assist->artisanCommand('migrate') }}`.
- Pasang dependensi JS dengan `{{ $assist->nodePackageManagerCommand('install') }}`.
@if ($assist->hasPackage(\Laravel\Boost\Support\PackageRegistry::INERTIA_REACT))
- Frontend memakai Inertia React.
@endif
```

Di proyek biasa, template di atas menghasilkan `php artisan migrate` dan `npm install`. Di proyek yang memakai Sail, hasilnya otomatis menjadi `vendor/bin/sail artisan migrate`.

| Method `$assist` | Hasil |
| --- | --- |
| `artisanCommand('migrate')` | `php artisan migrate`, atau versi Sail |
| `composerCommand('install')` | `composer install`, atau versi Sail |
| `nodePackageManagerCommand('install')` | `npm install`, menyesuaikan package manager proyek |
| `binCommand('pint')` | `vendor/bin/pint` |
| `hasPackage($nama, $versi = null)` | `true` jika paket Composer atau npm terpasang. Gunakan konstanta di `Laravel\Boost\Support\PackageRegistry` |
| `hasComposerScript('test')` | `true` jika script Composer itu ada |
| `hasSkillsEnabled()`, `hasMcpEnabled()`, `hasCloudEnabled()` | Status fitur Boost |

Ada satu jebakan yang terbukti saat pengujian. Kalau template Blade-mu error (misalnya salah nama konstanta), `boost:update` tetap berkata berhasil, tetapi guideline itu tidak ditulis ke `AGENTS.md`. Setelah update, selalu cek bahwa judul `=== .ai/<nama> rules ===` muncul.

### Mengganti guideline bawaan

Buat file di `.ai/guidelines/` dengan path yang sama dengan nama guideline bawaan. Contohnya, untuk mengganti guideline Pint, buat `.ai/guidelines/pint/core.md`. Setelah `boost:update`, bagian `=== pint/core rules ===` di `AGENTS.md` berisi versimu.

Nama-nama guideline bisa dilihat dari judul `=== ... rules ===` di `AGENTS.md`.

### Membuang guideline atau skill

Publish file config dulu:

```bash
php artisan vendor:publish --tag=boost-config
```

Lalu isi daftar pengecualian di `config/boost.php`:

```php
'guidelines' => [
    'exclude' => ['laravel/style'],
],

'skills' => [
    'exclude' => ['deploying-to-cloud'],
],
```

Kamu juga bisa menambah key `purpose` berisi penjelasan singkat tentang aplikasi. Kalimat itu muncul di bagian awal guidelines sehingga agent paham domainnya:

```php
'purpose' => 'Aplikasi manajemen langganan untuk UMKM.',
```

### Membuat skill sendiri

Skill cocok untuk panduan panjang yang hanya relevan sesekali, misalnya alur membuat modul laporan. Buat `.ai/skills/<nama>/SKILL.md`:

```markdown
---
name: membuat-invoice
description: "Use when creating or changing invoices, billing periods or payment status."
---

# Membuat invoice

Selalu gunakan `App\Actions\Billing\CreateInvoice` ...
```

Bagian `description` adalah yang paling penting, karena agent memutuskan memuat skill hanya dari kalimat itu. Tulis dengan jelas kapan skill dipakai.

Setelah `boost:update`, skill disalin ke `.claude/skills/` dan dicatat di `boost.json`. Kalau folder skill di `.ai/skills` dihapus, update berikutnya juga menghapus salinannya.

Untuk mengganti skill bawaan, buat skill dengan nama yang sama, misalnya `.ai/skills/livewire-development/SKILL.md`. Skill dari `.ai/skills` selalu menang.

### Membuat MCP tool sendiri

Tool sendiri berguna untuk memberi agent akses ke data domain aplikasimu, misalnya ringkasan status pesanan. Contoh berikut sudah terbukti berjalan.

1. Buat class tool:

    ```bash
    php artisan make:mcp-tool UserStatsTool --no-interaction
    ```

2. Isi `app/Mcp/Tools/UserStatsTool.php`:

    ```php
    <?php

    namespace App\Mcp\Tools;

    use App\Models\User;
    use Illuminate\Contracts\JsonSchema\JsonSchema;
    use Illuminate\JsonSchema\Types\Type;
    use Laravel\Mcp\Request;
    use Laravel\Mcp\Response;
    use Laravel\Mcp\Server\Attributes\Description;
    use Laravel\Mcp\Server\Tool;
    use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

    #[Description('Return user counts: total and verified email. Optionally limited to users created in the last N days.')]
    #[IsReadOnly]
    class UserStatsTool extends Tool
    {
        public function handle(Request $request): Response
        {
            $validated = $request->validate([
                'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            ]);

            $query = User::query()->when(
                $validated['days'] ?? null,
                fn ($query, int $days) => $query->where('created_at', '>=', now()->subDays($days)),
            );

            return Response::json([
                'total' => (clone $query)->count(),
                'verified' => (clone $query)->whereNotNull('email_verified_at')->count(),
            ]);
        }

        /**
         * @return array<string, Type>
         */
        public function schema(JsonSchema $schema): array
        {
            return [
                'days' => $schema->integer()
                    ->description('Only count users created within the last N days.'),
            ];
        }
    }
    ```

3. Daftarkan di `config/boost.php` (publish dulu jika belum ada):

    ```php
    'mcp' => [
        'tools' => [
            'include' => [
                App\Mcp\Tools\UserStatsTool::class,
            ],
        ],
    ],
    ```

4. Uji tanpa agent. Argumennya JSON yang di-encode base64:

    ```bash
    php artisan boost:execute-tool 'App\Mcp\Tools\UserStatsTool' $(echo -n '{"days":30}' | base64)
    ```

5. Mulai ulang sesi agent. Daftar tool hanya dibaca saat server MCP menyala, jadi tool baru belum terlihat di sesi yang sedang berjalan.

Hal yang perlu diketahui:

- Nama tool diambil dari nama class dalam format kebab-case. Class `UserStatsTool` menjadi `user-stats-tool`.
- Deskripsi di `#[Description]` dibaca agent untuk memutuskan kapan tool dipakai. Tulis spesifik.
- `#[IsReadOnly]` memberi tahu agent bahwa tool ini aman. Tool yang menulis data sebaiknya tidak dibuat kecuali benar-benar perlu.
- Jika validasi gagal, pesan error dikembalikan ke agent sehingga ia bisa memperbaiki argumennya.
- Tool dijalankan di proses PHP terpisah setiap kali dipanggil, jadi perubahan kode langsung berlaku tanpa restart. Restart hanya perlu saat menambah atau menghapus tool.

### Scoped guidelines

Sebagian guideline bawaan hanya relevan untuk folder tertentu, misalnya aturan Pest untuk `tests/**`. Secara default semuanya tetap ditulis ke `AGENTS.md`. Dengan pengaturan berikut di `.env`, bagian-bagian itu dipindah ke `.ai/rules/boost/` dan hanya dibaca saat agent menyentuh folder terkait:

```ini
BOOST_RULES_SCOPED_GUIDELINES=true
```

Saat pengujian, `AGENTS.md` menyusut sekitar 14% dan beberapa file baru muncul di `.ai/rules/boost/` (misalnya untuk route, model, dan test). Folder `.ai/rules/boost/` dikelola Boost sepenuhnya, jadi jangan diedit manual. Mematikan pengaturan ini lalu menjalankan `boost:update` akan menghapus folder itu lagi.

### Variabel env Boost

Boost membaca 10 variabel env. Tuliskan semuanya di `.env.example` dengan nilai default-nya, supaya terlihat apa saja yang bisa diatur tanpa membuka source Boost. Installer boosts-guidelines menambahkan blok ini otomatis:

```ini
# Laravel Boost: hanya aktif jika APP_ENV=local atau APP_DEBUG=true.
# Di production set BOOST_ENABLED=false.
BOOST_ENABLED=true
BOOST_RULES_ENABLED=true
BOOST_RULES_SCOPED_GUIDELINES=false
BOOST_BROWSER_LOGS_WATCHER=true
BOOST_BROWSER_LOG_LEVELS=error,warning,info,debug

# Isi hanya jika deteksi otomatis memakai path yang salah (misalnya beberapa versi PHP).
# BOOST_PHP_EXECUTABLE_PATH=
# BOOST_COMPOSER_EXECUTABLE_PATH=
# BOOST_NPM_EXECUTABLE_PATH=
# BOOST_VENDOR_BIN_EXECUTABLE_PATH=
# BOOST_CURRENT_DIRECTORY_EXECUTABLE_PATH=
```

| Variabel | Default | Jika diubah |
| --- | --- | --- |
| `BOOST_ENABLED` | `true` | `false` mematikan seluruh Boost: command, server MCP, dan browser logs |
| `BOOST_RULES_ENABLED` | `true` | `false` menghilangkan tool `record-rule` dan Boost berhenti mengelola `.ai/rules` |
| `BOOST_RULES_SCOPED_GUIDELINES` | `false` | `true` memindahkan guideline per folder ke `.ai/rules/boost/` (lihat [Scoped guidelines](#scoped-guidelines)) |
| `BOOST_BROWSER_LOGS_WATCHER` | `true` | `false` mematikan penangkapan log browser |
| `BOOST_BROWSER_LOG_LEVELS` | `error,warning,info,debug` | Batasi, misalnya `error`, jika log browser terlalu ramai |
| `BOOST_*_EXECUTABLE_PATH` | deteksi otomatis | Path PHP, Composer, npm, `vendor/bin`, atau folder kerja yang dipakai Boost |

Variabel path sengaja dibiarkan sebagai komentar karena tidak punya nilai default. Setelah mengubah `BOOST_PHP_EXECUTABLE_PATH`, jalankan `php artisan boost:install --mcp` supaya `.mcp.json` ikut memakai path baru.

Ada dua opsi yang tidak punya variabel env, yaitu `tinker_tool_enabled` dan daftar tool MCP tambahan. Keduanya diatur di `config/boost.php`.

### Referensi konfigurasi

Semua opsi di bawah dibaca dari `config/boost.php`. Sebagian tidak ada di file config bawaan dan perlu ditambahkan sendiri.

| Key | Default | Fungsi |
| --- | --- | --- |
| `enabled` (`BOOST_ENABLED`) | `true` | Saklar utama |
| `purpose` | kosong | Penjelasan aplikasi untuk guidelines |
| `enforce_tests` | otomatis | Memaksa guideline "Test Enforcement" aktif atau mati. Otomatis aktif jika ada minimal 6 test |
| `guidelines.exclude` | `[]` | Guideline yang dibuang |
| `skills.exclude` | `[]` | Skill yang dibuang. Skill di `.ai/skills` tidak terpengaruh |
| `rules.enabled` (`BOOST_RULES_ENABLED`) | `true` | Fitur `.ai/rules` dan tool `record-rule` |
| `rules.scoped_guidelines` (`BOOST_RULES_SCOPED_GUIDELINES`) | `false` | Memindahkan guideline per folder ke `.ai/rules/boost/` |
| `mcp.tools.include` | `[]` | Menambah tool MCP sendiri |
| `mcp.tools.exclude` | `[]` | Menyembunyikan tool bawaan |
| `mcp.tool_timeout` | `180` | Batas waktu tool dalam detik |
| `tinker_tool_enabled` | `false` | Menyalakan tool `tinker` |
| `browser_logs_watcher` (`BOOST_BROWSER_LOGS_WATCHER`) | `true` | Menangkap log browser |
| `browser_log_levels` (`BOOST_BROWSER_LOG_LEVELS`) | `error,warning,info,debug` | Level console yang ditangkap |
| `executable_paths.php` (`BOOST_PHP_EXECUTABLE_PATH`) | otomatis | Path PHP. Juga ada `composer`, `npm`, `vendor_bin`, dan `sail` |
| `agents.<agent>.guidelines_path`, `mcp_config_path`, `skills_path` | per agent | Mengubah lokasi file untuk agent tertentu |
| `hosted.api_url` | `https://boost.laravel.com` | Alamat layanan `search-docs` |
| `github.token` | kosong | Token GitHub untuk `boost:add-skill` agar tidak kena rate limit |

## Bekerja dalam tim

### Apa yang di-commit

Dokumentasi resmi menyebut `.mcp.json`, `AGENTS.md` (atau `CLAUDE.md`), dan `boost.json` boleh dimasukkan ke `.gitignore`, karena semuanya bisa dibuat ulang. Pilih satu cara dan sepakati di tim:

| Pilihan | Kelebihan | Kekurangan |
| --- | --- | --- |
| Di-commit | Developer baru dan CI langsung punya konteks yang sama | Diff besar setiap kali `boost:update` |
| Di-ignore | Repo bersih | Setiap developer wajib menjalankan `php artisan boost:install` setelah clone |

Apa pun pilihannya, folder `.ai/`, `memory/PRD.md`, dan `docs/decisions.md` wajib di-commit. Di sanalah sumber aslinya. File Boost lainnya hanya hasil generate.

### Onboarding developer baru

1. Clone repo, lalu jalankan langkah setup proyek (minimal `composer install` dan `npm install`).
2. Pastikan `.env` berisi `APP_ENV=local`.
3. Jika file Boost di-ignore, jalankan `php artisan boost:install`.
4. Buka agent di folder proyek, lalu tanyakan "Tabel apa saja yang ada di database?" untuk memastikan MCP tersambung.

Kalau Claude Code tidak memuat MCP secara otomatis, daftarkan manual:

```bash
claude mcp add -s local -t stdio laravel-boost php artisan boost:mcp
```

### CI dan deploy

Boost tidak mengganggu test karena mati otomatis saat `php artisan test`.

Untuk deploy, gunakan `composer install --no-dev`. Boost adalah dependensi development sehingga tidak ikut terpasang, dan `composer install` tidak menjalankan `post-update-cmd`.

Jangan menjalankan `composer update` di server yang bukan local. Di sana command `boost:*` tidak terdaftar, sehingga script `post-update-cmd` berhenti dengan error "There are no commands defined in the boost namespace". Ini terbukti saat pengujian.

### Agent lain di tim yang sama

Boost mendukung 13 agent. Sebagian besar membaca `AGENTS.md`, jadi satu file guidelines melayani hampir semuanya. Yang berbeda adalah lokasi konfigurasi MCP dan skill.

| Agent | Guidelines | Konfigurasi MCP | Skills |
| --- | --- | --- | --- |
| Claude Code | `CLAUDE.md` jika ada, kalau tidak `AGENTS.md` | `.mcp.json` | `.claude/skills` |
| Cursor | `AGENTS.md` | `.cursor/mcp.json` | `.cursor/skills` |
| Codex | `AGENTS.md` | `.codex/config.toml` | `.agents/skills` |
| GitHub Copilot | `AGENTS.md` | `.vscode/mcp.json` | `.github/skills` |
| Junie | `AGENTS.md` | `.junie/mcp/mcp.json` | `.junie/skills` |
| Kiro | `AGENTS.md` | `.kiro/settings/mcp.json` | `.kiro/skills` |
| Zed | `AGENTS.md` | `.zed/settings.json` | `.agents/skills` |
| Amp | `AGENTS.md` | `.amp/settings.json` | `.agents/skills` |
| OpenCode | `AGENTS.md` | `opencode.json`, atau `opencode.jsonc` jika sudah ada | `.agents/skills` |
| Antigravity | `AGENTS.md` | `.agents/mcp_config.json` | `.agents/skills` |
| Factory | `AGENTS.md` | `.factory/mcp.json` | `.factory/skills` |
| Grok Build | `AGENTS.md` | `.grok/config.toml` | `.grok/skills` |
| Pi | `AGENTS.md` | `.pi/mcp.json` | `.pi/skills` |

Untuk menambah agent, jalankan ulang `php artisan boost:install` dan centang agent baru. Gate penegak di boosts-guidelines hanya berlaku untuk Claude Code.

Agent yang belum didukung bisa ditambahkan sendiri dengan membuat class turunan `Laravel\Boost\Install\Agents\Agent` (contohnya `ClaudeCode.php` di source Boost), lalu mendaftarkannya di `AppServiceProvider::boot()`:

```php
use Laravel\Boost\Boost;

Boost::registerAgent('customagent', CustomAgent::class);
```

### Sail, Herd, dan Nightwatch

Boost mendeteksi tiga integrasi saat instalasi.

Sail terdeteksi jika `vendor/bin/sail` dan file `compose.yml` atau `docker-compose.yml` ada. Jika dipilih, server MCP dijalankan lewat `vendor/bin/sail artisan boost:mcp`, dan perintah di guidelines ikut memakai Sail.

Herd terdeteksi jika aplikasinya terpasang dan `APP_URL` berakhiran `.test`. Boost lalu menambah guideline Herd: situs selalu tersedia di `http://<nama-folder>.test`, jadi agent tidak perlu menjalankan `php artisan serve`.

Nightwatch muncul sebagai pilihan jika paket `laravel/nightwatch` terpasang. Jika dipilih, Boost menambahkan server MCP Nightwatch (`https://nightwatch.laravel.com/mcp`) supaya agent bisa membaca data monitoring production.

### Memperbarui dan melepas Boost

Untuk memperbarui, jalankan `composer update laravel/boost`. Script `post-update-cmd` otomatis menjalankan `boost:update`, sehingga guidelines dan skill ikut terbarui.

Script itu berjalan tanpa interaksi, jadi skill untuk paket yang baru dipasang tidak ditawarkan. Setelah menambah paket besar (misalnya Livewire atau Pennant), jalankan `php artisan boost:update` sendiri di terminal agar Boost menanyakannya.

Untuk melepas Boost:

1. Hapus baris `@php artisan boost:update --ansi` dari `post-update-cmd` di `composer.json`. Jika tidak, `composer remove` akan gagal karena script itu memanggil command yang sudah tidak ada.
2. Jalankan `composer remove laravel/boost`.
3. Hapus `boost.json`, entri `laravel-boost` di `.mcp.json`, `config/boost.php` jika ada, tag `<laravel-boost-guidelines>` beserta isinya di `AGENTS.md`, dan skill hasil Boost di `.claude/skills/`.
4. Putuskan nasib `.ai/rules`. Isinya tetap berguna, tetapi tanpa Boost tidak ada lagi instruksi yang menyuruh agent membacanya. Tambahkan instruksi sendiri di `AGENTS.md` jika ingin tetap dipakai.

## Menegakkan Boost

Boost sendiri tidak punya mekanisme penegak. Guidelines, skill, dan rules hanyalah teks yang berharap dibaca. Dalam praktik, agent sering melewatinya: instruksi membaca `.ai/rules/index.md` tenggelam di tengah `AGENTS.md`, skill hanya dimuat jika agent merasa perlu, dan `search-docs` dilewati jika agent yakin dengan ingatannya.

boosts-guidelines menambahkan tiga lapis penegak untuk Claude Code. Cara memasangnya ada di `ADOPTION.md`.

| Lapis | Bentuknya di proyek | Yang dijamin |
| --- | --- | --- |
| Gate hook | `.claude/settings.json` dan `.claude/hooks/` | Agent tidak bisa mengedit file sebelum membaca rules, memuat skill, dan memanggil `search-docs`. Agent tidak bisa menyelesaikan giliran sebelum format, static analysis, test, dan review lolos |
| Arch test | `tests/Unit/ArchTest.php` | Aturan struktural dicek mesin, siapa pun penulisnya |
| Reviewer | `.claude/agents/boost-reviewer.md` | Perubahan dibandingkan dengan rules yang berlaku, dan hasilnya wajib `VERDICT: PASS` |

Gate hanya memastikan rules dibaca. Kepatuhan isinya dijaga oleh arch test dan reviewer.

### Lapis 1: gate di Claude Code

| Hook | Kapan berjalan | Yang dilakukan |
| --- | --- | --- |
| `SessionStart` (`boost-context.php`) | Sesi baru, dilanjutkan, `/clear`, dan setelah percakapan diringkas | Memasukkan aturan gate, peta skill wajib, index rules, dan isi `memory/PRD.md` ke konteks agent |
| `PreToolUse` (`boost-gate.php`) | Sebelum `Edit`, `Write`, `MultiEdit`, `NotebookEdit`, dan perintah Bash yang menulis file | Menolak perubahan sampai semua rule yang cocok dibaca, skill yang dipetakan dimuat, dan `search-docs` dipanggil (untuk kode aplikasi) |
| `Stop` (`boost-quality.php`) | Saat agent hendak menyelesaikan giliran | Merapikan file PHP yang berubah dengan Pint, menjalankan PHPStan untuk file itu, menjalankan seluruh test, menjalankan pemeriksaan frontend jika ada, lalu mewajibkan review dengan `VERDICT: PASS` |

Contoh pesan penolakan, saat README diedit tanpa membaca rule dokumentasi:

```text
Boost gate menolak perubahan pada README.md. Sebelum mengubah file ini:
- Baca file rule ini dengan tool Read: .ai/rules/documentation.md.
Setelah itu, ikuti isinya dan ulangi perubahan. Gate ini diatur di .claude/hooks/boost-gate.json.
```

Cara gate mengetahui apa yang sudah dilakukan agent: ia membaca file transcript sesi Claude Code dan mencari pemanggilan `Read`, `Skill`, `search-docs`, dan `Agent`. Pemanggilan yang gagal atau ditolak tidak dihitung. Setelah percakapan diringkas, hitungan dimulai dari nol, karena isi rules yang dibaca sebelumnya sudah tidak ada di konteks agent.

Stop gate memeriksa file yang berubah sejak prompt terakhir: file yang diedit dengan tool edit, ditambah file di folder yang dipantau yang waktu modifikasinya lebih baru dari prompt. Dengan begitu, file yang ditulis lewat Bash atau `php artisan make:*` tetap ikut diperiksa. Hanya file sumber yang dihitung (ekstensi di `quality.source_extensions`, misalnya `php`, `js`, `ts`, `vue`, `svelte`, `css`, dan `json`), sehingga data runtime seperti database SQLite yang dipakai untuk session dan cache tidak memicu review. Jika proyek memakai git, file yang isinya sama dengan commit terakhir sebelum giliran dimulai juga tidak dihitung, sehingga perubahan yang sudah dikembalikan tidak memicu review. Perbandingannya dengan commit itu, bukan dengan HEAD, jadi commit di tengah giliran tidak menyembunyikan pekerjaan. Agar tidak berputar tanpa akhir, Stop gate menahan paling banyak 3 kali per giliran. Setelah itu giliran dilepas dengan peringatan yang terlihat oleh user.

### Konfigurasi gate

Semua perilaku gate diatur di `.claude/hooks/boost-gate.json`, yang dibuat installer sesuai stack proyek:

| Key | Isi |
| --- | --- |
| `exempt` | Path yang tidak dijaga gate, misalnya `.claude/**`, `.ai/**`, dan `vendor/**` |
| `skills` | Peta glob ke skill wajib, dibuat dari skill yang terpasang di `.claude/skills` |
| `search_docs` | Apakah `search-docs` wajib, dan untuk path mana |
| `quality` | Perintah format, static analysis, test, dan pemeriksaan frontend, folder yang dipantau, ekstensi file sumber (`source_extensions`), serta batas `max_blocks` |
| `review` | Apakah review wajib, nama subagent-nya, dan path yang memicu review |

Rules yang wajib dibaca tidak diatur di sini. Gate mengambilnya langsung dari `.ai/rules/index.md`, jadi rule baru otomatis ikut dijaga.

Untuk mematikan sementara di satu mesin, tambahkan `"disableAllHooks": true` di `.claude/settings.local.json`. File itu tidak di-commit, jadi gate tetap aktif untuk anggota tim lain. Untuk melonggarkan sebagian, set `"enabled": false` di `quality` atau `review`, atau `"required": false` di `search_docs`.

### Lapis 2: arch test

`tests/Unit/ArchTest.php` memakai fitur arch test bawaan Pest. Isinya preset `php`, `security`, dan `laravel` dari Pest, ditambah tujuh aturan:

- kode aplikasi tidak memanggil `env()`,
- model meng-extend Eloquent `Model`,
- controller tidak memakai facade `DB`,
- Form Request meng-extend `FormRequest` dan berakhiran `Request`,
- Action tidak memakai `request()`, `session()`, `redirect()`, atau `response()`,
- job mengimplementasikan `ShouldQueue`,
- isi `app/Enums` berupa enum.

Test ini ikut berjalan di `php artisan test`, di Stop gate, dan di CI proyek. Saat pengujian, controller sementara yang memakai `DB`, `env()`, `dd()`, dan `md5()` membuat 4 dari 10 test gagal, lalu kembali hijau setelah file itu dihapus.

### Lapis 3: reviewer

`boost-reviewer` adalah subagent yang hanya bisa membaca. Ia menentukan perubahan nyata lewat `git diff`, membaca index rules, rule yang cocok dengan file dalam lingkup, guideline standar produksi, dan checklist produksi, lalu membaca ulang kodenya dari disk. Ia tidak mempercayai klaim di prompt, dan hanya `docs/decisions.md` yang dianggap sumber keputusan.

Ia menilai perubahan, bukan seluruh file. Temuannya dibagi empat tingkat:

| Tingkat | Isi | Memblokir |
| --- | --- | --- |
| BLOKIR | Pelanggaran rule di baris yang berubah, cacat nyata, test yang hilang, file baru di atas 400 baris, atau penyimpangan tanpa keputusan yang sah | Ya |
| CATATAN WARISAN | Pelanggaran di baris yang tidak berubah | Tidak |
| KEPUTUSAN USER | Penyimpangan beralasan teknis yang menunggu persetujuan pemilik | Tidak |
| SARAN | Gaya, redaksi, atau konsistensi dokumen | Tidak |

Laporan ditutup dengan `VERDICT: PASS` (tidak ada BLOKIR) atau `VERDICT: FAIL`. Stop gate membaca kemunculan `VERDICT` yang terakhir. Jika agent mengubah file lagi setelah review, review harus diulang. Pada review ulang, reviewer memverifikasi temuan sebelumnya dan tidak memperluas jenis temuan. Tanpa git, seluruh isi file yang disebut dianggap masuk lingkup.

Rancangan ini lahir dari satu temuan nyata. Reviewer versi awal menilai seluruh file yang disentuh dan memberi `FAIL` karena pelanggaran lama. Karena Stop gate mewajibkan `PASS`, agent akhirnya memperbaiki kode lama dan menulis entri keputusan atas nama pemilik, jauh di luar yang diminta.

### Hasil pengujian gate

| Skenario | Hasil |
| --- | --- |
| Edit model tanpa persiapan apa pun | Ditolak, dengan daftar rule yang cocok, skill `laravel-best-practices`, dan `search-docs` |
| Edit model setelah semua syarat dipenuhi | Diizinkan |
| Syarat dipenuhi, lalu percakapan diringkas | Ditolak lagi (uji simulasi) |
| Read yang gagal (file tidak ada) | Tidak dihitung sebagai sudah dibaca |
| Bash `echo x > app/Foo.php` dan `sed -i ... routes/web.php` | Ditolak |
| Bash yang menulis ke `/tmp` atau hanya membaca rule | Diizinkan |
| Edit di `.claude/` | Diizinkan (dikecualikan) |
| Skill yang dimuat dengan prefix plugin | Dikenali |
| Stop tanpa perubahan file | Diizinkan |
| Stop dengan error tipe di file PHP | Ditolak oleh PHPStan |
| Stop dengan kode benar tetapi belum direview | Ditolak, agent diminta menjalankan `boost-reviewer` |
| Stop dengan reviewer `FAIL` | Ditolak |
| Stop dengan reviewer `PASS` | Diizinkan |
| Stop gagal 4 kali berturut-turut | 3 kali ditolak, lalu dilepas dengan peringatan |
| Sesi nyata: edit README tanpa membaca rule dokumentasi | Ditolak |
| Sesi nyata: Stop gate di akhir giliran | Berjalan dan lolos |

Satu temuan dari sesi nyata: saat Read dan Edit dikirim dalam satu pesan, Claude Code belum menulis Read ke transcript ketika hook Edit berjalan, sehingga edit ditolak walaupun rule sudah dibaca. Gate sekarang mencoba ulang hingga sekitar 2 detik sebelum menolak. Uji dengan Read yang tercatat 0,7 detik terlambat berhasil diizinkan.

### Batasan yang perlu kamu tahu

- **Dibaca belum tentu dipatuhi.** Gate hanya menjamin rules dibaca. Arch test dan reviewer yang menjaga isinya.
- **Deteksi tulis lewat Bash adalah heuristik.** Gate mengenali redirect (`>` dan `>>`, tetapi bukan operator `=>` atau `->`), `tee`, `sed -i`, `perl -i`, `cp`, dan `mv`. Cara lain, misalnya skrip `python3`, `php -r "file_put_contents(...)"`, dan `php artisan make:*`, lolos dari gate edit. Stop gate dan reviewer tetap memeriksa hasilnya. Pada uji pembuatan fitur, ini tidak merugikan karena agent sudah membaca rules di awal sesi.
- **Pint di Stop gate bisa merapikan baris lama.** Gate menjalankan Pint pada file PHP yang disentuh. Jika file itu belum rapi menurut Pint, baris lama ikut berubah (misalnya urutan trait) dan tersisa di `git diff` walaupun perubahan agent sudah dikembalikan. Matikan lewat `quality.format` jika tidak diinginkan.
- **Biaya per giliran.** Stop gate menjalankan seluruh test setiap kali ada perubahan PHP, dan reviewer berjalan setiap ada perubahan kode aplikasi. Untuk suite yang lambat, ubah perintah `tests` (misalnya dengan `--parallel`). Untuk proyek kecil, review bisa dimatikan.
- **Penolakan butuh sekitar 2 detik** karena percobaan ulang tadi. Edit yang diizinkan tidak terpengaruh.
- **Penanda ringkasan percakapan belum terverifikasi di sesi nyata.** Gate mengenali penanda `compact_boundary` dan `isCompactSummary`. Jika penandanya ternyata berbeda, gate tetap menghitung bacaan sebelum ringkasan. Akibatnya gate jadi lebih longgar, bukan salah menolak.
- **Gate adalah pagar pengaman, bukan batas keamanan.** Siapa pun yang memegang mesin bisa mematikannya dengan `disableAllHooks`.

## Troubleshooting

| Gejala | Penyebab umum | Solusi |
| --- | --- | --- |
| Agent tidak punya tool `laravel-boost` | Boost tidak aktif karena environment bukan local dan debug mati | Cek dengan `php artisan list boost`. Jika kosong, set `APP_ENV=local` |
| Agent tidak punya tool `laravel-boost`, padahal Boost aktif | `.mcp.json` tidak terbaca atau sesi agent dimulai sebelum instalasi | Mulai ulang agent, atau daftarkan manual dengan `claude mcp add` |
| Skill dari paket npm hilang setelah `boost:update` | `node_modules` belum terpasang saat `boost:install` atau `boost:update` dijalankan | Jalankan `npm install`, lalu `php artisan boost:update` |
| Edit ditolak "Boost gate menolak perubahan" | Rule, skill, atau `search-docs` yang disyaratkan belum dipenuhi di konteks saat ini | Penuhi daftar di pesan penolakan. Pesan itu ditujukan ke agent, jadi biasanya agent memperbaikinya sendiri |
| Giliran tidak mau selesai karena Boost gate | Format, PHPStan, test, atau review belum lolos | Baca alasan yang ditampilkan. Setelah 3 kali, gate melepas giliran dengan peringatan |
| Gate tidak berjalan sama sekali | PHP tidak ada di PATH, atau `disableAllHooks` aktif | Cek `php -v` dan `.claude/settings.local.json`. Hook untuk tool edit memakai `onFailure: block`, jadi jika script gate error, edit ikut ditolak |
| Tool memakai versi PHP yang salah | Ada beberapa PHP di mesin (Herd, Homebrew) | Set `BOOST_PHP_EXECUTABLE_PATH` di `.env`, lalu `php artisan boost:install --mcp` |
| Tool berhenti karena timeout | Query atau proses terlalu lama | Naikkan `mcp.tool_timeout`, atau perkecil query |
| Tool buatan sendiri tidak muncul | Belum masuk `mcp.tools.include`, atau sesi agent belum dimulai ulang | Cek config, lalu mulai ulang agent |
| `browser-logs` kosong | Halaman bukan HTML dari grup middleware `web`, atau watcher mati | Buka console browser dan cari pesan "Browser logger active". Untuk halaman di luar grup `web`, tambahkan `@boostJs` |
| `last-error` berkata log tidak ditemukan | Channel log bukan file (misalnya `stderr`) | Gunakan `stack` atau `single` di local |
| Guideline buatan sendiri tidak muncul di `AGENTS.md` | Template Blade error. `boost:update` melewatinya tanpa pesan | Periksa sintaks dan nama konstanta, lalu update lagi |
| Editan di `AGENTS.md` hilang setelah update | Editan berada di dalam tag `<laravel-boost-guidelines>` | Pindahkan ke `.ai/guidelines/` atau ke luar tag |
| Agent tidak mematuhi rule baru | File rule dibuat manual sehingga index belum diperbarui, atau glob tidak cocok dengan path | Jalankan `php artisan boost:index-rules` dan periksa `paths:` |
| `composer update` di server gagal di `boost:update` | Command Boost tidak terdaftar di luar local | Di server gunakan `composer install --no-dev` |
| `boost:update` berkata "Please set up Boost with [php artisan boost:install] first" setelah `boost:install` non-interaktif | `boost:install --no-interaction` tidak menyimpan `agents` di `boost.json`, dan `boost:update` menolak berjalan tanpa itu | Tambahkan `"agents": ["claude_code"]` ke `boost.json`, atau jalankan installer boosts-guidelines yang mengisinya otomatis |
| Reviewer memblokir karena kode lama | Reviewer versi lama menilai seluruh file | Perbarui `boost-reviewer.md` dan `boost-quality.php` ke versi 1.1.0 atau lebih baru |
| Setelah perubahan uji dikembalikan, masih ada satu baris berbeda di file itu | Pint di Stop gate merapikan baris lama | Kembalikan dengan `git checkout -- <file>`, atau pilih file yang lolos `vendor/bin/pint --test` |
| Boost aktif di staging | `APP_DEBUG=true` di staging | Set `BOOST_ENABLED=false` |
| `search-docs` memberi contoh API lama | Versi paket belum dicakup Documentation API | Andalkan skill yang terpasang. Lihat [Kebiasaan yang disarankan](#kebiasaan-yang-disarankan) |

## Di balik layar

Bagian ini untuk yang ingin memahami cara kerja Boost atau sedang men-debug perilaku yang aneh.

### Arsitektur

```
Saat boost:install atau boost:update:

  vendor/laravel/boost/.ai/*              ┐
  <paket>/resources/boost/*  (vendor/     │      GuidelineComposer ──► AGENTS.md
                              node_modules)├──►  SkillComposer     ──► .claude/skills/
  .ai/guidelines, .ai/skills (milik tim)  ┘      McpWriter         ──► .mcp.json
                                                 Config            ──► boost.json

Saat agent bekerja:

  Claude Code ──stdio──► php artisan boost:mcp ──► server MCP "laravel-boost"
                                                        │ setiap pemanggilan tool
                                                        ▼
                                  php artisan boost:execute-tool <Class> <argumen>
                                  (proses PHP baru untuk setiap pemanggilan)
```

Command yang tersedia:

| Command | Fungsi |
| --- | --- |
| `boost:install` | Wizard instalasi. Bisa dibatasi dengan `--guidelines`, `--skills`, atau `--mcp` |
| `boost:update` | Menyusun ulang guidelines dan skill. `--no-discover` untuk tidak menawarkan paket baru |
| `boost:mcp` | Menyalakan server MCP. Dipanggil oleh agent, bukan oleh manusia |
| `boost:add-skill` | Mengambil skill dari repo GitHub, dengan audit keamanan otomatis |
| `boost:list-skills` | Menampilkan skill yang tersedia |
| `boost:index-rules` | Membuat ulang `.ai/rules/index.md` |
| `boost:execute-tool` | Command internal untuk menjalankan satu tool. Tersembunyi dari daftar |

### Bagaimana guidelines disusun

Boost mengumpulkan guideline dari lima sumber, lalu menggabungkannya menjadi satu blok di `AGENTS.md`:

1. Guideline inti yang selalu ada: `foundation`, `boost/core`, `php/core`, dan `deployments/core`.
2. Guideline kondisional seperti `herd` atau `sail`, yang hanya muncul jika terdeteksi.
3. Guideline per paket yang terpasang: `<paket>/core` ditambah folder versi mayornya, misalnya `php/8.5`. Versi paket dibaca dari `composer.lock` dan `package-lock.json` oleh Laravel Roster.
4. Guideline dari paket pihak ketiga yang menyediakan `resources/boost/guidelines/`. Hanya dependensi langsung yang dibaca, supaya paket yang ikut terpasang secara tidak langsung tidak bisa menyisipkan instruksi.
5. Guideline milik tim di `.ai/guidelines/`, yang ditaruh paling atas.

Ada dua aturan prioritas. Jika Pest terpasang, guideline PHPUnit dibuang. Jika Flux Pro terpasang, guideline Flux Free dibuang.

Untuk paket resmi Laravel dan Inertia, guideline `core` diambil dari paket itu sendiri jika tersedia. Contohnya, guideline Inertia berasal dari `vendor/inertiajs/inertia-laravel`, Wayfinder dari `vendor/laravel/wayfinder`, dan Inertia React v3 dari `node_modules/@inertiajs/react`.

### Bagaimana skill dipilih

Skill tidak dipasang semua. Untuk setiap paket yang terpasang, Boost mencari skill di dua tempat: folder bawaan Boost (`vendor/laravel/boost/.ai/<paket>/`, bisa per versi mayor) dan folder `resources/boost/skills/` milik paket resmi itu sendiri, termasuk paket npm. Jika keduanya punya skill dengan nama sama, versi dari paket yang dipakai.

| Skill | Dipakai untuk | Muncul jika | Sumber |
| --- | --- | --- | --- |
| `laravel-best-practices` | Menulis dan me-review kode backend | `laravel/framework` | Bawaan Boost |
| `testing-best-practices` | Merancang dan me-review test | `laravel/framework` | Bawaan Boost |
| `infer-conventions` | Menemukan konvensi proyek dan merekamnya sebagai rules | Selalu | Bawaan Boost |
| `deploying-to-cloud` | Deploy ke Laravel Cloud | Integrasi Cloud dipilih saat instalasi | Bawaan Boost |
| `tailwindcss-development` | Styling dengan Tailwind | Tailwind 3 atau 4 | Bawaan Boost, per versi mayor |
| `livewire-development` | Komponen Livewire | Livewire 2, 3, atau 4 di-require langsung | Bawaan Boost, per versi mayor |
| `fluxui-development` | Komponen Flux UI | Flux Free atau Pro | Bawaan Boost |
| `volt-development` | Komponen Volt | Livewire Volt | Bawaan Boost |
| `folio-routing` | Routing berbasis file | Laravel Folio | Bawaan Boost |
| `pennant-development` | Feature flag | Laravel Pennant | Bawaan Boost |
| `mcp-development` | Membuat server MCP | `laravel/mcp` di-require langsung | Paket `laravel/mcp` |
| `fortify-development` | Login, registrasi, 2FA, dan passkey | Laravel Fortify | Paket `laravel/fortify` |
| `wayfinder-development` | Memanggil route dari frontend lewat fungsi TypeScript | Laravel Wayfinder | Paket `laravel/wayfinder` |
| `inertia-react-development`, `inertia-vue-development`, `inertia-svelte-development` | Halaman, form, dan navigasi Inertia | Paket Inertia untuk framework itu | Bawaan Boost untuk versi 1 dan 2, paket npm Inertia untuk versi 3 |

Beberapa hal yang tidak terlihat dari luar:

- Skill Inertia versi 3 dikirim oleh paket npm Inertia sendiri, bukan oleh Boost. Isinya ikut berubah saat paket npm itu diperbarui dan `boost:update` dijalankan.
- Karena sebagian skill berasal dari `node_modules`, menjalankan `boost:install` atau `boost:update` sebelum `npm install` membuat skill itu tidak terpasang, bahkan menghapusnya jika sudah ada. Ini terbukti saat pengujian.
- `laravel/mcp` selalu ada di `vendor/` karena dibutuhkan Boost, tetapi skill `mcp-development` hanya dipasang jika proyek me-require `laravel/mcp` langsung. Aturan yang sama berlaku untuk Livewire.
- Dokumentasi resmi masih mencantumkan skill `pest-testing`, tetapi di Boost 2.10.4 skill itu tidak ada. Panduan Pest ada di guideline `pest/core` dan skill `testing-best-practices`.

### Bagaimana tool MCP dijalankan

Server MCP adalah proses yang terus hidup selama sesi agent. Kalau tool dijalankan di dalam proses itu, perubahan kode, config, atau `.env` tidak akan terlihat sampai server dimulai ulang. Karena itu Boost menjalankan setiap pemanggilan tool di proses PHP baru (`boost:execute-tool`), dengan variabel `.env` dikosongkan agar proses baru membaca nilai terbaru. Error fatal di satu tool juga tidak mematikan server.

Hanya tool yang terdaftar yang boleh dijalankan: tool bawaan Boost, ditambah `mcp.tools.include`, dikurangi `mcp.tools.exclude`.

Validasi `database-query` dilakukan dengan memeriksa teks query, bukan dengan hak akses database. Untuk perlindungan tambahan, buat koneksi database dengan user yang hanya bisa membaca, lalu minta agent memakai koneksi itu.

### Browser logs

1. Middleware `InjectBoost` menyisipkan sebuah script ke setiap respons HTML di grup `web`. Respons JSON, redirect, dan file dilewati.
2. Script itu menangkap `console.log`, `console.error`, dan sejenisnya, error JavaScript yang tidak tertangani, dan promise yang gagal.
3. Log dikirim ke `POST /_boost/browser-logs` dan ditulis ke `storage/logs/browser.log`.
4. Agent membacanya lewat tool `browser-logs`.

Route itu tidak memakai proteksi CSRF dan hanya ada saat Boost aktif. Ini alasan lain untuk tidak menyalakan `APP_DEBUG` di server yang bisa diakses publik. Jika log terlalu ramai, batasi dengan `BOOST_BROWSER_LOG_LEVELS=error`.

### Project rules

Setiap file di `.ai/rules/` diawali frontmatter `paths:` berisi daftar glob. `record-rule` menentukan file tujuan dari glob yang diberikan, membuat file baru jika belum ada, menambahkan aturan sebagai subjudul, lalu membuat ulang `index.md`. Instruksi di `AGENTS.md` menyuruh agent membaca index itu sebelum merencanakan atau mengedit file.

### Peta source code

| Lokasi di `vendor/laravel/boost` | Isinya |
| --- | --- |
| `src/BoostServiceProvider.php` | Menentukan kapan Boost aktif, mendaftarkan MCP, command, route, dan middleware |
| `src/Mcp/Boost.php` | Definisi server MCP beserta tool, resource, dan prompt |
| `src/Mcp/ToolExecutor.php` | Menjalankan tool di proses terpisah |
| `src/Mcp/ToolRegistry.php` | Daftar tool yang boleh dijalankan |
| `src/Mcp/Tools/` | Kode setiap tool |
| `src/Mcp/Prompts/` | Kode setiap prompt beserta syarat kemunculannya |
| `src/Console/` | Command `boost:*` |
| `src/Install/GuidelineComposer.php` | Penyusunan guidelines |
| `src/Install/SkillComposer.php` | Pemilihan skill |
| `src/Install/GuidelineAssist.php` | Variabel `$assist` untuk template Blade |
| `src/Install/Agents/` | Pengaturan per agent |
| `src/Rules/RuleRepository.php` | Penulisan `.ai/rules` dan index |
| `src/Middleware/InjectBoost.php` | Penyisipan script browser log |
| `.ai/` | Template guideline dan skill bawaan |

## Lampiran

### Temuan pengujian

Perilaku berikut terbukti saat pengujian dan tidak tertulis di dokumentasi resmi:

| Yang diuji | Hasil |
| --- | --- |
| Daftar tool dari server MCP | 9 tool bawaan. `tinker` tidak muncul kecuali diaktifkan |
| Daftar prompt dari server MCP | Hanya prompt yang syaratnya terpenuhi, misalnya `upgrade-laravel-v13` tidak muncul di Laravel 13 |
| Tool MCP buatan sendiri | Muncul sebagai nama kebab-case dari class, bisa dipanggil lewat MCP, dan menolak argumen yang tidak valid |
| Guideline Markdown di `.ai/guidelines` | Muncul paling atas di blok Boost |
| Guideline Blade dengan `$assist` | Perintah menyesuaikan proyek, misalnya `php artisan migrate` dan `npm install` |
| Guideline Blade yang error | Dilewati tanpa pesan error oleh `boost:update` |
| Override guideline bawaan (contoh `pint/core`) | Bagian itu di `AGENTS.md` diganti versi buatan sendiri |
| Skill di `.ai/skills` | Disalin ke `.claude/skills` dan dicatat di `boost.json`. Terhapus otomatis saat sumbernya dihapus |
| Scoped guidelines | `AGENTS.md` menyusut sekitar 14%, dan file baru muncul di `.ai/rules/boost/` |
| `boost:update` tanpa `node_modules` | Skill dari paket npm hilang |
| Command Boost di luar local | Tidak terdaftar ("There are no commands defined in the boost namespace") |
| `boost:install --guidelines --skills --mcp --no-interaction` | Hanya Claude Code yang dikonfigurasi di mesin uji, tetapi `agents` tidak disimpan di `boost.json`, sehingga `boost:update` gagal |
| `boost:update` sebelum `npm install` | Skill dari paket npm tidak terpasang, bahkan hilang jika sudah ada |

### Hasil uji adopsi dan pembuatan fitur oleh agent

Uji dilakukan di proyek Laravel 12.72 yang belum memakai Boost, dengan paket spatie permission dan activitylog, Yajra DataTables, Blade dan Tailwind, tanpa Pest, PHPStan, dan halaman login. Setiap fase adalah sesi Claude Code baru (headless, Sonnet 5.5) yang dijalankan dari folder proyek, dengan prompt yang ditulis seperti user biasa. Angka di bawah adalah satu kali jalan per fase.

| Fase | Versi standar | Turn | Durasi | Hasil |
| --- | --- | --- | --- | --- |
| Adopsi mengikuti `ADOPTION.md` | 1.0.1 | 25 | sekitar 2,6 menit | Selesai sampai langkah 8. Berhenti meminta persetujuan dependensi, lalu melanjutkan setelah dibalas |
| Validasi gate | 1.0.1 | 32 | sekitar 3 menit | Gate bekerja, tetapi reviewer menilai seluruh file lama. Agent akhirnya mengubah kode lama dan menulis entri keputusan baru |
| Pembuatan fitur CRUD | 1.0.1 | 65 | sekitar 11,6 menit | 35 test lolos, tanpa dependensi baru. Reviewer dipanggil 6 kali, sekitar 66% durasi sesi |
| Validasi gate diulang | 1.1.0 | 21 | sekitar 1,7 menit | Reviewer hanya memblokir penanda uji di baris yang berubah, dua pelanggaran lama dilaporkan tanpa diperbaiki, `docs/decisions.md` tidak berubah |
| Pengembalian perubahan uji | 1.1.0 | 3 | sekitar 14 detik | Tidak ada perubahan tersisa di kode aplikasi dan Stop gate tidak menahan giliran |

Pelajaran yang paling berguna:

- Membaca rules di awal sesi sepadan. Pada pembuatan fitur, agent membaca 16 rule, memuat 2 skill, dan memanggil `search-docs` sebelum menulis satu baris pun, sehingga gate tidak perlu menolak.
- Review berulang tidak sepadan jika reviewer menilai seluruh file. Itu sebabnya reviewer versi 1.1.0 hanya menilai perubahan.
- Agent mengikuti aturan persetujuan dependensi dan keputusan dengan baik. Ia berhenti, bertanya, dan melaporkan hal yang belum beres secara jujur, termasuk bahwa halaman login belum ada.
- Penanda sementara seperti "uji gate" sebagai isi perubahan uji ditolak reviewer, karena komentar harus menjelaskan alasan. Gunakan perubahan komentar yang bermakna.

### Paket pendamping: laravel/pao

`laravel/pao` bukan bagian dari Boost, tetapi tujuannya sejalan dan sering terpasang bersama di starter kit baru. Saat perintah seperti `php artisan`, Pest, PHPUnit, atau PHPStan dijalankan oleh AI agent, pao mengganti tampilan output menjadi lebih ringkas supaya agent tidak menghabiskan konteks untuk teks yang tidak perlu. pao mendeteksi agent dari variabel environment yang dipasang agent itu, misalnya `CLAUDECODE` untuk Claude Code. Saat perintah yang sama dijalankan manusia di terminal, tampilannya tetap seperti biasa.

Untuk mematikannya, set `PAO_DISABLE=true`. Untuk mencoba tampilannya di terminal sendiri, set `PAO_FORCE=true`.

### Istilah

| Istilah | Arti |
| --- | --- |
| Agent | Asisten AI yang bisa membaca dan menulis kode di proyek, misalnya Claude Code atau Cursor |
| MCP | Model Context Protocol, standar yang memungkinkan agent memanggil tool dari aplikasi lain |
| Guideline | Instruksi yang selalu dibaca agent di awal sesi |
| Skill | Panduan per topik yang hanya dimuat saat relevan |
| Rule | Aturan tim yang berlaku untuk folder tertentu, disimpan di `.ai/rules` |
| Glob | Pola path file, misalnya `app/Models/**` berarti semua file di dalam `app/Models` |
| Paket first-party | Paket resmi dari Laravel atau Inertia |
| Dependensi langsung | Paket yang tercantum di `composer.json` atau `package.json` proyek, bukan paket yang ikut terpasang karena dibutuhkan paket lain |
