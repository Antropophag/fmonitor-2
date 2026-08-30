<?php

declare(strict_types=1);

$root=getenv('FMONITOR_CALENDAR_TEST_ROOT')?:dirname(__DIR__);$calendarFile=getenv('FMONITOR_CALENDAR_TEST_FILE')?:__DIR__.'/Calendar.php';
require_once $root . '/app/PilotHttp/PilotHttp.php';
require_once $root . '/app/PilotHttp/PilotView.php';
require_once $calendarFile;

use FMonitor2\PilotHttp\HttpUser;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:getenv('FMONITOR_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:getenv('FMONITOR_DB_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:getenv('FMONITOR_DB_PASSWORD')?:'fmonitor2_demo_local',getenv('FMONITOR_TEST_DB_NAME')?:getenv('FMONITOR_DB_NAME')?:'fmonitor2_demo',(int)(getenv('FMONITOR_TEST_DB_PORT')?:getenv('FMONITOR_DB_PORT')?:23306));$db->set_charset('utf8mb4');
$token='cal_'.bin2hex(random_bytes(5)).'_';$p=$token.'p_';$l=$token.'l_';$tables=[];
$create=static function(string$name,string$sql)use($db,&$tables):void{$db->query("CREATE TABLE `{$name}` ({$sql}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");$tables[]=$name;};
try{
    $create($l.'fm_maintable','id BIGINT PRIMARY KEY,ordadr_address VARCHAR(255),entrance VARCHAR(40),regnumber VARCHAR(80),workdatestart DATETIME NULL,workdatefinish DATETIME NULL,plan_finish_date DATETIME NULL,workdateendadjusted DATETIME NULL,ptoactdate DATETIME NULL');
    $create($p.'fm2_installation_cases','id BIGINT PRIMARY KEY,legacy_installation_object_id BIGINT,process_state VARCHAR(40),actual_start_date DATE NULL');
    $create($p.'fm2_assignment_orders','id BIGINT PRIMARY KEY,installation_case_id BIGINT,version_no INT,status VARCHAR(40),order_date DATE NULL,registered_at VARCHAR(40) NULL,registration_number VARCHAR(80) NULL');
    $create($p.'fm2_process_tasks','id BIGINT PRIMARY KEY,installation_case_id BIGINT,task_type VARCHAR(80),due_date DATE NULL,status VARCHAR(40)');
    $db->query("INSERT INTO `{$l}fm_maintable` VALUES
        (1,'Адрес проверки','1','1001','2026-08-10',NULL,'2026-08-29','2026-08-28','2026-08-30'),
        (2,'Адрес проверки 2','2','1002','2026-08-14',NULL,NULL,NULL,NULL),
        (3,'Адрес проверки 3','3','1003','2026-08-14',NULL,NULL,NULL,NULL),
        (4,'Адрес проверки 4','4','1004','2026-08-14',NULL,NULL,NULL,NULL),
        (5,'Адрес проверки 5','5','1005','2026-08-14',NULL,NULL,NULL,NULL)");
    $db->query("INSERT INTO `{$p}fm2_installation_cases` VALUES(1,1,'working','2026-08-11')");
    $orders=[];$tasks=[];for($i=1;$i<=80;$i++){$orders[]="({$i},1,{$i},'registered','2026-08-12','2026-08-13T10:00:00+03:00','R-{$i}')";$tasks[]="({$i},1,'perform_inspection','2026-08-14','open')";}
    $db->query("INSERT INTO `{$p}fm2_assignment_orders` VALUES".implode(',',$orders));$db->query("INSERT INTO `{$p}fm2_process_tasks` VALUES".implode(',',$tasks));
    $class=new ReflectionClass(RapidPilotCalendar::class);$calendar=$class->newInstanceWithoutConstructor();foreach(['db'=>$db,'processPrefix'=>$p,'legacyPrefix'=>$l]as$name=>$value){$property=$class->getProperty($name);$property->setValue($calendar,$value);}$read=$class->getMethod('read');
    $first=new DateTimeImmutable('2026-08-01',new DateTimeZone('Europe/Moscow'));$last=new DateTimeImmutable('2026-08-31',new DateTimeZone('Europe/Moscow'));$events=$read->invoke($calendar,$first,$last);$repeat=$read->invoke($calendar,$first,$last);
    if($events!==$repeat||count($events)!==6)throw new RuntimeException('calendar projection must remain deterministic and expose only planned dates');
    $types=array_count_values(array_column($events,'type'));$expected=['planned_end'=>1,'planned_start'=>5];ksort($types);ksort($expected);if($types!==$expected)throw new RuntimeException('calendar projection event counts differ');
    putenv('FMONITOR_NOW=2026-08-14T12:00:00+03:00');$render=$class->getMethod('render');$html=$render->invoke($calendar,new HttpUser(1,'Проверяющий','qa@example.invalid'),$first,$first,$last,new DateTimeImmutable('2026-08-14',new DateTimeZone('Europe/Moscow')),$events);
    $dom=new DOMDocument();libxml_use_internal_errors(true);if(!$dom->loadHTML($html,LIBXML_NONET|LIBXML_NOWARNING|LIBXML_NOERROR))throw new RuntimeException('calendar HTML parse failed');$xpath=new DOMXPath($dom);
    if(!str_contains($html,'6 событий')||$xpath->query('//*[@data-shlz-calendar-grid]')->length!==1||$xpath->query('//th[@scope="row"]')->length!==2||$xpath->query('//th[@scope="col"]')->length!==32||$xpath->query('//th[@scope="colgroup"]')->length!==3||$xpath->query('//tbody/descendant::*[@data-shlz-calendar-grid-state="today"]')->length<2||$xpath->query('//*[@data-shlz-calendar-grid-disclosure="cell" and contains(@class,"shlz-button--sm")]')->length!==1)throw new RuntimeException('calendar DOM contract differs');
    $more=[];for($i=6;$i<=5006;$i++)$more[]="({$i},'Адрес {$i}','1','R-{$i}','2026-08-14',NULL,NULL,NULL,NULL)";foreach(array_chunk($more,500)as$chunk)$db->query("INSERT INTO `{$l}fm_maintable` VALUES".implode(',',$chunk));
    try{$read->invoke($calendar,$first,$last);throw new RuntimeException('calendar source overflow was silently truncated');}catch(ReflectionException $error){throw$error;}catch(Throwable $error){$cause=$error instanceof ReflectionException?null:$error;if(!str_contains($cause->getMessage(),'Calendar source projection overflow'))throw$error;}
    echo "PASS calendar bounded projections, deterministic DOM and fail-closed overflow\n";
}finally{for($i=count($tables)-1;$i>=0;$i--)$db->query("DROP TABLE `{$tables[$i]}`");$db->close();}
