<?php
declare(strict_types=1);

return [
    'id' => 'fmonitor2',
    'basePath' => dirname(__DIR__, 2),
    'runtimePath' => getenv('FMONITOR_YII_RUNTIME_PATH') ?: sys_get_temp_dir() . '/fmonitor2-yii',
    'language' => 'ru-RU',
    'timeZone' => 'Europe/Moscow',
    'bootstrap' => ['log'],
    'components' => [
        'log' => [
            'traceLevel' => 0,
            'targets' => [[
                'class' => yii\log\FileTarget::class,
                'logFile' => '/dev/stderr',
                'enableRotation' => false,
                'levels' => ['error', 'warning'],
                'logVars' => [],
                'prefix' => static fn(): string => '',
            ]],
        ],
    ],
];
