<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/InspectionFixture.php';

// YII2-CHECKLIST-PHOTO-VIEWING-001: clean browser, full-size, retry and local-preview UI.
$fixture=null;$process=null;
try {
    $fixture=new InspectionFixture(dirname(__DIR__,2));$fixture->open();$http=$fixture->http;
    $page=$fixture->page();$csrf=InspectionFixture::csrf($page);$png=InspectionFixture::png(23);
    $op=array_replace(InspectionFixture::operation(711),['type'=>'photo_uploaded','mime'=>'image/png','size'=>strlen($png),'sha256'=>hash('sha256',$png),'originalName'=>'browser.png']);unset($op['itemId'],$op['installerTabIds']);
    $accepted=InspectionFixture::result($fixture->send($op,$csrf,bytes:$png),200,'accepted');$photoId=$accepted['projection']['photos'][0]['id'];
    $config=$http->artifacts.'/photo-view-browser.json';$result=$http->artifacts.'/photo-view-result.json';$log=$http->artifacts.'/photo-view-browser.log';
    file_put_contents($config,json_encode(['origin'=>'http://127.0.0.1:'.$http->server['port'],'email'=>$http->emails[73],'readerEmail'=>$http->emails[95],'password'=>$http->password,'photoId'=>$photoId,'png'=>base64_encode($png),'result'=>$result,'playwright'=>getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($http->root).'/shlz-ui/node_modules/playwright'],JSON_THROW_ON_ERROR));chmod($config,0600);
    $process=proc_open([getenv('FMONITOR_TEST_NODE_BINARY')?:'node',dirname(__DIR__).'/Support/checklist_photo_viewing_browser.cjs',$config],[0=>['file','/dev/null','r'],1=>['file',$log,'a'],2=>['file',$log,'a']],$pipes,$http->root);
    if(!is_resource($process))throw new TestFailure('SETUP_FAILURE browser');
    $deadline=microtime(true)+120;do{$state=proc_get_status($process);if(!$state['running'])break;usleep(20000);}while(microtime(true)<$deadline);
    if($state['running'])throw new TestFailure('browser timeout '.$http->artifacts);$exit=$state['exitcode'];proc_close($process);$process=null;
    assertSameValue(0,$exit,'INTENDED_RED browser '.file_get_contents($log));
    $observed=json_decode(file_get_contents($result),true,flags:JSON_THROW_ON_ERROR);
    assertSameValue([true,true,true,true,true],array_values($observed),'all photo viewing browser scenarios');
    $http->noLegacy();echo "PASS: YII2-CHECKLIST-PHOTO-VIEWING-001 browser\n";
} finally {
    if(is_resource($process)){proc_terminate($process,9);proc_close($process);}if(isset($config)&&is_file($config))unlink($config);if($fixture instanceof InspectionFixture)$fixture->close();
}
