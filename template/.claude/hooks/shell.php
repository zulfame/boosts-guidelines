<?php

/**
 * Heuristics for shell commands the agent runs through the Bash tool: does a
 * command write files, and which files. This is not a shell parser.
 */

declare(strict_types=1);

/**
 * Remove text that only looks like shell syntax: heredoc bodies, and quoted
 * strings that contain whitespace (prose, code, or inline scripts). Without this,
 * a ">" inside a heredoc or an inline PHP script is read as a redirect to a
 * bogus file. A quoted string without whitespace is kept because it may be a
 * path, for example `> "app/Models/Foo.php"`.
 */
function boostStripShellText(string $command): string
{
    $withoutHeredocs = preg_replace('/(<<-?[ \t]*([\'"]?)(\w+)\2[^\n]*)\n.*?\n[ \t]*\3[ \t]*(?=\n|$)/s', '$1', $command) ?? $command;

    return preg_replace_callback(
        '/\'[^\']*\'|"(?:[^"\\\\]|\\\\.)*"/s',
        fn (array $match): string => preg_match('/\s/', $match[0]) === 1 ? "''" : $match[0],
        $withoutHeredocs,
    ) ?? $withoutHeredocs;
}

function boostLooksLikeWrite(string $command): bool
{
    return preg_match(
        '/(?<![0-9&<>=\-])>{1,2}\s*[^\s&]|\btee\s|\bsed\s+-i|\bperl\s+-[a-z]*i|\b(cp|mv)\s/',
        boostStripShellText($command),
    ) === 1;
}

/**
 * Files a shell command writes to: redirections, tee, in-place sed or perl, and
 * the destination of cp or mv.
 *
 * @return list<string>
 */
function boostBashWriteTargets(string $command): array
{
    $command = boostStripShellText($command);
    $targets = [];

    // A redirect is ">" or ">>" not preceded by a digit, "&", "<", ">", "=" or "-",
    // so PHP and JS operators such as "=>" and "->" inside commands are ignored.
    if (preg_match_all('/(?<![0-9&<>=\-])>{1,2}\s*([^\s;&|<>]+)/', $command, $matches)) {
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

function boostIsGitCommit(string $command): bool
{
    return preg_match('/\bgit(?:\s+-[-\w]+(?:[= ][^\s;&|]+)?)*\s+commit\b/', boostStripShellText($command)) === 1;
}
