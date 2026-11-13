<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/PreopeningFixture.php';
// MONTH-SESSION-001: public HTTP/browser behavior with a private synthetic identity and persistent profile.
$f=null;$process=null;
try{
    $f=new PreopeningFixture(dirname(__DIR__,2));$f->start();
    $config=$f->artifacts.'/month-session.json';file_put_contents($config,json_encode(['origin'=>'http://127.0.0.1:'.$f->server['port'],'email'=>$f->emails[18],'password'=>$f->password,'profile'=>$f->artifacts.'/persistent-profile','playwright'=>getenv('FMONITOR_TEST_PLAYWRIGHT_MODULE')?:dirname($f->root).'/shlz-ui/node_modules/playwright'],JSON_THROW_ON_ERROR));chmod($config,0600);
    $process=proc_open([getenv('FMONITOR_TEST_NODE_BINARY')?:'node',__DIR__.'/month_session_browser.mjs',$config],[0=>['file','/dev/null','r'],1=>['file',$f->artifacts.'/month-session.log','a'],2=>['file',$f->artifacts.'/month-session.log','a']],$pipes,$f->root);
    if(!is_resource($process))throw new TestFailure('SETUP_FAILURE browser');$deadline=microtime(true)+90;
    do{$state=proc_get_status($process);if(!$state['running'])break;usleep(20000);}while(microtime(true)<$deadline);
    if($state['running'])throw new TestFailure('browser timeout');$exit=$state['exitcode'];proc_close($process);$process=null;
    assertSameValue(0,$exit,'month-session browser: '.file_get_contents($f->artifacts.'/month-session.log'));
    $old=getenv('FMONITOR_TRUSTED_REQUEST_SCHEME');putenv('FMONITOR_TRUSTED_REQUEST_SCHEME=https');$settings=require $f->root.'/config/yii/web.php';if($old===false)putenv('FMONITOR_TRUSTED_REQUEST_SCHEME');else putenv('FMONITOR_TRUSTED_REQUEST_SCHEME='.$old);
    $session=$settings['components']['session'];$user=$settings['components']['user'];
    assertSameValue([2592000,2592000,true,true,true,'/pilot',yii\web\Cookie::SAME_SITE_LAX],[$session['timeout'],$session['cookieParams']['lifetime'],$session['useStrictMode'],$session['cookieParams']['secure'],$session['cookieParams']['httponly'],$session['cookieParams']['path'],$session['cookieParams']['sameSite']],'30-day server/client lifetime and production cookie security');
    assertSameValue(false,$user['enableAutoLogin'],'no new remember-me mechanism');assertSameValue(false,isset($user['authTimeout'])||isset($user['absoluteAuthTimeout']),'no short hidden user timeout introduced');
    echo "PASS MONTH-SESSION-001 persistent restart, external navigation, month lifetime and CSRF\n";
}finally{if(is_resource($process)){proc_terminate($process,9);proc_close($process);}if($f instanceof PreopeningFixture)$f->close();}
