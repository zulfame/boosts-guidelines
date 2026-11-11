---
description: Review satu kali seluruh perubahan fitur dengan boost-reviewer
argument-hint: "[base-ref opsional, misalnya main atau abc1234]"
---

Jalankan review sekali untuk seluruh perubahan fitur ini. Review itu mahal, jadi jangan diulang tanpa diminta user.

1. Tentukan berkas yang direview dengan `php .claude/hooks/review-scope.php $ARGUMENTS`. Tanpa argumen, hasilnya adalah perubahan yang belum di-commit. Jika fitur ini sudah di-commit sebagian, user memberi base commit atau branch sebagai argumen. Jika hasilnya kosong, laporkan itu ke user dan berhenti.
2. Jalankan subagent `boost-reviewer` dengan tool Agent (subagent_type: boost-reviewer), satu kali, dengan daftar berkas itu dan base jika ada. Jangan menyertakan kesimpulan, klaim, atau daftar "trade-off yang sudah diterima" di prompt. Subagent berjalan di latar belakang, jadi tunggu laporannya masuk dan jangan jalankan ulang.
3. Baca laporannya. Perbaiki hanya temuan BLOKIR di perubahan fitur ini. Catatan warisan dan saran tidak diperbaiki, cukup dilaporkan ke user. Temuan KEPUTUSAN USER tidak kamu putuskan sendiri: catat sebagai `diusulkan` di `docs/decisions.md` jika belum ada, lalu laporkan.
4. Jika ada temuan BLOKIR yang kamu perbaiki, jalankan reviewer satu kali lagi hanya untuk berkas yang diperbaiki. Sebut temuan BLOKIR sebelumnya sebagai daftar yang harus diverifikasi, bukan sebagai klaim bahwa sudah beres. Tidak ada review ketiga: sisa temuan dilaporkan ke user.
5. Tutup dengan ringkasan untuk user: vonis akhir, apa yang diperbaiki, dan hal yang menunggu keputusannya.
