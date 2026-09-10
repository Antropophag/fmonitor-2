<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Assets;
final class UserAccessAssetBundle extends \yii\web\AssetBundle
{
    public $baseUrl='/pilot/assets';
    public $js=['users.js'];
    public $depends=[ShellAssetBundle::class];
}
