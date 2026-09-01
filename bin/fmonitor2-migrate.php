<?php

declare(strict_types=1);

use FMonitor2\InstallationProcess\ProcessCommandCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;
use FMonitor2\InstallationProcess\BitrixWorkforceHistorySchemaMigration;
use FMonitor2\InstallationProcess\WorkforceCatalogSchemaMigration;
use FMonitor2\InstallationProcess\IdentityAccessSchemaMigration;
use FMonitor2\InstallationProcess\IdentityAccessDefinitionSchemaMigration;
use FMonitor2\InstallationProcess\DatabaseUnavailable;
use FMonitor2\InstallationProcess\CanonicalMigrationApplication;
use FMonitor2\InstallationProcess\ChecklistTemplateSchemaMigration;
use FMonitor2\InstallationProcess\InspectionEvidenceSchemaMigration;

spl_autoload_register(static function (string $class): void {
    $prefix = 'FMonitor2\\InstallationProcess\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = dirname(__DIR__) . '/app/InstallationProcess/'
        . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

/** @param array<string, mixed> $result */
function finishMigrationRunner(array $result, int $exitCode): never
{
    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), "\n";
    exit($exitCode);
}

$environmentNames = [
    'FMONITOR_DB_HOST',
    'FMONITOR_DB_PORT',
    'FMONITOR_DB_NAME',
    'FMONITOR_DB_USER',
    'FMONITOR_DB_PASSWORD',
    'FMONITOR_PROCESS_TABLE_PREFIX',
];
$environment = [];
foreach ($environmentNames as $name) {
    $value = getenv($name);
    if ($value === false) {
        finishMigrationRunner(['ok' => false, 'reason' => 'CONFIGURATION_INVALID'], 64);
    }
    $environment[$name] = $value;
}

$port = $environment['FMONITOR_DB_PORT'];
$tablePrefix = $environment['FMONITOR_PROCESS_TABLE_PREFIX'];
if ($environment['FMONITOR_DB_HOST'] === ''
    || $environment['FMONITOR_DB_NAME'] === ''
    || $environment['FMONITOR_DB_USER'] === ''
    || preg_match('/^[0-9]+$/D', $port) !== 1
    || (int) $port < 1
    || (int) $port > 65535
    || strlen($tablePrefix) > 25
    || preg_match('/^[A-Za-z0-9_]*$/D', $tablePrefix) !== 1
) {
    finishMigrationRunner(['ok' => false, 'reason' => 'CONFIGURATION_INVALID'], 64);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $connection = @new mysqli(
        $environment['FMONITOR_DB_HOST'],
        $environment['FMONITOR_DB_USER'],
        $environment['FMONITOR_DB_PASSWORD'],
        $environment['FMONITOR_DB_NAME'],
        (int) $port,
    );
    if (!$connection->set_charset('utf8mb4')) {
        throw new RuntimeException('Character set was not confirmed.');
    }
} catch (Throwable) {
    finishMigrationRunner(['ok' => false, 'reason' => 'DATABASE_UNAVAILABLE'], 69);
}

$migrations = [
    1 => ProductionProcessSchemaMigration::class,
    2 => WorkforceCatalogSchemaMigration::class,
    3 => ProcessUserCapabilitiesSchemaMigration::class,
    4 => ProcessCommandCapabilitiesSchemaMigration::class,
    5 => BitrixWorkforceHistorySchemaMigration::class,
    6 => IdentityAccessSchemaMigration::class,
    7 => ChecklistTemplateSchemaMigration::class,
    8 => InspectionEvidenceSchemaMigration::class,
];
$databasePreflight = static function () use ($connection, $tablePrefix): int {
    IdentityAccessDefinitionSchemaMigration::databaseCollation($connection);

    foreach (IdentityAccessDefinitionSchemaMigration::tables() as $identityTable) {
        if (FMonitor2\InstallationProcess\MariaDbSchemaInspector::tableExists(
            $connection,
            $tablePrefix . $identityTable,
        )) {
            return 6;
        }
    }

    return 1;
};
$outcome = CanonicalMigrationApplication::run(
    connection: $connection,
    tablePrefix: $tablePrefix,
    migrations: $migrations,
    databasePreflight: $databasePreflight,
);
try {
    $connection->close();
} catch (Throwable) {
}
finishMigrationRunner($outcome['result'], $outcome['exitCode']);
