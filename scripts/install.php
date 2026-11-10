<?php

/**
 * Install the boosts-guidelines standard into a Laravel project.
 *
 * Usage:
 *   php scripts/install.php /path/to/laravel-project [--dry-run] [--force] [--no-artisan]
 *
 * What it does:
 *   1. Checks that the target is a Laravel project with Laravel Boost installed.
 *   2. Copies template/ into the project. Existing files that differ are kept
 *      unless --force is given; identical files are skipped.
 *   3. Merges the gate hooks into .claude/settings.json without removing anything.
 *   4. Generates .claude/hooks/boost-gate.json from the skills Boost installed,
 *      the tools the project has (PHPStan, Pest), and its package manager.
 *   5. Appends the Boost env block to .env.example and .env when missing.
 *   6. Runs `php artisan boost:update` and `php artisan boost:index-rules`.
 *
 * It never deletes files.
 */

declare(strict_types=1);

$options = array_values(array_filter(array_slice($argv, 1), fn (string $arg): bool => str_starts_with($arg, '--')));
$arguments = array_values(array_filter(array_slice($argv, 1), fn (string $arg): bool => ! str_starts_with($arg, '--')));

$dryRun = in_array('--dry-run', $options, true);
$force = in_array('--force', $options, true);
$runArtisan = ! in_array('--no-artisan', $options, true);

$source = dirname(__DIR__);
$target = isset($arguments[0]) ? rtrim((string) realpath($arguments[0]), '/') : '';

if ($target === '' || ! is_dir($target)) {
    fail('Folder proyek tujuan tidak ditemukan. Pakai: php scripts/install.php /path/ke/proyek [--dry-run] [--force] [--no-artisan]');
}

if (! is_file($target.'/artisan') || ! is_file($target.'/composer.json')) {
    fail("{$target} bukan proyek Laravel (tidak ada artisan atau composer.json).");
}

if (! is_dir($target.'/vendor/laravel/boost')) {
    fail('Laravel Boost belum terpasang. Jalankan dulu: composer require laravel/boost --dev && php artisan boost:install');
}

if (! is_file($target.'/boost.json')) {
    fail('boost.json tidak ada. Jalankan dulu: php artisan boost:install (pilih Claude Code, guidelines, skills, dan MCP).');
}

$report = ['created' => [], 'identical' => [], 'kept' => [], 'overwritten' => [], 'merged' => [], 'notes' => []];
$hasPest = is_dir($target.'/vendor/pestphp/pest');
$hasPhpstan = is_file($target.'/vendor/bin/phpstan');
$hasPint = is_file($target.'/vendor/bin/pint');
$today = date('Y-m-d');
$reviewAt = date('Y-m-d', strtotime('+6 months'));

if ($dryRun) {
    say("Mode uji (--dry-run): tidak ada file yang diubah.\n");
}

say("Sumber : {$source}");
say("Tujuan : {$target}\n");

// 1. Copy template files.
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source.'/template', FilesystemIterator::SKIP_DOTS));

foreach ($iterator as $file) {
    if (! $file->isFile()) {
        continue;
    }

    $relative = substr($file->getPathname(), strlen($source.'/template/'));

    if ($relative === '.claude/settings.json') {
        continue;
    }

    if ($relative === 'tests/Unit/ArchTest.php' && ! $hasPest) {
        $report['notes'][] = 'Pest tidak terpasang, jadi tests/Unit/ArchTest.php tidak disalin. Arch test butuh Pest.';

        continue;
    }

    $contents = (string) file_get_contents($file->getPathname());

    if ($relative === 'docs/decisions.md') {
        if (is_file($target.'/'.$relative)) {
            $report['identical'][] = 'docs/decisions.md (sudah ada, tidak pernah ditimpa karena berisi keputusan proyek)';

            continue;
        }

        $contents = str_replace(['{{ADOPTED_AT}}', '{{REVIEW_AT}}'], [$today, $reviewAt], $contents);
    }

    place($target, $relative, $contents, $force, $dryRun, $report);
}

