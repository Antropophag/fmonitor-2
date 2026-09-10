<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/InstallerDirectoryFixture.php';
$f=null;$process=null;
try {
    $f=new InstallerDirectoryFixture(dirname(__DIR__,2));$f->open();$h=$f->http;$before=$h->facts();
    $config=$h->artifacts.'/directory-browser.json';$result=$h->artifacts.'/directory-result.json';
    file_put_contents($config,json_encode(['origin'=>'http://127.0.0.1:'.$h->server['port'],'email'=>$h->emails[18],'password'=>$h->password,'artifacts'=>$h->artifacts,'result'=>$result,'playwright'=>getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($h->root).'/shlz-ui/node_modules/playwright'],JSON_THROW_ON_ERROR));chmod($config,0600);
    $log=$h->artifacts.'/directory-browser.log';$process=proc_open([getenv('FMONITOR_TEST_NODE_BINARY')?:'node',__DIR__.'/installer_directory_browser.mjs',$config],[0=>['file','/dev/null','r'],1=>['file',$log,'a'],2=>['file',$log,'a']],$pipes,$h->root);if(!is_resource($process))throw new TestFailure('SETUP_FAILURE browser');
    $deadline=microtime(true)+100;do{$state=proc_get_status($process);if(!$state['running'])break;usleep(20000);}while(microtime(true)<$deadline);if($state['running'])throw new TestFailure('browser timeout '.$h->artifacts);$exit=$state['exitcode'];proc_close($process);$process=null;
    if(str_contains(file_get_contents($log),'INTENDED_RED'))echo "INTENDED_RED Yii installer directory browser route absent\n";assertSameValue(0,$exit,'browser '.file_get_contents($log));assertSameValue(true,json_decode(file_get_contents($result),true,flags:JSON_THROW_ON_ERROR)['passed'],'browser passed');assertSameValue($before,$h->facts(),'browser read-only');$f->routeNoLegacy();echo 'PASS: YII2-INSTALLER-DIRECTORY-001 browser '.$h->artifacts."\n";
} finally {if(is_resource($process)){proc_terminate($process,9);proc_close($process);}if(isset($config)&&is_file($config))unlink($config);if($f instanceof InstallerDirectoryFixture)$f->close();}
