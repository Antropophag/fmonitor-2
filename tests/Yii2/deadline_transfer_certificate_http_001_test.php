<?php
// DEADLINE-TRANSFER-CERTIFICATE-001 — root authored Yii public flow.
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require dirname(__DIR__,2).'/app/autoload.php';require __DIR__.'/PreopeningFixture.php';
use FMonitor2\Tests\Support\DeadlineCertificateFixture as CertFixture;
use FMonitor2\Tests\Support\AssignmentOrderOriginalPdfCorpus as Pdf;
$fixture=null;
try{
 $fixture=new PreopeningFixture(dirname(__DIR__,2));$db=$fixture->db;$p=$fixture->p;CertFixture::grants($db,$p);$fixture->start();$cookies=[];$fixture->login($cookies);$path='/pilot/objects/4512/deadline-certificates';
 $get=$fixture->request('GET',$path,[],$cookies);assertSameValue(200,$get['status'],'RED_ASSERTION certificate Yii page exists');
 foreach(['name="certificateDate"','name="newDeadline"','name="pdf"','name="requestId"','name="expectedVersion"','multipart/form-data'] as $text)assertSameValue(true,str_contains($get['body'],$text),'certificate form '.$text);
 $csrf=$fixture->csrf($get['body']);$canary='CERTIFICATE_PDF_BYTES_9f6e2740';$a=Pdf::classic(['<< /Type /Catalog /Pages 2 0 R /FixtureCanary ('.$canary.') >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] >>']);$fields=['actorId'=>'96','installationCaseId'=>'999999','_csrf'=>$csrf,'requestId'=>'00000000-0000-4000-8000-000000000601','expectedVersion'=>'0','certificateDate'=>'2026-09-01','newDeadline'=>'2026-09-20','correctionReason'=>'','sourceLabel'=>'HTTP fixture','sourceLocator'=>'fixture://http/A'];
 $send=static function(array $f,string $bytes)use($fixture,$path,&$cookies):array{[$headers,$body]=CertFixture::multipart($f,$bytes);return $fixture->request('POST',$path,[],$cookies,$headers,$body);};
 $bad=$send(array_replace($fields,['_csrf'=>'invalid']),$a);assertSameValue(400,$bad['status'],'CSRF enforced');
 $accepted=$send($fields,$a);assertSameValue([303,$path],[$accepted['status'],$accepted['headers']['location'][0]??null],'success returns to certificate history');
 $saved=$db->query("SELECT actor_id,installation_case_id FROM {$p}fm2_deadline_certificate_revisions ORDER BY id LIMIT 1")->fetch_assoc();assertSameValue(['18','6101'],array_values($saved),'session actor and route case override hostile body');
 $page=$fixture->request('GET',$path,[],$cookies);assertSameValue(200,$page['status'],'working return page');
 assertSameValue(1,preg_match('#/pilot/objects/4512/deadline-certificates/(\d+)/pdf#',$page['body'],$match),'download link');$revision=(int)$match[1];$downloadPath=$match[0];
 $download=$fixture->request('GET',$downloadPath,[],$cookies);assertSameValue([200,$a],[$download['status'],$download['body']],'exact PDF bytes');assertSameValue('application/pdf',$download['headers']['content-type'][0]??null,'PDF media type');assertSameValue(true,str_contains($download['headers']['content-disposition'][0]??'','attachment;'),'PDF attachment');assertSameValue('nosniff',$download['headers']['x-content-type-options'][0]??null,'nosniff');assertSameValue(true,str_contains($download['headers']['cache-control'][0]??'','no-store'),'private PDF cache');
 assertSameValue(true,str_contains($download['headers']['cache-control'][0]??'','private'),'PDF is private');
 $replay=$send($fields,$a);assertSameValue(303,$replay['status'],'HTTP replay');
 $conflict=$send(array_replace($fields,['newDeadline'=>'2026-09-21']),$a);assertSameValue(409,$conflict['status'],'HTTP operation collision');
 $correction=array_replace($fields,['requestId'=>'00000000-0000-4000-8000-000000000602','expectedVersion'=>'1','certificateDate'=>'2026-09-15','newDeadline'=>'2026-08-16','correctionReason'=>'Corrected date']);assertSameValue(303,$send($correction,$a."\n")['status'],'HTTP correction');
 $history=$fixture->request('GET',$path,[],$cookies);assertSameValue(true,str_contains($history['body'],'Corrected date'),'correction reason visible');preg_match_all('#/pilot/objects/4512/deadline-certificates/(\d+)/pdf#',$history['body'],$links);assertSameValue(2,count(array_unique($links[1])),'both PDF versions visible');assertSameValue($a,$fixture->request('GET',$downloadPath,[],$cookies)['body'],'old PDF unchanged');
 assertSameValue(409,$send(array_replace($correction,['requestId'=>'00000000-0000-4000-8000-000000000603']),$a)['status'],'stale correction');
 $badFields=array_replace($correction,['requestId'=>'00000000-0000-4000-8000-000000000604','expectedVersion'=>'2','newDeadline'=>'2026-02-30']);assertSameValue(422,$send($badFields,$a)['status'],'invalid form date');
 assertSameValue(404,$fixture->request('GET','/pilot/objects/999999/deadline-certificates',[],$cookies)['status'],'unknown object');assertSameValue(405,$fixture->request('DELETE',$path,[],$cookies)['status'],'method boundary');
 foreach([73,94,96] as $actor){$actorCookies=[];$fixture->login($actorCookies,$actor);$read=$fixture->request('GET',$path,[],$actorCookies);assertSameValue($actor===96?200:403,$read['status'],'role certificate read');if($actor===96)assertSameValue(false,str_contains($read['body'],'name="pdf"'),'OTIZ has no upload form');$token=$fixture->token($actorCookies);[$headers,$body]=CertFixture::multipart(array_replace($fields,['actorId'=>'18','installationCaseId'=>'6101','_csrf'=>$token,'requestId'=>sprintf('00000000-0000-4000-8000-%012d',700+$actor)]),$a);$denied=$fixture->request('POST',$path,[],$actorCookies,$headers,$body);assertSameValue(403,$denied['status'],'role cannot write');}
 foreach([73,94]as$actor){$deniedCookies=[];$fixture->login($deniedCookies,$actor);foreach(['/pilot/objects/4512/deadline-certificates','/pilot/objects/999999/deadline-certificates','/pilot/objects/999999/deadline-certificates/999999/pdf']as$deniedPath)assertSameValue(403,$fixture->request('GET',$deniedPath,[],$deniedCookies)['status'],'forbidden existing/missing parity');}
 // A known revision cannot be fetched through a different existing object.
 $db->query("INSERT INTO {$p}fm2_installation_cases SELECT 6102,4513,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version FROM {$p}fm2_installation_cases WHERE id=6101");
 $columns=array_column($db->query("SHOW COLUMNS FROM {$p}fm_maintable")->fetch_all(MYSQLI_ASSOC),'Field');$select=array_map(static fn(string $c):string=>$c==='id'?'4513':'`'.$c.'`',$columns);$db->query("INSERT INTO {$p}fm_maintable SELECT ".implode(',',$select)." FROM {$p}fm_maintable WHERE id=4512");
 assertSameValue(200,$fixture->request('GET','/pilot/objects/4513/deadline-certificates',[],$cookies)['status'],'second object exists');
 assertSameValue(404,$fixture->request('GET','/pilot/objects/4513/deadline-certificates/'.$revision.'/pdf',[],$cookies)['status'],'cross-object revision refused');
 assertSameValue(404,$fixture->request('GET',$path.'/99999999/pdf',[],$cookies)['status'],'nonexistent revision refused');
 foreach(['POST','PUT','PATCH','DELETE'] as $method)assertSameValue(405,$fixture->request($method,$downloadPath,['_csrf'=>$csrf],$cookies)['status'],'download method '.$method);
 foreach(['PUT','PATCH'] as $method)assertSameValue(405,$fixture->request($method,$path,['_csrf'=>$csrf],$cookies)['status'],'collection method '.$method);
 foreach(['2026-09-01','2026-09-20','2026-09-15','2026-08-16','18'] as $value)assertSameValue(true,str_contains($history['body'],$value),'history metadata '.$value);
 foreach($db->query("SELECT recorded_at FROM {$p}fm2_deadline_certificate_revisions ORDER BY id")->fetch_all(MYSQLI_ASSOC) as $row)assertSameValue(true,str_contains($history['body'],$row['recorded_at']),'exact audit time visible');
 $card=$fixture->request('GET','/pilot/objects/4512',[],$cookies);assertSameValue(true,str_contains($card['body'],$path),'object return navigation');
 $actorName=$db->query("SELECT full_name FROM {$p}fm2_pilot_users WHERE user_id=18")->fetch_column();preg_match_all('#<tr\b[^>]*>(.*?)</tr>#s',$history['body'],$historyRows);$matching=array_values(array_filter($historyRows[1],static fn($html)=>str_contains($html,$downloadPath)));assertSameValue(1,count($matching),'one historical row for initial revision');$visible=html_entity_decode(strip_tags($matching[0]),ENT_QUOTES|ENT_HTML5,'UTF-8');assertSameValue(true,str_contains($visible,$actorName)&&preg_match('/(?<![0-9])18(?![0-9])/',$visible)===1,'actor name and ID in the exact history row');
 $next=array_replace($correction,['actorId'=>'97','installationCaseId'=>'6102','requestId'=>'00000000-0000-4000-8000-000000000605','expectedVersion'=>'2']);
 $invalidPdf=$send($next,Pdf::forbidden('JavaScript'));assertSameValue(422,$invalidPdf['status'],'unsafe PDF maps422');
 $db->query("CREATE TRIGGER certificate_http_failure BEFORE INSERT ON {$p}fm2_deadline_certificate_pdf_chunks FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='injected chunk failure'");
 try{$unavailable=$send($next,$a);assertSameValue(503,$unavailable['status'],'actual storage failure maps503');}finally{$db->query('DROP TRIGGER certificate_http_failure');}
 assertSameValue(2,(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_deadline_certificate_revisions")->fetch_column(),'HTTP storage failure left no partial revision');
 // Start a fresh fixture server with a complete DI bootstrap before the first request.
 $bootstrap='<?php define("YII_DEBUG",false);define("YII_ENV","prod"); require '.var_export($fixture->root.'/vendor/autoload.php',true).'; require '.var_export($fixture->root.'/vendor/yiisoft/yii2/Yii.php',true).'; $app=new yii\\web\\Application(require '.var_export($fixture->root.'/config/yii/web.php',true).'); Yii::$container->set(FMonitor2\\DeadlineTransferCertificate\\DeadlineTransferCertificates::class, static function($container,$params,$config){return new FMonitor2\\DeadlineTransferCertificate\\DeadlineTransferCertificates($params[0],$params[1],$params[2],static function(string $phase):void{if($phase==="afterCommit")throw new RuntimeException("injected lost response");});}); $app->run();';
 $fixture->start([],$bootstrap);
 try{$unknown=$send($next,$a);assertSameValue(503,$unknown['status'],'unknown committed outcome maps503');}finally{$fixture->start();}
 assertSameValue(3,(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_deadline_certificate_revisions")->fetch_column(),'unknown response retained committed fact');
 assertSameValue(303,$send($next,$a)['status'],'same HTTP identity resolves unknown');assertSameValue(3,(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_deadline_certificate_revisions")->fetch_column(),'unknown retry does not duplicate');assertSameValue(0,(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_deadline_certificate_revisions WHERE installation_case_id=6102 OR actor_id<>18")->fetch_column(),'hostile client cannot write another case or forge author');
 $json=$fixture->request('GET',$path,[],$cookies,['Accept: application/json']);
 foreach([$page,$history,$conflict,$invalidPdf,$unavailable,$unknown,$json,$card] as $response)assertSameValue(false,str_contains($response['body'],$canary),'no PDF bytes in HTML or JSON/errors');
 foreach([$fixture->artifacts.'/server.log',$fixture->base->safeLog,...(glob($fixture->base->control.'/yii-runtime/logs/*')?:[])] as $log)if(is_file($log))assertSameValue(false,str_contains(file_get_contents($log),$canary),'no PDF bytes in request/application logs');
 echo "PASS DEADLINE-TRANSFER-CERTIFICATE-001 Yii flow\n";
}finally{if($fixture!==null)$fixture->close();}
