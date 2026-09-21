<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Assets;
final class PreopeningAssetBundle extends \yii\web\AssetBundle
{
    public $sourcePath=null;public $basePath='@app/app/YiiRuntime/Assets';public $baseUrl='/pilot/assets';
    public $css=['shlz.css','pilot.css','preopening.css'];public $js=['preloader.js','navigation.js','preopening.js','template-offer.js'];public $jsOptions=['position'=>\yii\web\View::POS_END,'type'=>'module'];

    public function init(): void
    {
        parent::init();
        $this->css = ['shlz.css', AssetVersion::file('pilot.css'), 'preopening.css'];
        $this->js = ['preloader.js', AssetVersion::file('navigation.js'), 'preopening.js', 'template-offer.js'];
    }
}
