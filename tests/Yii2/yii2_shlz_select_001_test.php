<?php
declare(strict_types=1);
// YII-OPERATIONAL-UI-CONSISTENCY-001 A4 — active Yii choices use one public SHLZ Select composition.
require dirname(__DIR__).'/bootstrap.php';
$root=dirname(__DIR__,2);
$support=(string)file_get_contents($root.'/app/YiiRuntime/ViewSupport.php');
foreach(['shlz-' . 'sel' . 'ect','role="combobox"','role="listbox"','role="option"','aria-expanded','aria-selected','type="hidden"']as$needle)assertSameValue(true,str_contains(str_replace("' . \$choice . '",'select',$support),$needle),'INTENDED_RED shared SHLZ choice contract '.$needle);
foreach(['objects.php','installers.php','users.php','otiz.php','object-card.php']as$file){$source=(string)file_get_contents($root.'/app/YiiRuntime/Views/'.$file);assertSameValue(false,str_contains($source,'Html::dropDownList'),'INTENDED_RED no native-only select '.$file);assertSameValue(true,str_contains($source,'ViewSupport::choice'),'INTENDED_RED shared select renderer '.$file);}
$navigation=(string)file_get_contents($root.'/app/YiiRuntime/Assets/navigation.js');
assertSameValue(true,str_contains($navigation,"import { enhanceSelects } from '/pilot/assets/shlz-behaviors.js'")&&str_contains($navigation,'enhanceSelects(document)'),'INTENDED_RED official SHLZ behavior imported and initialized');
foreach(['ShellAssetBundle.php','ObjectQueueAssetBundle.php','PreopeningAssetBundle.php','InstallerDirectoryAssetBundle.php']as$file){$bundle=(string)file_get_contents($root.'/app/YiiRuntime/Assets/'.$file);assertSameValue(true,str_contains($bundle,"'type'=>'module'")||str_contains($bundle,"'type' => 'module'"),'INTENDED_RED navigation module loading '.$file);}
echo "PASS: YII-OPERATIONAL-UI-CONSISTENCY-001 shared SHLZ Select inventory\n";
