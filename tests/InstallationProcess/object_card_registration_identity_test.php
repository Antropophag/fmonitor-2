<?php
declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/rapid-pilot/ObjectDetails.php';

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
    echo "PASS complete object registration identity\n";
} finally {
    putenv($previousPrefix===false?'FMONITOR_PROCESS_TABLE_PREFIX':'FMONITOR_PROCESS_TABLE_PREFIX='.$previousPrefix);
}
