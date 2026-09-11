<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/YiiRuntime/Commands/SchemaMigrateController.php';

return yii\helpers\ArrayHelper::merge(require __DIR__ . '/common.php', [
    'controllerNamespace' => 'FMonitor2\\YiiRuntime\\Commands',
    'controllerMap' => ['schema-migrate' => FMonitor2\YiiRuntime\Commands\SchemaMigrationCommand::class],
    'components' => ['db' => null],
]);
