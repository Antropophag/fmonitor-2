<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require dirname(__DIR__,2).'/app/autoload.php';require __DIR__.'/Yii2AuthFixture.php';
use FMonitor2\Tests\Yii2\Yii2AuthFixture;
function yosStart(string$r,array$e):array{$l=stream_socket_server('tcp://127.0.0.1:0',$c,$m);if(!is_resource($l))throw new TestFailure('SETUP_FAILURE: port');preg_match('/:(\d+)$/D',(string)stream_socket_get_name($l,false),$x);$p=(int)$x[1];fclose($l);$env=getenv();foreach(array_keys($env)as$k)if(str_starts_with((string)$k,'FMONITOR_'))unset($env[$k]);$q=[];$h=proc_open([PHP_BINARY,'-d','display_errors=0','-S',"127.0.0.1:$p",$r.'/public/yii.php'],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$q,$r,array_replace($env,$e));if(!is_resource($h))throw new TestFailure('SETUP_FAILURE: server');$d=microtime(true)+5;do{$s=@fsockopen('127.0.0.1',$p,$a,$b,.1);if(is_resource($s)){fclose($s);return['process'=>$h,'pipes'=>$q,'port'=>$p];}usleep(20000);}while(microtime(true)<$d);throw new TestFailure('SETUP_FAILURE: listen');}
function yosStop(?array&$s):void{if($s===null)return;proc_terminate($s['process']);foreach($s['pipes']as$p)if(is_resource($p))fclose($p);proc_close($s['process']);$s=null;}
function yosRequest(array$s,string$m,string$p,array$f,array&$cookies):array{$b=$f===[]?'':http_build_query($f);$h=['Host: fmonitor.example.test','Connection: close'];if($cookies)$h[]='Cookie: '.implode('; ',array_map(static fn($k,$v)=>"$k=$v",array_keys($cookies),$cookies));if($b!=='')$h[]='Content-Type: application/x-www-form-urlencoded';$ctx=stream_context_create(['http'=>['ignore_errors'=>true,'follow_location'=>0,'method'=>$m,'header'=>implode("\r\n",$h),'content'=>$b]]);$body=file_get_contents("http://127.0.0.1:{$s['port']}$p",false,$ctx);$raw=$http_response_header??[];$headers=[];foreach(array_slice($raw,1)as$l){$at=strpos($l,':');if($at!==false)$headers[strtolower(substr($l,0,$at))][]=trim(substr($l,$at+1));}foreach($headers['set-cookie']??[]as$c)if(preg_match('/^([^=;]+)=([^;]*)/',$c,$x))$cookies[$x[1]]=$x[2];preg_match('#^HTTP/\S+ (\d+)#',$raw[0]??'',$x);return['status'=>(int)($x[1]??0),'headers'=>$headers,'body'=>(string)$body];}
function yosCsrf(string$html):string{if(!preg_match('/name="_csrf" value="([^"]+)"/',$html,$m))throw new TestFailure('SETUP_FAILURE: csrf');return html_entity_decode($m[1],ENT_QUOTES|ENT_HTML5,'UTF-8');}
function yosForm(string $html, string $action): string
{
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    try { $document->loadHTML('<?xml encoding="UTF-8">' . $html); }
    finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
    $matches = [];
    foreach ($document->getElementsByTagName('form') as $form) {
        if ($form->getAttribute('action') === $action) $matches[] = $form;
    }
    assertSameValue(1, count($matches), 'exactly one form for ' . $action);
    $form = $matches[0];
    assertSameValue('post', strtolower($form->getAttribute('method')), 'form submits POST to ' . $action);
    $controls = [];
    foreach ($form->getElementsByTagName('input') as $input) {
        $controls[$input->getAttribute('name')][] = $input;
    }
    foreach (['_csrf', 'operationId'] as $name) {
        assertSameValue(1, count($controls[$name] ?? []), 'one ' . $name . ' in ' . $action);
        assertSameValue(false, $controls[$name][0]->hasAttribute('disabled'), 'enabled ' . $name);
        assertSameValue('hidden', strtolower($controls[$name][0]->getAttribute('type')), 'hidden ' . $name);
        assertSameValue(true, $controls[$name][0]->getAttribute('value') !== '', 'nonempty ' . $name);
    }
    assertSameValue(1, preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $controls['operationId'][0]->getAttribute('value')), 'canonical operation UUID in ' . $action);
    $content = '';
    foreach ($form->childNodes as $node) $content .= $document->saveHTML($node);
    return $content;
}
$root=dirname(__DIR__,2);$fixture=null;$server=null;
try{
 $fixture=new Yii2AuthFixture($root);$fixture->setPermission('otiz.manage');$p=$fixture->prefix;$db=$fixture->db;$hash=str_repeat('d',64);
 $db->query("INSERT INTO {$p}fm2_pilot_otiz_snapshots(id,report_date,status,rules_version,calculated_at,calculated_by_user_id,accepted_at,accepted_by_user_id,total_pool_cents,total_closed_cents,total_available_cents,content_hash)VALUES(301,'2026-09-30','accepted','premium-calculation-v2-excel','2026-10-01T09:00:00+03:00',9101,'2026-10-01T10:00:00+03:00',9101,100000,0,100000,'$hash')");
 $db->query("INSERT INTO {$p}fm2_pilot_otiz_snapshot_objects(snapshot_id,object_id,regnumber,address,previous_progress_bp,current_progress_bp,progress_fact_date,premium_cents,shaft_bp,kss_bp,accrued_cents,fund_cents,closed_before_cents,remaining_cents,pool_cents,distributed_cents,undistributed_cents,calculation_state,inputs_json)VALUES(301,7301,'HTTP-1','Synthetic',0,10000,'2026-09-30',100000,10000,10000,100000,100000,0,100000,100000,100000,0,'ready','{}')");
 $counts=static fn():array=>[(int)$db->query("SELECT COUNT(*) n FROM {$p}fm2_pilot_otiz_payment_closures")->fetch_assoc()['n'],(int)$db->query("SELECT COUNT(*) n FROM {$p}fm2_pilot_otiz_events")->fetch_assoc()['n'],(int)$db->query("SELECT COUNT(*) n FROM {$p}fm2_otiz_settlement_operations")->fetch_assoc()['n']];
 $server=yosStart($root,$fixture->environment());$cookies=[];$get=yosRequest($server,'GET','/pilot/login',[],$cookies);$guestBefore=$counts();$guestRead=yosRequest($server,'GET','/pilot/otiz/snapshots/301',[],$cookies);assertSameValue([303,'/pilot/otiz/login'],[$guestRead['status'],$guestRead['headers']['location'][0]??null],'guest snapshot redirects to login');$guest=yosRequest($server,'POST','/pilot/otiz/snapshots/301/closures',['_csrf'=>yosCsrf($get['body']),'objectId'=>'7301','discipline'=>'1.00','basis'=>'guest','operationId'=>'00000000-0000-4000-8000-000000000299'],$cookies);assertSameValue([303,'/pilot/otiz/login'],[$guest['status'],$guest['headers']['location'][0]??null],'guest command redirects to login');assertSameValue($guestBefore,$counts(),'guest appends no facts');
 $email=yosRequest($server,'POST','/pilot/login',['_csrf'=>yosCsrf($get['body']),'email'=>$fixture->email],$cookies);$csrf=yosCsrf($email['body']);$login=yosRequest($server,'POST','/pilot/login',['_csrf'=>$csrf,'email'=>$fixture->email,'password'=>$fixture->password],$cookies);assertSameValue(303,$login['status'],'authenticated OTIZ actor');$snapshotPage=yosRequest($server,'GET','/pilot/otiz/snapshots/301',[],$cookies);assertSameValue(200,$snapshotPage['status'],'authenticated retained snapshot page');foreach(['Выплаты на 30.09.2026','HTTP-1','Synthetic','1 000,00 ₽','/pilot/otiz/payments','/pilot/otiz/history','/pilot/otiz/objects','/pilot/otiz/snapshots/301/export.xlsx']as$needle)assertSameValue(true,str_contains(str_replace("\u{00a0}",' ',$snapshotPage['body']),$needle),'historical snapshot read parity preserves '.$needle);foreach(['/pilot/otiz/snapshots/301/closures','/pilot/otiz/snapshots/301/payments/complete']as$action)assertSameValue(false,str_contains($snapshotPage['body'],'action="'.$action.'"'),'historical incomplete snapshot exposes no legacy financial writer '.$action);$csrf=yosCsrf($snapshotPage['body']);$beforeRetired=$counts();foreach([['/pilot/otiz/snapshots/301/closures',['objectId'=>'7301','discipline'=>'1.00','basis'=>'retired']],['/pilot/otiz/snapshots/301/payments/complete',[]]]as[$path,$payload]){$retired=yosRequest($server,'POST',$path,['_csrf'=>$csrf,'operationId'=>'00000000-0000-4000-8000-000000000399']+$payload,$cookies);assertSameValue(409,$retired['status'],'legacy financial writer retired '.$path);}assertSameValue($beforeRetired,$counts(),'retired legacy writers append no money, events or receipts');$export=yosRequest($server,'GET','/pilot/otiz/snapshots/301/export.xlsx',[],$cookies);assertSameValue([200,'PK'],[$export['status'],substr($export['body'],0,2)],'historical legacy export remains readable');echo"PASS: OTIZ-SETTLEMENT-001 historical read-only compatibility\n";
}finally{yosStop($server);if($fixture instanceof Yii2AuthFixture)$fixture->close();}