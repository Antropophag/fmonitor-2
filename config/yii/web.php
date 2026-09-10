<?php
declare(strict_types=1);

use FMonitor2\YiiRuntime\SafeErrorHandler;
use FMonitor2\YiiRuntime\WebResponse;
use FMonitor2\YiiRuntime\ReliableSession;
use FMonitor2\IdentityAccess\YiiCanonicalAccessChecker;
use FMonitor2\IdentityAccess\YiiLocalIdentity;
use FMonitor2\IdentityAccess\MariaDbYiiLocalIdentityStore;
use FMonitor2\IdentityAccess\YiiUserAccess;
use yii\helpers\ArrayHelper;

return ArrayHelper::merge(require __DIR__ . '/common.php', [
    'controllerNamespace' => 'FMonitor2\\YiiRuntime\\Controllers',
    'components' => [
        'request' => [
            'cookieValidationKey' => getenv('FMONITOR_YII_COOKIE_VALIDATION_KEY') ?: '',
            'scriptUrl' => '/yii.php',
            'baseUrl' => '',
            'enableCsrfCookie' => false,
        ],
        'localIdentity' => [
            'class' => MariaDbYiiLocalIdentityStore::class,
            'tablePrefix' => getenv('FMONITOR_PROCESS_TABLE_PREFIX') ?: '',
            'identityKey' => getenv('FMONITOR_YII_IDENTITY_KEY') ?: '',
        ],
        'canonicalAccess' => ['class' => YiiCanonicalAccessChecker::class],
        'userAccess' => ['class'=>YiiUserAccess::class,'tablePrefix'=>getenv('FMONITOR_PROCESS_TABLE_PREFIX')?:''],
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
            'cache' => false,
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
                'GET,HEAD pilot/objects' => 'object-queue/index',
                'GET,HEAD pilot/objects/<id:[1-9]\\d*>' => 'object-card/view',
                'GET,HEAD pilot/objects/<id:[1-9]\\d*>/assignment-order/prepare' => 'object-card/prepare',
                'POST pilot/objects/<id:[1-9]\\d*>/assignment-order/prepare' => 'object-card/gone',
                'GET,HEAD,POST pilot/objects/<id:[1-9]\\d*>/assignment-order/selection' => 'selection/index',
                'GET,HEAD pilot/objects/<id:[1-9]\\d*>/assignment-order/installers' => 'selection/installers',
                'POST pilot/objects/<id:[1-9]\\d*>/assignment-orders/<orderId:[1-9]\\d*>/template' => 'assignment-order-template/generate',
                'GET,HEAD pilot/objects/<id:[1-9]\\d*>/assignment-orders/<orderId:[1-9]\\d*>/originals/submit' => 'original/form',
                'POST pilot/objects/<id:[1-9]\\d*>/assignment-orders/<orderId:[1-9]\\d*>/originals' => 'original/upload',
                'GET,HEAD pilot/objects/<id:[1-9]\\d*>/assignment-orders/<orderId:[1-9]\\d*>/originals/history' => 'original-history/index',
                'GET,HEAD pilot/objects/<id:[1-9]\\d*>/assignment-orders/<orderId:[1-9]\\d*>/originals/<revisionId:[A-Za-z0-9][A-Za-z0-9._:-]{0,79}>/download' => 'original-history/download',
                'GET,HEAD,POST pilot/objects/<id:[1-9]\\d*>/execution' => 'execution/index',
                'GET,HEAD,POST pilot/objects/<id:[1-9]\\d*>/assignment-orders/<orderId:[1-9]\\d*>/artifacts/<artifact:(?:order|appendix|signed_original)>' => 'object-card/gone',
                'GET,HEAD,POST pilot/objects/<id:[1-9]\\d*>/assignment-orders/<orderId:[1-9]\\d*>/registration' => 'object-card/gone',
                'GET,HEAD,POST pilot/objects/<id:[1-9]\\d*>/control-engineer' => 'object-card/gone',
                'GET,HEAD,POST pilot/objects/<id:[1-9]\\d*>/open' => 'object-card/gone',
                'pilot/objects/<id:[1-9]\\d*>' => 'object-card/method',
                'pilot/objects/<id:[1-9]\\d*>/assignment-order/selection' => 'selection/index-method',
                'pilot/objects/<id:[1-9]\\d*>/assignment-order/installers' => 'selection/installers-method',
                'pilot/objects/<id:[1-9]\\d*>/assignment-orders/<orderId:[1-9]\\d*>/template' => 'assignment-order-template/method',
                'pilot/objects/<id:[1-9]\\d*>/assignment-orders/<orderId:[1-9]\\d*>/originals/submit' => 'original/form-method',
                'pilot/objects/<id:[1-9]\\d*>/assignment-orders/<orderId:[1-9]\\d*>/originals' => 'original/upload-method',
                'pilot/objects/<id:[1-9]\\d*>/assignment-orders/<orderId:[1-9]\\d*>/originals/history' => 'original-history/index-method',
                'pilot/objects/<id:[1-9]\\d*>/assignment-orders/<orderId:[1-9]\\d*>/originals/<revisionId:[A-Za-z0-9][A-Za-z0-9._:-]{0,79}>/download' => 'original-history/download-method',
                'pilot/objects/<id:[1-9]\\d*>/execution' => 'execution/method',
                'GET,HEAD,POST pilot/objects/<id:[1-9]\\d*>/inspection-schedule' => 'object-queue/schedule',
                'GET pilot/admin/roles' => 'roles/index',
                'GET,HEAD pilot/admin/users' => 'user-access/index',
                'GET,HEAD pilot/users' => 'user-access/index',
                'GET,HEAD,POST pilot/admin/users/invite' => 'user-access/invite',
                'GET,HEAD,POST pilot/admin/users/<id:[1-9]\\d*>/invitation' => 'user-access/reissue',
                'GET,HEAD,POST pilot/admin/users/<id:[1-9]\\d*>/roles' => 'user-access/role',
                'GET,HEAD,POST pilot/admin/users/<id:[1-9]\\d*>/roles/<roleId:[1-9]\\d*>' => 'user-access/role',
                'GET,HEAD,POST pilot/admin/users/<id:[1-9]\\d*>/status' => 'user-access/status',
                'GET,POST pilot/activate' => 'user-access/activate',
                'GET pilot/otiz' => 'otiz-settlement/index',
                'GET pilot/otiz/objects' => 'otiz-settlement/objects',
                'GET pilot/otiz/payments' => 'otiz-settlement/payments',
                'GET pilot/otiz/history' => 'otiz-settlement/history',
                'GET pilot/otiz/snapshots/<id:\\d+>' => 'otiz-settlement/snapshot',
                'GET pilot/otiz/snapshots/<id:\\d+>/export.xlsx' => 'otiz-settlement/export',
                'POST pilot/otiz/snapshots/<id:\\d+>/closures' => 'otiz-settlement/discipline',
                'POST pilot/otiz/snapshots/<id:\\d+>/payments/complete' => 'otiz-settlement/complete',
                'POST pilot/otiz/closures/<id:[1-9]\\d*>/reverse' => 'otiz-settlement/reverse',
                ...(require __DIR__ . '/assets.php'),
            ],
        ],
    ],
]);
