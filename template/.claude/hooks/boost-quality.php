<?php

/**
 * Stop gate: before the agent may end its turn, the files changed in this turn
 * are formatted, statically analysed, and tested. When review.mode is
 * "every_turn", application changes must also have a passing verdict from the
 * boost-reviewer subagent. In the default mode ("on_request") the review runs
 * only when the user asks for it with /review-fitur.
 *
 * To avoid an endless loop the gate blocks at most `max_blocks` times per turn,
 * then lets the turn end with a visible warning.
 */

declare(strict_types=1);

require __DIR__.'/lib.php';
require __DIR__.'/shell.php';
require __DIR__.'/review.php';

$input = boostReadInput();
$root = boostProjectDir($input);
$config = boostConfig($root);
$quality = $config['quality'] ?? [];
$review = $config['review'] ?? [];

if (! ($quality['enabled'] ?? false) && ! ($review['enabled'] ?? false)) {
    exit(0);
}

$events = boostTranscriptEvents($input['transcript_path'] ?? null, $root);
[$turnEvents, $turnStartedAt] = boostCurrentTurn($events);
$changed = boostChangedFiles($turnEvents, $turnStartedAt, $root, $config);

if ($changed === []) {
    exit(0);
}

$counterFile = sys_get_temp_dir().'/boost-gate-'.preg_replace('/\W/', '', (string) ($input['session_id'] ?? 'unknown')).'.json';
$counter = is_file($counterFile) ? (json_decode((string) file_get_contents($counterFile), true) ?: []) : [];

if (($counter['turn'] ?? '') !== $turnStartedAt) {
    $counter = ['turn' => $turnStartedAt, 'blocks' => 0];
}

$problems = [];

if ($quality['enabled'] ?? false) {
    $problems = boostRunQualityChecks($changed, $quality, $root);
}

if ($problems === [] && ($review['enabled'] ?? false) && boostReviewMode($review) === 'every_turn') {
    $reviewProblem = boostReviewProblem($turnEvents, boostReviewableFiles($changed, $config, $root), $review);

    if ($reviewProblem !== null) {
        $problems[] = $reviewProblem;
    }
}

if ($problems === []) {
    @unlink($counterFile);

    exit(0);
}

$maxBlocks = (int) ($quality['max_blocks'] ?? 1);

if ($counter['blocks'] >= $maxBlocks) {
    @unlink($counterFile);

    boostEmit([
        'systemMessage' => 'Boost gate sudah menahan giliran ini '.$maxBlocks.' kali dan masih gagal. Giliran dihentikan agar tidak berputar terus. Periksa manual: '.implode(' | ', array_map(fn (string $p): string => strtok($p, "\n"), $problems)),
    ]);
}

$counter['blocks']++;
file_put_contents($counterFile, json_encode($counter));

boostEmit([
    'decision' => 'block',
    'reason' => "Boost gate: pekerjaan belum selesai.\n\n".implode("\n\n", $problems)."\n\nPerbaiki, lalu selesaikan giliran lagi. Jangan mengubah kode di luar permintaan demi lolos gate, dan jangan menulis keputusan atas nama pemilik. (Percobaan ".$counter['blocks'].' dari '.$maxBlocks.')',
]);

/**
 * @return array{0: list<array>, 1: string}
 */
function boostCurrentTurn(array $events): array
{
    $start = 0;
    $startedAt = '';

    foreach ($events as $i => $event) {
        if ($event['kind'] === 'prompt') {
            $start = $i + 1;
            $startedAt = $event['time'];
        }
    }

    return [array_slice($events, $start), $startedAt];
}

/**
 * Files edited with file tools this turn, plus files in watched folders whose
 * modification time is after the turn started (this also catches shell writes).
 *
 * @return list<string>
 */
function boostChangedFiles(array $turnEvents, string $turnStartedAt, string $root, array $config): array
{
    $files = [];

    foreach ($turnEvents as $event) {
        if ($event['kind'] === 'edit' && $event['value'] !== '') {
            $files[] = $event['value'];
        }
    }

    $since = $turnStartedAt !== '' ? strtotime($turnStartedAt) : false;

    if ($since !== false) {
        foreach ($config['quality']['watch'] ?? [] as $watched) {
            $path = $root.'/'.$watched;

            if (is_file($path)) {
                if (filemtime($path) >= $since) {
                    $files[] = $watched;
                }

                continue;
            }

            if (! is_dir($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getMTime() >= $since) {
                    $files[] = (string) boostRelativePath($file->getPathname(), $root);
                }
            }
        }
    }

    // Only source files count. Runtime data in watched folders (for example a
    // SQLite database used for sessions and cache) changes on its own.
    $sourceExtensions = $config['quality']['source_extensions'] ?? ['php', 'js', 'jsx', 'ts', 'tsx', 'mjs', 'cjs', 'vue', 'svelte', 'css', 'scss', 'json'];

    $files = array_filter(array_unique($files), function (string $file) use ($root, $config, $sourceExtensions): bool {
        return is_file($root.'/'.$file)
            && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $sourceExtensions, true)
            && ! boostMatchesAny($config['exempt'] ?? [], $file);
    });

    sort($files);

    return boostDropUnchangedFiles(array_values($files), $root, $turnStartedAt);
}

