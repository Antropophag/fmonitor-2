<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\{SelectionHttpFixture as F,SelectionHttpAssertions as A};
// ASSIGNMENT-ORDER-COMPOSITION-HTTP-001: actual router admission and closed fresh writers.
$failures=[];$f=null;
try {
    $f=new F();$native=$f->original->selection;$base=A::input($f);$valid=A::body($base);
    $withoutEngineer=$base;unset($withoutEngineer['controlEngineerUserId']);
    $withoutConfirmation=$base;unset($withoutConfirmation['controlEngineerConfirmed']);
    $cases=[
        'anonymous'=>['GET',A::PATH,'',null,[],303],
        'manager'=>['GET',A::PATH,'',31,[],200],
        'admin-only'=>['GET',A::PATH,'',99,[],403],
        'forged actor header'=>['GET',A::PATH,'',99,['FMONITOR_AUTH_USER_ID'=>'18','REMOTE_USER'=>'test18@shlz.ru'],403],
        'forged actor field'=>['POST',A::PATH,$valid.'&actorUserId=99',18,[],400],
        'CSRF'=>['POST',A::PATH,A::body(array_replace($base,['csrfToken'=>'bad'])),18,[],403],
        'media'=>['POST',A::PATH,$valid,18,['Content-Type'=>'application/json'],415],
        'size'=>['POST',A::PATH,str_repeat('x',32769),18,[],413],
        'duplicate scalar'=>['POST',A::PATH,$valid.'&mode=new_order',18,[],400],
        'nested key'=>['POST',A::PATH,$valid.'&installerTabIds%5Bx%5D=7001',18,[],400],
        'malformed percent'=>['POST',A::PATH,$valid.'&requestId=%ZZ',18,[],400],
        'bad number'=>['POST',A::PATH,A::body(array_replace($base,['expectedSelectionRevision'=>'01'])),18,[],400],
        'non-v4 request'=>['POST',A::PATH,A::body(array_replace($base,['requestId'=>'11111111-1111-1111-8111-000000000301'])),18,[],400],
        'missing engineer'=>['POST',A::PATH,A::body($withoutEngineer),18,[],422],
        'missing confirmation'=>['POST',A::PATH,A::body($withoutConfirmation),18,[],422],
        'unconfirmed engineer'=>['POST',A::PATH,A::body(array_replace($base,['controlEngineerConfirmed'=>'no'])),18,[],400],
        'method'=>['PUT',A::PATH,'',18,[],405],
        'template GET'=>['GET','/pilot/objects/4512/assignment-orders/81/template','',18,[],405],
        'template CSRF'=>['POST','/pilot/objects/4512/assignment-orders/81/template','csrfToken=bad',18,[],403],
        'missing object'=>['GET','/pilot/objects/9999/assignment-order/selection','',18,[],404],
        'legacy prepare GET'=>['GET','/pilot/objects/4512/assignment-order/prepare','',18,[],303],
        'legacy prepare POST'=>['POST','/pilot/objects/4512/assignment-order/prepare',$valid,18,[],410],
        'legacy registration'=>['POST','/pilot/objects/4512/assignment-orders/81/registration',$valid,18,[],410],
        'legacy engineer'=>['POST','/pilot/objects/4512/control-engineer',$valid,18,[],410],
        'legacy opening'=>['POST','/pilot/objects/4512/open',$valid,18,[],303],
        'legacy artifact'=>['GET','/pilot/objects/4512/assignment-orders/81/artifacts/order','',18,[],410],
    ];
    foreach($cases as $name=>[$method,$path,$body,$actor,$headers,$status]){
        try{$before=$native->rows();$r=$f->request($method,$path,$body,$actor,$headers);assertSameValue($status,$r['status'],'INTENDED_RED '.$name);assertSameValue($before,$native->rows(),'admission no domain facts '.$name);
            if($name==='legacy prepare GET')assertSameValue(A::PATH,$r['headers']['location']??null,'existing card link reaches new wizard');
            if($name==='legacy opening')assertSameValue('/pilot/objects/4512/execution',$r['headers']['location']??null,'legacy opening reaches separate native execution step');
            if($status>=400)assertSameValue(false,str_contains($r['body'],$f->csrf),'error does not disclose CSRF');
            echo "PASS $name\n";
        }catch(Throwable $error){$failures[]=$name;echo "FAIL $name: ".$error->getMessage()."\n";}
    }
    $log=file_get_contents($f->original->control.'/http.log');
    assertSameValue(true,str_contains($log,'FMONITOR_ORDER_HTTP_REJECT status=403 reason=csrf_invalid'),'safe CSRF rejection audit');
    assertSameValue(false,str_contains($log,$f->csrf),'no token in safe audit');
    $native->db->query("DELETE FROM fm2_pilot_role_permissions WHERE role_id=1 AND permission='assignment_order.composition.select'");
    $before=$native->rows();assertSameValue(403,$f->request('POST',A::PATH,$valid)['status'],'revoked permission before command');assertSameValue($before,$native->rows(),'revoke no command audit');
    $native->db->query('UPDATE fm2_pilot_users SET status=0 WHERE user_id=18');assertSameValue(303,$f->request('GET',A::PATH)['status'],'inactive native session redirects login');
    echo "PASS revoke and inactive session\n";
}finally{if($f!==null)$f->close();}
$off=null;try{$off=new F(false);assertSameValue(404,$off->request('GET',A::PATH)['status'],'new route disabled without explicit fresh flag');echo "PASS fresh flag disabled\n";}finally{if($off!==null)$off->close();}
exit($failures===[]?0:1);
