<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/autoload.php';
require __DIR__.'/Yii2AuthFixture.php';
use FMonitor2\Tests\Yii2\Yii2AuthFixture;

// OTIZ-SAVED-INSTALLER-ALLOCATION-EXPLANATION-001: existing authenticated snapshot detail seam.
function osiaeRequest(array$server,string$method,string$path,array&$cookies,array$form=[]):array
{
    $headers=['Host: 127.0.0.1:'.$server['port'],'Connection: close'];
    if($cookies)$headers[]='Cookie: '.implode('; ',array_map(static fn($key,$value)=>"$key=$value",array_keys($cookies),$cookies));
    $body='';if($method==='POST'){$body=http_build_query($form);$headers[]='Content-Type: application/x-www-form-urlencoded';}
    $context=stream_context_create(['http'=>['ignore_errors'=>true,'follow_location'=>0,'method'=>$method,'header'=>implode("\r\n",$headers),'content'=>$body]]);
    $content=file_get_contents('http://127.0.0.1:'.$server['port'].$path,false,$context);$raw=$http_response_header??[];$responseHeaders=[];
    foreach(array_slice($raw,1)as$line){$at=strpos($line,':');if($at!==false)$responseHeaders[strtolower(substr($line,0,$at))][]=trim(substr($line,$at+1));}
    foreach($responseHeaders['set-cookie']??[]as$cookie)if(preg_match('/^([^=;]+)=([^;]*)/',$cookie,$match))$cookies[$match[1]]=$match[2];
    preg_match('#^HTTP/\S+ (\d+)#',$raw[0]??'',$match);return['status'=>(int)($match[1]??0),'headers'=>$responseHeaders,'body'=>(string)$content];
}
function osiaeCsrf(string$html):string
{
    if(!preg_match('/name="_csrf" value="([^"]+)"/',$html,$match))throw new TestFailure('SETUP_FAILURE: csrf');
    return html_entity_decode($match[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
}
function osiaeInventory(mysqli$db,string$p):array
{
    $tables=['fm2_pilot_otiz_snapshots','fm2_pilot_otiz_snapshot_objects','fm2_pilot_otiz_snapshot_allocations','fm2_pilot_otiz_snapshot_issues','fm2_pilot_otiz_payment_closures','fm2_pilot_otiz_events','fm2_otiz_settlement_operations','fm2_jobs','fm2_outbox_intents'];$out=[];
    foreach($tables as$table)$out[$table]=hash('sha256',json_encode($db->query("SELECT * FROM `{$p}{$table}` ORDER BY 1")->fetch_all(MYSQLI_ASSOC),JSON_THROW_ON_ERROR));return$out;
}
function osiaeSeed(mysqli$db,string$p):void
{
    $hash=str_repeat('a',64);
    $db->query("INSERT INTO `{$p}fm2_pilot_otiz_snapshots`(id,report_date,status,rules_version,calculated_at,calculated_by_user_id,accepted_at,accepted_by_user_id,total_pool_cents,total_closed_cents,total_available_cents,content_hash)VALUES
      (821,'2026-08-31','accepted','premium-calculation-v2-excel','2026-09-01T09:00:00+03:00',9101,'2026-09-01T10:00:00+03:00',9101,12345,0,12345,'$hash'),
      (822,'2026-09-30','accepted','premium-calculation-v2-excel','2026-10-01T09:00:00+03:00',9101,'2026-10-01T10:00:00+03:00',9101,24680,0,24680,'$hash')");
    $db->query("INSERT INTO `{$p}fm2_pilot_otiz_snapshot_objects`(snapshot_id,object_id,regnumber,address,previous_progress_bp,current_progress_bp,progress_fact_date,premium_cents,shaft_bp,kss_bp,accrued_cents,fund_cents,closed_before_cents,remaining_cents,pool_cents,distributed_cents,undistributed_cents,calculation_state,inputs_json)VALUES
      (821,8821,'ALLOC-1','Старый объект',0,10000,'2026-08-31',12345,10000,10000,12345,12345,0,12345,12345,12345,0,'ready','{}'),
      (821,8822,'ALLOC-EMPTY','Без сохранённого распределения',0,10000,'2026-08-31',10000,10000,10000,10000,10000,0,10000,10000,10000,0,'ready','{}'),
      (822,8821,'ALLOC-1','Новый срез того же объекта',10000,10000,'2026-09-30',24680,10000,10000,24680,24680,0,24680,24680,24680,0,'ready','{}')");
    $long='Длинное сохранённое основание участия '.str_repeat('для проверки переноса ',6);
    $statement=$db->prepare("INSERT INTO `{$p}fm2_pilot_otiz_snapshot_allocations`(snapshot_id,object_id,tab_id,full_name,position_name,contribution_bp,base_ktu_bp,adjustment_ktu_bp,effective_ktu_bp,share_bp,amount_cents,employment_status,participation_basis)VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)");
    foreach([
      [821,8821,'Т-01','Анна Монтажник','Монтажник',2300,10000,1700,11700,3100,3333,'employed','Распоряжение № 7'],
      [821,8821,'Т-02','<img src=x onerror=alert(1)> & Монтажник','Монтажник',7700,10000,-700,9300,6900,9012,'employed','<script>alert("allocation")</script>& '.$long],
      [821,8821,'Т-03','Без основания','Монтажник',0,0,0,0,0,0,'employed','   '],
      [822,8821,'Т-88','Участник последующего snapshot','Монтажник',10000,10000,0,10000,10000,24680,'employed','Основание последующего snapshot']
    ]as$row){$statement->bind_param('iisssiiiiiiss',...$row);$statement->execute();}
    $db->query("INSERT INTO `{$p}fm2_installation_cases`(id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version)VALUES(9901,8821,'working','2026-09-01','2026-09-01T08:00:00Z',9101,'2026-08-01T08:00:00Z','2026-09-01T08:00:00Z',1)");
    $db->query("INSERT INTO `{$p}fm2_assignment_orders`(id,installation_case_id,version_no,kind,status,order_date,control_engineer_user_id,control_engineer_fio_snapshot,control_engineer_position_snapshot,organization_form,object_address_snapshot,entrance_snapshot,object_registration_number_snapshot,planned_start_date_snapshot,planned_finish_date_snapshot,prepared_at,prepared_by_user_id)VALUES(9981,9901,1,'initial','registered','2026-09-01',9101,'Инженер','Инженер','brigade','Текущий адрес','1','ALLOC-1','2026-09-01','2026-09-30','2026-09-01T07:00:00Z',9101)");
    $db->query("INSERT INTO `{$p}fm2_order_installers`(assignment_order_id,installer_tab_id,fio_snapshot,position_snapshot,employment_status_snapshot,employed_from_snapshot,workforce_source_snapshot,workforce_source_updated_at_snapshot,valid_from,change_action)VALUES(9981,9999,'НОВЫЙ ТЕКУЩИЙ УЧАСТНИК','Монтажник','employed','2026-09-01','current-composition','2026-09-30T06:00:00Z','2026-09-01','assign')");
    $db->query("INSERT INTO `{$p}fm2_pilot_otiz_snapshot_issues`(snapshot_id,object_id,severity,issue_code,message,owner_role,state)VALUES(821,8821,'warning','OBJECT_WARNING','Просрочка по объекту','ОТиЗ','open')");
}

$root=dirname(__DIR__,2);$fixture=new Yii2AuthFixture($root);$fixture->setPermission('otiz.manage');$db=$fixture->db;$p=$fixture->prefix;$server=null;$artifacts=sys_get_temp_dir().'/fmonitor-29-allocation-'.bin2hex(random_bytes(5));mkdir($artifacts,0700,true);
try{
    osiaeSeed($db,$p);$listener=stream_socket_server('tcp://127.0.0.1:0',$errno,$error);if(!is_resource($listener))throw new TestFailure('SETUP_FAILURE: port');preg_match('/:(\d+)$/D',(string)stream_socket_get_name($listener,false),$match);$port=(int)$match[1];fclose($listener);
    $env=getenv();foreach(array_keys($env)as$key)if(str_starts_with((string)$key,'FMONITOR_'))unset($env[$key]);$env=array_replace($env,$fixture->environment(),['FMONITOR_TRUSTED_REQUEST_HOST'=>'127.0.0.1:'.$port]);$log=$artifacts.'/server.log';$server=['process'=>proc_open([PHP_BINARY,'-d','display_errors=0','-S','127.0.0.1:'.$port,$root.'/public/yii.php'],[0=>['file','/dev/null','r'],1=>['file',$log,'a'],2=>['file',$log,'a']],$pipes,$root,$env),'port'=>$port];if(!is_resource($server['process']))throw new TestFailure('SETUP_FAILURE: server');$deadline=microtime(true)+5;do{$socket=@fsockopen('127.0.0.1',$port,$errno,$error,.1);if(is_resource($socket)){fclose($socket);break;}usleep(20000);}while(microtime(true)<$deadline);
    $path='/pilot/otiz/snapshots/821';$cookies=[];$guest=osiaeRequest($server,'GET',$path,$cookies);assertSameValue(303,$guest['status'],'guest denied');foreach(['Анна Монтажник','Т-01','Распоряжение № 7','123,45 ₽','33,33 ₽','90,12 ₽']as$secret)assertSameValue(false,str_contains($guest['body'],$secret),'guest hides '.$secret);
    $login=osiaeRequest($server,'GET','/pilot/login',$cookies);$email=osiaeRequest($server,'POST','/pilot/login',$cookies,['_csrf'=>osiaeCsrf($login['body']),'email'=>$fixture->email]);$auth=osiaeRequest($server,'POST','/pilot/login',$cookies,['_csrf'=>osiaeCsrf($email['body']),'email'=>$fixture->email,'password'=>$fixture->password]);assertSameValue([303,$path],[$auth['status'],$auth['headers']['location'][0]??null],'login returns to snapshot');
    $before=osiaeInventory($db,$p);$page=osiaeRequest($server,'GET',$path,$cookies);assertSameValue(200,$page['status'],'authorized snapshot');file_put_contents($artifacts.'/page.html',$page['body']);$text=html_entity_decode(str_replace("\u{00a0}",' ',strip_tags($page['body'])),ENT_QUOTES|ENT_HTML5,'UTF-8');
    foreach(['Расчётная дата','31.08.2026','Сумма к распределению','123,45 ₽','Анна Монтажник','Т-01','Основание участия','Распоряжение № 7','Не сохранено в этом расчёте']as$expected)assertSameValue(true,str_contains($text,$expected),'INTENDED_RED: explanation contains '.$expected);
    assertSameValue(true,str_contains($page['body'],'data-installer-allocation-details'),'INTENDED_RED: semantic worker details present');assertSameValue(true,str_contains($page['body'],'&lt;img src=x onerror=alert(1)&gt; &amp; Монтажник'),'identity escaped');assertSameValue(true,str_contains($page['body'],'&lt;script&gt;alert(&quot;allocation&quot;)&lt;/script&gt;&amp;'),'basis escaped');assertSameValue(false,str_contains($page['body'],'<script>alert("allocation")</script>'),'basis cannot execute');foreach(['НОВЫЙ ТЕКУЩИЙ УЧАСТНИК','Т-88','Участник последующего snapshot','Основание последующего snapshot','30.09.2026','246,80 ₽']as$value)assertSameValue(false,str_contains($text,$value),'old snapshot excludes '.$value);
    if(!preg_match('/<details[^>]*data-installer-tab="Т-01"[^>]*>(.*?)<\/details>/su',$page['body'],$anna))throw new TestFailure('INTENDED_RED: Anna details missing');$annaText=html_entity_decode(str_replace("\u{00a0}",' ',strip_tags($anna[1])),ENT_QUOTES|ENT_HTML5,'UTF-8');foreach(['Вклад','23,00 %','КТУ','1,17','Доля','31,00 %','Итог работника в расчёте','33,33 ₽']as$value)assertSameValue(true,str_contains($annaText,$value),'Anna owns saved '.$value);assertSameValue(false,str_contains($annaText,'Просрочка по объекту'),'object issue not attributed to Anna');assertSameValue(true,str_contains($text,'Просрочка по объекту'),'object issue remains visible');
    if(!preg_match('/<details[^>]*data-installer-tab="Т-02"[^>]*>(.*?)<\/details>/su',$page['body'],$boris))throw new TestFailure('INTENDED_RED: Boris details missing');$borisText=html_entity_decode(str_replace("\u{00a0}",' ',strip_tags($boris[1])),ENT_QUOTES|ENT_HTML5,'UTF-8');foreach(['Вклад','77,00 %','КТУ','0,93','Доля','69,00 %','Итог работника в расчёте','90,12 ₽']as$value)assertSameValue(true,str_contains($borisText,$value),'Boris owns saved '.$value);
    $later=osiaeRequest($server,'GET','/pilot/otiz/snapshots/822',$cookies);$laterText=html_entity_decode(str_replace("\u{00a0}",' ',strip_tags($later['body'])),ENT_QUOTES|ENT_HTML5,'UTF-8');foreach(['30.09.2026','246,80 ₽','Т-88','Участник последующего snapshot','Основание последующего snapshot']as$value)assertSameValue(true,str_contains($laterText,$value),'later snapshot owns '.$value);foreach(['Анна Монтажник','Т-01','Распоряжение № 7','123,45 ₽','НОВЫЙ ТЕКУЩИЙ УЧАСТНИК']as$value)assertSameValue(false,str_contains($laterText,$value),'later snapshot excludes '.$value);
    $empty=osiaeRequest($server,'GET','/pilot/otiz/snapshots/821',$cookies);assertSameValue(true,str_contains(html_entity_decode(strip_tags($empty['body']),ENT_QUOTES|ENT_HTML5,'UTF-8'),'В этом расчёте распределение по работникам не сохранено'),'empty allocation state');assertSameValue($before,osiaeInventory($db,$p),'GET appends or mutates no facts');
    $fixture->setPermission('OTIZ.MANAGE');$denied=osiaeRequest($server,'GET',$path,$cookies);assertSameValue(403,$denied['status'],'near-match permission denied');foreach(['Анна Монтажник','Т-01','Распоряжение № 7','123,45 ₽','33,33 ₽','90,12 ₽']as$secret)assertSameValue(false,str_contains($denied['body'],$secret),'403 hides '.$secret);assertSameValue($before,osiaeInventory($db,$p),'denial appends no facts');$fixture->setPermission('otiz.manage');
    $config=['origin'=>'http://127.0.0.1:'.$port,'email'=>$fixture->email,'password'=>$fixture->password,'artifacts'=>$artifacts,'result'=>$artifacts.'/browser-result.json','playwright'=>getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($root).'/shlz-ui/node_modules/playwright'];file_put_contents($artifacts.'/browser-config.json',json_encode($config,JSON_THROW_ON_ERROR));chmod($artifacts.'/browser-config.json',0600);
    $browser=proc_open([getenv('FMONITOR_TEST_NODE_BINARY')?:'node',__DIR__.'/otiz_saved_installer_allocation_browser.mjs',$artifacts.'/browser-config.json'],[0=>['file','/dev/null','r'],1=>['file',$artifacts.'/browser.log','a'],2=>['file',$artifacts.'/browser.log','a']],$browserPipes,$root);if(!is_resource($browser))throw new TestFailure('SETUP_FAILURE: browser');$deadline=microtime(true)+60;do{$state=proc_get_status($browser);if(!$state['running'])break;usleep(20000);}while(microtime(true)<$deadline);if($state['running']){proc_terminate($browser,9);throw new TestFailure('SETUP_FAILURE: browser timeout');}$exit=$state['exitcode'];proc_close($browser);assertSameValue(0,$exit,'INTENDED_RED: browser allocation explanation '.(string)@file_get_contents($artifacts.'/browser.log'));assertSameValue($before,osiaeInventory($db,$p),'browser appends or mutates no facts');
    echo 'PASS: OTIZ-SAVED-INSTALLER-ALLOCATION-EXPLANATION-001 HTTP/browser read-only details; artifacts '.$artifacts."\n";
}finally{if(is_array($server)&&is_resource($server['process'])){proc_terminate($server['process']);proc_close($server['process']);}$fixture->close();}
