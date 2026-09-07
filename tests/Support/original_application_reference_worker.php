<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\Tests\Support\SelectedOriginalFixture as F;
use FMonitor2\Tests\Support\SelectedOriginalInput as Input;
$c=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);
if(preg_match('/^t_aoir_matrix_[0-9a-f]{12}$/D',$c['database'])!==1)exit(64);
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local',$c['database'],(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));
try {
    $db->set_charset('utf8mb4');$db->query('SET SESSION innodb_lock_wait_timeout=10');
    $fresh=new O\AssignmentOrderOriginalMariaDbFreshTerminalReaderFactory(new O\AssignmentOrderOriginalFreshReaderConfig(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306),$c['database'],getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',$c['password'],$c['prefix']));
    $app=O\ProductionAssignmentOrderOriginalFactory::createForSelections($db,new O\AssignmentOrderOriginalProductionConfig($c['private'],$c['prefix'],$c['log']),$fresh);
    echo 'THREAD '.$db->thread_id."\nPHASE ready\n";fflush(STDOUT);
    $r=$app->submitAssignmentOrderOriginal(new O\SubmitAssignmentOrderOriginalCommand('22222222-2222-4222-8222-000000000002',O\AssignmentOrderOriginalMode::CORRECTION,4512,81,18,'2026-09-03',true,$c['root'],$c['revision'],$c['revision'],'Уточнена дата',new O\AssignmentOrderOriginalUpload(new Input(F::pdf()),'signed.pdf','application/pdf')));
    echo 'RESULT '.json_encode([$r->status()->value,$r->revisionNumber(),$r->documentDate()],JSON_THROW_ON_ERROR)."\n";
}finally{$db->close();}
