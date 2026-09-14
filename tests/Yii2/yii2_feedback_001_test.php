<?php
declare(strict_types=1);
/** FEEDBACK-001 A1–A7: real public owner + isolated MariaDB + Yii HTTP. */
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
    foreach (['id'=>$id,'description'=>$text,'actorId'=>9401,'pagePath'=>'/pilot/objects/1450','objectId'=>1450,'appVersion'=>'2.0','results'=>[]] as $field=>$expected) assertSameValue($expected,$item[$field],'stored '.$field);
    assertSameValue(true,is_string($item['createdAt'])&&strtotime($item['createdAt'])!==false,'system timestamp');
    foreach (['SECRET','private','ordinary.person','phone','password'] as $secret) assertSameValue(false,str_contains(json_encode(feedbackFacts($f)),$secret),'minimal context '.$secret);
    $before=feedbackFacts($f);
    assertSameValue($saved,$owner->submit(9401,$key,$text,'/pilot/objects/1450'),'A4 normalized replay');
    assertSameValue($saved,feedbackOwner($f,'2.1')->submit(9401,$key,$text,'/pilot/objects/1450'),'replay survives release version change');
    assertSameValue('conflict',$owner->submit(9401,$key,'Другой текст','/pilot/objects/1450')['status'],'request collision');
    assertSameValue($before,feedbackFacts($f),'replay/collision immutable');
    $n=10;
    foreach (['/pilot/activate?token=SECRET','https://evil.test/pilot/objects/1450','//evil.test/','/pilot/objects/SECRET','/pilot/objects/%31','/pilot/objects/999999999999999999999999',"/pilot/objects/1450\r\n",'/pilot\\objects\\1450'] as $path) {
        $r=$owner->submit(9401,feedbackUuid($n++),'Контекст',$path);assertSameValue('saved',$r['status'],'fallback safe');$v=$owner->listing(9101)['items'][0];
        assertSameValue(['/pilot/objects',null],[$v['pagePath'],$v['objectId']],'unsafe context fallback');
    }
    foreach (['/pilot/objects','/pilot/installers','/pilot/construction-control','/pilot/admin/users','/pilot/admin/roles','/pilot/feedback','/pilot/admin/feedback','/pilot/objects/1450/checklist','/pilot/objects/1450/assignment-order/selection','/pilot/objects/1450/execution','/pilot/construction-control/objects/1450/checklist','/pilot/objects/1450/assignment-orders/7/originals/submit','/pilot/objects/1450/assignment-orders/7/originals/history'] as $path) {
        $r=$owner->submit(9401,feedbackUuid($n++),'Маршрут',$path.'?token=SECRET');$v=$owner->listing(9101)['items'][0];assertSameValue($path,$v['pagePath'],'allowed route');
    }
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
    $failed=false;try {feedbackOwner($f,'unsafe secret version')->submit(9401,feedbackUuid(104),'Текст','/pilot/objects');}catch(Throwable){$failed=true;}assertSameValue(true,$failed,'invalid server version rejected');assertSameValue($before,feedbackFacts($f),'version no facts');
    // A5 append-only result, exact replay, collision, missing root, current permission revocation.
    $rootBefore=$f->rows('fm2_feedback');$r=$owner->recordResult(9101,$id,feedbackUuid(200),'Проверено: исправлено');assertSameValue('saved',$r['status'],'result saved');
    $before=feedbackFacts($f);assertSameValue($r,$owner->recordResult(9101,$id,feedbackUuid(200),'Проверено: исправлено'),'result replay');assertSameValue('conflict',$owner->recordResult(9101,$id,feedbackUuid(200),'Другой результат')['status'],'result collision');assertSameValue($before,feedbackFacts($f),'result replay immutable');
    assertSameValue('saved',$owner->recordResult(9101,$id,feedbackUuid(201),'Повторно проверено на стенде')['status'],'second note');assertSameValue($rootBefore,$f->rows('fm2_feedback'),'roots never rewritten');
    $found=array_values(array_filter($owner->listing(9101)['items'],fn($x)=>$x['id']===$id))[0];
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
    echo "PASS: FEEDBACK-001 A1-A7 owner, HTTP, concurrency, schema and recovery inventory\n";
} finally {if($f instanceof UserAccessFixture)$f->close();}
