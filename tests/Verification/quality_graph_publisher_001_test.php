<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/** QUALITY-GRAPH-GOVERNANCE-001 v0.6, custom publisher RED. */

$root = dirname(__DIR__, 2);
$publisherPath = $root . '/.github/workflows/quality-graph-publish.yml';
$baselinePath = $root . '/.quality-graph/generated-publisher-v0.1.7.yml';

assertSameValue(true, is_file($publisherPath), 'SETUP_FAILURE: qg generate must provide publisher input');
assertSameValue(true, is_file($baselinePath), 'RED_ASSERTION: pinned generated publisher baseline must be retained for allowlisted comparison');

$publisher = (string) file_get_contents($publisherPath);
assertSameValue(1, preg_match_all('/^\s+workflow_run:$/m', $publisher), 'Publisher must expose workflow_run exactly once');
assertSameValue(0, preg_match('/issue_comment|^\s+command:|operation:\s*command|actions:\s*write|issues:\s*write|pull-requests:\s*write|actions\/checkout@/m', $publisher), 'RED_ASSERTION: publisher must remove comment command, extra writes and checkout');
assertSameValue(1, preg_match_all('/^\s+actions: read$/m', $publisher), 'Publisher must grant actions:read exactly once');
assertSameValue(1, preg_match_all('/^\s+contents: read$/m', $publisher), 'Publisher must grant contents:read exactly once');
assertSameValue(1, preg_match_all('/^\s+checks: write$/m', $publisher), 'Publisher must grant checks:write exactly once');
assertSameValue(1, preg_match_all('/uses: alchemmist\/quality-graph@caf5366a04ca01b230f1df5585d0fbd9693d7bef/', $publisher), 'Publisher must use the pinned runtime exactly once');
$operation = <<<'YAML'
operation: ${{ github.event.action == 'completed' && 'publish' || 'watch' }}
YAML;
assertSameValue(1, substr_count($publisher, $operation), 'Publisher must preserve upstream watch/publish projection');

echo "QUALITY-GRAPH-PUBLISHER-001 TESTS PASSED\n";