/**
 * With git, drop files whose content equals the last commit made before this
 * turn started (for example a file the agent changed and then restored). New
 * files stay. Comparing with that commit, not with HEAD, means committing
 * during the turn does not hide the work. Without git, or without a commit
 * before the turn, every file stays.
 *
 * @param  list<string>  $files
 * @return list<string>
 */
function boostDropUnchangedFiles(array $files, string $root, string $turnStartedAt): array
{
    $base = boostBaseCommit($root, $turnStartedAt);

    if ($files === [] || $base === null) {
        return $files;
    }

    $dir = escapeshellarg($root);

    return array_values(array_filter($files, function (string $file) use ($dir, $base): bool {
        $output = [];
        exec("cd {$dir} && git cat-file -e ".escapeshellarg($base.':'.$file).' 2>/dev/null', $output, $existsAtBase);

        if ($existsAtBase !== 0) {
            return true;
        }

        exec("cd {$dir} && git diff --quiet {$base} -- ".escapeshellarg($file).' 2>/dev/null', $output, $differs);

        return $differs !== 0;
    }));
}

function boostBaseCommit(string $root, string $turnStartedAt): ?string
{
    if ($turnStartedAt === '') {
        return null;
    }

    $output = [];
    exec('cd '.escapeshellarg($root).' && git rev-list -1 --before='.escapeshellarg($turnStartedAt).' HEAD 2>/dev/null', $output, $code);
    $sha = trim($output[0] ?? '');

    return $code === 0 && preg_match('/^[0-9a-f]{40}$/', $sha) === 1 ? $sha : null;
}

/**
 * @return list<string>
 */
function boostRunQualityChecks(array $changed, array $quality, string $root): array
{
    $problems = [];
    $php = array_values(array_filter($changed, fn (string $file): bool => str_ends_with($file, '.php')));
    $staticPaths = $quality['static_paths'] ?? [];
    $analysable = array_values(array_filter($php, function (string $file) use ($staticPaths): bool {
        foreach ($staticPaths as $prefix) {
            if (str_starts_with($file, $prefix)) {
                return true;
            }
        }

        return false;
    }));
    $frontend = array_filter($changed, fn (string $file): bool => str_starts_with($file, 'resources/js/'));

    if ($php !== [] && ! empty($quality['format'])) {
        boostRun($quality['format'], $php, $root);
    }

    if ($analysable !== [] && ! empty($quality['static'])) {
        [$code, $output] = boostRun($quality['static'], $analysable, $root);

        if ($code !== 0) {
            $problems[] = "Static analysis gagal:\n".boostTrim($output);
        }
    }

    if ($php !== [] && ! empty($quality['tests'])) {
        [$code, $output] = boostRun($quality['tests'], [], $root);

        if ($code !== 0) {
            $problems[] = "Test gagal:\n".boostTrim($output);
        }
    }

    if ($frontend !== [] && ! empty($quality['frontend'])) {
        [$code, $output] = boostRun($quality['frontend'], [], $root);

        if ($code !== 0) {
            $problems[] = "Pemeriksaan frontend gagal:\n".boostTrim($output);
        }
    }

    return $problems;
}

/**
 * @return array{0: int, 1: string}
 */
function boostRun(string $template, array $files, string $root): array
{
    $arguments = implode(' ', array_map('escapeshellarg', $files));
    $command = 'cd '.escapeshellarg($root).' && '.str_replace('{files}', $arguments, $template).' 2>&1';
    $output = [];
    $code = 0;

    exec($command, $output, $code);

    return [$code, implode("\n", $output)];
}

function boostTrim(string $output): string
{
    $output = trim((string) preg_replace('/\e\[[0-9;]*[A-Za-z]/', '', $output));

    return strlen($output) > 1500 ? substr($output, 0, 1500)."\n(dipotong)" : $output;
}
