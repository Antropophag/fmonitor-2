<?php
declare(strict_types=1);
// Included by FEEDBACK-001; real Yii transport, no controller mocks.
$f->start();$guest=[];$admin=[];$ordinary=[];
$r=$f->request('GET','/pilot/feedback?from=%2Fpilot%2Fobjects',[],$guest);assertSameValue(303,$r['status'],'guest feedback login');
assertSameValue(303,$f->login($admin)['status'],'admin login');assertSameValue(303,$f->login($ordinary,'ordinary.person@shlz.ru')['status'],'ordinary login');
$form=$f->request('GET','/pilot/feedback?from='.rawurlencode('/pilot/objects/1450?token=SECRET'),[],$ordinary);assertSameValue(200,$form['status'],'feedback form');
assertSameValue(false,str_contains($form['body'],'SECRET'),'form drops secret');
$fields=['_csrf'=>$f->csrf($form['body']),'requestId'=>feedbackInput($form['body'],'requestId'),'pagePath'=>feedbackInput($form['body'],'pagePath'),'description'=>'<script>alert("x")</script> HTTP описание','appVersion'=>'CLIENT-SECRET','objectId'=>'999'];
$before=feedbackFacts($f);$r=$f->request('POST','/pilot/feedback',array_diff_key($fields,['_csrf'=>true]),$ordinary);assertSameValue(400,$r['status'],'missing CSRF');assertSameValue($before,feedbackFacts($f),'CSRF no write');
$r=$f->request('POST','/pilot/feedback',array_replace($fields,['description'=>['bad']]),$ordinary);assertSameValue(400,$r['status'],'array description denied');
$r=$f->request('POST','/pilot/feedback',array_replace($fields,['description'=>' ']),$ordinary);assertSameValue(422,$r['status'],'empty description HTTP');assertSameValue($fields['requestId'],feedbackInput($r['body'],'requestId'),'validation keeps request');
$r=$f->request('POST','/pilot/feedback',$fields,$ordinary);assertSameValue(200,$r['status'],'saved confirmation');assertSameValue(true,str_contains($r['body'],'Обращение сохранено'),'clear success');assertSameValue(true,str_contains($r['body'],'href="/pilot/objects/1450"'),'safe return');
$before=feedbackFacts($f);$r=$f->request('POST','/pilot/feedback',$fields,$ordinary);assertSameValue(200,$r['status'],'HTTP repeat');assertSameValue($before,feedbackFacts($f),'HTTP repeat no duplicate');
$latest=$owner->listing(9101)['items'][0];assertSameValue(['2.0',1450],[$latest['appVersion'],$latest['objectId']],'server version and derived object');$httpId=$latest['id'];
$r=$f->request('GET','/pilot/admin/feedback',[],$ordinary);assertSameValue(403,$r['status'],'ordinary list forbidden');assertSameValue(false,str_contains($r['body'],'HTTP описание'),'list privacy');
$r=$f->request('POST',"/pilot/admin/feedback/$httpId/result",['_csrf'=>$fields['_csrf'],'requestId'=>feedbackUuid(800),'result'=>'Нет'],$ordinary);assertSameValue(403,$r['status'],'ordinary result forbidden');
$listing=$f->request('GET','/pilot/admin/feedback',[],$admin);assertSameValue(200,$listing['status'],'admin list');assertSameValue(false,str_contains($listing['body'],'<script>alert('),'stored XSS escaped');assertSameValue(true,str_contains($listing['body'],'&lt;script&gt;'),'literal escaped text');
$resultFields=['_csrf'=>$f->csrf($listing['body']),'requestId'=>feedbackUuid(801),'result'=>'HTTP проверено'];
$r=$f->request('POST',"/pilot/admin/feedback/$httpId/result",$resultFields,$admin);assertSameValue(true,in_array($r['status'],[200,303],true),'HTTP result success');$before=feedbackFacts($f);
$r=$f->request('POST',"/pilot/admin/feedback/$httpId/result",$resultFields,$admin);assertSameValue(true,in_array($r['status'],[200,303],true),'HTTP result replay');assertSameValue($before,feedbackFacts($f),'HTTP result replay no duplicate');
$r=$f->request('GET',"/pilot/admin/feedback/$httpId/result",[],$admin);assertSameValue(true,in_array($r['status'],[404,405],true),'GET cannot write');assertSameValue($before,feedbackFacts($f),'GET no facts');
$retryFields=array_replace($fields,['requestId'=>feedbackUuid(810),'description'=>'Сохраните мой текст']);
$f->db->query("CREATE TRIGGER {$f->p}feedback_fail_http BEFORE INSERT ON {$f->p}fm2_feedback FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='private SQL failure'");
$r=$f->request('POST','/pilot/feedback',$retryFields,$ordinary);assertSameValue(503,$r['status'],'save failure HTTP');assertSameValue(true,str_contains($r['body'],'Повторите отправку'),'retry explained');assertSameValue(true,str_contains($r['body'],'Сохраните мой текст'),'retry text retained');assertSameValue($retryFields['requestId'],feedbackInput($r['body'],'requestId'),'retry ID retained');
foreach (['private SQL failure','SQLSTATE',$f->dmlPassword] as $secret) assertSameValue(false,str_contains($r['body'],$secret),'safe failure page');
$f->db->query("DROP TRIGGER {$f->p}feedback_fail_http");assertSameValue(200,$f->request('POST','/pilot/feedback',$retryFields,$ordinary)['status'],'HTTP retry recovered');
$r=$f->request('POST','/pilot/feedback',array_replace($retryFields,['description'=>'Изменённый текст']),$ordinary);assertSameValue(409,$r['status'],'HTTP conflict');
// Shared and copied shells expose the small action without changing process controllers.
foreach (['/pilot/admin/users','/pilot/admin/roles','/pilot/feedback'] as $path) {
    $r=$f->request('GET',$path,[],$admin);assertSameValue(200,$r['status'],'adjacent route '.$path);assertSameValue(true,str_contains($r['body'],'/pilot/feedback'),'feedback navigation '.$path);
}
$f->noLegacy();
// A3/A4 complete method, rejection, no-write and retry-field matrix.
$reject=function(string $method,string $path,array $data,array &$cookies,int $status,array $headers=[])use($f):array {
 $before=feedbackFacts($f);$response=$f->request($method,$path,$data,$cookies,$headers);assertSameValue($status,$response['status'],"$method $path -> $status");assertSameValue($before,feedbackFacts($f),'transport rejection/HEAD changes no facts');return$response;
};
foreach(['/pilot/feedback','/pilot/admin/feedback']as$path){$r=$reject('HEAD',$path,[],$admin,200);assertSameValue('',$r['body'],'HEAD body empty');}
foreach([['PUT','/pilot/feedback'],['DELETE','/pilot/feedback'],['POST','/pilot/admin/feedback'],['GET',"/pilot/admin/feedback/$httpId/result"],['HEAD',"/pilot/admin/feedback/$httpId/result"],['PUT',"/pilot/admin/feedback/$httpId/result"]]as[$method,$path])$reject($method,$path,['_csrf'=>$resultFields['_csrf']],$admin,405);
$guestForm=$f->request('GET','/pilot/login',[],$guest);$guestCsrf=$f->csrf($guestForm['body']);
$reject('POST','/pilot/feedback',['description'=>'Guest'],$guest,400);
$reject('POST','/pilot/feedback',['_csrf'=>$guestCsrf,'description'=>'Guest','requestId'=>feedbackUuid(820),'pagePath'=>'/pilot/objects'],$guest,303);
$reject('POST','/pilot/feedback',array_replace($fields,['description'=>['bad']]),$ordinary,400);
$reject('POST','/pilot/feedback',array_replace($fields,['pagePath'=>['bad']]),$ordinary,400);
$validText='Сохраните это описание';$r=$reject('POST','/pilot/feedback',array_replace($fields,['requestId'=>'invalid','description'=>$validText]),$ordinary,422);
assertSameValue(true,str_contains($r['body'],$validText),'422 retains valid description');assertSameValue('/pilot/objects/1450',feedbackInput($r['body'],'pagePath'),'422 retains safe context');
$reject('GET','/pilot/admin/feedback',[],$ordinary,403);
$reject('POST',"/pilot/admin/feedback/$httpId/result",['_csrf'=>$fields['_csrf'],'requestId'=>feedbackUuid(821),'result'=>'Denied'],$ordinary,403);
foreach([['result'=>['bad']],['requestId'=>['bad']]]as$bad)$reject('POST',"/pilot/admin/feedback/$httpId/result",array_replace($resultFields,$bad),$admin,400);
$reject('POST',"/pilot/admin/feedback/$httpId/result",['requestId'=>feedbackUuid(821),'result'=>'No csrf'],$admin,400);
$r=$reject('POST',"/pilot/admin/feedback/$httpId/result",array_replace($resultFields,['requestId'=>'bad','result'=>'Сохраните результат']),$admin,422);assertSameValue(true,str_contains($r['body'],'Сохраните результат'),'result422 retains text');
$reject('POST','/pilot/admin/feedback/999999/result',array_replace($resultFields,['requestId'=>feedbackUuid(822)]),$admin,404);
$reject('POST',"/pilot/admin/feedback/$httpId/result",array_replace($resultFields,['result'=>'Collision']),$admin,409);
$f->db->query("CREATE TRIGGER {$f->p}feedback_fail_result BEFORE INSERT ON {$f->p}fm2_feedback_results FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PRIVATE-RESULT-SQL'");
$retryResult=array_replace($resultFields,['requestId'=>feedbackUuid(823),'result'=>'Повтор результата']);
$r=$reject('POST',"/pilot/admin/feedback/$httpId/result",$retryResult,$admin,503);assertSameValue(true,str_contains($r['body'],'Повтор результата'),'result503 retains text');assertSameValue($retryResult['requestId'],feedbackInput($r['body'],'requestId'),'result503 retains ID');assertSameValue(true,str_contains($r['body'],'Повторите отправку'),'result503 retry explanation');
foreach(['PRIVATE-RESULT-SQL','SQLSTATE',$f->dmlPassword]as$secret)assertSameValue(false,str_contains($r['body'],$secret),'safe result failure');
$f->db->query("DROP TRIGGER {$f->p}feedback_fail_result");$r=$f->request('POST',"/pilot/admin/feedback/$httpId/result",$retryResult,$admin);assertSameValue(true,in_array($r['status'],[200,303],true),'result retry recovers');
$f->db->query("CREATE TRIGGER {$f->p}feedback_fail_again BEFORE INSERT ON {$f->p}fm2_feedback FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='PRIVATE-SUBMIT-SQL'");
$r=$reject('POST','/pilot/feedback',array_replace($fields,['requestId'=>feedbackUuid(824),'description'=>$validText]),$ordinary,503);assertSameValue(feedbackUuid(824),feedbackInput($r['body'],'requestId'),'503 same request ID');assertSameValue('/pilot/objects/1450',feedbackInput($r['body'],'pagePath'),'503 retains safe context');assertSameValue(true,str_contains($r['body'],$validText),'503 retains valid text');$f->db->query("DROP TRIGGER {$f->p}feedback_fail_again");
$hostile=array_replace($fields,['requestId'=>feedbackUuid(825),'description'=>'Минимальные данные','fullName'=>'PRIVATE-FULL-NAME','email'=>'PRIVATE-EMAIL','phone'=>'PRIVATE-PHONE','ip'=>'PRIVATE-IP','document'=>'PRIVATE-DOCUMENT']);
$r=$f->request('POST','/pilot/feedback',$hostile,$ordinary,['X-Forwarded-For: 203.0.113.99','X-Private-Test: PRIVATE-HEADER','Referer: https://evil.test/PRIVATE-REFERER']);assertSameValue(200,$r['status'],'hostile metadata ignored');
$serialized=json_encode([feedbackFacts($f),$owner->listing(9101)],JSON_THROW_ON_ERROR);
foreach(['PRIVATE-FULL-NAME','PRIVATE-EMAIL','PRIVATE-PHONE','PRIVATE-IP','PRIVATE-DOCUMENT','203.0.113.99','PRIVATE-HEADER','PRIVATE-REFERER','CLIENT-SECRET']as$secret)assertSameValue(false,str_contains($serialized,$secret),'forbidden metadata not collected');
