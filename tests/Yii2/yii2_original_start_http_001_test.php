<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/PreopeningFixture.php';
// ACTUAL-START-FROM-ORDER-001: real HTTP partial/retry, invalid input, and legacy read-only history.
function oshMetadata(PreopeningFixture$f,array&$cookies,int$n=1):array{
    return ['csrfToken'=>$f->token($cookies),'requestId'=>sprintf('17171717-1717-4717-8717-%012d',$n),'mode'=>'initial','documentDate'=>'2026-09-01','appendixStartDate'=>'2030-10-09','compositionConfirmed'=>true,'rootOriginalId'=>null,'targetRevisionId'=>null,'expectedCurrentRevisionId'=>null,'correctionReason'=>null,'originalFilename'=>'signed.pdf'];
}
$f=new PreopeningFixture(dirname(__DIR__,2));try{
    $f->start();$cookies=[];assertSameValue(303,$f->login($cookies)['status'],'login');assertSameValue(303,$f->selection($cookies)['status'],'selection');
    $metadata=oshMetadata($f,$cookies);$missing=$metadata;unset($missing['appendixStartDate']);assertSameValue(400,$f->upload($cookies,$missing)['status'],'missing signed date rejected at HTTP');
    $bad=$metadata;$bad['appendixStartDate']='2026-02-30';assertSameValue(422,$f->upload($cookies,$bad)['status'],'invalid calendar date rejected');
    $templates=$f->rows('fm2_checklist_template_snapshots');$f->db->query("DELETE FROM {$f->p}fm2_checklist_template_snapshots");
    $pending=$f->upload($cookies,$metadata);assertSameValue(503,$pending['status'],'accepted evidence with failed opening is HTTP503');$body=json_decode($pending['body'],true,512,JSON_THROW_ON_ERROR);
    assertSameValue(['pending',true,$metadata['requestId']],[$body['status'],$body['retryable'],$body['requestId']],'partial receipt and retry identity');assertSameValue(true,is_string($body['currentRevisionId']),'partial contains actual accepted revision');
    assertSameValue(1,count($f->rows('fm2_assignment_order_original_revisions')),'partial retained PDF');assertSameValue(null,$f->rows('fm2_installation_cases')[0]['actual_start_date'],'partial did not open');
    foreach($templates as$row)$f->insert($f->p.'fm2_checklist_template_snapshots',$row);
    $retry=$f->upload($cookies,$metadata);assertSameValue(200,$retry['status'],'same HTTP request resumes original+opening');assertSameValue('2030-10-09',$f->rows('fm2_installation_cases')[0]['actual_start_date'],'HTTP retry uses signed date');assertSameValue(1,count($f->rows('fm2_assignment_order_original_revisions')),'HTTP retry has no duplicate PDF');
    $reader=[];$f->login($reader,95);$denied=$f->upload($reader,oshMetadata($f,$reader,2));assertSameValue(403,$denied['status'],'reader cannot invoke compound write');
    echo "PASS original-start HTTP partial/retry/denial\n";
}finally{$f->close();}
$f=new PreopeningFixture(dirname(__DIR__,2));try{
    // Observe actual Yii Request body acquisition, not a controller-source string match.
    $marker=$f->artifacts.'/original-body-reads';
    $router='<?php require '.var_export($f->root.'/vendor/autoload.php',true).';require '.var_export($f->root.'/vendor/yiisoft/yii2/Yii.php',true).';class ObservedOriginalRequest extends yii\\web\\Request {public function getRawBody(){if(str_ends_with($_SERVER["REQUEST_URI"],"/originals"))file_put_contents('.var_export($marker,true).',"read\\n",FILE_APPEND);return parent::getRawBody();}}$config=require '.var_export($f->root.'/config/yii/web.php',true).';$config["components"]["request"]["class"]=ObservedOriginalRequest::class;(new yii\\web\\Application($config))->run();';
    $f->start([], $router);$cookies=[];$f->login($cookies);$f->selection($cookies);
    $f->db->query("DELETE FROM {$f->p}fm2_pilot_role_permissions WHERE role_id=1 AND permission='installation.open'");
    $denied=$f->upload($cookies,oshMetadata($f,$cookies));assertSameValue(403,$denied['status'],'upload permission alone is denied before body acquisition');assertSameValue(false,is_file($marker),'denied original body was never read');
    $f->db->query("INSERT INTO {$f->p}fm2_pilot_role_permissions(role_id,permission) VALUES(1,'installation.open')");
    $accepted=$f->upload($cookies,oshMetadata($f,$cookies,2));assertSameValue(201,$accepted['status'],'both capabilities allow actual upload');assertSameValue(true,is_file($marker),'positive control proves body-read probe is reached');
    echo "PASS original-start HTTP admission before PDF body\n";
}finally{$f->close();}
$f=new PreopeningFixture(dirname(__DIR__,2));try{
    $f->start();$cookies=[];$f->login($cookies);$f->selection($cookies);$f->nativeOriginal();
    $history=$f->request('GET','/pilot/objects/4512/assignment-orders/81/originals/history',[],$cookies);
    assertSameValue(200,$history['status'],'legacy original history stays readable');assertSameValue(true,str_contains($history['body'],'Не зафиксирована'),'legacy missing date is explicit unknown');
    $form=$f->request('GET','/pilot/objects/4512/assignment-orders/81/originals/submit',[],$cookies);
    assertSameValue(200,$form['status'],'legacy correction form is readable');
    assertSameValue(1,preg_match('/name="appendixStartDate"[^>]*value=""/',$form['body']),'legacy missing date never substituted from plan');
    echo "PASS original-start legacy history unknown date\n";
}finally{$f->close();}

foreach([1,2,3,5]as$version){
 $f=new PreopeningFixture(dirname(__DIR__,2));try{
  $f->start();$cookies=[];$f->login($cookies);$f->selection($cookies);$m=oshMetadata($f,$cookies);$m['requestId']='17171717-1717-'.$version.'717-8717-000000000001';
  $r=$f->upload($cookies,$m);assertSameValue(201,$r['status'],'original UUID v'.$version.' opens immediately');assertSameValue(['working','2030-10-09','18'],array_map(static fn($key)=>$f->rows('fm2_installation_cases')[0][$key],['process_state','actual_start_date','opened_by_user_id']),'UUID opening facts');
  $before=$f->facts();assertSameValue(200,$f->upload($cookies,$m)['status'],'UUID exact replay');assertSameValue($before,$f->facts(),'UUID replay creates no duplicate facts');
 }finally{$f->close();}
}
