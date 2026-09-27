<?php
declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__,2).'/vendor/autoload.php';
require dirname(__DIR__,2).'/vendor/yiisoft/yii2/Yii.php';

use FMonitor2\InstallationProcess\CanonicalMigrationApplication;
use FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue;
use FMonitor2\Otiz\OtizSettlementV2;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function correctionDeny(string $expected, callable $command, string $message): void
{
    try {
        $command();
        throw new TestFailure($message.' accepted');
    } catch (DomainException $error) {
        assertSameValue($expected, $error->getMessage(), $message);
    }
}

function correctionInput(
    int $objectId,
    string $sourceRevision,
    string $recognitionDate,
    int $cumulativeGrossCents,
    int $baselineClaimedCents = 0,
    int $kssBp = 10000,
): array {
    return [
        'reportDate' => $recognitionDate,
        'objects' => [[
            'objectId' => $objectId,
            'regnumber' => 'CORR-'.$objectId,
            'fundCents' => 1000000,
            'kssBp' => $kssBp,
            'entitlements' => [[
                'rightKey' => 'object-'.$objectId.'-checklist-progress',
                'sourceKind' => 'checklist',
                'sourceId' => 'case-'.$objectId.'-checklist',
                'sourceRevision' => $sourceRevision,
                'kind' => 'progress',
                'recognitionDate' => $recognitionDate,
                'baselineClaimedCents' => $baselineClaimedCents,
                'cumulativeGrossCents' => $cumulativeGrossCents,
                'grossCents' => $cumulativeGrossCents - $baselineClaimedCents,
            ]],
            'recipients' => [[
                'employeeId' => 'employee-001',
                'tabNumber' => '001',
                'name' => 'Иванов Иван',
                'weight' => 1,
                'employment' => 'employed',
            ]],
        ]],
    ];
}

if (($argv[1] ?? '') === '--accept-worker') {
    [$host, $port, $database, $user, $password, $prefix, $action, $admissionSourceRevision, $calculationId, $revision, $operationId, $ready, $go, $result] = array_slice($argv, 2);
    $connection = null;
    try {
        $connection = new yii\db\Connection([
            'dsn' => "mysql:host={$host};port={$port};dbname={$database}",
            'username' => $user,
            'password' => $password,
            'charset' => 'utf8mb4',
        ]);
        $connection->open();
        $workerOwner = new OtizSettlementV2($connection, $prefix, static fn(): string => '2026-09-27T09:00:00+03:00', static fn(): array => ['decision' => 'allow', 'sourceRevision' => $admissionSourceRevision, 'incidentId' => null, 'observedAt' => '2026-09-27T08:00:00+03:00']);
        file_put_contents($ready, 'ready', LOCK_EX);
        $deadline = microtime(true) + 10;
        while (!is_file($go) && microtime(true) < $deadline) {
            usleep(1000);
        }
        if (!is_file($go)) {
            throw new RuntimeException('WORKER_GO_TIMEOUT');
        }
        try {
            $accepted = $action==='cancel'?$workerOwner->cancel(501,(int)$calculationId,(int)$revision,'Concurrent cancellation',$operationId):$workerOwner->accept(501, (int) $calculationId, (int) $revision, $operationId);
            $payload = ['status' => $action==='cancel'?'cancelled':'accepted', 'calculationId' => $accepted['calculationId']];
        } catch (DomainException $error) {
            $payload = ['status' => $error->getMessage(), 'calculationId' => (int) $calculationId];
        }
        file_put_contents($result, json_encode($payload, JSON_THROW_ON_ERROR), LOCK_EX);
        $connection->close();
        exit(0);
    } catch (Throwable $error) {
        if ($connection instanceof yii\db\Connection) {
            $connection->close();
        }
        file_put_contents($result, json_encode(['status' => 'worker_error', 'message' => $error->getMessage()], JSON_THROW_ON_ERROR), LOCK_EX);
        exit(2);
    }
}

$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$user = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$password = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$database = 't_otiz_v2_corrections_'.bin2hex(random_bytes(5));
$admin = new mysqli($host, $user, $password, '', $port);
$db = null;
$yii = null;

