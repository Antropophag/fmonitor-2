<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$root = dirname(__DIR__, 2);
$productionRoots = ['public', 'config/yii', 'app/YiiRuntime', 'app/Runtime'];
$forbidden = [
    'rapid-pilot/' => 'legacy PHP source path',
    'RapidPilotLocalAuth' => 'legacy authentication owner',
    'fm2auth_' => 'legacy session namespace',
    'rapid-pilot/router.php' => 'legacy HTTP composition',
];
$violations = [];

foreach ($productionRoots as $relativeRoot) {
    $absoluteRoot = $root . '/' . $relativeRoot;
    $paths = is_file($absoluteRoot)
        ? [new SplFileInfo($absoluteRoot)]
        : new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absoluteRoot, FilesystemIterator::SKIP_DOTS));
    foreach ($paths as $path) {
        if (!$path->isFile()) continue;
        $relative = substr($path->getPathname(), strlen($root) + 1);
        $source = (string) file_get_contents($path->getPathname());
        foreach ($forbidden as $needle => $label) {
            if (str_contains($source, $needle)) $violations[] = "{$relative}: {$label} ({$needle})";
        }
    }
}

sort($violations);
assertSameValue([], $violations, 'INTENDED_RED: production dependency frontier excludes legacy runtime paths, symbols and session namespace');
echo "PASS: YII2-PRODUCTION-WEB-CUTOVER-001 production dependency frontier\n";
