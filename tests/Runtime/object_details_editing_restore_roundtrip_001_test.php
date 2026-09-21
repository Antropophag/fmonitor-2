<?php
declare(strict_types=1);
require dirname(__DIR__).'/Support/RecoveryContainerNetwork.php';
use FMonitor2\Tests\Support\RecoveryContainerNetwork;
$root=dirname(__DIR__,2);$network=RecoveryContainerNetwork::forHost(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1');$tag='fmonitor2-runtime:object-details-restore-'.bin2hex(random_bytes(5));
$run=static function(array$command,string$cwd):int{$p=proc_open($command,[0=>['file','/dev/null','r'],1=>STDOUT,2=>STDERR],$pipes,$cwd,getenv());return is_resource($p)?proc_close($p):70;};
try{if($run(['docker','build','--file',$root.'/deploy/runtime/Dockerfile','--tag',$tag,$root],$root)!==0)exit(70);exit($run(['docker','run','--rm',...$network['arguments'],'--volume',$root.'/tests:/workspace/fmonitor-2/tests:ro','--env','FMONITOR_RECOVERY_CONTAINER_TEST=1','--env','FMONITOR_OBJECT_DETAILS_RESTORE_ONLY=1','--env','FMONITOR_TEST_DB_HOST='.$network['databaseHost'],'--env','FMONITOR_TEST_DB_PORT='.(getenv('FMONITOR_TEST_DB_PORT')?:'23306'),'--env','FMONITOR_TEST_DB_ADMIN_USER='.(getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root'),'--env','FMONITOR_TEST_DB_ADMIN_PASSWORD='.(getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local'),'--entrypoint','php',$tag,'tests/Runtime/runtime_recovery_001_test.php'],$root));}finally{$run(['docker','image','rm',$tag],$root);}
