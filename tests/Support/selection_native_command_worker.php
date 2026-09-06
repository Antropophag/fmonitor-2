<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\Tests\Support\SelectionNativeFixture as F;
$name=$argv[1]??'';
if(preg_match('/^t_aoir_matrix_[0-9a-f]{12}$/D',$name)!==1)exit(64);
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$connect=static function()use($name):mysqli {
    $db=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local',$name,(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));$db->set_charset('utf8mb4');$db->query('SET SESSION innodb_lock_wait_timeout=10');return $db;
};
$db=$connect();
try {
    $p=C\AssignmentOrderCompositionNativeVerificationFactory::dependencies($db,$connect);
    $clock=new class implements C\SelectionClock {public function now():C\SelectionInstantLookup{return C\SelectionInstantLookup::found(new C\SelectionInstant('2026-09-05T09:00:00Z'));}};
    $app=C\AssignmentOrderCompositionFactory::create(new C\SelectionDependencies($p->authorizer,$p->facts,$clock,$p->requests,$p->transactions,$p->freshReaders,$p->audits,$p->terminalAttempts));
    echo 'THREAD '.$db->thread_id."\nPHASE ready\n";fflush(STDOUT);
    $r=$app->selectAssignmentOrderComposition(F::command((int)$argv[2],(int)($argv[6]??0),(int)$argv[4],(int)$argv[3]));
    if(($argv[5]??'')==='hold'&&$r->status()===C\AssignmentOrderCompositionStatus::SELECTED){
        echo "PHASE committed\n";fflush(STDOUT);$read=[STDIN];$write=null;$except=null;
        if(stream_select($read,$write,$except,10)!==1||fgets(STDIN)!=="continue\n")exit(65);
    }
    echo 'RESULT '.json_encode((new C\SelectionResultSerializer())->serialize($r),JSON_THROW_ON_ERROR)."\n";
}finally{$db->close();}
