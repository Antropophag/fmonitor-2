<?php
declare(strict_types=1);require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/PreopeningFixture.php';
$f=null;$process=null;try{
 $f=new PreopeningFixture(dirname(__DIR__,2));$f->start();$cookies=[];$f->login($cookies,18);
 $config=['origin'=>'http://127.0.0.1:'.$f->server['port'],'cookies'=>$cookies,'playwright'=>getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname(__DIR__,3).'/shlz-ui/node_modules/playwright','artifacts'=>$f->artifacts];$path=$f->artifacts.'/browser-config.json';file_put_contents($path,json_encode($config,JSON_THROW_ON_ERROR));chmod($path,0600);
 $process=proc_open(['node',__DIR__.'/object_comments_20261030_browser.mjs',$path],[0=>['file','/dev/null','r'],1=>['file',$f->artifacts.'/browser.log','a'],2=>['file',$f->artifacts.'/browser.log','a']],$pipes,dirname(__DIR__,2));assertSameValue(true,is_resource($process),'browser process');$deadline=microtime(true)+60;do{$state=proc_get_status($process);if(!$state['running'])break;usleep(20000);}while(microtime(true)<$deadline);if($state['running']){proc_terminate($process,9);throw new TestFailure('browser deadline');}$exit=$state['exitcode'];proc_close($process);$process=null;assertSameValue(0,$exit,'object comment browser; '.$f->artifacts.'; '.file_get_contents($f->artifacts.'/browser.log'));
 echo "PASS: object comments desktop/narrow save, exact patch and stale recovery; artifacts {$f->artifacts}\n";
}finally{if(is_resource($process)){proc_terminate($process,9);proc_close($process);}if(isset($path))@unlink($path);if($f instanceof PreopeningFixture)$f->close();}