// 2. Merge hooks into .claude/settings.json.
$templateSettings = json_decode((string) file_get_contents($source.'/template/.claude/settings.json'), true);
$settingsPath = $target.'/.claude/settings.json';
$settings = is_file($settingsPath) ? json_decode((string) file_get_contents($settingsPath), true) : [];

if (! is_array($settings)) {
    fail('.claude/settings.json di proyek tujuan bukan JSON yang valid. Perbaiki dulu, lalu jalankan ulang.');
}

$added = 0;

foreach ($templateSettings['hooks'] as $event => $entries) {
    foreach ($entries as $entry) {
        $command = $entry['hooks'][0]['command'];
        $exists = false;

        foreach ($settings['hooks'][$event] ?? [] as $existing) {
            foreach ($existing['hooks'] ?? [] as $hook) {
                if (($hook['command'] ?? '') === $command && ($existing['matcher'] ?? '') === ($entry['matcher'] ?? '')) {
                    $exists = true;
                }
            }
        }

        if (! $exists) {
            $settings['hooks'][$event][] = $entry;
            $added++;
        }
    }
}

if ($added > 0) {
    write($settingsPath, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n", $dryRun);
    $report['merged'][] = ".claude/settings.json ({$added} hook ditambahkan)";
} else {
    $report['identical'][] = '.claude/settings.json (semua hook sudah ada)';
}

// 3. Generate .claude/hooks/boost-gate.json for this project's stack.
$gate = json_decode((string) file_get_contents($source.'/stubs/boost-gate.base.json'), true);
$installedSkills = installedSkills($target);
$gate['skills'] = skillEntries($installedSkills);

if (! $hasPhpstan) {
    $gate['quality']['static'] = '';
    $report['notes'][] = 'PHPStan tidak terpasang, jadi static analysis di Stop gate dimatikan. Pasang larastan/larastan lalu isi quality.static di boost-gate.json.';
}

if (! $hasPint) {
    $gate['quality']['format'] = '';
    $report['notes'][] = 'Pint tidak terpasang, jadi auto-format di Stop gate dimatikan.';
}

$gate['quality']['frontend'] = frontendCheck($target);

if ($gate['quality']['frontend'] === '' && is_file($target.'/package.json')) {
    $report['notes'][] = 'Tidak ada script types:check atau typecheck di package.json, jadi pemeriksaan frontend di Stop gate kosong. Isi quality.frontend jika proyek punya pemeriksaan tipe atau lint.';
}

$gateJson = json_encode($gate, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
place($target, '.claude/hooks/boost-gate.json', $gateJson, $force, $dryRun, $report);

if ($installedSkills === []) {
    $report['notes'][] = 'Tidak ada skill Boost di .claude/skills. Jalankan php artisan boost:install dengan fitur skills, lalu jalankan installer ini lagi dengan --force agar peta skill terisi.';
}

// 4. Env block.
$envBlock = (string) file_get_contents($source.'/stubs/env-boost.txt');

foreach (['.env.example', '.env'] as $envFile) {
    $path = $target.'/'.$envFile;

    if (! is_file($path)) {
        continue;
    }

    if (str_contains((string) file_get_contents($path), 'BOOST_ENABLED')) {
        $report['identical'][] = "{$envFile} (variabel Boost sudah ada)";

        continue;
    }

    if (! $dryRun) {
        file_put_contents($path, rtrim((string) file_get_contents($path))."\n".$envBlock);
    }

    $report['merged'][] = "{$envFile} (blok variabel Boost ditambahkan)";
}

// 5. Regenerate Boost output.
if ($runArtisan && ! $dryRun) {
    foreach (['boost:update --no-discover --no-interaction', 'boost:index-rules'] as $command) {
        $output = [];
        $code = 0;
        exec('cd '.escapeshellarg($target).' && php artisan '.$command.' 2>&1', $output, $code);
        $report[$code === 0 ? 'merged' : 'notes'][] = 'php artisan '.$command.': '.($code === 0 ? 'berhasil' : 'gagal ('.trim(implode(' ', $output)).')');
    }
}

// Report.
$labels = [
    'created' => 'Dibuat',
    'merged' => 'Digabung atau dijalankan',
    'overwritten' => 'Ditimpa (--force)',
    'kept' => 'Dibiarkan karena isinya berbeda (pakai --force untuk menimpa)',
    'identical' => 'Sudah sama, dilewati',
    'notes' => 'Perlu perhatian',
];

foreach ($labels as $key => $label) {
    if ($report[$key] === []) {
        continue;
    }

    say("{$label}:");

    foreach ($report[$key] as $line) {
        say("  - {$line}");
    }

    say('');
}

say('Skill terdeteksi: '.($installedSkills === [] ? '(tidak ada)' : implode(', ', $installedSkills)));
say("\nLangkah berikutnya ada di ADOPTION.md, mulai dari langkah 4.");

exit(0);

function place(string $target, string $relative, string $contents, bool $force, bool $dryRun, array &$report): void
{
    $path = $target.'/'.$relative;

    if (is_file($path)) {
        if (file_get_contents($path) === $contents) {
            $report['identical'][] = $relative;

            return;
        }

        if (! $force) {
            $report['kept'][] = $relative;

            return;
        }

        write($path, $contents, $dryRun);
        $report['overwritten'][] = $relative;

        return;
    }

    write($path, $contents, $dryRun);
    $report['created'][] = $relative;
}

function write(string $path, string $contents, bool $dryRun): void
{
    if ($dryRun) {
        return;
    }

    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }

    file_put_contents($path, $contents);
}

/**
 * @return list<string>
 */
function installedSkills(string $target): array
{
    $skills = [];

    foreach (glob($target.'/.claude/skills/*/SKILL.md') ?: [] as $file) {
        $skills[] = basename(dirname($file));
    }

    sort($skills);

    return $skills;
}

/**
 * Map Boost skills to the paths where they must be loaded before editing.
 *
 * @param  list<string>  $installed
 * @return list<array{glob: string, skills: list<string>}>
 */
function skillEntries(array $installed): array
{
    $map = [
        'laravel-best-practices' => ['app/**/*.php', 'routes/**/*.php', 'database/**/*.php', 'config/**/*.php', 'bootstrap/app.php'],
        'testing-best-practices' => ['tests/**'],
        'pest-testing' => ['tests/**'],
        'fortify-development' => ['app/Actions/Fortify/**'],
        'inertia-react-development' => ['resources/js/**'],
        'inertia-vue-development' => ['resources/js/**'],
        'inertia-svelte-development' => ['resources/js/**'],
        'livewire-development' => ['app/Livewire/**', 'resources/views/livewire/**', 'resources/views/components/**'],
        'volt-development' => ['resources/views/livewire/**', 'resources/views/pages/**'],
        'fluxui-development' => ['resources/views/**'],
        'folio-routing' => ['resources/views/pages/**'],
        'tailwindcss-development' => ['resources/css/**'],
        'pennant-development' => ['app/Features/**'],
        'mcp-development' => ['app/Mcp/**'],
    ];

    $byGlob = [];

    foreach ($map as $skill => $globs) {
        if (! in_array($skill, $installed, true)) {
            continue;
        }

        foreach ($globs as $glob) {
            $byGlob[$glob][] = $skill;
        }
    }

    $entries = [];

    foreach ($byGlob as $glob => $skills) {
        $entries[] = ['glob' => $glob, 'skills' => array_values(array_unique($skills))];
    }

    return $entries;
}

function frontendCheck(string $target): string
{
    $packageJson = $target.'/package.json';

    if (! is_file($packageJson)) {
        return '';
    }

    $scripts = json_decode((string) file_get_contents($packageJson), true)['scripts'] ?? [];
    $runner = match (true) {
        is_file($target.'/pnpm-lock.yaml') => 'pnpm run',
        is_file($target.'/yarn.lock') => 'yarn',
        is_file($target.'/bun.lock'), is_file($target.'/bun.lockb') => 'bun run',
        default => 'npm run',
    };

    foreach (['types:check', 'typecheck', 'type-check'] as $script) {
        if (isset($scripts[$script])) {
            return "{$runner} {$script}";
        }
    }

    return '';
}

function say(string $line): void
{
    fwrite(STDOUT, $line."\n");
}

function fail(string $message): never
{
    fwrite(STDERR, "Gagal: {$message}\n");

    exit(1);
}
