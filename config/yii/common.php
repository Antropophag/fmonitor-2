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
        'db' => static function(): yii\db\Connection {
            $host=getenv('FMONITOR_DB_HOST');$port=getenv('FMONITOR_DB_PORT');$name=getenv('FMONITOR_DB_NAME');$user=getenv('FMONITOR_DB_USER');$password=getenv('FMONITOR_DB_PASSWORD');
            if(!is_string($host)||$host===''||!is_string($port)||filter_var($port,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]])===false||!is_string($name)||$name===''||!is_string($user)||$user===''||!is_string($password)||$password==='')throw new RuntimeException('Database configuration unavailable.');
            return new yii\db\Connection(['dsn'=>'mysql:host='.$host.';port='.$port.';dbname='.$name,'username'=>$user,'password'=>$password,'charset'=>'utf8mb4']);
        },
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
