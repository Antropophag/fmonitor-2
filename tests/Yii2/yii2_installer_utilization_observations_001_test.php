<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/PreopeningFixture.php';

use FMonitor2\Workforce\InstallerUtilizationObservationSchemaMigration as Schema;
use FMonitor2\Workforce\MariaDbInstallerUtilization;
use FMonitor2\Workforce\MariaDbInstallerUtilizationObservations as Observations;

$f=null;$yii=null;
try {
    $f=new PreopeningFixture(dirname(__DIR__,2));$p=$f->p;
    Schema::apply($f->db,$p);
    foreach([1,2,5]as$role)$f->insert($p.'fm2_pilot_role_permissions',['role_id'=>$role,'permission'=>'installers.read']);
    $yii=new yii\db\Connection(['dsn'=>'mysql:host='.(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1').';port='.(getenv('FMONITOR_TEST_DB_PORT')?:23306).';dbname='.$f->database,'username'=>$f->dmlUser,'password'=>$f->dmlPassword,'charset'=>'utf8mb4']);
    $yii->open();$owner=new Observations($f->db,$p,new MariaDbInstallerUtilization($yii,$p,$p));
    $first=new DateTimeImmutable('2026-08-14');
    for($i=0;$i<43;$i++){$date=$first->modify("+$i day")->format('Y-m-d');$owner->capture($date,$date.'T03:17:00+03:00');}
    $f->start(['FMONITOR_NOW'=>'2026-09-25T12:00:00+03:00']);$full=[];$f->login($full,18);$before=$f->facts();$saved=$owner->history();

    foreach(['/pilot/dashboard','/pilot/dashboard/installers/observations/2026-09-25/without_current','/pilot/dashboard/installers/observations/2026-09-25/without_next']as$path){$get=$f->request('GET',$path,[],$full);$head=$f->request('HEAD',$path,[],$full);assertSameValue([200,200,''],[$get['status'],$head['status'],$head['body']],'J/K GET/HEAD '.$path);}
    assertSameValue($before,$f->facts(),'K no business write');assertSameValue($saved,$owner->history(),'K no capture/sync');

    $dashboard=$f->request('GET','/pilot/dashboard',[],$full);if(str_contains($dashboard['body'],'data-dashboard-chart="installer-forecast"'))echo"INTENDED_RED duplicate forecast widget\n";
    foreach(['Загрузка монтажников и динамика','Заняты','Свободны','Освобождаются','Конфликты','Нет данных']as$text)assertSameValue(true,str_contains($dashboard['body'],$text),'forecast dashboard '.$text);
    assertSameValue(1,substr_count($dashboard['body'],'data-dashboard-chart="utilization"'),'one existing utilization widget');assertSameValue(0,substr_count($dashboard['body'],'data-dashboard-chart="installer-forecast"'),'no duplicate forecast widget');assertSameValue(6,substr_count($dashboard['body'],'class="fm2-forecast-week"'),'one six-week forecast');assertSameValue(30,substr_count($dashboard['body'],'class="fm2-forecast-value'),'six by five forecast values');foreach(['data-observation-date=','data-installer-utilization-history','data-utilization-week-empty']as$historical)assertSameValue(false,str_contains($dashboard['body'],$historical),'historical visualization removed '.$historical);
    $dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8"'.$dashboard['body']);$xp=new DOMXPath($dom);$pair=$xp->query('//*[contains(concat(" ",normalize-space(@class)," ")," fm2-dashboard-primary-charts ")]')->item(0);assertSameValue(true,$pair instanceof DOMElement,'primary chart pair exists');assertSameValue(['stages','utilization'],array_map(static fn(DOMElement$node):string=>$node->getAttribute('data-dashboard-chart'),iterator_to_array($xp->query('./section',$pair))),'stages left utilization forecast right');

    $detail=$f->request('GET','/pilot/dashboard/installers/observations/2026-09-25/without_next',[],$full);assertSameValue(true,str_contains($detail['body'],'7002')&&str_contains($detail['body'],'7001'),'saved subset remains in drill-down');
    foreach(['/pilot/dashboard/installers/observations/1999-01-01/without_next','/pilot/dashboard/installers/observations/2026-09-25/bogus']as$path)assertSameValue(404,$f->request('GET',$path,[],$full)['status'],'unknown coordinate');

    $dashboardPaths=['/pilot/dashboard','/pilot/dashboard/installers/observations/2026-09-25/without_next'];
    $guest=[];foreach(['GET','HEAD']as$method)foreach($dashboardPaths as$path){$r=$f->request($method,$path,[],$guest);assertSameValue(303,$r['status'],'guest '.$method.' '.$path);if($method==='HEAD')assertSameValue('',$r['body'],'guest HEAD empty');foreach(['7001','7002','2026-09-25','Без текущих работ']as$private)assertSameValue(false,str_contains($r['body'],$private),'guest no leak '.$private);}
    foreach([[95,'permissionless'],[73,'construction-control']]as[$actor,$label]){$cookies=[];$f->login($cookies,$actor);if($actor===95)$f->db->query("DELETE FROM {$p}fm2_pilot_role_permissions WHERE role_id=5 AND permission='installers.read'");foreach(['GET','HEAD']as$method)foreach($dashboardPaths as$path){$r=$f->request($method,$path,[],$cookies);assertSameValue(200,$r['status'],$label.' '.$method.' '.$path);if($method==='HEAD')assertSameValue('',$r['body'],$label.' HEAD empty');elseif($path==='/pilot/dashboard'){assertSameValue(6,substr_count($r['body'],'class="fm2-forecast-week"'),$label.' six forecast weeks');assertSameValue(30,substr_count($r['body'],'class="fm2-forecast-value'),$label.' forecast available');}else foreach(['7001','7002']as$tab)assertSameValue(true,str_contains($r['body'],$tab),$label.' saved drill-down '.$tab);}}

    $directory=$f->request('GET','/pilot/installers',[],$full);$card=$f->request('GET','/pilot/installers/7001',[],$full);foreach([[$directory['body'],'7001'],[$card['body'],'7001']]as[$html,$tab]){$dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8"'.$html);$xp=new DOMXPath($dom);assertSameValue(1,$xp->query('//*[@data-installer-tab-id="'.$tab.'"]//*[contains(concat(" ",normalize-space(@class)," ")," shlz-status ") and normalize-space()="Трудоустроен"]')->length,'M workforce status-bound label');foreach(['Источник интеграции','Последняя интеграция']as$hidden)assertSameValue(false,str_contains($html,$hidden),'M hidden '.$hidden);}

    $f->db->query("DELETE FROM {$p}fm2_workforce_catalog");$unavailable=$f->request('GET','/pilot/dashboard',[],$full);assertSameValue(200,$unavailable['status'],'empty source keeps dashboard available');assertSameValue(true,str_contains($unavailable['body'],'Прогноз временно недоступен')&&str_contains($unavailable['body'],'Нулевые значения не публикуются'),'source-only unavailable block');assertSameValue(false,str_contains($unavailable['body'],'fm2-forecast-value'),'unavailable is not fabricated zero');
    echo "PASS: INSTALLER-UTILIZATION-OBSERVATIONS-001 HTTP behavior\n";
} finally {if($yii instanceof yii\db\Connection)$yii->close();if($f instanceof PreopeningFixture)$f->close();}
