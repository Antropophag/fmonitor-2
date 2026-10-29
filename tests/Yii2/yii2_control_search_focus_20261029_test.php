<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
$pipes=[];$p=proc_open(['node',dirname(__DIR__).'/Support/control_queue_search_focus_browser.cjs',dirname(__DIR__,2).'/app/YiiRuntime/Assets/control-queue.js'],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($p);echo $out;assertSameValue(0,$code,$err);
