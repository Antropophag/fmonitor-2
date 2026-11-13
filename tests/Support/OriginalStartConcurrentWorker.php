<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\AssignmentOrderComposition as C;
$input=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);
$host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';$port=(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306);$user=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';$password=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';
$db=new mysqli($host,$user,$password,$input['database'],$port);$db->set_charset('utf8mb4');
$fresh=new O\AssignmentOrderOriginalMariaDbFreshTerminalReaderFactory(new O\AssignmentOrderOriginalFreshReaderConfig($host,$port,$input['database'],$user,$input['passwordFile'],'start_'));
$originalClock=new class implements O\AssignmentOrderOriginalClock{public function nowUtc():string{return '2026-09-05T09:00:00Z';}};
$clock=new class implements C\SelectionClock{public function now():C\SelectionInstantLookup{return C\SelectionInstantLookup::found(new C\SelectionInstant('2026-09-05T10:00:00Z'));}};
$original=O\AssignmentOrderSelectedOriginalVerificationFactory::create($db,new O\AssignmentOrderOriginalProductionConfig($input['privateRoot'],'start_',$input['safeLog']),$fresh,$originalClock);
$command=new O\SubmitAssignmentOrderOriginalCommand('12121212-1212-4212-8212-000000000001',O\AssignmentOrderOriginalMode::INITIAL,4512,81,18,'2026-09-04',true,null,null,null,null,new O\AssignmentOrderOriginalUpload(new O\AssignmentOrderOriginalMemoryStream(base64_decode($input['pdf'],true)),'signed.pdf','application/pdf'));
$deadline=microtime(true)+10;while(!is_file($input['go'])){if(microtime(true)>$deadline)throw new RuntimeException('barrier timeout');usleep(10000);}
$r=C\ProductionOriginalStartFactory::create($db,'start_',$original,$clock)->submit($command,4512,'2026-09-20');
file_put_contents($argv[2],json_encode(['status'=>$r['status'],'reasonCode'=>$r['reasonCode'],'retryable'=>$r['retryable']],JSON_THROW_ON_ERROR));$db->close();
