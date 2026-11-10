<?php

/**
 * Shared helpers for the Boost enforcement hooks.
 *
 * The hooks read the Claude Code transcript (JSONL) to find out what the agent
 * has already done in the current context: which files it read, which skills it
 * loaded, whether it called search-docs, and which subagents it ran.
 */

declare(strict_types=1);

function boostReadInput(): array
{
    $raw = stream_get_contents(STDIN);
    $decoded = json_decode((string) $raw, true);

    return is_array($decoded) ? $decoded : [];
}

function boostProjectDir(array $input): string
{
    $dir = getenv('CLAUDE_PROJECT_DIR') ?: ($input['cwd'] ?? getcwd());

    return rtrim((string) $dir, '/');
}

function boostConfig(string $root): array
{
    $path = $root.'/.claude/hooks/boost-gate.json';
    $config = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

    return is_array($config) ? $config : [];
}

function boostRelativePath(string $path, string $root): ?string
{
    if ($path === '') {
        return null;
    }

    if (! str_starts_with($path, '/')) {
        $path = $root.'/'.$path;
    }

    $path = preg_replace('#/\./#', '/', $path) ?? $path;

    if (! str_starts_with($path, $root.'/')) {
        return null;
    }

    return substr($path, strlen($root) + 1);
}

function boostGlobMatches(string $glob, string $path): bool
{
    $regex = '';
    $length = strlen($glob);

    for ($i = 0; $i < $length; $i++) {
        $char = $glob[$i];

        if ($char === '*' && ($glob[$i + 1] ?? '') === '*') {
            $i++;

            if (($glob[$i + 1] ?? '') === '/') {
                $i++;
                $regex .= '(?:.*/)?';
            } else {
                $regex .= '.*';
            }
        } elseif ($char === '*') {
            $regex .= '[^/]*';
        } elseif ($char === '?') {
            $regex .= '[^/]';
        } else {
            $regex .= preg_quote($char, '#');
        }
    }

    return preg_match('#^'.$regex.'$#', $path) === 1;
}

function boostMatchesAny(array $globs, string $path): bool
{
    foreach ($globs as $glob) {
        if (boostGlobMatches((string) $glob, $path)) {
            return true;
        }
    }

    return false;
}

/**
 * @return list<array{globs: list<string>, file: string}>
 */
