<?php
declare(strict_types=1);
// CALENDAR-EFFECTIVE-OBJECT-DETAILS-001 A1-A6 — real Yii HTTP and browser reload.
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/PreopeningFixture.php';

$f=null;$browser=null;
try{
    $f=new PreopeningFixture(dirname(__DIR__,2));$p=$f->p;
    $f->db->query("UPDATE {$p}fm_maintable SET ordadr_address='Legacy address',entrance='2',regnumber='LEG-4512',workdatestart='2026-10-15',workdatefinish='2026-10-15',plan_finish_date='2026-10-16' WHERE id=4512");
    $f->insert($p.'fm2_pilot_inspection_schedules',['id'=>7412,'installation_case_id'=>6101,'legacy_object_id'=>4512,'control_engineer_user_id'=>73,'inspection_date'=>'2026-10-15','scheduled_by_user_id'=>18,'scheduled_at'=>'2026-09-24T03:00:00+03:00']);
    $first=json_encode([],JSON_THROW_ON_ERROR);
    $f->insert($p.'fm2_object_detail_edits',['object_id'=>4512,'revision'=>1,'values_json'=>$first,'updated_at_utc'=>'2026-09-24 00:00:00','updated_by_user_id'=>18]);
    $legacyBefore=$f->db->query("SELECT * FROM {$p}fm_maintable WHERE id=4512")->fetch_assoc();
    $scheduleBefore=$f->db->query("SELECT * FROM {$p}fm2_pilot_inspection_schedules WHERE id=7412")->fetch_assoc();
    $f->start(['FMONITOR_NOW'=>'2026-09-24T03:00:00+03:00']);$cookies=[];assertSameValue(303,$f->login($cookies,18)['status'],'reader login');
    $read=static function()use($f,&$cookies):array{$response=$f->request('GET','/pilot/calendar?date=2026-10-15',[],$cookies);assertSameValue(200,$response['status'],'calendar HTTP');$dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$response['body']);return[$response,new DOMXPath($dom)];};
    $agenda=static function(DOMXPath$xp,string$type):string{$node=$xp->query('//*[@data-calendar-agenda]//*[@data-object-id="4512" and @data-event-type="'.$type.'"]')->item(0);assertSameValue(true,$node!==null,'agenda event '.$type);return trim($node->textContent);};
    $sequence=static function(DOMXPath$xp):array{$out=[];foreach($xp->query('//table//*[@data-object-id="4512" and @data-event-type]')as$node){$cell=$xp->query('ancestor::td[1]',$node)->item(0);$headers=$cell?->getAttribute('headers')??'';preg_match('/calendar-day-(\d{4}-\d{2}-\d{2})/',$headers,$m);$out[]=['type'=>$node->getAttribute('data-event-type'),'object'=>$node->getAttribute('data-object-id'),'schedule'=>$node->getAttribute('data-schedule-id'),'date'=>$m[1]??''];}return$out;};
    $replace=static function(int$revision,array$values)use($f,$p):void{$json=json_encode($values,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$s=$f->db->prepare("UPDATE {$p}fm2_object_detail_edits SET revision=?,values_json=?,updated_at_utc=? WHERE object_id=4512");$s->execute([$revision,$json,'2026-09-24 00:0'.$revision.':00']);};
    $factsBefore=$f->facts();[, $initialXp]=$read();$initialSequence=$sequence($initialXp);
    foreach(['inspection','planned_start','planned_end']as$type){$text=$agenda($initialXp,$type);assertSameValue(true,str_contains($text,'Legacy address')&&str_contains($text,'подъезд 2')&&str_contains($text,'Рег. № LEG-4512'),'absent keys fall back in '.$type);}
    assertSameValue(3,count($initialSequence),'one table event of each type without duplicates');
    $replace(2,['address'=>null,'entrance'=>null,'regnumber'=>null]);[, $nullXp]=$read();
    foreach(['inspection','planned_start','planned_end']as$type){$text=$agenda($nullXp,$type);assertSameValue(false,str_contains($text,'Legacy address')||str_contains($text,'LEG-4512')||str_contains($text,'подъезд 2'),'INTENDED_RED explicit null does not fall back in '.$type);}
    $replace(3,['address'=>'','entrance'=>'','regnumber'=>'']);[, $emptyXp]=$read();
    foreach(['inspection','planned_start','planned_end']as$type){$text=$agenda($emptyXp,$type);assertSameValue(false,str_contains($text,'Legacy address')||str_contains($text,'LEG-4512')||str_contains($text,'подъезд 2'),'explicit empty does not fall back in '.$type);}
    $replace(4,['address'=>'Next address','entrance'=>'7','regnumber'=>'CUR-4512']);
    $updated=$f->request('GET','/pilot/calendar?date=2026-10-15',[],$cookies);
    assertSameValue(200,$updated['status'],'next read after correction');
    $updatedDom=new DOMDocument();@$updatedDom->loadHTML('<?xml encoding="UTF-8">'.$updated['body']);$updatedXp=new DOMXPath($updatedDom);
    foreach(['inspection','planned_start','planned_end']as$type){$text=$agenda($updatedXp,$type);assertSameValue(true,str_contains($text,'Next address')&&str_contains($text,'подъезд 7')&&str_contains($text,'Рег. № CUR-4512'),'all latest effective values in '.$type);}
    assertSameValue($initialSequence,$sequence($updatedXp),'ordered type/object/schedule/date identity unchanged after revisions');
    $queue=$f->request('GET','/pilot/objects?q='.rawurlencode('CUR-4512'),[],$cookies);$card=$f->request('GET','/pilot/objects/4512',[],$cookies);
    assertSameValue(true,$queue['status']===200&&str_contains($queue['body'],'CUR-4512')&&str_contains($queue['body'],'Next address'),'registry effective search and render');
    assertSameValue(true,$card['status']===200&&str_contains($card['body'],'CUR-4512')&&str_contains($card['body'],'Next address'),'card effective render');
    $browserInput=['url'=>'http://127.0.0.1:'.$f->server['port'],'cookies'=>$cookies,'playwright'=>getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($f->root).'/shlz-ui/node_modules/playwright'];
    $browser=proc_open([getenv('FMONITOR_TEST_NODE_BINARY')?:'node',__DIR__.'/calendar_effective_object_details_001_browser.mjs'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$f->root);
    if(!is_resource($browser))throw new TestFailure('SETUP_FAILURE browser');fwrite($pipes[0],json_encode($browserInput,JSON_THROW_ON_ERROR));fclose($pipes[0]);$browserOut=stream_get_contents($pipes[1]);$browserErr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$browserExit=proc_close($browser);$browser=null;
    assertSameValue([0,''],[$browserExit,$browserErr],'browser reload '.$browserOut);
    assertSameValue($legacyBefore,$f->db->query("SELECT * FROM {$p}fm_maintable WHERE id=4512")->fetch_assoc(),'legacy source unchanged');
    assertSameValue($scheduleBefore,$f->db->query("SELECT * FROM {$p}fm2_pilot_inspection_schedules WHERE id=7412")->fetch_assoc(),'schedule identity/date unchanged');
    $factsAfter=$f->facts();$factsBefore[$p.'fm2_object_detail_edits']=$factsAfter[$p.'fm2_object_detail_edits'];assertSameValue($factsBefore,$factsAfter,'reads add no DB/history/job/outbox facts');
    echo "PASS CALENDAR-EFFECTIVE-OBJECT-DETAILS-001 HTTP/browser effective details\n";
}finally{if(is_resource($browser)){proc_terminate($browser,9);proc_close($browser);}if($f instanceof PreopeningFixture)$f->close();}
