<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

// Architecture policy: concrete business SQL inside InspectionEvidence belongs
// only to explicitly named MariaDb* adapters/owners.
$module = dirname(__DIR__, 2) . '/app/InspectionEvidence';
$offenders = [];
foreach (glob($module . '/*.php') ?: [] as $file) {
    $source = (string) file_get_contents($file);
    if (!preg_match('/\b(?:SELECT|INSERT|UPDATE|DELETE)\b/i', $source)) {
        continue;
    }
    if (!str_starts_with(basename($file), 'MariaDb')) {
        $offenders[] = basename($file);
    }
}
sort($offenders);
assertSameValue([], $offenders, 'InspectionEvidence SQL is owned only by MariaDb* files.');

echo "PASS: InspectionEvidence SQL owner policy\n";