function boostRuleIndex(string $root): array
{
    $index = $root.'/.ai/rules/index.md';

    if (! is_file($index)) {
        return [];
    }

    $rows = [];

    foreach (file($index, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        if (! preg_match('/^\|\s*(.+?)\s*\|\s*(\.ai\/rules\/[^|\s]+)\s*\|$/', $line, $match)) {
            continue;
        }

        $rows[] = [
            'globs' => array_map('trim', explode(',', $match[1])),
            'file' => $match[2],
        ];
    }

    return $rows;
}

/**
 * @return list<string>
 */
function boostRulesFor(string $relativePath, string $root): array
{
    $files = [];

    foreach (boostRuleIndex($root) as $row) {
        if (boostMatchesAny($row['globs'], $relativePath)) {
            $files[] = $row['file'];
        }
    }

    return array_values(array_unique($files));
}

/**
 * @return list<string>
 */
function boostSkillsFor(string $relativePath, array $config): array
{
    $skills = [];

    foreach ($config['skills'] ?? [] as $entry) {
        if (boostGlobMatches((string) $entry['glob'], $relativePath)) {
            $skills = array_merge($skills, $entry['skills']);
        }
    }

    return array_values(array_unique($skills));
}

/**
 * Parse the transcript into a flat list of events, starting after the last
 * compaction so that anything the agent no longer has in context is ignored.
 * Tool calls that failed or were denied are skipped.
 *
 * @return list<array{kind: string, value: string, time: string, result?: string}>
 */
function boostTranscriptEvents(?string $transcriptPath, string $root): array
{
    if ($transcriptPath === null || ! is_file($transcriptPath)) {
        return [];
    }

    $entries = [];
    $failedToolIds = [];
    $toolResults = [];

    foreach (file($transcriptPath, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $entry = json_decode($line, true);

        if (! is_array($entry)) {
            continue;
        }

        $isCompaction = (($entry['type'] ?? '') === 'system' && ($entry['subtype'] ?? '') === 'compact_boundary')
            || (($entry['isCompactSummary'] ?? false) === true);

        if ($isCompaction) {
            $entries = [];
            $failedToolIds = [];
            $toolResults = [];

            continue;
        }

        $entries[] = $entry;

        if (($entry['type'] ?? '') === 'user' && is_array($entry['message']['content'] ?? null)) {
            foreach ($entry['message']['content'] as $block) {
                if (($block['type'] ?? '') !== 'tool_result') {
                    continue;
                }

                $id = (string) ($block['tool_use_id'] ?? '');

                if (($block['is_error'] ?? false) === true) {
                    $failedToolIds[$id] = true;
                }

                $toolResults[$id] = boostBlockText($block['content'] ?? '');
            }
        }
    }

    $events = [];

    foreach ($entries as $entry) {
        $time = (string) ($entry['timestamp'] ?? '');
        $type = $entry['type'] ?? '';

        if ($type === 'user' && boostIsUserPrompt($entry)) {
            $events[] = ['kind' => 'prompt', 'value' => '', 'time' => $time];

            continue;
        }

        if ($type !== 'assistant' || ! is_array($entry['message']['content'] ?? null)) {
            continue;
        }

        foreach ($entry['message']['content'] as $block) {
            if (($block['type'] ?? '') !== 'tool_use') {
                continue;
            }

            $id = (string) ($block['id'] ?? '');

            if (isset($failedToolIds[$id])) {
                continue;
            }

            $name = (string) ($block['name'] ?? '');
            $input = is_array($block['input'] ?? null) ? $block['input'] : [];

            $event = match (true) {
                $name === 'Read' => ['kind' => 'read', 'value' => (string) boostRelativePath((string) ($input['file_path'] ?? ''), $root)],
                $name === 'Bash' => ['kind' => 'bash', 'value' => (string) ($input['command'] ?? '')],
                $name === 'Skill' => ['kind' => 'skill', 'value' => (string) ($input['skill'] ?? '')],
                str_ends_with($name, 'search-docs') => ['kind' => 'search_docs', 'value' => ''],
                in_array($name, ['Agent', 'Task'], true) => ['kind' => 'agent', 'value' => (string) ($input['subagent_type'] ?? ''), 'result' => $toolResults[$id] ?? ''],
                in_array($name, ['Edit', 'Write', 'MultiEdit', 'NotebookEdit'], true) => ['kind' => 'edit', 'value' => (string) boostRelativePath((string) ($input['file_path'] ?? $input['notebook_path'] ?? ''), $root)],
                default => null,
            };

            if ($event !== null) {
                $events[] = $event + ['time' => $time];
            }
        }
    }

    return $events;
}

function boostIsUserPrompt(array $entry): bool
{
    if (($entry['isMeta'] ?? false) === true) {
        return false;
    }

    $content = $entry['message']['content'] ?? null;

    if (is_string($content)) {
        return true;
    }

    foreach ((array) $content as $block) {
        if (($block['type'] ?? '') === 'tool_result') {
            return false;
        }
    }

    return $content !== null;
}

function boostBlockText(mixed $content): string
{
    if (is_string($content)) {
        return $content;
    }

    $text = '';

    foreach ((array) $content as $block) {
        if (is_array($block) && ($block['type'] ?? '') === 'text') {
            $text .= $block['text'];
        }
    }

    return $text;
}

function boostHasReadFile(array $events, string $relativePath): bool
{
    $quoted = preg_quote($relativePath, '#');

    foreach ($events as $event) {
        if ($event['kind'] === 'read' && $event['value'] === $relativePath) {
            return true;
        }

        if ($event['kind'] === 'bash' && preg_match('#\b(cat|head|tail|less|bat|sed\s+-n)\s+[^|;&>]*'.$quoted.'#', $event['value']) === 1) {
            return true;
        }
    }

    return false;
}

function boostHasLoadedSkill(array $events, string $skill): bool
{
    foreach ($events as $event) {
        if ($event['kind'] !== 'skill') {
            continue;
        }

        if ($event['value'] === $skill || str_ends_with($event['value'], ':'.$skill)) {
            return true;
        }
    }

    return false;
}

function boostHasEvent(array $events, string $kind): bool
{
    foreach ($events as $event) {
        if ($event['kind'] === $kind) {
            return true;
        }
    }

    return false;
}

function boostEmit(array $payload): never
{
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    exit(0);
}
