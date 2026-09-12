<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/YiiRuntime/Commands/SchemaMigrateController.php';
require_once dirname(__DIR__, 2) . '/app/YiiRuntime/Commands/CaseImportController.php';
require_once dirname(__DIR__, 2) . '/app/YiiRuntime/Commands/WorkforceSyncController.php';
class_exists(FMonitor2\InstallationProcess\MariaDbWorkforceSynchronization::class);

return yii\helpers\ArrayHelper::merge(require __DIR__ . '/common.php', [
    'controllerNamespace' => 'FMonitor2\\YiiRuntime\\Commands',
    'controllerMap' => [
        'schema-migrate' => FMonitor2\YiiRuntime\Commands\SchemaMigrationCommand::class,
        'case-import' => FMonitor2\YiiRuntime\Commands\CaseImportCommand::class,
        'workforce-sync' => FMonitor2\YiiRuntime\Commands\WorkforceSyncController::class,
    ],
    'components' => ['db' => null],
]);
