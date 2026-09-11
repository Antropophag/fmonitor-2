<?php
declare(strict_types=1);

defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', false);

require dirname(__DIR__) . '/app/autoload.php';
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';

try {
    return (new yii\console\Application(require dirname(__DIR__) . '/config/yii/console.php'))->run();
} catch (\Throwable) {
    $field = in_array('schema-migrate/run', $argv, true) ? 'reason' : 'error';
    echo json_encode(['ok' => false, $field => 'CONFIGURATION_INVALID'], JSON_THROW_ON_ERROR), "\n";
    return 64;
}
