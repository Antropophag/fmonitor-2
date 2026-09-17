<?php
declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/rapid-pilot/ObjectDetails.php';
require_once dirname(__DIR__,2).'/app/PilotHttp/PilotHttp.php';
require_once dirname(__DIR__,2).'/app/PilotHttp/PilotView.php';
require_once dirname(__DIR__,2).'/app/PilotHttp/PilotDocumentView.php';
require_once dirname(__DIR__,2).'/app/PilotHttp/ObjectListView.php';
require_once dirname(__DIR__,2).'/app/PilotHttp/ObjectCardView.php';

use FMonitor2\PilotHttp\HttpUser;
use FMonitor2\PilotHttp\ProductionObjectCardRenderer;
use FMonitor2\PilotHttp\ProductionObjectListRenderer;

// Public presentation seam: enhancing the card must preserve its complete identity.
// No database access is needed to observe identity preservation.
$previousPrefix=getenv('FMONITOR_PROCESS_TABLE_PREFIX');
putenv('FMONITOR_PROCESS_TABLE_PREFIX');
try {
    foreach (['77-000123','000123','АБ/77-001','77-<unsafe>&123'] as $registration) {
        $escaped=htmlspecialchars($registration,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $html='<html><body><span aria-current="page">Объект монтажа № 4512</span>'
            .'<header class="fm2-object-identity"><div><h1>Объект монтажа № 4512</h1><p>Москва, ул. Примерная, д. 10</p><span>Регистрационный номер '.$escaped.'</span></div><div><span class="shlz-status">Требуется распоряжение</span></div></header>'
            .'<section class="fm2-next-action"><h2>Выберите состав</h2></section>'
            .'<div class="fm2-object-dashboard"><main></main><aside></aside></div></body></html>';
        $result=RapidPilotObjectDetails::enhance($html,'/pilot/objects/4512');
        $dom=new DOMDocument();$previousErrors=libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>'.$result);
        libxml_clear_errors();libxml_use_internal_errors($previousErrors);
        $xpath=new DOMXPath($dom);
        assertSameValue($registration,$xpath->query('//div[@class="fm2-registration"]/strong')->item(0)?->textContent,'complete registration number survives presentation');
        assertSameValue('Рег. № '.$registration,$xpath->query('//span[@aria-current="page"]')->item(0)?->textContent,'breadcrumb preserves the same identity');
        assertSameValue(0,$xpath->query('//unsafe')->length,'registration markup remains escaped');
    }

    // OBJECT-IDENTITY-PRESENTATION-049: the common list and the identity header
    // use business identifiers, while the internal ID remains in exact routes.
    $user=new HttpUser(18,'ФКР','fixture@example.invalid',['objects.read']);
    $common=['address'=>'Москва, ул. Примерная, д. 10','entrance'=>'2','plannedStartDate'=>'2026-10-05','plannedFinishDate'=>'2026-12-20','status'=>'Требуется распоряжение'];
    $list=(new ProductionObjectListRenderer())->render($user,[
        ['id'=>4512,'registrationNumber'=>'77-000123','factoryNumber'=>'ZAV-456']+$common,
        ['id'=>9841,'registrationNumber'=>'','factoryNumber'=>null]+$common,
    ]);
    foreach(['href="/pilot/objects/4512"','href="/pilot/objects/9841"','<strong>77-000123</strong>','Заводской номер лифта ZAV-456','<strong>Регистрационный номер не указан</strong>','Заводской номер лифта не указан']as$expected){
        assertSameValue(true,str_contains($list,$expected),'INTENDED_RED: list identity: '.$expected);
    }
    foreach(['>№ 4512<','>№ 9841<','Системный ID','Legacy ID','по адресу, регномеру или заводскому номеру']as$forbidden){
        assertSameValue(false,str_contains($list,$forbidden),'list must not expose or promise: '.$forbidden);
    }

    $cardBase=$common+['order'=>null,'controlEngineer'=>null,'opened'=>false,'actualStartDate'=>null,'actualStartDateUnknownAtCutover'=>false,'openedAt'=>null,'openedByUserId'=>null,'activeCaseProvenance'=>null,'dataOrigin'=>'native','events'=>[],'hasPtoAct'=>false,'canPrepare'=>true];
    $card=(new ProductionObjectCardRenderer())->render($user,['id'=>4512,'registrationNumber'=>'77-000123','factoryNumber'=>'ZAV-456']+$cardBase);
    assertSameValue(true,str_contains($card,'<h1>Регистрационный номер 77-000123</h1>'),'card uses registration as primary identity');
    assertSameValue(true,str_contains($card,'Заводской номер лифта ZAV-456'),'card shows factory number as secondary identity');
    assertSameValue(true,str_contains($card,'href="/pilot/objects/4512/assignment-order/prepare"'),'card action retains exact internal route');
    foreach(['Объект монтажа № 4512','Системный ID','Legacy ID']as$forbidden)assertSameValue(false,str_contains($card,$forbidden),'card header/shell must hide: '.$forbidden);

    $missing=(new ProductionObjectCardRenderer())->render($user,['id'=>9841,'registrationNumber'=>null,'factoryNumber'=>'']+$cardBase);
    assertSameValue(true,str_contains($missing,'<h1>Регистрационный номер не указан</h1>'),'card explains absent registration number');
    assertSameValue(true,str_contains($missing,'Заводской номер лифта не указан'),'card explains absent factory number');
    assertSameValue(false,str_contains($missing,'Объект монтажа № 9841'),'card never substitutes internal ID');
    echo "PASS complete object registration identity\n";
} finally {
    putenv($previousPrefix===false?'FMONITOR_PROCESS_TABLE_PREFIX':'FMONITOR_PROCESS_TABLE_PREFIX='.$previousPrefix);
}
