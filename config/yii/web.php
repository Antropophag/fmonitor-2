<?php
declare(strict_types=1);

use FMonitor2\YiiRuntime\SafeErrorHandler;
use FMonitor2\YiiRuntime\WebResponse;
use yii\helpers\ArrayHelper;

return ArrayHelper::merge(require __DIR__ . '/common.php', [
    'controllerNamespace' => 'FMonitor2\\YiiRuntime\\Controllers',
    'components' => [
        'request' => [
            'cookieValidationKey' => getenv('FMONITOR_YII_COOKIE_VALIDATION_KEY') ?: '',
            'scriptUrl' => '/yii.php',
            'baseUrl' => '',
        ],
        'response' => [
            'on beforeSend' => WebResponse::secure(...),
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
            ],
        ],
    ],
]);
