<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\OriginalStartFixture as F;
use FMonitor2\Tests\Support\SelectedOriginalFixture;
// ACTUAL-START-FROM-ORDER-001 A4: concurrent exact requests and durable final retry.
$f=new F();$processes=[];
try{
    $root=$f->original->control;$input=$root.'/concurrent.json';
    file_put_contents($input,json_encode(['database'=>$f->original->selection->schema->source->name,'passwordFile'=>$root.'/password','privateRoot'=>$f->original->privateRoot,'safeLog'=>$f->original->safeLog,'pdf'=>base64_encode(SelectedOriginalFixture::pdf()),'go'=>$root.'/go'],JSON_THROW_ON_ERROR));chmod($input,0600);
    foreach([1,2]as$n){$p=proc_open([PHP_BINARY,dirname(__DIR__).'/Support/OriginalStartConcurrentWorker.php',$input,$root.'/result-'.$n.'.json'],[0=>['file','/dev/null','r'],1=>['file',$root.'/child-'.$n.'.log','a'],2=>['file',$root.'/child-'.$n.'.log','a']],$pipes,dirname(__DIR__,2));if(!is_resource($p))throw new TestFailure('SETUP_FAILURE concurrent worker');$processes[$n]=$p;}
    file_put_contents($root.'/go','go');$deadline=microtime(true)+30;
    foreach($processes as$n=>$p){do{$s=proc_get_status($p);if(!$s['running'])break;usleep(20000);}while(microtime(true)<$deadline);assertSameValue(false,$s['running'],'bounded concurrent request');$code=$s['exitcode'];proc_close($p);unset($processes[$n]);assertSameValue(0,$code,'child process: '.file_get_contents($root.'/child-'.$n.'.log'));}
    $r=$f->owner()->submit($f->command(),4512,'2026-09-20');assertSameValue(true,in_array($r['status'],['accepted','replayed'],true),'final exact retry converges');
    assertSameValue([1,1,1,1],[count($f->rows('fm2_assignment_order_original_revisions')),count($f->rows('fm2_assignment_order_applications')),$f->openingCount(),count($f->rows('fm2_checklist_template_associations'))],'concurrent retries create one original/application/opening/template');
    assertSameValue('2026-09-20',$f->case()['actual_start_date'],'concurrent winner retains signed date');
    echo "PASS ACTUAL-START-FROM-ORDER-001 concurrent retry\n";
}finally{foreach($processes as$p)if(is_resource($p)){proc_terminate($p,9);proc_close($p);}$f->close();}
