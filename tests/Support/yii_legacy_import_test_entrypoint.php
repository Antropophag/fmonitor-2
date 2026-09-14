<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/autoload.php';

if (array_slice($argv, 1) !== ['legacy-import/run', '--interactive=0']) {
    echo "{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n";
    exit(64);
}
$outcome = FMonitor2\YiiRuntime\LegacyImportConsole::run();
echo json_encode($outcome['result'], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
exit($outcome['exitCode']);
