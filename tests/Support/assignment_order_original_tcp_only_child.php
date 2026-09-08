<?php

declare(strict_types=1);
// ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001 v0.7: synthetic endpoint probe.
require_once dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__, 2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
require_once dirname(__DIR__, 2).'/app/AssignmentOrderOriginal/MariaDbOriginalFreshTerminalReaderFactory.php';
use FMonitor2\AssignmentOrderOriginal as O;

if (count($argv) !== 5 || ini_get('mysqli.default_socket') !== $argv[2]) exit(41);
fwrite(STDOUT, "SETTING_OK\n");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
if ($argv[1] === 'direct') {
    try { $db = @new mysqli('localhost', 'synthetic', 'synthetic-only', 'synthetic', 23306); $db->close(); }
    catch (Throwable) { fwrite(STDOUT, "CONNECT_FAILED\n"); exit(0); }
    exit(42);
}
$factory = new O\AssignmentOrderOriginalMariaDbFreshTerminalReaderFactory(
    new O\AssignmentOrderOriginalFreshReaderConfig($argv[3], 23306, 'synthetic', 'synthetic', $argv[4], 'probe_'));
$result = $factory->open();
if ($result->status !== O\AssignmentOrderOriginalFreshReaderOpenStatus::UNAVAILABLE || $result->reader !== null) {
    $result->reader?->close(); exit(43);
}
fwrite(STDOUT, "UNAVAILABLE\n");
