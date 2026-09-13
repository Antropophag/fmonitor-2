<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/YiiRuntime/Commands/SchemaMigrateController.php';
require_once dirname(__DIR__, 2) . '/app/YiiRuntime/Commands/CaseImportController.php';
require_once dirname(__DIR__, 2) . '/app/YiiRuntime/Commands/WorkforceSyncController.php';
require_once dirname(__DIR__, 2) . '/app/RuntimeRestore/StandBackupFilesystem.php';
require_once dirname(__DIR__, 2) . '/app/RuntimeRestore/StandBackupApplication.php';
require_once dirname(__DIR__, 2) . '/app/YiiRuntime/Commands/StandBackupController.php';
class_exists(FMonitor2\InstallationProcess\MariaDbWorkforceSynchronization::class);

return yii\helpers\ArrayHelper::merge(require __DIR__ . '/common.php', [
    'controllerNamespace' => 'FMonitor2\\YiiRuntime\\Commands',
    'controllerMap' => [
        'schema-migrate' => FMonitor2\YiiRuntime\Commands\SchemaMigrationCommand::class,
        'case-import' => FMonitor2\YiiRuntime\Commands\CaseImportCommand::class,
        'workforce-sync' => FMonitor2\YiiRuntime\Commands\WorkforceSyncController::class,
        'stand-backup' => FMonitor2\YiiRuntime\Commands\StandBackupController::class,
    ],
    'components' => ['db' => null],
]);
