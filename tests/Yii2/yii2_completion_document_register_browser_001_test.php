<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/DocumentaryFixture.php';
$fixture=null;$process=null;
try{
    $fixture=new DocumentaryFixture(dirname(__DIR__,2));$fixture->open();$fixture->progress();
    DocumentaryFixture::accepted($fixture->post('record_pto',['ptoActDate'=>'2026-09-05']));
    $http=$fixture->http;$before=$http->facts();
    $input=['url'=>'http://127.0.0.1:'.$http->server['port'],'cookies'=>$fixture->cookies,'artifacts'=>$http->artifacts,'playwright'=>getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($http->root).'/shlz-ui/node_modules/playwright'];
    $process=proc_open([getenv('FMONITOR_TEST_NODE_BINARY')?:'node',__DIR__.'/completion_document_register_browser.mjs'],[0=>['pipe','r'],1=>['file',$http->artifacts.'/completion-register-browser.log','a'],2=>['file',$http->artifacts.'/completion-register-browser.log','a']],$pipes,$http->root);
    if(!is_resource($process))throw new TestFailure('SETUP_FAILURE browser');fwrite($pipes[0],json_encode($input));fclose($pipes[0]);
    $deadline=microtime(true)+120;do{$state=proc_get_status($process);if(!$state['running'])break;usleep(20000);}while(microtime(true)<$deadline);
    if($state['running'])throw new TestFailure('browser deadline');$exit=$state['exitcode'];proc_close($process);$process=null;
    assertSameValue(0,$exit,'completion register browser '.file_get_contents($http->artifacts.'/completion-register-browser.log'));
    $facts=$http->rows('fm2_pilot_completion_facts');assertSameValue(2,count($facts),'browser used one existing declaration writer');
    assertSameValue('declaration',$facts[1]['fact_type'],'existing writer recorded declaration');
    echo 'PASS completion register browser artifacts '.$http->artifacts."\n";
}finally{if(is_resource($process)){proc_terminate($process,9);proc_close($process);}if($fixture instanceof DocumentaryFixture)$fixture->close();}
