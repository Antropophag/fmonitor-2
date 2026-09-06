<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\{OriginalHttpFixture as F,SelectedOriginalFixture};
// ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001 v0.2: no command on transport/local admission errors.
$f=null;$failures=[];
try {
    $f=new F();$native=$f->http->original->selection;$fields=$f->fields();$json=base64_decode(F::header($fields));
    $wrongType=$fields;$wrongType['compositionConfirmed']='true';$unknown=$fields;$unknown['actorUserId']=99;
    $cases=[
        'anonymous'=>[$fields,[],null,303,null],
        'admin'=>[$fields,[],99,403,'ACCESS_DENIED'],
        'CSRF'=>[array_replace($fields,['csrfToken'=>'bad']),[],18,403,'CSRF_INVALID'],
        'media'=>[$fields,['Content-Type'=>'multipart/form-data; boundary=x'],18,415,'UNSUPPORTED_MEDIA_TYPE'],
        'metadata missing'=>[$fields,['X-FMonitor-Original'=>null],18,400,'INVALID_REQUEST'],
        'metadata invalid base64'=>[$fields,['X-FMonitor-Original'=>'!'],18,400,'INVALID_REQUEST'],
        'metadata too large'=>[$fields,['X-FMonitor-Original'=>str_repeat('A',16385)],18,400,'INVALID_REQUEST'],
        'metadata whitespace'=>[$fields,['X-FMonitor-Original'=>base64_encode(' '.$json)],18,400,'INVALID_REQUEST'],
        'duplicate JSON'=>[$fields,['X-FMonitor-Original'=>base64_encode(substr($json,0,-1).',"mode":"initial"}')],18,400,'INVALID_REQUEST'],
        'unknown actor field'=>[$unknown,[],18,400,'INVALID_REQUEST'],
        'boolean type'=>[$wrongType,[],18,400,'INVALID_REQUEST'],
        'unknown mode'=>[array_replace($fields,['mode'=>'upload']),[],18,400,'INVALID_REQUEST'],
        'bad UUID'=>[array_replace($fields,['requestId'=>'not-a-uuid']),[],18,400,'INVALID_REQUEST'],
    ];
    foreach($cases as $name=>[$data,$headers,$actor,$status,$code]){
        try{$before=$native->rows();$files=$f->http->original->privateFiles();$r=$f->post($data,null,$headers,$actor);
            assertSameValue($status,$r['status'],'INTENDED_RED '.$name);if($code!==null)assertSameValue(['error'=>$code],F::json($r,$status),'closed pre-command envelope');
            assertSameValue($before,$native->rows(),'no domain facts '.$name);assertSameValue($files,$f->http->original->privateFiles(),'no private writes '.$name);echo "PASS $name\n";
        }catch(Throwable $e){$failures[]=$name;echo "FAIL $name: ".$e->getMessage()."\n";}
    }
    $before=$native->rows();$r=$f->post($fields,'',['Content-Length'=>null]);assertSameValue(['error'=>'LENGTH_REQUIRED'],F::json($r,411),'missing length reaches adapter');assertSameValue($before,$native->rows(),'length denial no facts');
    $r=$f->http->request('GET',F::POST);assertSameValue(['error'=>'METHOD_NOT_ALLOWED'],F::json($r,405),'recognized unsupported method');assertSameValue('POST',$r['headers']['allow'],'exact Allow');
    assertSameValue(403,$f->http->request('GET',F::FORM,'',99)['status'],'admin-only cannot read writer form');
    $native->db->query("DELETE FROM fm2_pilot_role_permissions WHERE role_id=1 AND permission='assignment_order.original.upload'");
    $before=$native->rows();assertSameValue(['error'=>'ACCESS_DENIED'],F::json($f->post($fields),403),'local revoke');assertSameValue($before,$native->rows(),'local revoke before core');
    $native->schema->insert('fm2_pilot_role_permissions',['role_id'=>1,'permission'=>'assignment_order.original.upload']);
    $native->db->query("DELETE FROM fm2_process_user_capabilities WHERE user_id=18 AND capability='assignment_order.original.upload'");
    $r=F::json($f->post($fields),403);assertSameValue(['rejected','authorization_denied'],[$r['status'],$r['reasonCode']],'native cap independently required');
    assertSameValue(1,count($native->rows()['fm2_assignment_order_original_requests']),'approved original first-denial terminal preserved');
    $r=F::json($f->post($fields),403);assertSameValue('authorization_denied',$r['reasonCode'],'repeat native denial');
    assertSameValue(2,count($native->rows()['fm2_assignment_order_original_audits']),'approved repeated-denial audit');
    $log=file_get_contents($f->http->original->control.'/http.log');assertSameValue(true,str_contains($log,'FMONITOR_ORIGINAL_HTTP_REJECT status=403 reason=CSRF_INVALID'),'safe transport audit');
    foreach([$f->http->csrf,'signed.pdf',$json] as $secret)assertSameValue(false,str_contains($log,$secret),'no metadata/token/filename in log');
    echo "PASS local/native grant and audit separation\n";
}finally{if($f!==null)$f->close();}
exit($failures===[]?0:1);
