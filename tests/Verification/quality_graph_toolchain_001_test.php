<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

/** QUALITY-GRAPH-GOVERNANCE-001 v0.5, exact toolchain RED. */

function qgtPinsValid(string $yaml, string $toml): bool
{
    preg_match_all('/alchemmist\/quality-graph@([^\s"\']+)/', $yaml, $runtimeMatches);
    preg_match_all('/"(quality-graph-(?:cli|github)[^"]*)"/', $toml, $packageMatches);
    $packages = $packageMatches[1];
    sort($packages, SORT_STRING);
    $allDeclarations = $yaml . "\n" . $toml;
    $withoutApproved = str_replace(['quality-graph-cli==0.1.7', 'quality-graph-github==0.1.7'], '', $allDeclarations, $approvedOccurrences);
    return $runtimeMatches[1] === ['caf5366a04ca01b230f1df5585d0fbd9693d7bef']
        && $packages === ['quality-graph-cli==0.1.7', 'quality-graph-github==0.1.7']
        && substr_count($yaml, 'python -m pip install quality-graph-cli==0.1.7 quality-graph-github==0.1.7') === 1
        && $approvedOccurrences === 4
        && preg_match('/quality-graph-(?:cli|github)/', $withoutApproved) === 0;
}

$root = dirname(__DIR__, 2);
$declaration = $root . '/quality-graph.yml';
$project = $root . '/pyproject.toml';

assertSameValue(true, is_file($declaration), 'RED_ASSERTION: canonical Quality Graph declaration must exist');
assertSameValue(true, is_file($project), 'RED_ASSERTION: pinned Quality Graph Python project must exist');

$yaml = (string) file_get_contents($declaration);
$toml = (string) file_get_contents($project);
assertSameValue(true, qgtPinsValid($yaml, $toml), 'RED_ASSERTION: toolchain occurrence sets must contain only the audited exact release set');
assertSameValue(false, qgtPinsValid($yaml . "\n# alchemmist/quality-graph@master\n", $toml), 'Mutation: an additional floating runtime ref must be rejected');
assertSameValue(false, qgtPinsValid($yaml, $toml . "\n# \"quality-graph-cli>=0.1\"\n"), 'Mutation: an additional ranged package ref must be rejected');
assertSameValue(false, qgtPinsValid($yaml, str_replace('quality-graph-github==0.1.7', 'quality-graph-github==0.1.6', $toml)), 'Mutation: a mixed provider version must be rejected');
assertSameValue(false, qgtPinsValid($yaml . "\n# quality-graph-cli>=0.1\n", $toml), 'Mutation: an additional ranged runner package must be rejected');

echo "QUALITY-GRAPH-TOOLCHAIN-001 TESTS PASSED\n";
