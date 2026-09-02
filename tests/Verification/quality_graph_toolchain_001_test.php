<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/** QUALITY-GRAPH-GOVERNANCE-001 v0.5, exact toolchain RED. */

$root = dirname(__DIR__, 2);
$declaration = $root . '/quality-graph.yml';
$project = $root . '/pyproject.toml';

assertSameValue(true, is_file($declaration), 'RED_ASSERTION: canonical Quality Graph declaration must exist');
assertSameValue(true, is_file($project), 'RED_ASSERTION: pinned Quality Graph Python project must exist');

$yaml = (string) file_get_contents($declaration);
$toml = (string) file_get_contents($project);
$runtime = 'alchemmist/quality-graph@caf5366a04ca01b230f1df5585d0fbd9693d7bef';

assertSameValue(1, preg_match_all('/^\s+action: ' . preg_quote($runtime, '/') . '$/m', $yaml), 'Runtime action must use the audited immutable v0.1.7 commit exactly once');
assertSameValue(0, preg_match('/alchemmist\/quality-graph@(?:main|v\d|0\.1)/', $yaml), 'Floating Quality Graph action refs are forbidden');
assertSameValue(1, preg_match_all('/^\s*"quality-graph-cli==0\.1\.7",$/m', $toml), 'CLI must be pinned exactly once to 0.1.7');
assertSameValue(1, preg_match_all('/^\s*"quality-graph-github==0\.1\.7",$/m', $toml), 'GitHub provider must be pinned exactly once to 0.1.7');

echo "QUALITY-GRAPH-TOOLCHAIN-001 TESTS PASSED\n";
