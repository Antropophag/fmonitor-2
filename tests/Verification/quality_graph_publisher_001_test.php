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
assertSameValue('a5de72afaafb023a7f9fde24fc1be3872ea96dda1d16d23fb385e8fe58e2a8ee', hash_file('sha256', $baselinePath), 'Generated v0.1.7 comparison baseline must match the reviewed compiler output');
$expected = <<<'YAML'
# Repository-owned minimal publisher. Generated v0.1.7 baseline: .quality-graph/generated-publisher-v0.1.7.yml
---
name: Quality Graph Publisher
'on':
  workflow_run:
    workflows:
      - Quality Graph
    types:
      - requested
      - in_progress
      - completed
permissions: {}
concurrency:
  group: quality-graph-publish-${{ github.event.workflow_run.id }}
  cancel-in-progress: false
jobs:
  publish:
    name: Publish Quality Graph
    if: github.event.workflow_run.event == 'pull_request'
    runs-on: ubuntu-latest
    permissions:
      actions: read
      checks: write
      contents: read
    steps:
      - name: Publish trusted Quality Graph state
        uses: alchemmist/quality-graph@caf5366a04ca01b230f1df5585d0fbd9693d7bef
        with:
          operation: ${{ github.event.action == 'completed' && 'publish' || 'watch' }}
YAML;
assertSameValue($expected . "\n", $publisher, 'RED_ASSERTION: deployable publisher must equal the reviewed minimal workflow byte-for-byte');
assertSameValue(false, ($expected . "\nissue_comment: {}") === $publisher, 'Mutation: an extra trigger must not match');
assertSameValue(false, str_replace('actions: read', 'actions: write', $expected . "\n") === $publisher, 'Mutation: expanded permission must not match');
assertSameValue(false, ($expected . "\n- uses: actions/checkout@deadbeef") === $publisher, 'Mutation: extra execution step must not match');

echo "QUALITY-GRAPH-PUBLISHER-001 TESTS PASSED\n";
