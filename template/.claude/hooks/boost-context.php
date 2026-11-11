<?php

/**
 * SessionStart hook: puts the Boost gate rules, the rules index, the skill map,
 * and memory/PRD.md into the agent's context at startup, resume, clear, and
 * after compaction.
 */

declare(strict_types=1);

require __DIR__.'/lib.php';
require __DIR__.'/review.php';

$input = boostReadInput();
$root = boostProjectDir($input);
$config = boostConfig($root);
$source = (string) ($input['source'] ?? 'startup');

$lines = [];
$lines[] = '# Boost gate aktif di proyek ini';
$lines[] = '';
$lines[] = 'Hook Claude Code menegakkan aturan Boost. Perubahan file ditolak sampai syarat berikut terpenuhi di konteks saat ini:';
$lines[] = '1. Setiap file di `.ai/rules` yang glob-nya cocok dengan path target sudah dibaca dengan tool Read.';
$lines[] = '2. Skill yang dipetakan ke path target sudah dimuat dengan tool Skill.';
$lines[] = '3. Untuk kode aplikasi, tool `search-docs` sudah dipanggil minimal sekali.';
$lines[] = '4. Sebelum giliran selesai: format, static analysis, dan test harus lolos.';
$lines[] = boostReviewPolicyLine($config['review'] ?? []);
$lines[] = '5. Rules berlaku untuk kode yang kamu tulis atau ubah. Jangan mengubah kode lama di luar permintaan demi lolos gate atau review, dan jangan menulis keputusan atas nama pemilik. Laporkan hal seperti itu ke user di laporan akhir.';

if ($source === 'compact') {
    $lines[] = '';
    $lines[] = 'Percakapan baru saja diringkas. Rules dan skill yang dibaca sebelumnya tidak dihitung lagi, jadi baca dan muat ulang sebelum mengedit.';
}

$lines[] = '';
$lines[] = '## Peta skill wajib';

foreach ($config['skills'] ?? [] as $entry) {
    $lines[] = '- `'.$entry['glob'].'`: '.implode(', ', $entry['skills']);
}

$index = $root.'/.ai/rules/index.md';

if (is_file($index)) {
    $lines[] = '';
    $lines[] = '## Index rules (.ai/rules/index.md)';
    $lines[] = trim((string) file_get_contents($index));
}

$prd = $root.'/memory/PRD.md';
$lines[] = '';
$lines[] = '## Memori proyek (memory/PRD.md)';

if (is_file($prd)) {
    $content = (string) file_get_contents($prd);
    $lines[] = strlen($content) > 8000 ? substr($content, 0, 8000)."\n\n(dipotong, baca file lengkapnya dengan Read)" : $content;
} else {
    $lines[] = 'File belum ada. Tawarkan kepada user untuk membuatnya.';
}

boostEmit([
    'hookSpecificOutput' => [
        'hookEventName' => 'SessionStart',
        'additionalContext' => implode("\n", $lines),
    ],
]);

function boostReviewPolicyLine(array $review): string
{
    $agent = (string) ($review['agent'] ?? 'boost-reviewer');

    if (! ($review['enabled'] ?? false)) {
        return 'Review otomatis dimatikan di proyek ini.';
    }

    return match (boostReviewMode($review)) {
        'every_turn' => 'Setiap giliran yang mengubah kode aplikasi harus berakhir dengan subagent `'.$agent.'` memberi VERDICT: PASS. Subagent berjalan di latar belakang, jadi tunggu laporannya masuk dan jangan jalankan ulang.',
        'on_commit' => '`git commit` ditahan sampai subagent `'.$agent.'` memberi VERDICT: PASS untuk perubahan yang akan di-commit. Jangan menjalankan reviewer di tengah pekerjaan.',
        default => 'Review tidak berjalan otomatis dan mahal. Jangan menjalankan `'.$agent.'` sendiri di tengah pekerjaan. Setelah fitur selesai, beri tahu user bahwa review bisa dijalankan dengan /review-fitur. Jalankan perintah itu hanya saat user meminta atau menyatakan fitur sudah sesuai.',
    };
}
