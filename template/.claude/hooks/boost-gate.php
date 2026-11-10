<?php

/**
 * PreToolUse gate: refuses an edit until the agent has, in its current context,
 *  - read every rule file in .ai/rules whose globs match the target path,
 *  - loaded every skill mapped to that path in boost-gate.json,
 *  - called search-docs at least once (for paths that require it).
 */

declare(strict_types=1);

require __DIR__.'/lib.php';

$input = boostReadInput();
$root = boostProjectDir($input);
$config = boostConfig($root);
$toolName = (string) ($input['tool_name'] ?? '');
$toolInput = is_array($input['tool_input'] ?? null) ? $input['tool_input'] : [];

$targets = match (true) {
    in_array($toolName, ['Edit', 'Write', 'MultiEdit', 'NotebookEdit'], true) => [(string) ($toolInput['file_path'] ?? $toolInput['notebook_path'] ?? '')],
    $toolName === 'Bash' => boostBashWriteTargets((string) ($toolInput['command'] ?? '')),
    default => [],
};

$paths = [];

foreach ($targets as $target) {
    $relative = boostRelativePath($target, $root);

    if ($relative !== null && $relative !== '' && ! boostMatchesAny($config['exempt'] ?? [], $relative)) {
        $paths[] = $relative;
    }
}

if ($paths === []) {
    exit(0);
}

// Claude Code can write a tool call to the transcript slightly after the next
// tool starts (for example a Read and an Edit sent in one message), so a
// failing check is retried briefly before the edit is refused.
for ($attempt = 1; $attempt <= 4; $attempt++) {
    [$missingRules, $missingSkills, $needsSearchDocs] = boostMissingRequirements(
        boostTranscriptEvents($input['transcript_path'] ?? null, $root),
        array_unique($paths),
        $root,
        $config,
    );

    if ($missingRules === [] && $missingSkills === [] && ! $needsSearchDocs) {
        exit(0);
    }

    usleep(500_000);
}

$steps = [];

if ($missingRules !== []) {
    $steps[] = 'Baca file rule ini dengan tool Read: '.implode(', ', array_keys($missingRules)).'.';
}

if ($missingSkills !== []) {
    $steps[] = 'Muat skill ini dengan tool Skill: '.implode(', ', array_keys($missingSkills)).'.';
}

if ($needsSearchDocs) {
    $steps[] = 'Panggil tool search-docs (laravel-boost) minimal sekali untuk API paket yang akan kamu pakai.';
}

$reason = 'Boost gate menolak perubahan pada '.implode(', ', array_unique($paths)).". Sebelum mengubah file ini:\n- "
    .implode("\n- ", $steps)
    ."\nSetelah itu, ikuti isinya dan ulangi perubahan. Gate ini diatur di .claude/hooks/boost-gate.json.";

boostEmit([
    'hookSpecificOutput' => [
        'hookEventName' => 'PreToolUse',
        'permissionDecision' => 'deny',
        'permissionDecisionReason' => $reason,
    ],
]);

/**
 * @param  list<string>  $paths
 * @return array{0: array<string, true>, 1: array<string, true>, 2: bool}
 */
function boostMissingRequirements(array $events, array $paths, string $root, array $config): array
{
    $missingRules = [];
    $missingSkills = [];
    $needsSearchDocs = false;
    $searchDocs = $config['search_docs'] ?? [];

    foreach ($paths as $path) {
        foreach (boostRulesFor($path, $root) as $ruleFile) {
            if (! boostHasReadFile($events, $ruleFile)) {
                $missingRules[$ruleFile] = true;
            }
        }

        foreach (boostSkillsFor($path, $config) as $skill) {
            if (! boostHasLoadedSkill($events, $skill)) {
                $missingSkills[$skill] = true;
            }
        }

        if (($searchDocs['required'] ?? false) && boostMatchesAny($searchDocs['globs'] ?? [], $path) && ! boostHasEvent($events, 'search_docs')) {
            $needsSearchDocs = true;
        }
    }

    return [$missingRules, $missingSkills, $needsSearchDocs];
}

/**
 * Find files a shell command writes to: redirections, tee, in-place sed or perl,
 * and the destination of cp or mv. This is a heuristic, not a shell parser.
 *
 * @return list<string>
 */
function boostBashWriteTargets(string $command): array
{
    $targets = [];

    if (preg_match_all('/(?<![0-9&<>])>{1,2}\s*([^\s;&|<>]+)/', $command, $matches)) {
        $targets = array_merge($targets, $matches[1]);
    }

    if (preg_match_all('/\btee\s+(?:-a\s+)?([^\s;&|<>]+)/', $command, $matches)) {
        $targets = array_merge($targets, $matches[1]);
    }

    foreach (preg_split('/[;&|]+/', $command) ?: [] as $segment) {
        $tokens = preg_split('/\s+/', trim($segment)) ?: [];
        $program = $tokens[0] ?? '';

        $inPlace = ($program === 'sed' && preg_grep('/^-i/', $tokens))
            || ($program === 'perl' && preg_grep('/^-[a-z]*i/', $tokens));

        if ($inPlace) {
            foreach (array_slice($tokens, 1) as $token) {
                if (preg_match('/^[\w.\/-]+\.[a-z]{1,5}$/i', trim($token, '\'"'))) {
                    $targets[] = trim($token, '\'"');
                }
            }
        }

        if (in_array($program, ['cp', 'mv'], true) && count($tokens) >= 3) {
            $targets[] = trim((string) end($tokens), '\'"');
        }
    }

    return array_values(array_filter(array_map(
        fn (string $target): string => trim($target, '\'"'),
        $targets,
    ), fn (string $target): bool => $target !== '' && ! str_starts_with($target, '/dev/')));
}
