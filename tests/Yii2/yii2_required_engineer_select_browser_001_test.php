<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/PreopeningFixture.php';
$f=null;$process=null;
try{
    $f=new PreopeningFixture(dirname(__DIR__,2));$p=$f->p;
    $f->insert($p.'fm2_pilot_role_permissions',['role_id'=>1,'permission'=>'control_engineer.assign']);
    $f->insert($p.'fm2_pilot_users',['user_id'=>74,'full_name'=>'Инженер B','email'=>'engineer74@example.test','status'=>1,'activation_state'=>'active','session_version'=>1,'source_updated_at'=>'2026-09-15T09:00:00+03:00']);
    $f->insert($p.'fm2_pilot_user_roles',['user_id'=>74,'role_id'=>2,'origin'=>'fixture','assigned_at'=>'2026-09-15T09:00:00+03:00']);
    $f->start();$cookies=[];$f->login($cookies,18);
    $input=['url'=>'http://127.0.0.1:'.$f->server['port'],'cookies'=>$cookies,'playwright'=>getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($f->root).'/shlz-ui/node_modules/playwright'];
    $process=proc_open([getenv('FMONITOR_TEST_NODE_BINARY')?:'node',__DIR__.'/required_engineer_select_browser.mjs'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$f->root);
    if(!is_resource($process))throw new TestFailure('SETUP_FAILURE browser');fwrite($pipes[0],json_encode($input));fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);$process=null;
    assertSameValue([0,''],[$exit,$err],'required engineer select browser '.$out.$err);echo$out;
}finally{if(is_resource($process)){proc_terminate($process,9);proc_close($process);}if($f instanceof PreopeningFixture)$f->close();}
