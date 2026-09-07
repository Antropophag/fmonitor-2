<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
$root=dirname(__DIR__,2);$process=proc_open(['node',$root.'/tests/InstallationProcess/support/control_queue_bulk_protocol_browser.js',$root.'/app/PilotHttp/control-queue.js'],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root);if(!is_resource($process))throw new TestFailure('SETUP_FAILURE: control queue browser');$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);assertSameValue(0,proc_close($process),'control queue executable '.trim($err));$result=json_decode($out,true,32,JSON_THROW_ON_ERROR);
assertSameValue([0,1,2],array_column($result['sent'],'baseRevision'),'navigation-resumed batch follows persisted predecessor acknowledgements, not sync-context revision');
foreach($result['sent']as$payload)foreach(['id','scope','status','message','localPredecessorId','localBatchId','localBatchSequence','acceptedRevision']as$key)assertSameValue(false,array_key_exists($key,$payload),'local queue metadata is absent from native HTTP payload: '.$key);
assertSameValue([1,2,3],array_column($result['stored'],'acceptedRevision'),'accepted predecessor revisions remain durable for later senders');
echo "PASS control queue resumes exact bulk dependency protocol\n";
