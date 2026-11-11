<?php

/**
 * Reads the Claude Code transcript (JSONL) into a flat list of events: which
 * files the agent read, which skills it loaded, whether it called search-docs,
 * which files it edited, and which subagents it ran with their reports.
 */

declare(strict_types=1);

/**
 * Parse the transcript into a flat list of events, starting after the last
 * compaction so that anything the agent no longer has in context is ignored.
 * Tool calls that failed or were denied are skipped.
 *
 * @return list<array{kind: string, value: string, time: string, result?: string, pending?: bool}>
 */
function boostTranscriptEvents(?string $transcriptPath, string $root): array
{
    if ($transcriptPath === null || ! is_file($transcriptPath)) {
        return [];
    }

    $entries = [];
    $failedToolIds = [];
    $toolResults = [];
    $handbacks = [];

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
            $handbacks = [];

            continue;
        }

        if (($entry['type'] ?? '') === 'queue-operation') {
            $handback = boostParseHandback((string) ($entry['content'] ?? ''));

            if ($handback !== null) {
                $handbacks[$handback['agent']] = $handback['report'];
            }

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
                in_array($name, ['Agent', 'Task'], true) => boostAgentEvent((string) ($input['subagent_type'] ?? ''), $toolResults[$id] ?? '', $handbacks),
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

/**
 * A subagent started in the background answers the call with only "Async agent
 * launched ... agentId: X". The real report arrives later as a separate
 * harness message, so the report is looked up by that agent id. A launch whose
 * report has not arrived yet is marked pending.
 *
 * @param  array<string, string>  $handbacks
 * @return array{kind: string, value: string, result: string, pending: bool}
 */
function boostAgentEvent(string $subagent, string $launchResult, array $handbacks): array
{
    $isAsync = str_contains($launchResult, 'Async agent launched') && preg_match('/agentId:\s*([\w-]+)/', $launchResult, $match) === 1;

    if (! $isAsync) {
        return ['kind' => 'agent', 'value' => $subagent, 'result' => $launchResult, 'pending' => false];
    }

    $report = $handbacks[$match[1]] ?? null;

    return ['kind' => 'agent', 'value' => $subagent, 'result' => $report ?? '', 'pending' => $report === null];
}

/**
 * The harness delivers a subagent's final report as a message that starts with
 * an agent-message frame and indents every line of the report, so only indented
 * lines are the report. A line at column zero inside it would be forged.
 *
 * @return array{agent: string, report: string}|null
 */
function boostParseHandback(string $content): ?array
{
    if (preg_match('/\A<agent-message from="([\w-]+)">\n/', $content, $match) !== 1) {
        return null;
    }

    $report = [];

    foreach (explode("\n", substr($content, strlen($match[0]))) as $line) {
        if (str_starts_with($line, '  ')) {
            $report[] = substr($line, 2);
        }
    }

    return ['agent' => $match[1], 'report' => implode("\n", $report)];
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
