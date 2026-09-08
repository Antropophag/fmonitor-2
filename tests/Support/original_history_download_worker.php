<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\AssignmentOrderOriginal as O;
$c=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);
if(preg_match('/^t_aoir_matrix_[0-9a-f]{12}$/D',$c['database'])!==1)exit(64);
$db=null;$exit=0;
try{
    $db=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local',$c['database'],(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));$db->set_charset('utf8mb4');
    echo "PHASE ready\n";fflush(STDOUT);
    $r=O\AssignmentOrderOriginalHistoryReaderFactory::create($db,$c['root'],$c['prefix'])->prepareDownload(4512,81,$c['revision']);
    echo 'RESULT '.json_encode([$r->status->value,$r->download!==null],JSON_THROW_ON_ERROR)."\n";
}catch(Throwable){fwrite(STDERR,"History download worker failed.\n");$exit=70;}
finally{if($db!==null)$db->close();}
exit($exit);
