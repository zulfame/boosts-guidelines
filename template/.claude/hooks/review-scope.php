<?php

/**
 * Prints the files a review should cover, one per line: files that differ from a
 * git ref (HEAD by default, or the base given as the first argument), limited to
 * the review globs in boost-gate.json and without exempt paths such as generated
 * code. Used by the /review-fitur command and by the block messages.
 *
 * Usage: php .claude/hooks/review-scope.php [base-ref]
 */

declare(strict_types=1);

require __DIR__.'/lib.php';
require __DIR__.'/review.php';

$root = dirname(__DIR__, 2);
$config = boostConfig($root);
$base = $argv[1] ?? null;

$changed = boostChangedSince($root, $base);

if ($changed === []) {
    fwrite(STDERR, "Tidak ada perubahan yang terbaca dari git (atau git tidak tersedia, atau base tidak dikenal).\n");
    exit(0);
}

$files = boostReviewableFiles($changed, $config, $root);

if ($files === []) {
    fwrite(STDERR, "Tidak ada berkas yang perlu direview: semua perubahan ada di luar review.globs atau di path yang dikecualikan.\n");
    exit(0);
}

echo implode("\n", $files)."\n";
