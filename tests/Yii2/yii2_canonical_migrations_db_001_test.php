<?php
declare(strict_types=1);
/** YII2-CANONICAL-MIGRATIONS-001 A3/A4: complete migration oracle through bin/yii. */
putenv('FMONITOR_TEST_MIGRATION_ENTRYPOINT=yii');
require dirname(__DIR__) . '/InstallationProcess/production_migration_runner_001_test.php';
