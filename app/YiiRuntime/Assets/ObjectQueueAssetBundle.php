<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Assets;

final class ObjectQueueAssetBundle extends \yii\web\AssetBundle
{
    public $sourcePath = null;
    public $basePath = '@app/app/YiiRuntime/Assets';
    public $baseUrl = '/pilot/assets';
    public $css = ['shlz.css','pilot.css'];
    public $js = ['preloader.js','navigation.js','object-queue.js','inspection-schedule.js'];
    public $jsOptions = ['position' => \yii\web\View::POS_END];
}
