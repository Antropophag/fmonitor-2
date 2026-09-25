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

    $dashboard=$f->request('GET','/pilot/dashboard',[],$full);
    foreach(['Загрузка монтажников и динамика','Без текущих работ','Из них без следующего назначения','15.08–25.09.2026','Предыдущие 6 недель','Следующие 6 недель','Полный кадровый срез','01.09.2026']as$text)assertSameValue(true,str_contains($dashboard['body'],$text),'dashboard '.$text);
    foreach(['fm2-utilization-summary','>В работе<','>Ожидают начала<','data-installer-utilization-members','data-installer-tab-id="7001"','data-installer-tab-id="7002"','Монтажник 7001','Монтажник 7002','data-observation-date="2026-08-14"','style="height:']as$removed)assertSameValue(false,str_contains($dashboard['body'],$removed),'dashboard excludes tiles/roster/unsafe marks '.$removed);
    assertSameValue(6,substr_count($dashboard['body'],'data-utilization-week'),'six weekly groups');
    assertSameValue(6,substr_count($dashboard['body'],'data-observation-date='),'one latest observation per week');
    foreach(['2026-08-21','2026-08-28','2026-09-04','2026-09-11','2026-09-18','2026-09-25']as$date)assertSameValue(true,str_contains($dashboard['body'],'data-observation-date="'.$date.'"'),'latest observation '.$date);
    assertSameValue(true,str_contains($dashboard['body'],'href="/pilot/dashboard?utilizationTo=2026-08-14"')&&str_contains($dashboard['body'],'aria-disabled="true"'),'six-week navigation bounds');
    foreach(['fm2-chart-bar__mark-frame','viewBox="0 0 56 116"','shlz-button']as$contract)assertSameValue(true,str_contains($dashboard['body'],$contract),'CSP-safe shlz chart '.$contract);

    $previous=$f->request('GET','/pilot/dashboard?utilizationTo=2026-08-14',[],$full);assertSameValue(200,$previous['status'],'previous window');assertSameValue(6,substr_count($previous['body'],'data-utilization-week'),'previous retains six week slots');assertSameValue(1,substr_count($previous['body'],'data-observation-date='),'previous contains saved 14.08 point');assertSameValue(true,str_contains($previous['body'],'04.07–14.08.2026')&&str_contains($previous['body'],'href="/pilot/dashboard?utilizationTo=2026-09-25"'),'previous labels and forward link');
    foreach(['/pilot/dashboard?utilizationTo=bogus','/pilot/dashboard?utilizationTo=2026-09-26']as$path)assertSameValue(400,$f->request('GET',$path,[],$full)['status'],'invalid/future window '.$path);

    $detail=$f->request('GET','/pilot/dashboard/installers/observations/2026-09-25/without_next',[],$full);assertSameValue(true,str_contains($detail['body'],'7002')&&str_contains($detail['body'],'7001'),'saved subset remains in drill-down');
    foreach(['/pilot/dashboard/installers/observations/1999-01-01/without_next','/pilot/dashboard/installers/observations/2026-09-25/bogus']as$path)assertSameValue(404,$f->request('GET',$path,[],$full)['status'],'unknown coordinate');

    $dashboardPaths=['/pilot/dashboard','/pilot/dashboard/installers/observations/2026-09-25/without_next'];
    $guest=[];foreach(['GET','HEAD']as$method)foreach($dashboardPaths as$path){$r=$f->request($method,$path,[],$guest);assertSameValue(303,$r['status'],'guest '.$method.' '.$path);if($method==='HEAD')assertSameValue('',$r['body'],'guest HEAD empty');foreach(['7001','7002','2026-09-25','Без текущих работ']as$private)assertSameValue(false,str_contains($r['body'],$private),'guest no leak '.$private);}
    foreach([[95,'permissionless'],[73,'construction-control']]as[$actor,$label]){$cookies=[];$f->login($cookies,$actor);if($actor===95)$f->db->query("DELETE FROM {$p}fm2_pilot_role_permissions WHERE role_id=5 AND permission='installers.read'");foreach(['GET','HEAD']as$method)foreach($dashboardPaths as$path){$r=$f->request($method,$path,[],$cookies);assertSameValue(200,$r['status'],$label.' '.$method.' '.$path);if($method==='HEAD')assertSameValue('',$r['body'],$label.' HEAD empty');elseif($path==='/pilot/dashboard'){assertSameValue(6,substr_count($r['body'],'data-utilization-week'),$label.' six weeks');assertSameValue(false,str_contains($r['body'],'data-installer-utilization-members'),$label.' no roster');}else foreach(['7001','7002']as$tab)assertSameValue(true,str_contains($r['body'],$tab),$label.' saved drill-down '.$tab);}}

    $directory=$f->request('GET','/pilot/installers',[],$full);$card=$f->request('GET','/pilot/installers/7001',[],$full);foreach([[$directory['body'],'7001'],[$card['body'],'7001']]as[$html,$tab]){$dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8"'.$html);$xp=new DOMXPath($dom);assertSameValue(1,$xp->query('//*[@data-installer-tab-id="'.$tab.'"]//*[contains(concat(" ",normalize-space(@class)," ")," shlz-status ") and normalize-space()="Трудоустроен"]')->length,'M workforce status-bound label');foreach(['Источник интеграции','Последняя интеграция']as$hidden)assertSameValue(false,str_contains($html,$hidden),'M hidden '.$hidden);}

    $f->db->query("UPDATE {$p}fm2_workforce_catalog SET reconciliation_state='missing_from_delivery' WHERE installer_tab_id=7002");$unavailable=$f->request('GET','/pilot/dashboard',[],$full);assertSameValue([200,null],[$unavailable['status'],$unavailable['headers']['retry-after'][0]??null],'incomplete installer source keeps dashboard available');assertSameValue(true,str_contains($unavailable['body'],'data-installer-utilization-history-unavailable')&&str_contains($unavailable['body'],'Сводка временно недоступна')&&str_contains($unavailable['body'],'Нулевые значения не публикуются'),'explicit unavailable block');foreach(['Свободны: 0','Без текущих работ: 0','data-installer-tab-id="7001"','data-installer-tab-id="7002"','Монтажник 7001','Монтажник 7002']as$leak)assertSameValue(false,str_contains($unavailable['body'],$leak),'unavailable is not zero/free or PII '.$leak);$f->db->query("UPDATE {$p}fm2_workforce_catalog SET reconciliation_state='delivered' WHERE installer_tab_id=7002");assertSameValue(200,$f->request('GET','/pilot/dashboard',[],$full)['status'],'live summary recovers after valid source');
    echo "PASS: INSTALLER-UTILIZATION-OBSERVATIONS-001 HTTP behavior\n";
} finally {if($yii instanceof yii\db\Connection)$yii->close();if($f instanceof PreopeningFixture)$f->close();}
