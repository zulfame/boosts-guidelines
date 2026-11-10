# Instruksi untuk agent di repositori boosts-guidelines

Repositori boosts-guidelines berisi standar dan perangkat untuk memasang Laravel Boost beserta standar kode produksi dan gate penegaknya ke proyek Laravel. Ini bukan aplikasi Laravel. Baca `README.md` untuk gambaran umum.

## Jika diminta memasang standar ke sebuah proyek

Ikuti `ADOPTION.md` dari langkah 1 sampai 10 secara berurutan, dan laporkan hasil setiap pemeriksaannya. Jangan mengubah kode aplikasi proyek tujuan untuk menyesuaikan dengan rules tanpa persetujuan user.

## Jika diminta mengubah standar

- File yang dipasang ke proyek ada di `template/`, dengan path yang sama seperti di proyek tujuan. Konfigurasi gate dasar ada di `stubs/boost-gate.base.json`, dan peta skill dibuat oleh `scripts/install.php`.
- Isi guideline, rule, dan dokumen ditulis dalam bahasa Indonesia. Nama file tetap bahasa Inggris. Nama class, method, dan perintah ditulis apa adanya.
- Rules harus berlaku untuk semua stack Laravel (Blade, Livewire, Inertia dengan React, Vue, atau Svelte). Jangan menyebut file milik proyek tertentu atau detail satu teknologi frontend. Detail stack ditangani skill Boost.
- Setiap file rule wajib diawali frontmatter `paths:` berisi glob. Gate dan Boost membaca index dari sana.
- Tulis dokumentasi untuk dibaca manusia: alur dari yang paling sering dibutuhkan, kalimat lugas, tanpa tanda pisah panjang.
- Catat setiap perubahan di `CHANGELOG.md`.

## Cara menguji perubahan

1. Periksa sintaks: `php -l` untuk setiap file PHP di `scripts/` dan `template/.claude/hooks/`.
2. Siapkan salinan proyek Laravel di folder sementara yang sudah menjalankan `boost:install`. Pastikan `vendor/laravel/boost/.ai` dan `node_modules` ikut tersedia, karena keduanya sumber skill Boost.
3. Jalankan `php scripts/install.php <salinan> --dry-run`, lalu tanpa `--dry-run`, lalu sekali lagi untuk memastikan aman dijalankan ulang.
4. Di salinan itu, jalankan `php artisan test --compact tests/Unit/ArchTest.php`.
5. Uji gate dengan input simulasi, misalnya:

   ```bash
   php -r 'echo json_encode(["tool_name"=>"Edit","tool_input"=>["file_path"=>getcwd()."/app/Models/User.php"],"transcript_path"=>"/tidak-ada","cwd"=>getcwd()]);' | CLAUDE_PROJECT_DIR=$(pwd) php .claude/hooks/boost-gate.php
   ```

   Hasil yang diharapkan: JSON dengan `permissionDecision` bernilai `deny` dan daftar rule yang harus dibaca.
6. Hapus salinan sementara setelah selesai.
