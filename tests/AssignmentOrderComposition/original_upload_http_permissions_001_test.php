<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\{OriginalHttpFixture as F,SelectionHttpAssertions as A};
// ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001: exact local read/action and native command grants.
$failures=[];
$run=static function(string $name,Closure $check)use(&$failures):void{
    $f=null;try{$f=new F();$check($f);echo "PASS $name\n";}
    catch(Throwable $e){$failures[]=$name;echo "FAIL $name: ".$e->getMessage()."\n";}
    finally{if($f!==null)$f->close();}
};
$revoke=static function(F $f,string $permission):void{
    $db=$f->http->original->selection->db;
    $db->query("DELETE FROM fm2_pilot_role_permissions WHERE role_id=1 AND permission='".$db->real_escape_string($permission)."'");
};
$run('read grant required only by form',static function(F $f)use($revoke):void{
    $revoke($f,'assignment_order.original.read');$before=$f->http->original->selection->rows();
    assertSameValue(['error'=>'ACCESS_DENIED'],F::json($f->http->request('GET',F::FORM),403),'form requires explicit read');
    assertSameValue($before,$f->http->original->selection->rows(),'denied form writes nothing');
    assertSameValue('accepted',F::json($f->post($f->fields()),201)['status'],'upload does not inherit form read requirement');
});
$run('initial requires upload even with correction',static function(F $f)use($revoke):void{
    $revoke($f,'assignment_order.original.upload');$before=$f->http->original->selection->rows();
    assertSameValue(['error'=>'ACCESS_DENIED'],F::json($f->http->request('GET',F::FORM),403),'initial form exact action');
    assertSameValue(['error'=>'ACCESS_DENIED'],F::json($f->post($f->fields()),403),'correction grant cannot upload');
    assertSameValue($before,$f->http->original->selection->rows(),'local denial no command facts');
});
$run('correction requires correct independently',static function(F $f)use($revoke):void{
    $accepted=F::json($f->post($f->fields()),201);$fields=$f->fields(2);
    $fields=array_replace($fields,['mode'=>'correction','rootOriginalId'=>$accepted['rootOriginalId'],'targetRevisionId'=>$accepted['currentRevisionId'],
        'expectedCurrentRevisionId'=>$accepted['currentRevisionId'],'documentDate'=>'2026-09-02','correctionReason'=>'Исправлена дата']);
    $revoke($f,'assignment_order.original.correct');$native=$f->http->original->selection;$before=$native->rows();$files=$f->http->original->privateFiles();
    assertSameValue(['error'=>'ACCESS_DENIED'],F::json($f->http->request('GET',F::FORM),403),'correction form exact action');
    assertSameValue(['error'=>'ACCESS_DENIED'],F::json($f->post($fields),403),'upload grant cannot correct');
    assertSameValue($before,$native->rows(),'local correction denial no facts');assertSameValue($files,$f->http->original->privateFiles(),'original unchanged');
    $native->schema->insert('fm2_pilot_role_permissions',['role_id'=>1,'permission'=>'assignment_order.original.correct']);
    $revoke($f,'assignment_order.original.upload');
    assertSameValue(200,$f->http->request('GET',F::FORM)['status'],'correction form does not require upload');
    $native->db->query("DELETE FROM fm2_process_user_capabilities WHERE user_id=18 AND capability='assignment_order.original.correct'");
    $before=$native->rows();$result=F::json($f->post($fields),201);assertSameValue('accepted',$result['status'],'active local correction role authorizes native command without legacy process capability');
    A::rowsPreserved($before,$native->rows(),['fm2_assignment_order_original_roots','fm2_assignment_order_original_revisions','fm2_assignment_order_original_requests','fm2_assignment_order_original_fingerprints','fm2_assignment_order_original_events','fm2_assignment_order_original_audits']);
});
$run('active local upload role is native authority',static function(F $f):void{
    $native=$f->http->original->selection;$native->db->query("DELETE FROM fm2_process_user_capabilities WHERE user_id=18 AND capability IN('assignment_order.original.upload','assignment_order.original.correct')");assertSameValue('accepted',F::json($f->post($f->fields()),201)['status'],'FKR role upload succeeds without legacy process capability');
});
$run('inactive and administrative roles do not gain original authority',static function(F $f):void{
    $native=$f->http->original->selection;$native->db->query("UPDATE fm2_pilot_roles SET status=0 WHERE role_id=1");assertSameValue(['error'=>'ACCESS_DENIED'],F::json($f->post($f->fields()),403),'inactive FKR role denied');$native->db->query("UPDATE fm2_pilot_roles SET status=1 WHERE role_id=1");$native->db->query("UPDATE fm2_pilot_users SET status=0 WHERE user_id=18");assertSameValue([303,'/pilot/login'],($r=$f->post($f->fields()))?[$r['status'],$r['headers']['location']??null]:[],'inactive FKR user returns to login');assertSameValue(['error'=>'ACCESS_DENIED'],F::json($f->post($f->fields(),null,[],99),403),'administrator has no implicit upload authority');
});
$run('wrong object and order have no fallback',static function(F $f):void{
    $native=$f->http->original->selection;$before=$native->rows();
    foreach(['/pilot/objects/4513/assignment-orders/81/originals','/pilot/objects/4512/assignment-orders/82/originals'] as $path){
        assertSameValue(['error'=>'NOT_FOUND'],F::json($f->post($f->fields(),null,[],18,$path),404),'identity mismatch POST');
        assertSameValue(['error'=>'NOT_FOUND'],F::json($f->http->request('GET',$path.'/submit'),404),'identity mismatch form');
    }
    assertSameValue($before,$native->rows(),'missing context never invokes command');
});
$run('correction reason is required by native owner',static function(F $f):void{
    $accepted=F::json($f->post($f->fields()),201);$native=$f->http->original->selection;$before=$native->rows();
    $fields=array_replace($f->fields(2),['mode'=>'correction','rootOriginalId'=>$accepted['rootOriginalId'],'targetRevisionId'=>$accepted['currentRevisionId'],
        'expectedCurrentRevisionId'=>$accepted['currentRevisionId'],'documentDate'=>'2026-09-02','correctionReason'=>null]);
    $result=F::json($f->post($fields),422);assertSameValue(['rejected','invalid_command'],[$result['status'],$result['reasonCode']],'missing correction reason');
    A::rowsPreserved($before,$native->rows(),['fm2_assignment_order_original_requests','fm2_assignment_order_original_audits']);
});
$run('overflow route identity',static function(F $f):void{
    $before=$f->http->original->selection->rows();
    $path='/pilot/objects/4512/assignment-orders/9999999999999999999999999999/originals';
    assertSameValue(['error'=>'INVALID_REQUEST'],F::json($f->post($f->fields(),null,[],18,$path),400),'recognized identity overflow');
    assertSameValue($before,$f->http->original->selection->rows(),'overflow never invokes command');
});
$run('revoked native session',static function(F $f):void{
    $native=$f->http->original->selection;$native->db->query('UPDATE fm2_pilot_users SET status=0 WHERE user_id=18');$before=$native->rows();
    foreach([$f->http->request('GET',F::FORM),$f->post($f->fields())] as $r){
        assertSameValue(303,$r['status'],'inactive native session redirects');assertSameValue('/pilot/login',$r['headers']['location']??null,'login destination');
    }
    assertSameValue($before,$native->rows(),'session rejection does not invoke command');
});
exit($failures===[]?0:1);
