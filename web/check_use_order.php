<?php

/**
 * check_use_order.php
 *
 * Usage:
 *   php check_use_order.php [--fix|--check] [--staged] [paths...]
 *   - If no paths given, defaults to: app Modules routes tests config database bootstrap resources packages
 *   - If a single file path is given, only that file is processed.
 *   - --fix   : rewrite files in place (default if no flag given)
 *   - --check : do not write; exit 1 if any file would change
 *   - --staged: only process staged files (overrides paths)
 *
 * Rule:
 *   - Group vendor imports first (Illuminate\, Laravel\, Symfony\, Psr\, Carbon\, GuzzleHttp\, etc.),
 *     then dev imports (App\, Modules\, Custom\).
 *   - Alphabetical inside each group.
 *   - Keep one blank line around the import block.
 */
ini_set('display_errors', '1');
error_reporting(E_ALL);

/** Determine if a "dev" namespace (your own code) */
function is_dev_namespace(string $line): bool {
    return (bool) preg_match('/^use\s+(App|Modules|Custom)\\\\/u', $line);
}

/** Normalize line endings to match the original file */
function detect_eol(string $content): string {
    if (strpos($content, "\r\n") !== false) {
        return "\r\n";
    }
    if (strpos($content, "\r") !== false) {
        return "\r";
    }

    return "\n";
}

/** Sort and rewrite a single file; returns [changed(bool), newContent(string)] */
function sort_uses_in_content(string $content): array {
    $eol = detect_eol($content);
    // Normalize to \n for processing
    $norm  = str_replace(["\r\n", "\r"], "\n", $content);
    $lines = explode("\n", $norm);

    $beforeUse = [];
    $useLines  = [];
    $afterUse  = [];

    $state = 'before_use';
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($state === 'before_use') {
            if ($trimmed === '' || str_starts_with($trimmed, '<?php') || str_starts_with($trimmed, 'namespace ') || str_starts_with($trimmed, '/**') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '*/')) {
                $beforeUse[] = $line;

                continue;
            }
            if (preg_match('/^use\s+.*;$/u', $trimmed)) {
                $state      = 'in_use';
                $useLines[] = $trimmed;

                continue;
            }
            $beforeUse[] = $line;
        } elseif ($state === 'in_use') {
            if ($trimmed === '' || preg_match('/^use\s+.*;$/u', $trimmed)) {
                if ($trimmed !== '') {
                    $useLines[] = $trimmed;
                }
            } else {
                $state      = 'after_use';
                $afterUse[] = $line;
            }
        } else {
            $afterUse[] = $line;
        }
    }

    if (!$useLines) {
        return [false, $content]; // nothing to do
    }

    // classify
    $vendorUses = [];
    $devUses    = [];
    foreach ($useLines as $line) {
        if (is_dev_namespace($line)) {
            $devUses[] = $line;
        } else {
            $vendorUses[] = $line;
        }
    }
    sort($vendorUses, SORT_NATURAL | SORT_FLAG_CASE);
    sort($devUses, SORT_NATURAL | SORT_FLAG_CASE);

    // build new block
    $sortedUses = [];
    if ($vendorUses) {
        $sortedUses = array_merge($sortedUses, $vendorUses);
    }
    if ($vendorUses && $devUses) {
        $sortedUses[] = '';
    }
    if ($devUses) {
        $sortedUses = array_merge($sortedUses, $devUses);
    }

    // ensure exactly one blank line around the block
    // trim trailing blank lines from beforeUse
    while ($beforeUse && trim(end($beforeUse)) === '') {
        array_pop($beforeUse);
    }
    // trim leading blank lines from afterUse
    while ($afterUse && trim($afterUse[0]) === '') {
        array_shift($afterUse);
    }

    $outputLines = array_merge($beforeUse, [''], $sortedUses, [''], $afterUse);
    $new         = implode("\n", $outputLines);
    if (!str_ends_with($new, "\n")) {
        $new .= "\n";
    }
    // restore original EOL
    $new = str_replace("\n", $eol, $new);

    $changed = ($new !== $content);

    return [$changed, $new];
}

function is_php_file(string $p): bool {
    return (bool) preg_match('/\.php$/i', $p) && !str_contains($p, 'vendor/') && !str_contains($p, 'storage/');
}

function list_all_php_files(array $roots): array {
    $files = [];
    foreach ($roots as $r) {
        if (is_file($r) && is_php_file($r)) {
            $files[] = $r;

            continue;
        }
        if (!is_dir($r)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($r, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            $p = str_replace('\\', '/', $f->getPathname());
            if (is_php_file($p)) {
                $files[] = $p;
            }
        }
    }
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    return $files;
}

function list_staged_php_files(): array {
    @exec('git diff --name-only --cached --diff-filter=ACMRTUXB', $out, $code);
    if ($code !== 0) {
        return [];
    }
    $files = [];
    foreach ($out as $p) {
        $p = trim($p);
        if ($p !== '' && is_file($p) && is_php_file($p)) {
            $files[] = $p;
        }
    }
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    return $files;
}

// ---- CLI ----
$opts      = getopt('', ['fix', 'check', 'staged']);
$modeFix   = isset($opts['fix']);
$modeCheck = isset($opts['check']);
$staged    = isset($opts['staged']);
if (!$modeFix && !$modeCheck) {
    $modeFix = true;
} // default

$paths = [];
foreach ($argv as $a) {
    if ($a === $argv[0]) {
        continue;
    }
    if ($a === '--fix' || $a === '--check' || $a === '--staged') {
        continue;
    }
    $paths[] = $a;
}
if ($staged) {
    $targets = list_staged_php_files();
} else {
    if (!$paths) {
        $paths = ['app', 'Modules', 'routes', 'tests', 'config', 'database', 'bootstrap', 'resources', 'packages'];
    }
    $targets = list_all_php_files($paths);
}

if (!$targets) {
    echo "No PHP files matched.\n";
    exit(0);
}

$changedFiles = [];
foreach ($targets as $file) {
    $orig = file_get_contents($file);
    if ($orig === false) {
        fwrite(STDERR, "Cannot read $file\n");
        exit(2);
    }
    [$changed, $new] = sort_uses_in_content($orig);
    if ($changed) {
        if ($modeFix) {
            file_put_contents($file, $new);
            echo "✅ Fixed: $file\n";
        } else {
            $changedFiles[] = $file;
        }
    }
}

if ($modeCheck) {
    if ($changedFiles) {
        echo "Imports order not normalized in:\n";
        foreach ($changedFiles as $f) {
            echo " - $f\n";
        }
        echo "Run: php check_use_order.php --fix\n";
        exit(1);
    }
    echo "OK (no changes needed).\n";
} else {
    echo "Done (fixed where needed).\n";
}
