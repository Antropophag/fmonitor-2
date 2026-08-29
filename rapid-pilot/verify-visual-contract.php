<?php

declare(strict_types=1);

/** @return list<string> */
function verifyRapidPilotVisualContract(string $root):array
{
    $css=(string)file_get_contents($root.'/rapid-pilot/pilot.css');
    $view=(string)file_get_contents($root.'/app/PilotHttp/PilotView.php');
    $prepare=(string)file_get_contents($root.'/app/PilotHttp/PrepareFormView.php');
    $card=(string)file_get_contents($root.'/app/PilotHttp/ObjectCardView.php');
    $list=(string)file_get_contents($root.'/app/PilotHttp/ObjectListView.php');
    $shell=(string)file_get_contents($root.'/app/PilotHttp/PilotShellView.php');
    $checklist=(string)file_get_contents($root.'/app/PilotHttp/ChecklistView.php');
    $router=(string)file_get_contents($root.'/public/router.php');
    $failures=[];

    $require=static function(bool $condition,string $message)use(&$failures):void{if(!$condition)$failures[]=$message;};

$require(strpos($view,'/pilot/assets/shlz.css')<strpos($view,'/pilot/assets/pilot.css'),'shlz.css must load before pilot.css');
$require(preg_match('/(?:^|})\s*\.shlz-(?:button|status)\s*(?:,|\{)/m',$css)!==1,'pilot.css must not redefine bare shlz button/status contracts');
$require(!str_contains($css,'.shlz-button:hover'),'pilot.css must not replace shlz button hover states');
$require(str_contains($view,'class="fm2-breadcrumb-link"'),'breadcrumbs must use the FMonitor compact link contract');
$require(!str_contains($view,'breadcrumb(array $links,string $current):string{$items=\'\';foreach($links as[$label,$href])$items.=\'<li><a class="shlz-link"'),'breadcrumbs must not inherit the 16px shlz Link role');

foreach([400,500,600]as$weight){
    foreach(['cyrillic','latin']as$subset){
        $file="golos-text-{$subset}-{$weight}-normal.woff2";
        $require(is_file($root.'/rapid-pilot/fonts/'.$file),"missing pinned Golos Text asset: {$file}");
        $require(str_contains($css,'/pilot/assets/fonts/'.$file),"missing @font-face source: {$file}");
        $require(str_contains($router,$file)||str_contains($router,'golos-text-(?:cyrillic|latin)-(?:400|500|600)-normal'),"font route does not allow: {$file}");
    }
}

foreach([
    [$prepare,'Сформировать распоряжение'],
    [$card,'Открыть работы'],
    [$card,'Сохранить номер'],
]as[$markup,$label])$require(preg_match('/class="shlz-button shlz-button--primary[^"]*"[^>]*>'.preg_quote($label,'/').'</u',$markup)===1,"primary action lacks shlz-button--primary: {$label}");

$require(preg_match('/<a class="shlz-button/u',$view.$prepare.$card.$list.$shell)!==1,'navigation links must use the shlz Link contract, not Button classes');
$require(str_contains($css,'.fm2-check-page'),'the served rapid-pilot stylesheet must include the checklist surface');
$require(str_contains($checklist,'shlz-button--primary fm2-complete-section'),'checklist primary actions must use shlz-ui primary modifiers');
$require(!str_contains($checklist,'data-action-dock')&&!str_contains($css,'.fm2-check-dock'),'checklist must not restore the floating action dock');
$require(str_contains($css,'.fm2-sidebar { position: fixed; z-index: 40; inset: auto 0 0;')&&str_contains($css,'padding-block-end: calc(64px + env(safe-area-inset-bottom))'),'mobile primary navigation must be fixed to the bottom without covering content');

    return $failures;
}

if(realpath((string)($_SERVER['SCRIPT_FILENAME']??''))===__FILE__){
    $failures=verifyRapidPilotVisualContract(dirname(__DIR__));
    if($failures!==[]){foreach($failures as$failure)fwrite(STDERR,"VISUAL_CONTRACT: {$failure}\n");exit(1);}
    fwrite(STDOUT,"Visual contract OK: shlz-ui ownership, Golos Text delivery, breadcrumbs, and primary actions verified.\n");
}
