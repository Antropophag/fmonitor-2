<?php
declare(strict_types=1);

use FMonitor2\YiiRuntime\SafeErrorHandler;
use FMonitor2\YiiRuntime\WebResponse;
use FMonitor2\YiiRuntime\ReliableSession;
use FMonitor2\IdentityAccess\YiiCanonicalAccessChecker;
use FMonitor2\IdentityAccess\YiiLocalIdentity;
use FMonitor2\IdentityAccess\MariaDbYiiLocalIdentityStore;
use yii\helpers\ArrayHelper;

return ArrayHelper::merge(require __DIR__ . '/common.php', [
    'controllerNamespace' => 'FMonitor2\\YiiRuntime\\Controllers',
    'components' => [
        'request' => [
            'cookieValidationKey' => getenv('FMONITOR_YII_COOKIE_VALIDATION_KEY') ?: '',
            'scriptUrl' => '/yii.php',
            'baseUrl' => '',
        ],
        'db' => static function(): yii\db\Connection {$host=getenv('FMONITOR_DB_HOST');$port=getenv('FMONITOR_DB_PORT');$name=getenv('FMONITOR_DB_NAME');$user=getenv('FMONITOR_DB_USER');$password=getenv('FMONITOR_DB_PASSWORD');if(!is_string($host)||$host===''||!is_string($port)||filter_var($port,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]])===false||!is_string($name)||$name===''||!is_string($user)||$user===''||!is_string($password)||$password==='')throw new \RuntimeException('Database configuration unavailable.');return new yii\db\Connection(['dsn'=>'mysql:host='.$host.';port='.$port.';dbname='.$name,'username'=>$user,'password'=>$password,'charset'=>'utf8mb4']);},
        'localIdentity' => [
            'class' => MariaDbYiiLocalIdentityStore::class,
            'tablePrefix' => getenv('FMONITOR_PROCESS_TABLE_PREFIX') ?: '',
        ],
        'canonicalAccess' => ['class' => YiiCanonicalAccessChecker::class],
        'session' => [
            'class' => ReliableSession::class,
            'name' => (static function(): string {$name=getenv('FMONITOR_YII_SESSION_COOKIE');return is_string($name)&&preg_match('/^[A-Za-z][A-Za-z0-9_]{1,63}$/D',$name)===1?$name:'fm2yii';})(),
            'savePath' => getenv('FMONITOR_YII_SESSION_PATH') ?: '/home/fmonitor/.local/state/fmonitor2/yii-sessions',
            'timeout' => 604800,
            'useStrictMode' => true,
            'cookieParams' => [
                'lifetime' => 604800, 'path' => '/pilot', 'httponly' => true,
                'secure' => getenv('FMONITOR_TRUSTED_REQUEST_SCHEME') === 'https',
                'sameSite' => yii\web\Cookie::SAME_SITE_STRICT,
            ],
        ],
        'user' => [
            'identityClass' => YiiLocalIdentity::class,
            'enableAutoLogin' => false,
            'enableSession' => true,
            'loginUrl' => ['/auth/login'],
            'accessChecker' => 'canonicalAccess',
        ],
        'response' => [
            'on beforeSend' => WebResponse::closeAndSecure(...),
            'on afterPrepare' => WebResponse::head(...),
        ],
        'errorHandler' => ['class' => SafeErrorHandler::class],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => true,
            'rules' => [
                'health/live' => 'health/live',
                'health/ready' => 'health/ready',
                'GET pilot/login' => 'auth/login',
                'POST pilot/login' => 'auth/login',
                'POST pilot/logout' => 'auth/logout',
                'GET pilot/logout' => 'auth/logout',
                'GET pilot/admin/roles' => 'roles/index',
                'GET pilot/assets/<name:(shlz|pilot)>.css' => 'pilot-asset/css',
            ],
        ],
    ],
]);