try {
    $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db = new mysqli($host, $user, $password, $database, $port);
    $prefix = 'oc_';
    $migration = CanonicalMigrationApplication::run($db, $prefix, ProductionPilotMigrationCatalogue::migrations());
    assertSameValue([0, true], [$migration['exitCode'], $migration['result']['ok'] ?? null], 'correction schema setup');

    $db->query("INSERT INTO {$prefix}fm2_pilot_users(user_id,full_name,email,status,activation_state,source_updated_at) VALUES(501,'ОТиЗ','otiz@example.test',1,'active','2026-09-27T08:00:00+03:00')");
    $db->query("INSERT INTO {$prefix}fm2_pilot_roles(role_id,code,name,description,status,source_updated_at) VALUES(51,'otiz','ОТиЗ','test',1,'2026-09-27T08:00:00+03:00')");
    $db->query("INSERT INTO {$prefix}fm2_pilot_role_permissions VALUES(51,'otiz.manage')");
    $db->query("INSERT INTO {$prefix}fm2_pilot_user_roles(user_id,role_id,origin,assigned_at) VALUES(501,51,'test','2026-09-27T08:00:00+03:00')");

    $yii = new yii\db\Connection([
        'dsn' => "mysql:host={$host};port={$port};dbname={$database}",
        'username' => $user,
        'password' => $password,
        'charset' => 'utf8mb4',
    ]);
    $yii->open();

    $admission = [];
    $owner = new OtizSettlementV2(
        $yii,
        $prefix,
        static fn(): string => '2026-09-27T09:00:00+03:00',
        static function (int $objectId, string $phase) use (&$admission): array {
            return $admission[$objectId] ?? [
                'decision' => 'allow',
                'sourceRevision' => 'admission-v1',
                'incidentId' => null,
                'observedAt' => '2026-09-27T08:00:00+03:00',
            ];
        },
    );

    // F01: the canonical builder field is kssBp; the owner must derive the saved reduction from it.
    $kssDraft = $owner->createDraft(501, correctionInput(8101, 'progress-v1', '2026-09-27', 1000000, 0, 5000), '00000000-0000-4000-8000-000000008101');
    assertSameValue(
        [500000, 500000, 500000],
        [
            $kssDraft['objects'][0]['deadlineCents'],
            $kssDraft['objects'][0]['payableCents'],
            $kssDraft['totalCents'],
        ],
        'F01 Kss 0.5 reaches saved projection cents',
    );
    $kssAccepted = $owner->accept(501, $kssDraft['calculationId'], $kssDraft['revision'], '00000000-0000-4000-8000-000000008102');
    assertSameValue(500000, (int) $db->query("SELECT amount_cents FROM {$prefix}fm2_otiz_recipient_obligations WHERE calculation_id=".(int) $kssAccepted['calculationId'])->fetch_column(), 'F01 accepted obligation uses reduced cents');
    $zeroInput=correctionInput(8102,'progress-zero','2026-09-27',1000000,0,0);$zeroAccepted=$owner->createDraft(501,$zeroInput,'00000000-0000-4000-8000-000000008103');$zeroAccepted=$owner->accept(501,$zeroAccepted['calculationId'],$zeroAccepted['revision'],'00000000-0000-4000-8000-000000008104');$zeroFacts=(int)$db->query("SELECT COUNT(*) FROM {$prefix}fm2_otiz_payment_facts")->fetch_column();correctionDeny('ZERO_OBLIGATION',fn()=>$owner->markPaid(501,$zeroAccepted['calculationId'],$zeroAccepted['revision'],'2026-09-27','00000000-0000-4000-8000-000000008105'),'F01/M10 zero obligation payment');assertSameValue($zeroFacts,(int)$db->query("SELECT COUNT(*) FROM {$prefix}fm2_otiz_payment_facts")->fetch_column(),'zero obligation writes no payment fact');

    // F02: report date and cumulative source revision are lineage, not a license to claim the same work twice.
    $first = $owner->createDraft(501, correctionInput(8201, 'progress-v1', '2026-09-26', 300000), '00000000-0000-4000-8000-000000008201');
    $overlap = $owner->createDraft(501, correctionInput(8201, 'progress-v2', '2026-09-27', 300000), '00000000-0000-4000-8000-000000008202');
    $workerRoot = sys_get_temp_dir().'/otiz-correction-workers-'.bin2hex(random_bytes(5));
    mkdir($workerRoot, 0700, true);
    $workers = [];
    foreach ([[$first, '00000000-0000-4000-8000-000000008203'], [$overlap, '00000000-0000-4000-8000-000000008204']] as $index => [$draft, $operationId]) {
        $ready = $workerRoot.'/'.$index.'.ready';
        $result = $workerRoot.'/'.$index.'.json';
        $pipes = [];
        $process = proc_open([PHP_BINARY, __FILE__, '--accept-worker', $host, (string) $port, $database, $user, $password, $prefix, 'accept', 'admission-v1', (string) $draft['calculationId'], (string) $draft['revision'], $operationId, $ready, $workerRoot.'/go', $result], [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2));
        if (!is_resource($process)) {
            throw new TestFailure('SETUP_FAILURE: correction worker start');
        }
        $workers[] = ['process' => $process, 'pipes' => $pipes, 'ready' => $ready, 'result' => $result];
    }
    $deadline = microtime(true) + 10;
    while (count(array_filter($workers, static fn(array $worker): bool => is_file($worker['ready']))) !== 2 && microtime(true) < $deadline) {
        usleep(1000);
    }
    assertSameValue(2, count(array_filter($workers, static fn(array $worker): bool => is_file($worker['ready']))), 'independent acceptance workers ready');
    file_put_contents($workerRoot.'/go', 'go', LOCK_EX);
    $outcomes = [];
    foreach ($workers as $worker) {
        $deadline = microtime(true) + 15;
        while (!is_file($worker['result']) && microtime(true) < $deadline) {
            usleep(1000);
        }
        assertSameValue(true, is_file($worker['result']), 'bounded correction worker result');
        $stdout = stream_get_contents($worker['pipes'][1]);
        $stderr = stream_get_contents($worker['pipes'][2]);
        fclose($worker['pipes'][1]);
        fclose($worker['pipes'][2]);
        $exit = proc_close($worker['process']);
        assertSameValue(0, $exit, 'correction worker exit '.$stdout.$stderr);
        $outcomes[] = json_decode((string) file_get_contents($worker['result']), true, flags: JSON_THROW_ON_ERROR)['status'];
    }
    sort($outcomes, SORT_STRING);
    assertSameValue(['STALE_ENTITLEMENT_BASELINE', 'accepted'], $outcomes, 'F02 independent connections accept one overlapping baseline only');
    assertSameValue(1, (int) $db->query("SELECT COUNT(*) FROM {$prefix}fm2_otiz_entitlement_claims WHERE object_id=8201 AND active=1")->fetch_column(), 'F02 exactly one active economic claim');
    $increment = $owner->createDraft(501, correctionInput(8201, 'progress-v2', '2026-09-27', 400000, 300000), '00000000-0000-4000-8000-000000008205');
    $increment = $owner->accept(501, $increment['calculationId'], $increment['revision'], '00000000-0000-4000-8000-000000008206');
    assertSameValue(100000, $increment['totalCents'], 'F02 genuine later increment remains available');

    $admission[8501]=['decision'=>'allow','sourceRevision'=>'race-allow','incidentId'=>null,'observedAt'=>'2026-09-27T08:00:00+03:00'];$raceOld=$owner->createDraft(501,correctionInput(8501,'race-v1','2026-09-27',100000),'00000000-0000-4000-8000-000000008501');$raceOld=$owner->accept(501,$raceOld['calculationId'],$raceOld['revision'],'00000000-0000-4000-8000-000000008502');$raceDelta=$owner->createDraft(501,correctionInput(8501,'race-v2','2026-09-27',200000,100000),'00000000-0000-4000-8000-000000008503');$raceRoot=sys_get_temp_dir().'/otiz-cancel-race-'.bin2hex(random_bytes(5));mkdir($raceRoot,0700,true);$raceWorkers=[];foreach([['cancel',$raceOld,'00000000-0000-4000-8000-000000008504'],['accept',$raceDelta,'00000000-0000-4000-8000-000000008505']]as$i=>[$action,$draft,$operationId]){$pipes=[];$process=proc_open([PHP_BINARY,__FILE__,'--accept-worker',$host,(string)$port,$database,$user,$password,$prefix,$action,'race-allow',(string)$draft['calculationId'],(string)$draft['revision'],$operationId,$raceRoot."/$i.ready",$raceRoot.'/go',$raceRoot."/$i.json"],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));$raceWorkers[]=['process'=>$process,'pipes'=>$pipes,'ready'=>$raceRoot."/$i.ready",'result'=>$raceRoot."/$i.json"];}$deadline=microtime(true)+10;while(count(array_filter($raceWorkers,fn($w)=>is_file($w['ready'])))!==2&&microtime(true)<$deadline)usleep(1000);assertSameValue(2,count(array_filter($raceWorkers,fn($w)=>is_file($w['ready']))),'cancel/accept workers ready');file_put_contents($raceRoot.'/go','go',LOCK_EX);$raceOutcomes=[];foreach($raceWorkers as$worker){$deadline=microtime(true)+15;while(!is_file($worker['result'])&&microtime(true)<$deadline)usleep(1000);assertSameValue(true,is_file($worker['result']),'cancel/accept worker result');$stdout=stream_get_contents($worker['pipes'][1]);$stderr=stream_get_contents($worker['pipes'][2]);fclose($worker['pipes'][1]);fclose($worker['pipes'][2]);assertSameValue(0,proc_close($worker['process']),'cancel/accept worker exit '.$stdout.$stderr);$raceOutcomes[]=json_decode(file_get_contents($worker['result']),true,flags:JSON_THROW_ON_ERROR)['status'];}sort($raceOutcomes,SORT_STRING);assertSameValue(true,in_array($raceOutcomes,[['STALE_ENTITLEMENT_BASELINE','cancelled'],['accepted','cancelled']],true),'serialized cancel-vs-stale-accept outcomes');$activeRace=(int)$db->query("SELECT COALESCE(SUM(gross_cents),0) FROM {$prefix}fm2_otiz_entitlement_claims WHERE object_id=8501 AND active=1")->fetch_column();assertSameValue($raceOutcomes[0]==='STALE_ENTITLEMENT_BASELINE'?0:100000,$activeRace,'cancel race never accepts stale cumulative amount after baseline release');

    // F03: replacement is an inspectable neutral draft; the old calculation changes only on explicit acceptance.
    $oldDraft = $owner->createDraft(501, correctionInput(8301, 'progress-v1', '2026-09-26', 1500000), '00000000-0000-4000-8000-000000008301');
    $oldDraft = $owner->saveDecision(501, $oldDraft['calculationId'], $oldDraft['revision'], 'employee-001', 'pay', 'Сохранить получателя', '00000000-0000-4000-8000-000000008302');
    $oldDraft = $owner->saveDeduction(501, $oldDraft['calculationId'], $oldDraft['revision'], 8301, 'employee-001', 1000, 'Сохранённое удержание', 'DOC-8301', '00000000-0000-4000-8000-000000008303');
    $old = $owner->accept(501, $oldDraft['calculationId'], $oldDraft['revision'], '00000000-0000-4000-8000-000000008304');
    $oldSnapshot = static fn() => [$owner->read($old['calculationId']),$db->query("SELECT * FROM {$prefix}fm2_otiz_entitlement_claims WHERE calculation_id=".(int)$old['calculationId']." ORDER BY id")->fetch_all(MYSQLI_ASSOC),$db->query("SELECT * FROM {$prefix}fm2_otiz_recipient_obligations WHERE calculation_id=".(int)$old['calculationId']." ORDER BY id")->fetch_all(MYSQLI_ASSOC),$db->query("SELECT * FROM {$prefix}fm2_otiz_deductions WHERE calculation_id=".(int)$old['calculationId']." ORDER BY id")->fetch_all(MYSQLI_ASSOC),$db->query("SELECT * FROM {$prefix}fm2_otiz_payment_decisions WHERE calculation_id=".(int)$old['calculationId']." ORDER BY id")->fetch_all(MYSQLI_ASSOC)];
    assertSameValue(true, method_exists($owner, 'createReplacementDraft'), 'F03 owner exposes separate replacement-draft command');
    $sameObjectEmpty=correctionInput(8301,'empty-replacement','2026-09-27',0);$sameObjectEmpty['objects'][0]['entitlements']=[];correctionDeny('INCOMPLETE_REPLACEMENT',fn()=>$owner->createReplacementDraft(501,$old['calculationId'],$old['revision'],$sameObjectEmpty,'Пустая замена того же объекта','00000000-0000-4000-8000-000000008314'),'F03 same-object replacement without entitlements');
    $beforeIncomplete=$oldSnapshot();correctionDeny('INCOMPLETE_REPLACEMENT',fn()=>$owner->createReplacementDraft(501,$old['calculationId'],$old['revision'],['reportDate'=>'2026-09-27','objects'=>[]],'Неполная замена','00000000-0000-4000-8000-000000008311'),'F03 incomplete replacement');assertSameValue($beforeIncomplete,$oldSnapshot(),'incomplete replacement preserves old bytes/facts');
    $candidate = $owner->createReplacementDraft(501, $old['calculationId'], $old['revision'], correctionInput(8301, 'progress-v1', '2026-09-27', 1500000), 'Замена основания', '00000000-0000-4000-8000-000000008305');
    assertSameValue(['accepted', 'draft', 1499000, 1, 1], [$owner->read($old['calculationId'])['status'], $candidate['status'], $candidate['totalCents'], count($candidate['deductions']), count($candidate['decisions'])], 'F03 preview preserves old, full obligation and manual facts');
    correctionDeny('CALCULATION_HAS_DEPENDENCIES',fn()=>$owner->cancel(501,$old['calculationId'],$old['revision'],'Нельзя отменить при replacement dependency','00000000-0000-4000-8000-000000008315'),'F03 dependent accepted history cannot be cancelled');
    $beforeCompeting=$oldSnapshot();correctionDeny('REPLACEMENT_ALREADY_EXISTS',fn()=>$owner->createReplacementDraft(501,$old['calculationId'],$old['revision'],correctionInput(8301,'progress-v2','2026-09-27',1500000),'Конкурирующая замена','00000000-0000-4000-8000-000000008312'),'F03 competing replacement draft');assertSameValue($beforeCompeting,$oldSnapshot(),'competing replacement preserves old bytes/facts');
    $owner->deleteDraft(501, $candidate['calculationId'], $candidate['revision'], 'Отказ от замены', '00000000-0000-4000-8000-000000008306');
    assertSameValue(['accepted', 1], [$owner->read($old['calculationId'])['status'], (int) $db->query("SELECT active FROM {$prefix}fm2_otiz_entitlement_claims WHERE calculation_id=".(int) $old['calculationId'])->fetch_column()], 'F03 abandoned replacement leaves old claim active');
    $candidate = $owner->createReplacementDraft(501, $old['calculationId'], $old['revision'], correctionInput(8301, 'progress-v2', '2026-09-27', 1500000), 'Подтверждённая замена', '00000000-0000-4000-8000-000000008307');
    $beforeBlocked=$oldSnapshot();$admission[8301]=['decision'=>'blocked','sourceRevision'=>'admission-blocked','incidentId'=>'incident-8301','observedAt'=>'2026-09-27T08:30:00+03:00'];correctionDeny('COMPOSITION_MISMATCH',fn()=>$owner->accept(501,$candidate['calculationId'],$candidate['revision'],'00000000-0000-4000-8000-000000008313'),'F03 admission changes after replacement preview');assertSameValue($beforeBlocked,$oldSnapshot(),'blocked replacement acceptance preserves old bytes/facts');$admission[8301]=['decision'=>'allow','sourceRevision'=>'admission-resolved','incidentId'=>'incident-8301','observedAt'=>'2026-09-27T08:40:00+03:00'];correctionDeny('SNAPSHOT_REPLACEMENT_REQUIRED',fn()=>$owner->accept(501,$candidate['calculationId'],$candidate['revision'],'00000000-0000-4000-8000-000000008333'),'replacement preview prepared before incident cannot revive after resolution');$owner->deleteDraft(501,$candidate['calculationId'],$candidate['revision'],'Refresh after incident','00000000-0000-4000-8000-000000008334');$candidate=$owner->createReplacementDraft(501,$old['calculationId'],$old['revision'],correctionInput(8301,'progress-v3','2026-09-27',1500000),'Fresh replacement after resolution','00000000-0000-4000-8000-000000008335');
    $replacement = $owner->accept(501, $candidate['calculationId'], $candidate['revision'], '00000000-0000-4000-8000-000000008308');
    assertSameValue(['cancelled', 'accepted', 0, 1, 1499000], [$owner->read($old['calculationId'])['status'], $replacement['status'], (int) $db->query("SELECT active FROM {$prefix}fm2_otiz_entitlement_claims WHERE calculation_id=".(int) $old['calculationId'])->fetch_column(), (int) $db->query("SELECT active FROM {$prefix}fm2_otiz_entitlement_claims WHERE calculation_id=".(int) $replacement['calculationId'])->fetch_column(), $replacement['totalCents']], 'F03 explicit acceptance atomically transfers the complete obligation');
    $paidReplacement = $owner->markPaid(501, $replacement['calculationId'], $replacement['revision'], '2026-09-27', '00000000-0000-4000-8000-000000008309');
    correctionDeny('PAID_CALCULATION', fn() => $owner->createReplacementDraft(501, $replacement['calculationId'], $replacement['revision'], correctionInput(8301, 'progress-v3', '2026-09-27', 1500000), 'Недопустимая замена', '00000000-0000-4000-8000-000000008310'), 'F03 paid replacement source');
    assertSameValue(1499000, $paidReplacement['paidCents'], 'F03 payment uses preserved replacement obligation');
    $owner->reversePayment(501,$paidReplacement['paymentId'],'Ошибочная отметка','00000000-0000-4000-8000-000000008316');correctionDeny('PAID_CALCULATION',fn()=>$owner->cancel(501,$replacement['calculationId'],$replacement['revision'],'Нельзя отменить выплаченную историю','00000000-0000-4000-8000-000000008317'),'F03 reversed payment history cannot be cancelled');correctionDeny('PAID_CALCULATION',fn()=>$owner->createReplacementDraft(501,$replacement['calculationId'],$replacement['revision'],correctionInput(8301,'progress-v4','2026-09-27',1500000),'Нельзя заменить выплаченную историю','00000000-0000-4000-8000-000000008318'),'F03 reversed payment history cannot be replaced');
    $multi=correctionInput(8303,'multi-a','2026-09-27',100000);$multi['objects'][0]['entitlements'][]=['rightKey'=>'object-8303-second-right','sourceKind'=>'pto','sourceId'=>'second-right','sourceRevision'=>'multi-b','kind'=>'pto','recognitionDate'=>'2026-09-27','baselineClaimedCents'=>0,'cumulativeGrossCents'=>50000,'grossCents'=>50000];$multiOld=$owner->createDraft(501,$multi,'00000000-0000-4000-8000-000000008319');$multiOld=$owner->accept(501,$multiOld['calculationId'],$multiOld['revision'],'00000000-0000-4000-8000-000000008320');$partial=correctionInput(8303,'multi-a2','2026-09-27',100000);correctionDeny('INCOMPLETE_REPLACEMENT',fn()=>$owner->createReplacementDraft(501,$multiOld['calculationId'],$multiOld['revision'],$partial,'Пропущено второе право','00000000-0000-4000-8000-000000008321'),'F03 replacement cannot omit one of multiple rights');
    $abandonOld=$owner->createDraft(501,correctionInput(8304,'abandon-v1','2026-09-27',100000),'00000000-0000-4000-8000-000000008322');$abandonOld=$owner->accept(501,$abandonOld['calculationId'],$abandonOld['revision'],'00000000-0000-4000-8000-000000008323');$abandon=$owner->createReplacementDraft(501,$abandonOld['calculationId'],$abandonOld['revision'],correctionInput(8304,'abandon-v2','2026-09-27',100000),'Preview','00000000-0000-4000-8000-000000008324');$owner->deleteDraft(501,$abandon['calculationId'],$abandon['revision'],'Отказ','00000000-0000-4000-8000-000000008325');$abandonCancelled=$owner->cancel(501,$abandonOld['calculationId'],$abandonOld['revision'],'После отказа от preview','00000000-0000-4000-8000-000000008326');assertSameValue('cancelled',$abandonCancelled['status'],'deleted replacement preview leaves ordinary original cancellation available');
    $interleavedOld=$owner->createDraft(501,correctionInput(8305,'interleave-v1','2026-09-27',100000),'00000000-0000-4000-8000-000000008327');$interleavedOld=$owner->accept(501,$interleavedOld['calculationId'],$interleavedOld['revision'],'00000000-0000-4000-8000-000000008328');$interleavedPreview=$owner->createReplacementDraft(501,$interleavedOld['calculationId'],$interleavedOld['revision'],correctionInput(8305,'interleave-v2','2026-09-27',100000),'Preview before payment','00000000-0000-4000-8000-000000008329');$interleavedPayment=$owner->markPaid(501,$interleavedOld['calculationId'],$interleavedOld['revision'],'2026-09-27','00000000-0000-4000-8000-000000008330');$owner->reversePayment(501,$interleavedPayment['paymentId'],'Interleaving fixture','00000000-0000-4000-8000-000000008331');$beforeInterleaved=$owner->read($interleavedOld['calculationId']);correctionDeny('PAID_CALCULATION',fn()=>$owner->accept(501,$interleavedPreview['calculationId'],$interleavedPreview['revision'],'00000000-0000-4000-8000-000000008332'),'F03 replacement acceptance rechecks any payment history');assertSameValue($beforeInterleaved,$owner->read($interleavedOld['calculationId']),'rejected interleaved replacement preserves paid/reversed original');

    // F04: a pre-incident draft and an old accepted snapshot stay invalid after producer resolution.
    $admission[8401] = ['decision' => 'allow', 'sourceRevision' => 'admission-v1', 'incidentId' => null, 'observedAt' => '2026-09-27T08:00:00+03:00'];
    $preIncident = $owner->createDraft(501, correctionInput(8401, 'progress-v1', '2026-09-27', 100000), '00000000-0000-4000-8000-000000008401');
    $admission[8401] = ['decision' => 'blocked', 'sourceRevision' => 'admission-v2', 'incidentId' => 'incident-8401', 'observedAt' => '2026-09-27T08:10:00+03:00'];
    $admission[8401] = ['decision' => 'allow', 'sourceRevision' => 'admission-v3', 'incidentId' => 'incident-8401', 'observedAt' => '2026-09-27T08:20:00+03:00'];
    correctionDeny('SNAPSHOT_REPLACEMENT_REQUIRED', fn() => $owner->accept(501, $preIncident['calculationId'], $preIncident['revision'], '00000000-0000-4000-8000-000000008402'), 'F04 pre-incident draft cannot revive');
    assertSameValue(true, method_exists($owner, 'assertPaymentExportAllowed'), 'F04 current payment export uses owner-level admission seam');
    $admission[8402]=['decision'=>'allow','sourceRevision'=>'basis-v1','incidentId'=>null,'observedAt'=>'2026-09-27T08:00:00+03:00'];$nullIncident=$owner->createDraft(501,correctionInput(8402,'null-incident-right','2026-09-27',100000),'00000000-0000-4000-8000-000000008403');$nullIncident=$owner->accept(501,$nullIncident['calculationId'],$nullIncident['revision'],'00000000-0000-4000-8000-000000008404');$admission[8402]=['decision'=>'blocked','sourceRevision'=>'basis-v2','incidentId'=>'incident-8402','observedAt'=>'2026-09-27T08:10:00+03:00'];$admission[8402]=['decision'=>'allow','sourceRevision'=>'basis-v3','incidentId'=>null,'observedAt'=>'2026-09-27T08:20:00+03:00'];correctionDeny('SNAPSHOT_REPLACEMENT_REQUIRED',fn()=>$owner->markPaid(501,$nullIncident['calculationId'],$nullIncident['revision'],'2026-09-27','00000000-0000-4000-8000-000000008405'),'F04 null-incident resolution cannot revive payment');correctionDeny('SNAPSHOT_REPLACEMENT_REQUIRED',fn()=>$owner->assertPaymentExportAllowed(501,$nullIncident['calculationId']),'F04 null-incident resolution cannot revive export');

    echo "settlement_v2_corrections_001_test: OK\n";
} finally {
    if ($yii instanceof yii\db\Connection) {
        $yii->close();
    }
    if ($db instanceof mysqli) {
        $db->close();
    }
    $admin->query("DROP DATABASE IF EXISTS `{$database}`");
    $admin->close();
}
