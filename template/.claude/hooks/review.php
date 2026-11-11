<?php

/**
 * Review helpers shared by the Stop gate, the commit gate, and review-scope.php:
 * which review mode is configured, which changed files are reviewable, and
 * whether the boost-reviewer subagent has given a passing verdict.
 */

declare(strict_types=1);

const BOOST_REVIEW_MODES = ['on_request', 'on_commit', 'every_turn'];

/**
 * on_request (default): no automatic review, the user runs /review-fitur.
 * on_commit: a passing review is required before `git commit`.
 * every_turn: a passing review is required before every turn that changes code.
 */
function boostReviewMode(array $review): string
{
    $mode = (string) ($review['mode'] ?? 'on_request');

    return in_array($mode, BOOST_REVIEW_MODES, true) ? $mode : 'on_request';
}

/**
 * @param  list<string>  $files
 * @return list<string>
 */
function boostReviewableFiles(array $files, array $config, string $root): array
{
    $globs = $config['review']['globs'] ?? [];

    $reviewable = array_filter($files, fn (string $file): bool => is_file($root.'/'.$file)
        && boostMatchesAny($globs, $file)
        && ! boostMatchesAny($config['exempt'] ?? [], $file));

    sort($reviewable);

    return array_values($reviewable);
}

/**
 * Files that differ from a git ref (HEAD by default), plus untracked files.
 * Returns an empty list when git cannot answer.
 *
 * @return list<string>
 */
function boostChangedSince(string $root, ?string $base): array
{
    $ref = ($base === null || $base === '') ? 'HEAD' : $base;

    if (preg_match('/^[\w.\/~^@-]+$/', $ref) !== 1) {
        return [];
    }

    $dir = escapeshellarg($root);
    $tracked = [];
    $untracked = [];

    exec("cd {$dir} && git diff --name-only --diff-filter=ACMR ".escapeshellarg($ref).' 2>/dev/null', $tracked, $code);

    if ($code !== 0) {
        return [];
    }

    exec("cd {$dir} && git ls-files --others --exclude-standard 2>/dev/null", $untracked);

    return array_values(array_unique(array_merge($tracked, $untracked)));
}

/**
 * Show a few file names and the count of the rest, so a block message does not
 * repeat a list of dozens of files every time.
 *
 * @param  list<string>  $files
 */
function boostFileSummary(array $files, int $limit = 6): string
{
    if (count($files) <= $limit) {
        return implode(', ', $files);
    }

    return implode(', ', array_slice($files, 0, $limit)).' dan '.(count($files) - $limit).' file lain (daftar lengkap: php .claude/hooks/review-scope.php)';
}

/**
 * @param  list<array>  $events
 * @param  list<string>  $reviewable
 */
function boostReviewProblem(array $events, array $reviewable, array $review): ?string
{
    if ($reviewable === []) {
        return null;
    }

    $agent = (string) ($review['agent'] ?? 'boost-reviewer');
    $lastEdit = -1;
    $lastReview = null;

    foreach ($events as $i => $event) {
        if ($event['kind'] === 'edit' || ($event['kind'] === 'bash' && boostLooksLikeWrite($event['value']))) {
            $lastEdit = $i;
        }

        if ($event['kind'] === 'agent' && $event['value'] === $agent) {
            $lastReview = ['index' => $i, 'result' => $event['result'] ?? '', 'pending' => (bool) ($event['pending'] ?? false)];
        }
    }

    $instruction = 'Jalankan subagent `'.$agent.'` dengan tool Agent (subagent_type: '.$agent.') untuk file: '.boostFileSummary($reviewable)
        .'. Subagent berjalan di latar belakang, jadi tunggu laporannya masuk dan jangan jalankan ulang. Jangan menyertakan kesimpulan, klaim, atau daftar "trade-off yang sudah diterima" di prompt.';

    if ($lastReview === null || $lastReview['index'] < $lastEdit) {
        return "Review belum dijalankan setelah perubahan terakhir.\n".$instruction;
    }

    if ($lastReview['pending']) {
        return 'Reviewer sudah dijalankan dan laporannya belum masuk. Tunggu laporan itu masuk, jangan jalankan reviewer lagi.';
    }

    $verdict = boostReviewVerdict($lastReview['result']);

    if ($verdict === null) {
        return "Laporan reviewer tidak ditutup dengan baris VERDICT.\n".$instruction;
    }

    if ($verdict !== 'PASS') {
        return "Reviewer memberi VERDICT: FAIL. Perbaiki hanya temuan BLOKIR di perubahanmu. Catatan warisan di kode lama cukup disebutkan ke user di laporan akhir. Lalu jalankan review ulang.\n".$instruction;
    }

    return null;
}

/**
 * The verdict is the last "VERDICT: PASS|FAIL" in the report, so a report that
 * quotes an earlier verdict cannot pass by accident.
 */
function boostReviewVerdict(string $report): ?string
{
    if (preg_match_all('/VERDICT:\s*(PASS|FAIL)/', $report, $matches) === 0) {
        return null;
    }

    return end($matches[1]);
}
