<?php
declare(strict_types=1);
// YII-OPERATIONAL-UI-CONSISTENCY-001 A4 — active Yii choices use one public SHLZ Select composition.
require dirname(__DIR__).'/bootstrap.php';
$root=dirname(__DIR__,2);
require_once $root.'/vendor/autoload.php';
require_once $root.'/vendor/yiisoft/yii2/Yii.php';
$support=(string)file_get_contents($root.'/app/YiiRuntime/ViewSupport.php');
foreach(['shlz-' . 'sel' . 'ect','role="combobox"','role="listbox"','role="option"','aria-expanded','aria-selected','type="hidden"']as$needle)assertSameValue(true,str_contains(str_replace("' . \$choice . '",'select',$support),$needle),'INTENDED_RED shared SHLZ choice contract '.$needle);
$invalid=FMonitor2\YiiRuntime\ViewSupport::choice('pitmaterial','bad',[''=>'Не изменять','brick'=>'Кирпич'],'Материал шахты',['aria-invalid'=>'true','aria-describedby'=>'pitmaterial-error']);
assertSameValue(2,substr_count($invalid,'aria-invalid="true"'),'INTENDED_RED invalid state reaches visible combobox and native fallback');
assertSameValue(2,substr_count($invalid,'aria-describedby="pitmaterial-error"'),'INTENDED_RED field error association reaches both operable controls');
assertSameValue(false,preg_match('/<input[^>]+aria-invalid=/',$invalid)===1,'disabled hidden transport does not own validation semantics');
foreach(['objects.php','installers.php','users.php','otiz.php','object-card.php']as$file){$source=(string)file_get_contents($root.'/app/YiiRuntime/Views/'.$file);assertSameValue(false,str_contains($source,'Html::dropDownList'),'INTENDED_RED no native-only select '.$file);assertSameValue(true,str_contains($source,'ViewSupport::choice'),'INTENDED_RED shared select renderer '.$file);}
$navigation=(string)file_get_contents($root.'/app/YiiRuntime/Assets/navigation.js');
assertSameValue(true,str_contains($navigation,"import { enhanceSelects } from '/pilot/assets/shlz-behaviors.js'")&&str_contains($navigation,'enhanceSelects(document)'),'INTENDED_RED official SHLZ behavior imported and initialized');
foreach(['ShellAssetBundle.php','ObjectQueueAssetBundle.php','PreopeningAssetBundle.php','InstallerDirectoryAssetBundle.php']as$file){$bundle=(string)file_get_contents($root.'/app/YiiRuntime/Assets/'.$file);assertSameValue(true,str_contains($bundle,"'type'=>'module'")||str_contains($bundle,"'type' => 'module'"),'INTENDED_RED navigation module loading '.$file);}
$preopeningBundle=(string)file_get_contents($root.'/app/YiiRuntime/Assets/PreopeningAssetBundle.php');assertSameValue(true,str_contains($preopeningBundle,"AssetVersion::file('preopening.js')"),'preopening interaction asset is content-versioned with its JSON contract');
echo "PASS: YII-OPERATIONAL-UI-CONSISTENCY-001 shared SHLZ Select inventory\n";
