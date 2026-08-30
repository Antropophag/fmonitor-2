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
    $otiz=(string)file_get_contents($root.'/rapid-pilot/Otiz.php');
    $calendar=(string)file_get_contents($root.'/rapid-pilot/Calendar.php');
    $objectDetails=(string)file_get_contents($root.'/rapid-pilot/ObjectDetails.php');
    $rapidRouter=(string)file_get_contents($root.'/rapid-pilot/router.php');
    $router=(string)file_get_contents($root.'/public/router.php');
    $failures=[];

    $require=static function(bool $condition,string $message)use(&$failures):void{if(!$condition)$failures[]=$message;};

$shlzLinkAt=strpos($view,'<link rel="stylesheet" href="/pilot/assets/shlz.css">');
$pilotLinkAt=strpos($view,'<link rel="stylesheet" href="\'.$pilotCssHref.\'">');
$require($shlzLinkAt!==false&&$pilotLinkAt!==false&&$shlzLinkAt<$pilotLinkAt,'shlz.css must load before pilot.css');
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
    [$prepare,'Загрузить распоряжение'],
    [$card,'Открыть работы'],
    [$card,'Загрузить оригинал'],
]as[$markup,$label])$require(preg_match('/class="shlz-button shlz-button--primary[^"]*"[^>]*>'.preg_quote($label,'/').'</u',$markup)===1,"primary action lacks shlz-button--primary: {$label}");

foreach(['Подготовить расчёт','Подтвердить расчёт','Отметить выплаты по срезу выполненными']as$label){$at=strpos($otiz,$label);$before=$at===false?'':substr($otiz,max(0,$at-700),700);$require($at!==false&&str_contains($before,'class="shlz-button shlz-button--primary"'),"OTIZ primary action lacks shlz-button--primary: {$label}");}
$require(str_contains($otiz,'class="fm2-breadcrumb-link"'),'OTIZ breadcrumbs must use the FMonitor compact link contract');
$require(str_contains($rapidRouter,"RapidPilotOtiz::matches"),'rapid-pilot router must expose the OTIZ surface');
$require(str_contains($otiz,'PilotView::document'),'OTIZ must reuse the canonical rapid-pilot shell');
$require(str_contains($calendar,'class="shlz-calendar-grid fm2-calendar-grid" data-shlz-calendar-grid'),'calendar must consume the public shlz Calendar Grid root');
$require(str_contains($calendar,'scope="row"')&&str_contains($calendar,'scope="col"')&&str_contains($calendar,'headers="calendar-row-'),'calendar must retain native table header relationships');
$require(str_contains($rapidRouter,'FMONITOR_SHLZ_UI_ROOT')&&str_contains($rapidRouter,'does not export Calendar Grid'),'calendar shlz-ui root must be configurable and diagnose a missing export');
$require(str_contains($rapidRouter,'RapidPilotCalendar::matches'),'rapid-pilot router must expose the calendar surface');
$require(!str_contains($css,'.shlz-calendar-grid {'),'pilot CSS must not redefine the Calendar Grid root contract');

$require(preg_match('/<a class="shlz-button/u',$view.$prepare.$card.$list.$shell)!==1,'navigation links must use the shlz Link contract, not Button classes');
$require(str_contains($css,'.fm2-check-page'),'the served rapid-pilot stylesheet must include the checklist surface');
$require(str_contains($css,'.fm2-object-workspace > .fm2-next-action { margin: 22px 22px 12px;'),'object-card current actions must share one inset geometry in every state');
$require(!str_contains($css,'.fm2-object-workspace > .fm2-next-action:has(.fm2-action-stack) {'),'current-action geometry must not depend on its child controls');
$require(str_contains($css,'block-size: 10px; margin-block-end: 12px;'),'work progress must remain a compact secondary indicator');
$require(str_contains($objectDetails,'<a class="shlz-button shlz-button--primary"$1>Загрузить распоряжение</a>'),'assignment-order upload must render as the primary action button');
$require(str_contains($objectDetails,'<label class="fm2-open-date" for="actualStartDate">'),'actual-start date must use the compact inline field composition');
$require(str_contains($css,'.fm2-next-action .fm2-inline-form:has(#actualStartDate) { display: flex;'),'actual-start date and open command must remain on one desktop row');
$require(str_contains($css,'.fm2-open-date .shlz-input { inline-size: 156px;')&&str_contains($css,'background: #fff; border: 1px solid var(--fm2-border); border-radius: 10px;'),'actual-start date must have one explicit white bounded surface');
$require(!str_contains($checklist,'data-complete-section'),'checklist sections must complete automatically without a redundant action');
$require(!str_contains($checklist,'data-action-dock')&&!str_contains($css,'.fm2-check-dock'),'checklist must not restore the floating action dock');
$require(str_contains($css,'.fm2-sidebar { position: fixed; z-index: 40; inset: auto 0 0;')&&str_contains($css,'padding-block-end: calc(64px + env(safe-area-inset-bottom))'),'mobile primary navigation must be fixed to the bottom without covering content');

    return $failures;
}

if(realpath((string)($_SERVER['SCRIPT_FILENAME']??''))===__FILE__){
    $failures=verifyRapidPilotVisualContract(dirname(__DIR__));
    if($failures!==[]){foreach($failures as$failure)fwrite(STDERR,"VISUAL_CONTRACT: {$failure}\n");exit(1);}
    fwrite(STDOUT,"Visual contract OK: shlz-ui ownership, Golos Text delivery, breadcrumbs, and primary actions verified.\n");
}
