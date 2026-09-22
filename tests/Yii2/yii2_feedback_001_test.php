<?php
declare(strict_types=1);
/** FEEDBACK-001 A1–A9: real public owner + isolated MariaDB + Yii HTTP. */
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/FeedbackFixture.php';
$f=null;
try {
    $f=new UserAccessFixture(dirname(__DIR__,2));$owner=feedbackOwner($f);
    assertSameValue(['items'=>[],'nextBeforeId'=>null],$owner->listing(9101),'empty feedback list');
    // A1/A2: independent literal expectations; DB reads observe persistence, never perform commands.
    $text='Не открывается карточка';$key=feedbackUuid(1);
    $saved=$owner->submit(9401,$key,'  '.$text.'  ','/pilot/objects/1450?token=SECRET&email=private#fragment');
    assertSameValue('saved',$saved['status'],'A1 saved');$id=$saved['id'];assertSameValue(true,is_int($id)&&$id>0,'numeric receipt');
    $items=$owner->listing(9101)['items'];assertSameValue(1,count($items),'one submission');$item=$items[0];
    $buildA='aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';$buildB='bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';
    foreach (['id'=>$id,'description'=>$text,'actorId'=>9401,'pagePath'=>'/pilot/objects/1450','objectId'=>1450,'appVersion'=>$buildA,'results'=>[]] as $field=>$expected) assertSameValue($expected,$item[$field],'stored '.$field);
    assertSameValue(true,is_string($item['createdAt'])&&strtotime($item['createdAt'])!==false,'system timestamp');
    foreach (['SECRET','private','ordinary.person','phone','password'] as $secret) assertSameValue(false,str_contains(json_encode(feedbackFacts($f)),$secret),'minimal context '.$secret);
    $before=feedbackFacts($f);
    assertSameValue($saved,$owner->submit(9401,$key,$text,'/pilot/objects/1450'),'A4 normalized replay');
    assertSameValue($saved,feedbackOwner($f,$buildB)->submit(9401,$key,$text,'/pilot/objects/1450'),'replay survives release version change');
    assertSameValue('conflict',$owner->submit(9401,$key,'Другой текст','/pilot/objects/1450')['status'],'request collision');
    assertSameValue($before,feedbackFacts($f),'replay/collision immutable');
    $n=10;
    foreach (['/pilot/activate?token=SECRET','https://evil.test/pilot/objects/1450','//evil.test/','/pilot/objects/SECRET','/pilot/objects/%31','/pilot/objects/999999999999999999999999',"/pilot/objects/1450\r\n",'/pilot\\objects\\1450','/pilot/otiz/snapshots/731/export.xlsx','/pilot/objects/1450/checklist/photos/9','/pilot/construction-control/objects/1450/sync-context','/pilot/arbitrary','/pilot/login'] as $path) {
        $r=$owner->submit(9401,feedbackUuid($n++),'Контекст',$path);assertSameValue('saved',$r['status'],'fallback safe');$v=$owner->listing(9101)['items'][0];
        assertSameValue(['/pilot/objects',null],[$v['pagePath'],$v['objectId']],'unsafe context fallback');
    }
    $allowed=[
        '/pilot/objects'=>null,'/pilot/installers'=>null,'/pilot/construction-control'=>null,'/pilot/calendar'=>null,'/pilot/dashboard'=>null,
        '/pilot/admin/users'=>null,'/pilot/users'=>null,'/pilot/admin/roles'=>null,'/pilot/feedback'=>null,'/pilot/admin/feedback'=>null,
        '/pilot/otiz'=>null,'/pilot/otiz/objects'=>null,'/pilot/otiz/payments'=>null,'/pilot/otiz/history'=>null,'/pilot/otiz/snapshots/731'=>null,
        '/pilot/objects/1450'=>1450,'/pilot/objects/1450/checklist'=>1450,'/pilot/objects/1450/assignment-order/prepare'=>1450,
        '/pilot/objects/1450/assignment-order/selection'=>1450,'/pilot/objects/1450/execution'=>1450,'/pilot/objects/1450/deadline-certificates'=>1450,
        '/pilot/construction-control/objects/1450/checklist'=>1450,'/pilot/objects/1450/assignment-orders/7/originals/submit'=>1450,
        '/pilot/objects/1450/assignment-orders/7/originals/history'=>1450,
    ];
    foreach ($allowed as $path=>$objectId) {
        $r=$owner->submit(9401,feedbackUuid($n++),'Маршрут',$path.'?token=SECRET#fragment');$v=$owner->listing(9101)['items'][0];assertSameValue([$path,$objectId],[$v['pagePath'],$v['objectId']],'allowed route '.$path);
    }
    $b=$owner->submit(9401,feedbackUuid($n++),'Сборка B','/pilot/dashboard');$bItem=$owner->listing(9101)['items'][0];assertSameValue([$b['id'],$buildA],[$bItem['id'],$bItem['appVersion']],'existing owner retains build A');
    $newB=feedbackOwner($f,$buildB)->submit(9401,feedbackUuid($n++),'Сборка B','/pilot/dashboard');$newBItem=$owner->listing(9101)['items'][0];assertSameValue([$newB['id'],$buildB],[$newBItem['id'],$newBItem['appVersion']],'new request stores full build B');
    $missing=$f->artifacts.'/missing-build';$unknownOwner=feedbackOwner($f,$buildA,$missing);$unknown=$unknownOwner->submit(9401,feedbackUuid($n++),'Без identity','/pilot/calendar');$unknownItem=$owner->listing(9101)['items'][0];assertSameValue([$unknown['id'],'unknown'],[$unknownItem['id'],$unknownItem['appVersion']],'missing identity is explicit unknown');
    $cwd=getcwd();chdir($f->artifacts);feedbackBuildFile($f,$buildA,'relative-build');feedbackOwner($f,$buildA,'feedback-build-relative-build')->submit(9401,feedbackUuid($n++),'Relative identity','/pilot/calendar');chdir($cwd);assertSameValue('unknown',$owner->listing(9101)['items'][0]['appVersion'],'existing valid relative identity path is unknown');
    $mutable=$f->artifacts.'/mutable-build';file_put_contents($mutable,$buildA."\n");chmod($mutable,0644);$mutableResult=feedbackOwner($f,$buildA,$mutable)->submit(9401,feedbackUuid($n++),'Изменяемая identity','/pilot/calendar');assertSameValue('unknown',$owner->listing(9101)['items'][0]['appVersion'],'mutable identity is unknown');assertSameValue(true,$mutableResult['id']>0,'unknown does not block feedback');
    $target=feedbackBuildFile($f,$buildA,'symlink-target');$link=$f->artifacts.'/symlink-build';symlink($target,$link);feedbackOwner($f,$buildA,$link)->submit(9401,feedbackUuid($n++),'Symlink identity','/pilot/calendar');assertSameValue('unknown',$owner->listing(9101)['items'][0]['appVersion'],'symlink identity is unknown');
    $hardTarget=feedbackBuildFile($f,$buildA,'hardlink-target');$hardLink=$f->artifacts.'/hardlink-build';link($hardTarget,$hardLink);feedbackOwner($f,$buildA,$hardLink)->submit(9401,feedbackUuid($n++),'Hardlink identity','/pilot/calendar');assertSameValue('unknown',$owner->listing(9101)['items'][0]['appVersion'],'multiply linked identity is unknown');
    $malformed=$f->artifacts.'/malformed-build';file_put_contents($malformed,str_repeat('g',64)."\n");chmod($malformed,0444);feedbackOwner($f,$buildA,$malformed)->submit(9401,feedbackUuid($n++),'Malformed identity','/pilot/calendar');assertSameValue('unknown',$owner->listing(9101)['items'][0]['appVersion'],'malformed identity is unknown');
    $unreadable=$f->artifacts.'/unreadable-build';file_put_contents($unreadable,$buildA."\n");chmod($unreadable,0000);feedbackOwner($f,$buildA,$unreadable)->submit(9401,feedbackUuid($n++),'Unreadable identity','/pilot/calendar');assertSameValue('unknown',$owner->listing(9101)['items'][0]['appVersion'],'unreadable identity is unknown');
    $feedbackSource=file_get_contents(dirname(__DIR__,2).'/app/YiiRuntime/FeedbackApplication.php');foreach(['RuntimeBuildIdentity','sourceIdentity','RecursiveDirectoryIterator','shell_exec','proc_open','popen','exec(','system(','passthru','pcntl_exec','pcntl_fork','curl_','fsockopen','pfsockopen','stream_socket_client','socket_create','socket_connect','socket_send','socket_write','.git','docker.sock']as$dependency)assertSameValue(false,str_contains($feedbackSource,$dependency),'feedback HTTP excludes heavy/external dependency '.$dependency);assertSameValue(0,preg_match('#["\'](?:https?|ftp|file|phar|data)://#i',$feedbackSource),'feedback HTTP source contains no external stream URI');
    $stableFs=new FeedbackIdentityFilesystemProbe(false);feedbackOwner($f,$buildA,'/virtual/build-id',$stableFs)->submit(9401,feedbackUuid($n++),'Stable handle','/pilot/calendar');assertSameValue('eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee',$owner->listing(9101)['items'][0]['appVersion'],'stable same-handle identity accepted');assertSameValue([['open','/virtual/build-id'],['fstat',1],['read',66],['fstat',2],['lstat','/virtual/build-id'],['close']],$stableFs->calls,'exact stable identity filesystem sequence');
    $rebindFs=new FeedbackIdentityFilesystemProbe(true);feedbackOwner($f,$buildA,'/virtual/build-id',$rebindFs)->submit(9401,feedbackUuid($n++),'Rebound handle','/pilot/calendar');assertSameValue('unknown',$owner->listing(9101)['items'][0]['appVersion'],'pathname rebind is unknown');assertSameValue([['open','/virtual/build-id'],['fstat',1],['read',66],['fstat',2],['lstat','/virtual/build-id'],['close']],$rebindFs->calls,'exact rebound identity filesystem sequence');
    $mutatedFs=new FeedbackIdentityFilesystemProbe(false,true);feedbackOwner($f,$buildA,'/virtual/build-id',$mutatedFs)->submit(9401,feedbackUuid($n++),'Mutated handle','/pilot/calendar');assertSameValue('unknown',$owner->listing(9101)['items'][0]['appVersion'],'post-read handle mutation is unknown');assertSameValue([['open','/virtual/build-id'],['fstat',1],['read',66],['fstat',2],['lstat','/virtual/build-id'],['close']],$mutatedFs->calls,'exact mutated handle filesystem sequence');
    $probeInput=['mode'=>'dependencyProbe','environment'=>$f->environment(),'prefix'=>$f->p,'allowedRoot'=>$f->artifacts,'dependencyRoot'=>$f->root.'/vendor','requestId'=>feedbackUuid($n++)];$probeProcess=proc_open([PHP_BINARY,__DIR__.'/feedback_worker.php'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$probePipes,$f->root);if(!is_resource($probeProcess))throw new TestFailure('SETUP_FAILURE dependency probe');fwrite($probePipes[0],json_encode($probeInput,JSON_THROW_ON_ERROR));fclose($probePipes[0]);$probeOut=stream_get_contents($probePipes[1]);$probeErr=stream_get_contents($probePipes[2]);fclose($probePipes[1]);fclose($probePipes[2]);assertSameValue(0,proc_close($probeProcess),'missing identity dependency probe: '.$probeErr);$probe=json_decode($probeOut,true,flags:JSON_THROW_ON_ERROR);assertSameValue(['saved','unknown','/pilot/calendar'],[$probe['saved']['status'],$probe['item']['appVersion'],$probe['item']['pagePath']],'submit/listing stay inside configured file, dependency and DB boundary');
    // A3 fresh actor/permission checks at application seam, including direct invocation.
    foreach ([0,999999,9403] as $actor) {
        if ($actor===9403) $f->db->query("UPDATE {$f->p}fm2_pilot_users SET activation_state='blocked' WHERE user_id=9403");
        $before=feedbackFacts($f);assertSameValue('access_denied',$owner->submit($actor,feedbackUuid(100),'Нет','/pilot/objects')['status'],'inactive submit denied');assertSameValue($before,feedbackFacts($f),'denied no facts');
    }
    foreach ([9401,0,9403] as $actor) {
        $before=feedbackFacts($f);$denied=false;try {$owner->listing($actor);} catch (DomainException $e) {$denied=$e->getMessage()==='ACCESS_DENIED';}assertSameValue(true,$denied,'private directory');
        assertSameValue('access_denied',$owner->recordResult($actor,$id,feedbackUuid(101),'Нет')['status'],'private result');assertSameValue($before,feedbackFacts($f),'private no facts');
    }
    foreach (['','   ',str_repeat('Я',4001),"bad\0text", "bad\xFF"] as $invalid) {
        $before=feedbackFacts($f);assertSameValue('invalid',$owner->submit(9401,feedbackUuid(102),$invalid,'/pilot/objects')['status'],'invalid description');assertSameValue($before,feedbackFacts($f),'invalid no facts');
    }
    assertSameValue('saved',$owner->submit(9401,feedbackUuid(103),str_repeat('Я',4000),'/pilot/objects')['status'],'4000 unicode accepted');
    $before=feedbackFacts($f);assertSameValue('invalid',$owner->submit(9401,'not-uuid','Текст','/pilot/objects')['status'],'bad UUID');assertSameValue($before,feedbackFacts($f),'bad UUID no facts');
    // A5 append-only result, exact replay, collision, missing root, current permission revocation.
    $rootBefore=$f->rows('fm2_feedback');$r=$owner->recordResult(9101,$id,feedbackUuid(200),'Проверено: исправлено');assertSameValue('saved',$r['status'],'result saved');
    $before=feedbackFacts($f);assertSameValue($r,$owner->recordResult(9101,$id,feedbackUuid(200),'Проверено: исправлено'),'result replay');assertSameValue('conflict',$owner->recordResult(9101,$id,feedbackUuid(200),'Другой результат')['status'],'result collision');assertSameValue($before,feedbackFacts($f),'result replay immutable');
    assertSameValue('saved',$owner->recordResult(9101,$id,feedbackUuid(201),'Повторно проверено на стенде')['status'],'second note');assertSameValue($rootBefore,$f->rows('fm2_feedback'),'roots never rewritten');
    $found=null;$findCursor=null;do{$findPage=$owner->listing(9101,$findCursor);foreach($findPage['items']as$candidate)if($candidate['id']===$id)$found=$candidate;$findCursor=$findPage['nextBeforeId'];}while($found===null&&$findCursor!==null);assertSameValue(true,is_array($found),'historical feedback reachable through listing cursor');
    assertSameValue(['Проверено: исправлено','Повторно проверено на стенде'],array_column($found['results'],'result'),'ordered history');assertSameValue([9101,9101],array_column($found['results'],'actorId'),'result attribution');
    $before=feedbackFacts($f);
    foreach (['',str_repeat('Я',2001),"a\0b"] as $bad) assertSameValue('invalid',$owner->recordResult(9101,$id,feedbackUuid(202),$bad)['status'],'invalid result');
    assertSameValue('invalid',$owner->recordResult(9101,$id,'invalid','Текст')['status'],'bad result UUID');assertSameValue('not_found',$owner->recordResult(9101,999999,feedbackUuid(202),'Текст')['status'],'missing root');assertSameValue($before,feedbackFacts($f),'result rejects no facts');
    $f->auth->setRoleStatus(0);assertSameValue('access_denied',$owner->recordResult(9101,$id,feedbackUuid(202),'Текст')['status'],'fresh inactive role');$f->auth->setRoleStatus(1);
    // A4 fault after database command cannot claim success or retain partial facts.
    foreach (['fm2_feedback'=>fn()=>$owner->submit(9401,feedbackUuid(300),'Сбой','/pilot/objects'),'fm2_feedback_results'=>fn()=>$owner->recordResult(9101,$id,feedbackUuid(301),'Сбой')] as $table=>$call) {
        $f->db->query("CREATE TRIGGER {$f->p}feedback_fail BEFORE INSERT ON {$f->p}{$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='private SQL failure'");$before=feedbackFacts($f);$failed=false;try {$call();}catch(Throwable){$failed=true;}assertSameValue(true,$failed,'storage fault signalled');assertSameValue($before,feedbackFacts($f),'storage fault no facts');$f->db->query("DROP TRIGGER {$f->p}feedback_fail");assertSameValue('saved',$call()['status'],'retry after storage failure');
    }
    $countBefore=count($f->rows('fm2_feedback'));$same=['submit',[9401,feedbackUuid(400),'Параллельно','/pilot/objects']];$rr=feedbackParallel($f,[$same,$same]);assertSameValue($rr[0],$rr[1],'parallel same receipt');assertSameValue('saved',$rr[0]['status'],'parallel saved');assertSameValue($countBefore+1,count($f->rows('fm2_feedback')),'one parallel root');
    $rr=feedbackParallel($f,[['submit',[9401,feedbackUuid(401),'Один','/pilot/objects']],['submit',[9401,feedbackUuid(401),'Два','/pilot/objects']]]);$states=array_column($rr,'status');sort($states);assertSameValue(['conflict','saved'],$states,'parallel collision');
    $countBefore=count($f->rows('fm2_feedback_results'));$same=['recordResult',[9101,$id,feedbackUuid(402),'Параллельный разбор']];$rr=feedbackParallel($f,[$same,$same]);assertSameValue($rr[0],$rr[1],'parallel result receipt');assertSameValue('saved',$rr[0]['status'],'parallel result saved');assertSameValue($countBefore+1,count($f->rows('fm2_feedback_results')),'one parallel result');
    $rr=feedbackParallel($f,[['recordResult',[9101,$id,feedbackUuid(403),'Первый ответ']],['recordResult',[9101,$id,feedbackUuid(404),'Второй ответ']]]);assertSameValue(['saved','saved'],array_column($rr,'status'),'independent notes both append');assertSameValue($countBefore+3,count($f->rows('fm2_feedback_results')),'two independent notes retained');
    $other=$owner->submit(9401,feedbackUuid(405),'Другой объект','/pilot/objects');$before=feedbackFacts($f);assertSameValue('conflict',$owner->recordResult(9101,$other['id'],feedbackUuid(402),'Параллельный разбор')['status'],'same result key different root conflict');assertSameValue($before,feedbackFacts($f),'different-root conflict no facts');
    $rr=feedbackParallel($f,[['recordResult',[9101,$id,feedbackUuid(406),'Один']],['recordResult',[9101,$id,feedbackUuid(406),'Два']]]);$states=array_column($rr,'status');sort($states);assertSameValue(['conflict','saved'],$states,'parallel result collision');assertSameValue($countBefore+4,count($f->rows('fm2_feedback_results')),'one result on collision');
    // A5 keyset pagination never drops older reports.
    for ($i=500;$i<555;$i++) assertSameValue('saved',$owner->submit(9401,feedbackUuid($i),'Пагинация '.$i,'/pilot/objects')['status'],'page seed through owner');
    $seen=[];$cursor=null;do {$page=$owner->listing(9101,$cursor);assertSameValue(true,count($page['items'])<=50,'bounded list');$seen=array_merge($seen,array_column($page['items'],'id'));$cursor=$page['nextBeforeId'];}while($cursor!==null);
    $expected=array_map('intval',array_column($f->rows('fm2_feedback'),'id'));rsort($expected);assertSameValue($expected,$seen,'all rows exactly once descending');
    require __DIR__.'/feedback_http_matrix.php';
    require __DIR__.'/feedback_schema_matrix.php';
    echo "PASS: FEEDBACK-001 A1-A9 owner, HTTP, concurrency, schema and recovery inventory\n";
} finally {if($f instanceof UserAccessFixture)$f->close();}
