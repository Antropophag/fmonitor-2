<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

// Characterization only: records current literals/mappings without approving
// legacy authentication or boolean AccessPolicy as the target contract.
$root = dirname(__DIR__, 2);
$policy = (string) file_get_contents($root . '/app/PilotHttp/AccessPolicy.php');
$e2e = (string) file_get_contents($root . '/app/PilotHttp/PilotE2ECoordinator.php');
$queue = (string) file_get_contents($root . '/rapid-pilot/ObjectQueue.php');

assertSameValue(1, preg_match("/OBJECTS_READ\s*=\s*'objects\.read'/", $policy), 'current objects.read literal');
assertSameValue(true, str_contains($e2e, "AccessPolicy::OBJECTS_READ"), 'current composed /pilot/objects mapping references objects.read');
assertSameValue(true, str_contains($queue, 'AccessPolicy::OBJECTS_READ'), 'current rapid-pilot queue mapping references objects.read');
assertSameValue(true, str_contains($policy, "u.activation_state='active'"), 'current AccessPolicy query checks active activation');
assertSameValue(true, str_contains($policy, 'r.status=1'), 'current AccessPolicy query checks active roles');
assertSameValue(true, str_contains($policy, 'in_array($permission,$permissions,true)'), 'current helper compares exact permission values');

echo "PASS: LOCAL-RBAC-AUTH-CONTRACT-001 current mapping characterization\n";
