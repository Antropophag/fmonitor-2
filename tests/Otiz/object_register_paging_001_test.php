<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';

use FMonitor2\Otiz\ObjectRegister;
use FMonitor2\Tests\Support\ObjectRegisterPagingFixture;

$p='orp_'.bin2hex(random_bytes(5)).'_';$f=new ObjectRegisterPagingFixture($p);
$reject=static function(string$reason,callable$call):void{try{$call();}catch(DomainException$e){assertSameValue($reason,$e->getMessage(),'exact public refusal');return;}throw new TestFailure('Expected '.$reason);};
$ids=static fn(array$result):array=>array_map(static fn(array$row):int=>(int)$row['object_id'],$result['rows']);
$shape=static fn(array$result):array=>array_intersect_key($result,array_flip(['query','page','pageSize','pages','total','rows','summary']));
$facts=static function(mysqli$db,string$p):array{$out=[];$escaped=$db->real_escape_string($p.'%');$tables=array_column($db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$escaped}' ORDER BY TABLE_NAME")->fetch_all(MYSQLI_ASSOC),'TABLE_NAME');foreach($tables as$table){$create=$db->query("SHOW CREATE TABLE `{$table}`")->fetch_array(MYSQLI_NUM)[1];$rows=$db->query("SELECT * FROM `{$table}`")->fetch_all(MYSQLI_ASSOC);$encoded=array_map(static fn(array$row):string=>json_encode($row,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$rows);sort($encoded,SORT_STRING);$out[$table]=['schema'=>hash('sha256',$create),'rows'=>hash('sha256',implode("\n",$encoded)),'count'=>count($rows)];}return$out;};
try{
    assertSameValue(true,class_exists(ObjectRegister::class),'RED: public OTIZ object register seam exists after canonical fixture setup');
    $register=new ObjectRegister($f->db,$p,$p);$before=$facts($f->db,$p);
    $first=$register->read(18,['sort'=>'regnumber_asc']);
    assertSameValue(['q'=>'','state'=>'','sort'=>'regnumber_asc','page'=>1,'pageSize'=>50],$first['query'],'normalized query is public');
    assertSameValue([1,50,3,125,50],[$first['page'],$first['pageSize'],$first['pages'],$first['total'],count($first['rows'])],'first bounded page');
    assertSameValue([3,1,2,5,6,4],array_slice($ids($register->read(18,[])),0,6),'default order preserves snapshot/state rank before registration and id keys');
    $second=$register->read(18,['sort'=>'regnumber_asc','page'=>'2']);$third=$register->read(18,['sort'=>'regnumber_asc','page'=>3]);
    assertSameValue([50,25],[count($second['rows']),count($third['rows'])],'remaining bounded pages');
    $all=[...$ids($first),...$ids($second),...$ids($third)];
    assertSameValue(range(1,125),$all,'equal regnumbers use object id tie-break without omissions or duplicates');
    assertSameValue(125,count(array_unique($all)),'every object appears exactly once');
    $small=$register->read(18,['sort'=>'regnumber_desc','pageSize'=>'25','page'=>5,'tracking'=>'ignored']);
    assertSameValue(range(101,125),$ids($small),'descending regnumber still ends in object id ASC');
    assertSameValue([5,5,125],[$small['page'],$small['pages'],$small['total']],'allowed page size changes page count');

    $found=$register->read(18,['q'=>'  УНИКАЛЬНАЯ цель  ','page'=>'1']);
    assertSameValue([125],$ids($found),'case-insensitive trimmed search reaches an object beyond page one');
    assertSameValue(1,$found['total'],'search count covers the complete register');
    assertSameValue(0,$register->read(18,['q'=>'%'])['total'],'percent is a literal, not a wildcard');
    assertSameValue(0,$register->read(18,['q'=>'_'])['total'],'underscore is a literal, not a wildcard');
    $empty=$register->read(18,['q'=>'нет совпадений']);
    assertSameValue([1,1,0,0],[ $empty['page'],$empty['pages'],$empty['total'],count($empty['rows'])],'empty page one is a valid result');

    assertSameValue([1],[...$ids($register->read(18,['state'=>'completed']))],'fully closed ready snapshot is completed');
    assertSameValue([2,6],$ids($register->read(18,['state'=>'ready','sort'=>'regnumber_asc'])),'partially closed and latest-date ready snapshots remain ready');
    assertSameValue([3],$ids($register->read(18,['state'=>'blocked'])),'blocked filter applies before paging');
    assertSameValue([4],$ids($register->read(18,['state'=>'no_new_amount'])),'no-new-amount filter applies before paging');
    assertSameValue([5],$ids($register->read(18,['state'=>'missing_norm'])),'missing norm takes precedence over snapshot state');
    assertSameValue(600,(int)$register->read(18,['q'=>'Вымышленный адрес 006'])['rows'][0]['accrued_cents'],'latest snapshot uses report date before id');
    $summary=['total'=>125,'fund'=>6448000000,'earned'=>2000,'paid'=>199,'penalties'=>60,'balance'=>6447999771,'blocked'=>1,'calculated'=>124];
    assertSameValue($summary,$first['summary'],'independent global financial summary');
    assertSameValue($summary,$third['summary'],'summary does not shrink on a later page');
    assertSameValue($summary,$register->read(18,['state'=>'blocked'])['summary'],'summary does not shrink under a filter');
    assertSameValue($before,$facts($f->db,$p),'paging, filtering and summaries preserve source and business facts');

    $f->db->query("INSERT INTO `{$p}fm2_pilot_otiz_payment_closures`(snapshot_id,object_id,closed_on,paid_cents,discipline_cents,deadline_cents,basis,artifact,created_by_user_id,created_at) VALUES(101,7,'2026-09-04',60000000,0,0,'Over-fund clamp fixture','',18,'2026-09-04T10:00:00Z')");
    $clamped=$register->read(18,[]);
    assertSameValue(60000199,$clamped['summary']['paid'],'all closure facts remain in paid total');
    assertSameValue(6395999771,$clamped['summary']['balance'],'global balance clamps each object before summing');
    $blockedRow=$register->read(18,['state'=>'blocked'])['rows'][0];
    assertSameValue(50,(int)$blockedRow['deadline_penalty_cents'],'row deadline penalty comes from saved formula trace');
    assertSameValue(60,$clamped['summary']['penalties'],'global penalties use discipline plus trace deadline');

    $f->card(115,5,320," \tЖЕЛЕЗОБЕТОН\n",'ПАССАЖИРСКИЙ');
    $f->card(116,'+5','+320',' кирпич ',' пассажирский ');
    $f->card(117,'05','320','Железобетон','Пассажирский');
    $f->card(118,' 5 ',' 320 ','Железобетон','Пассажирский');
    $f->card(119,'5','501','Железобетон','Пассажирский');
    $f->card(120,'31','320','Железобетон','Пассажирский');
    $f->card(121,5,500,'Железобетон','ГРУЗОВОЙ');
    $f->card(122,'5','0500','Железобетон','Грузовой');
    $f->card(123,'5','320','Железобетон','');
    $f->card(124,'5','320',' Металлокаркас+стекло ','Пассажирский');
    $f->card(125,'5','320','дерево','Пассажирский');
    $afterFixtureChanges=$facts($f->db,$p);
    $missing=$register->read(18,['state'=>'missing_norm','sort'=>'regnumber_asc']);
    assertSameValue([5,117,119,120,122,125],$ids($missing),'SQL eligibility matches PHP integer and Cyrillic normalization boundaries');
    assertSameValue(59800000,(int)$register->read(18,['q'=>'адрес 116'])['rows'][0]['fund_cents'],'trimmed Cyrillic brick alias produces known fund literal');
    assertSameValue(52000000,(int)$register->read(18,['q'=>'адрес 123'])['rows'][0]['fund_cents'],'missing lift type keeps passenger capacity fallback');

    foreach([
        ['page'=>0],['page'=>'01'],['page'=>'1.0'],['page'=>1000001],['pageSize'=>101],['pageSize'=>null],
        ['state'=>'unknown'],['sort'=>''],['sort'=>'unknown'],['q'=>[]],['q'=>false],['q'=>str_repeat('я',121)],
    ]as$query)$reject('REGISTER_QUERY_INVALID',fn()=>$register->read(18,$query));
    $reject('REGISTER_PAGE_NOT_FOUND',fn()=>$register->read(18,['page'=>4]));
    $reject('REGISTER_FORBIDDEN',fn()=>$register->read(73,['page'=>0,'q'=>[]]));
    assertSameValue($afterFixtureChanges,$facts($f->db,$p),'all accepted and rejected normalization reads preserve source and business facts');
    echo "PASS OTIZ object register bounded server paging, global economics, normalization and authorization\n";
}finally{$f->close();}
