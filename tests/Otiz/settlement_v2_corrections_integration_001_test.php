<?php
declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Yii2/InspectionFixture.php';

use FMonitor2\Otiz\OtizSettlementV2;
use FMonitor2\Otiz\OtizSettlementV2DraftBuilder;

function correctionZip(string $bytes): array
{
    $path = tempnam(sys_get_temp_dir(), 'otiz-correction-xlsx');
    file_put_contents($path, $bytes);
    $zip = new ZipArchive();
    assertSameValue(true, $zip->open($path) === true, 'HTTP XLSX opens with an independent ZIP reader');
    $files = [];
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $name = $zip->getNameIndex($index);
        $files[$name] = $zip->getFromIndex($index);
    }
    $zip->close();
    unlink($path);
    return $files;
}

function correctionSheetPath(array $files, string $name): string
{
    $workbook = simplexml_load_string((string) $files['xl/workbook.xml']);
    $workbook->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $relationshipId = null;
    foreach ($workbook->xpath('//m:sheet') as $sheet) {
        if ((string) $sheet['name'] === $name) {
            $relationshipId = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            break;
        }
    }
    $relationships = simplexml_load_string((string) $files['xl/_rels/workbook.xml.rels']);
    $relationships->registerXPathNamespace('p', 'http://schemas.openxmlformats.org/package/2006/relationships');
    foreach ($relationships->xpath('//p:Relationship') as $relationship) {
        if ((string) $relationship['Id'] === $relationshipId) {
            return 'xl/'.(string) $relationship['Target'];
        }
    }
    throw new TestFailure('Missing workbook sheet relationship '.$name);
}
function correctionRows(string $xml):array{preg_match_all('/<row\b[^>]*>(.*?)<\/row>/s',$xml,$m);return$m[1]??[];}function correctionCells(string$row):array{preg_match_all('/<c\b([^>]*)>(.*?)<\/c>/s',$row,$m);$out=[];foreach($m[2]??[]as$i=>$body){preg_match('/<t[^>]*>(.*?)<\/t>|<v>(.*?)<\/v>/s',$body,$v);$raw=($v[1]??'')!==''?$v[1]:($v[2]??'');$attributes=$m[1][$i]??'';preg_match('/\br="([A-Z]+\d+)"/',$attributes,$ref);preg_match('/\bt="([^"]+)"/',$attributes,$type);$out[]=['attributes'=>$attributes,'ref'=>$ref[1]??'','type'=>$type[1]??'n','value'=>html_entity_decode(strip_tags($raw),ENT_QUOTES|ENT_XML1,'UTF-8')];}return$out;}
function correctionStyleFormat(array$files,string$attributes):string{if(!preg_match('/\bs="(\d+)"/',$attributes,$m))return'';$styles=simplexml_load_string($files['xl/styles.xml']);$styles->registerXPathNamespace('m','http://schemas.openxmlformats.org/spreadsheetml/2006/main');$xfs=$styles->xpath('//m:cellXfs/m:xf');$id=(int)$xfs[(int)$m[1]]['numFmtId'];foreach($styles->xpath('//m:numFmts/m:numFmt')as$format)if((int)$format['numFmtId']===$id)return(string)$format['formatCode'];return[2=>'0.00',10=>'0.00%',14=>'yyyy-mm-dd'][$id]??'builtin-'.$id;}

function correctionSeedProgress(InspectionFixture $fixture, array $items, int &$sequence, int &$revision, ?array $onlyTabs = null): void
{
    $http = $fixture->http;
    $prefix = $http->p;
    $operationPrototype = $http->db->query("SELECT * FROM {$prefix}fm2_checklist_operations WHERE item_id=28 ORDER BY id LIMIT 1")->fetch_assoc();
    $attributionPrototype = $http->db->query("SELECT * FROM {$prefix}fm2_checklist_operation_installers WHERE client_operation_id='".$http->db->real_escape_string((string) $operationPrototype['client_operation_id'])."' ORDER BY installer_tab_id")->fetch_all(MYSQLI_ASSOC);
    foreach ($items as $item) {
        $clientOperationId = sprintf('cccccccc-cccc-4ccc-8ccc-%012d', $sequence++);
        $operation = $operationPrototype;
        unset($operation['id']);
        $operation['client_operation_id'] = $clientOperationId;
        $operation['item_id'] = $item;
        $operation['base_revision'] = $revision;
        $operation['accepted_revision'] = $revision + 1;
        $operation['device_time'] = '2026-09-03T10:00:00+03:00';
        $operation['server_received_at'] = '2026-09-03T11:00:00+03:00';
        $http->insert($prefix.'fm2_checklist_operations', $operation);
        foreach ($attributionPrototype as $attribution) {
            if ($onlyTabs !== null && !in_array((string)$attribution['installer_tab_id'], $onlyTabs, true)) continue;
            $attribution['client_operation_id'] = $clientOperationId;
            $http->insert($prefix.'fm2_checklist_operation_installers', $attribution);
        }
        $revision++;
    }
}

function correctionIntegrationDeny(string $expected, callable $command, string $message): void
{
    try {
        $command();
        throw new TestFailure($message.' accepted');
    } catch (DomainException $error) {
        assertSameValue($expected, $error->getMessage(), $message);
    }
}

$fixture = null;
$yii = null;
try {
    $fixture = new InspectionFixture(dirname(__DIR__, 2));
    $fixture->open();
    $http = $fixture->http;
    $db = $http->db;
    $prefix = $http->p;

    $db->query("INSERT INTO {$prefix}fm2_pilot_role_permissions(role_id,permission) VALUES(7,'otiz.manage')");
    $db->query("INSERT INTO {$prefix}fm2_pilot_users(user_id,full_name,email,status,activation_state,source_updated_at) VALUES(74,'ОТиЗ accepter','otiz-accepter@example.test',1,'active','2026-09-04T08:00:00+03:00')");
    $db->query("INSERT INTO {$prefix}fm2_pilot_user_roles(user_id,role_id,origin,assigned_at) VALUES(74,7,'rereview','2026-09-04T08:00:00+03:00')");
    $payload = json_decode((string) $db->query("SELECT payload_json FROM {$prefix}fm2_pilot_object_details WHERE object_id=4512")->fetch_column(), true, flags: JSON_THROW_ON_ERROR);
    $payload['fields']['pitmaterial'] = ['raw' => '41', 'display' => '41'];
    $payload['fields']['lift_type'] = ['raw' => '1', 'display' => 'пассажирский'];
    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $statement = $db->prepare("UPDATE {$prefix}fm2_pilot_object_details SET payload_json=?,content_sha256=? WHERE object_id=4512");
    $sha = hash('sha256', $encoded);
    $statement->bind_param('ss', $encoded, $sha);
    $statement->execute();

    $page = $fixture->page();
    $csrf = InspectionFixture::csrf($page);
    $sequence = 1;
    $revision = 0;
    $first = InspectionFixture::operation($sequence++, $revision++, 28);
    $first['deviceTime'] = '2026-09-03T10:00:00+03:00';
    InspectionFixture::result($fixture->send($first, $csrf), 200, 'accepted');
    // Literal template weights: 14 + 14 + 2 = 30 percentage points.
    correctionSeedProgress($fixture, [29,30,31,32,33,34,36,37,38,39,40,41,1], $sequence, $revision);
    $db->query("UPDATE {$prefix}fm2_checklist_operations SET server_received_at='2026-09-03T11:00:00+03:00'");

    $environment = [
        'host' => getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306),
        'user' => getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        'password' => getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local',
    ];
    $yii = new yii\db\Connection([
        'dsn' => 'mysql:host='.$environment['host'].';port='.$environment['port'].';dbname='.$http->database,
        'username' => $environment['user'],
        'password' => $environment['password'],
        'charset' => 'utf8mb4',
    ]);
    $yii->open();
    $builder = new OtizSettlementV2DraftBuilder($yii, $prefix, $prefix);

    $db->query("UPDATE {$prefix}fm_maintable SET plan_finish_date='2026-07-15' WHERE id=4512");
    $thirty = $builder->build(73, '2026-09-03');
    assertSameValue(5000, $thirty['objects'][0]['kssBp'], 'real canonical facts produce literal Kss 0.5');
    $db->query("UPDATE {$prefix}fm_maintable SET plan_finish_date='2026-09-03' WHERE id=4512");
    $kssOne = $builder->build(73, '2026-09-03');
    $db->query("UPDATE {$prefix}fm_maintable SET plan_finish_date='2026-05-26' WHERE id=4512");
    $kssZero = $builder->build(73, '2026-09-03');
    assertSameValue([10000, 5000, 0], [$kssOne['objects'][0]['kssBp'], $thirty['objects'][0]['kssBp'], $kssZero['objects'][0]['kssBp']], 'real canonical facts cover Kss 1, 0.5 and 0');
    assertSameValue(5000, $thirty['objects'][0]['deadlineBp'] ?? null, 'builder publishes the canonical owner coefficient');

    // Add exactly ten percentage points after the 30% draft already exists.
    correctionSeedProgress($fixture, [2,3,4,5,6,7,9], $sequence, $revision, ['7002']);
    $db->query("UPDATE {$prefix}fm2_checklist_operations SET server_received_at='2026-09-03T11:00:00+03:00'");
    $db->query("UPDATE {$prefix}fm_maintable SET plan_finish_date='2026-09-04' WHERE id=4512");
    $forty = $builder->build(73, '2026-09-04');
    assertSameValue(4000, $forty['objects'][0]['confirmedBp'], 'real canonical fixture reaches 40%');
    assertSameValue([65000000, 19500000, 5000], [(int) $thirty['objects'][0]['fundCents'], (int) array_sum(array_column($thirty['objects'][0]['entitlements'],'grossCents')), (int) $thirty['objects'][0]['kssBp']], 'independent literal oracle inputs: 65,000,000-cent fund × 30% × Kss 0.5 = 9,750,000 payable cents');

    // Canonical correction: retract and re-register the same item without minting a new economic right.
    $prototype = $db->query("SELECT * FROM {$prefix}fm2_checklist_operations WHERE item_id=2 AND operation_type='item_completed' ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $originalOperationId = (string) $prototype['client_operation_id'];
    unset($prototype['id']);
    $prototype['client_operation_id'] = 'dddddddd-dddd-4ddd-8ddd-000000000001';
    $prototype['operation_type'] = 'completion_retracted';
    $prototype['base_revision'] = $revision;
    $prototype['accepted_revision'] = ++$revision;
    $prototype['payload_json'] = json_encode(['originalClientOperationId' => $originalOperationId, 'reason' => 'Корректировка fixture'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $http->insert($prefix.'fm2_checklist_operations', $prototype);
    $prototype['client_operation_id'] = 'dddddddd-dddd-4ddd-8ddd-000000000002';
    $prototype['operation_type'] = 'item_completed';
    $prototype['base_revision'] = $revision;
    $prototype['accepted_revision'] = ++$revision;
    $prototype['payload_json'] = '{}';
    $http->insert($prefix.'fm2_checklist_operations', $prototype);
    $installerPrototypes = $db->query("SELECT * FROM {$prefix}fm2_checklist_operation_installers WHERE client_operation_id='".$db->real_escape_string($originalOperationId)."' ORDER BY installer_tab_id")->fetch_all(MYSQLI_ASSOC);
    foreach ($installerPrototypes as $installer) {
        $installer['client_operation_id'] = $prototype['client_operation_id'];
        $http->insert($prefix.'fm2_checklist_operation_installers', $installer);
    }
    $correctedForty = $builder->build(73, '2026-09-04');
    $beforeKeys=array_column($forty['objects'][0]['entitlements'],'rightKey');$afterKeys=array_column($correctedForty['objects'][0]['entitlements'],'rightKey');sort($beforeKeys);sort($afterKeys);
    assertSameValue([$beforeKeys,26000000],[$afterKeys,array_sum(array_column($correctedForty['objects'][0]['entitlements'],'grossCents'))],'correction/re-registration keeps the same logical rights and total literal gross, independent of physical claim granularity');

    $db->query("INSERT INTO {$prefix}fm2_otiz_admission_inputs(object_id,source_revision,decision,reason_code,observed_at,incident_id) VALUES(4512,'admission-v1','allow',NULL,'2026-09-03T08:00:00+03:00',NULL)");
    $db->query("INSERT INTO {$prefix}fm2_otiz_admission_events(object_id,source_revision,decision,reason_code,observed_at,incident_id) VALUES(4512,'admission-v1','allow',NULL,'2026-09-03T08:00:00+03:00',NULL)");
    // Use the same persisted producer rows as HTTP, without a second admission oracle.
    $access = new FMonitor2\Otiz\MariaDbOtizSettlementV2Access($yii, $prefix);
    $application=$db->query("SELECT application_id,original_revision_id,composition_sha256 FROM {$prefix}fm2_assignment_order_applications WHERE object_id=4512 ORDER BY application_sequence DESC LIMIT 1")->fetch_assoc();$producer=new FMonitor2\Otiz\OtizSettlementV2Admission($yii,$prefix);$producerEvidence=['acceptedOriginalId'=>(string)$application['original_revision_id'],'applicationId'=>(string)$application['application_id'],'effectiveAttributionRevision'=>(string)$application['composition_sha256']];$producerBlocked=$producer->record(73,'00000000-0000-4000-8000-000000009290','producer-incident',4512,'producer-same-source','blocked','COMPOSITION_MISMATCH',$producerEvidence,'2026-09-03T08:10:00+03:00');$producerAllow=$producer->record(73,'00000000-0000-4000-8000-000000009291','producer-incident',4512,'producer-same-source','allow',null,$producerEvidence,'2026-09-03T08:20:00+03:00');$producerReplay=$producer->record(73,'00000000-0000-4000-8000-000000009290','producer-incident',4512,'producer-same-source','blocked','COMPOSITION_MISMATCH',$producerEvidence,'2099-01-01T00:00:00+03:00');assertSameValue(['recorded','recorded','no_change',$producerBlocked['generation'],$producerAllow['generation'],2,'allow'],[$producerBlocked['status'],$producerAllow['status'],$producerReplay['status'],$producerReplay['generation'],$producerAllow['generation'],(int)$db->query("SELECT COUNT(*) FROM {$prefix}fm2_otiz_admission_events WHERE object_id=4512 AND source_revision='producer-same-source'")->fetch_column(),$access->admission(4512)['decision']],'same-source producer sequence and old-operation replay preserve two append-only events and current allow');try{$producer->record(73,'00000000-0000-4000-8000-000000009290','producer-incident',4512,'producer-same-source','allow',null,$producerEvidence,'2026-09-03T08:30:00+03:00');throw new TestFailure('producer replay conflict accepted');}catch(DomainException$e){assertSameValue('OPERATION_CONFLICT',$e->getMessage(),'same operation different payload conflicts without event');}
    $owner = new OtizSettlementV2($yii, $prefix, static fn(): string => '2026-09-04T12:00:00+03:00', static fn(int $objectId, string $phase): array => $access->admission($objectId));

    $db->query("UPDATE {$prefix}fm_maintable SET plan_finish_date='2026-05-26' WHERE id=4512");
    $zeroDraft = $owner->createDraft(73, $kssZero, '00000000-0000-4000-8000-000000009298');
    $zeroAccepted = $owner->accept(74, $zeroDraft['calculationId'], $zeroDraft['revision'], '00000000-0000-4000-8000-000000009299');
    assertSameValue([0, 19500000, 0], [$zeroAccepted['totalCents'], (int) $db->query("SELECT SUM(gross_cents) FROM {$prefix}fm2_otiz_entitlement_claims WHERE calculation_id=".(int) $zeroAccepted['calculationId'])->fetch_column(), (int) $db->query("SELECT COUNT(*) FROM {$prefix}fm2_otiz_recipient_obligations WHERE calculation_id=".(int) $zeroAccepted['calculationId'])->fetch_column()], 'Kss zero recognizes the whole work without a fake payment obligation');
    $crossActorExport=$http->request('GET','/pilot/otiz/calculations/'.$zeroAccepted['calculationId'].'/export.xlsx?mode=history',[],$fixture->cookies);$crossActorFiles=correctionZip($crossActorExport['body']);$crossActorMetadata=array_map('correctionCells',correctionRows($crossActorFiles[correctionSheetPath($crossActorFiles,'Метаданные')]));$crossActorMap=[];foreach($crossActorMetadata as$row)if(count($row)>=2)$crossActorMap[$row[0]['value']]=$row[1]['value'];assertSameValue(['73','74'],[(string)$db->query("SELECT actor_user_id FROM {$prefix}fm2_otiz_calculation_revisions WHERE id=".(int)$zeroAccepted['calculationId'])->fetch_column(),$crossActorMap['Принял']??null],'R07 creator 73 and accepter 74 remain distinct through persisted owner and HTTP XLSX metadata');
    $owner->cancel(73, $zeroAccepted['calculationId'], $zeroAccepted['revision'], 'Fixture releases right for remaining scenarios', '00000000-0000-4000-8000-000000009300');

    $db->query("UPDATE {$prefix}fm_maintable SET plan_finish_date='2026-07-15' WHERE id=4512");
    $draft30 = $owner->createDraft(73, $thirty, '00000000-0000-4000-8000-000000009301');
    $draft40 = $owner->createDraft(73, $forty, '00000000-0000-4000-8000-000000009302');
    correctionIntegrationDeny('DEDUCTION_EXCEEDS_AVAILABLE',fn()=>$owner->saveDeduction(73,$draft30['calculationId'],$draft30['revision'],4512,null,10000000,'Rereview canonical ceiling','R-H03','00000000-0000-4000-8000-000000009327'),'H03 canonical draft excessive deduction');
    $accepted30 = $owner->accept(73, $draft30['calculationId'], $draft30['revision'], '00000000-0000-4000-8000-000000009303');
    $acceptedRow = $db->query("SELECT actor_user_id,updated_at,projection_json FROM {$prefix}fm2_otiz_calculation_revisions WHERE id=".(int) $accepted30['calculationId'])->fetch_assoc();
    $expectedContentHash = hash('sha256', (string) $acceptedRow['projection_json']);
    $expectedGross30 = 19500000;
    $expectedPayable30 = 9750000;
    assertSameValue([$expectedGross30, $expectedPayable30], [$accepted30['objects'][0]['grossCents'], $accepted30['totalCents']], 'builder to owner preserves independently calculated literal Kss cents');
    assertSameValue($expectedPayable30, (int) $db->query("SELECT SUM(amount_cents) FROM {$prefix}fm2_otiz_recipient_obligations WHERE calculation_id=".(int) $accepted30['calculationId'])->fetch_column(), 'accepted DB obligations equal the independent literal cents');
    $db->query("UPDATE {$prefix}fm_maintable SET plan_finish_date='2026-09-04' WHERE id=4512");
    correctionIntegrationDeny('STALE_ENTITLEMENT_BASELINE', fn() => $owner->accept(73, $draft40['calculationId'], $draft40['revision'], '00000000-0000-4000-8000-000000009304'), 'prebuilt 40% draft after 30% acceptance');
    $refreshed40 = $owner->refreshDraft(73, $draft40['calculationId'], $draft40['revision'], $builder->build(73, '2026-09-04'), '00000000-0000-4000-8000-000000009305');
    assertSameValue(6500000, $refreshed40['objects'][0]['grossCents'], 'refresh exposes only genuine 10% increment');
    $acceptedIncrement = $owner->accept(73, $refreshed40['calculationId'], $refreshed40['revision'], '00000000-0000-4000-8000-000000009323');
    assertSameValue([['7002', 6500000]], array_map(static fn(array $row): array => [(string) $row['employee_id'], (int) $row['amount_cents']], $db->query("SELECT employee_id,amount_cents FROM {$prefix}fm2_otiz_recipient_obligations WHERE calculation_id=".(int) $acceptedIncrement['calculationId'].' ORDER BY employee_id')->fetch_all(MYSQLI_ASSOC)), 'R01 real 30 to 40 percent delta belongs only to the installer attributed to the new work');
    $priorAcceptedBytes = $db->query("SELECT input_json,projection_json,status FROM {$prefix}fm2_otiz_calculation_revisions WHERE id=".(int) $acceptedIncrement['calculationId'])->fetch_assoc();

    $db->query("INSERT INTO {$prefix}fm2_pilot_completion_facts(installation_case_id,fact_type,fact_date,details,recorded_at,recorded_by_user_id) VALUES(6101,'pto_act','2026-09-04','','2026-09-04T09:00:00+03:00',73),(6101,'declaration','2026-09-04','R01 documentary fixture','2026-09-04T09:10:00+03:00',73)");
    $documentary = $builder->build(73, '2026-09-04');
    assertSameValue(10000,$documentary['objects'][0]['kssBp'],'T05 current corrected plan and PTO yield Kss 1 regardless of old accepted Kss 0.5');
    assertSameValue(['7001'=>1500,'7002'=>2500],$documentary['objects'][0]['sourceEvidence']['progress']['contributions'],'T05 independently proven historical contribution basis');
    $documentaryEntitlements = array_values(array_filter($documentary['objects'][0]['entitlements'], static fn(array $right): bool => in_array($right['kind'], ['pto', 'declaration'], true)));
    assertSameValue([6500000, 3250000], array_map(static fn(array $right): int => (int) $right['grossCents'], $documentaryEntitlements), 'real PTO and declaration facts produce exact 10 and 5 percent rights from the 65,000,000-cent fund');
    assertSameValue(['7001', '7002'], array_column($documentary['objects'][0]['recipients'], 'employeeId'), 'documentary rights retain both canonical stable recipients');
    $documentary['objects'][0]['entitlements'] = $documentaryEntitlements;
    $documentaryDraft = $owner->createDraft(73, $documentary, '00000000-0000-4000-8000-000000009324');
    $documentaryAccepted = $owner->accept(73, $documentaryDraft['calculationId'], $documentaryDraft['revision'], '00000000-0000-4000-8000-000000009325');
    assertSameValue([3656250, 6093750], array_map('intval', array_column($db->query("SELECT amount_cents FROM {$prefix}fm2_otiz_recipient_obligations WHERE calculation_id=".(int) $documentaryAccepted['calculationId'].' ORDER BY employee_id')->fetch_all(MYSQLI_ASSOC), 'amount_cents')), 'documentary rights use proven contribution weights 1500/2500 and current canonical Kss 1, never historical-minimum Kss');
    correctionIntegrationDeny('EMPTY_CALCULATION',fn()=>$owner->createDraft(73,$builder->build(73,'2026-09-04'),'00000000-0000-4000-8000-000000009328'),'R08 fully recognized canonical input has no eligible delta');

    $correctedOperationId = (string)$prototype['client_operation_id'];
    $canonicalCorrection = $prototype;
    $canonicalCorrection['client_operation_id'] = 'dddddddd-dddd-4ddd-8ddd-000000000003';
    $canonicalCorrection['operation_type'] = 'completion_retracted';
    $canonicalCorrection['base_revision'] = $revision;
    $canonicalCorrection['accepted_revision'] = ++$revision;
    $canonicalCorrection['payload_json'] = json_encode(['originalClientOperationId'=>$correctedOperationId,'reason'=>'Исправление получателя A→B'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    $http->insert($prefix.'fm2_checklist_operations',$canonicalCorrection);
    $canonicalCorrection['client_operation_id'] = 'dddddddd-dddd-4ddd-8ddd-000000000004';
    $canonicalCorrection['operation_type'] = 'item_completed';
    $canonicalCorrection['base_revision'] = $revision;
    $canonicalCorrection['accepted_revision'] = ++$revision;
    $canonicalCorrection['payload_json'] = '{}';
    $http->insert($prefix.'fm2_checklist_operations',$canonicalCorrection);
    foreach($installerPrototypes as$installer){if((string)$installer['installer_tab_id']!=='7002')continue;$installer['client_operation_id']=$canonicalCorrection['client_operation_id'];$http->insert($prefix.'fm2_checklist_operation_installers',$installer);}
    $canonicalRecipientCorrection=$builder->build(73,'2026-09-04');
    assertSameValue(0,array_sum(array_column($canonicalRecipientCorrection['objects'][0]['entitlements'],'grossCents')),'R02 re-registering an already recognized work adds no economic right; its recipient does not redefine the original 30% crew');

    correctionSeedProgress($fixture,[10],$sequence,$revision,['7002']);$lateOperation=$db->query("SELECT client_operation_id FROM {$prefix}fm2_checklist_operations WHERE item_id=10 ORDER BY id DESC LIMIT 1")->fetch_column();$stmt=$db->prepare("UPDATE {$prefix}fm2_checklist_operations SET device_time='2026-09-26T10:00:00+03:00',server_received_at='2026-09-28T10:00:00+03:00' WHERE client_operation_id=?");$stmt->bind_param('s',$lateOperation);$stmt->execute();assertSameValue(4,(new ReflectionMethod(OtizSettlementV2DraftBuilder::class,'__construct'))->getNumberOfParameters(),'R01 builder exposes an explicit prepared-at clock seam');$preReceiptBuilder=new OtizSettlementV2DraftBuilder($yii,$prefix,$prefix,static fn()=>'2026-09-27T12:00:00+03:00');$preReceipt=$preReceiptBuilder->build(73,'2026-09-26');assertSameValue(0,array_sum(array_column($preReceipt['objects'][0]['entitlements']??[],'grossCents')),'late fact is absent before knowledge cutoff');$lateBuilder=new OtizSettlementV2DraftBuilder($yii,$prefix,$prefix,static fn()=>'2026-09-28T12:00:00+03:00');$lateKnown=$lateBuilder->build(73,'2026-09-26');assertSameValue([650000,650000,$priorAcceptedBytes],[(int)($lateKnown['objects'][0]['entitlements'][0]['grossCents']??0),(int)array_sum(array_column($lateKnown['objects'][0]['entitlements'],'grossCents')),$db->query("SELECT input_json,projection_json,status FROM {$prefix}fm2_otiz_calculation_revisions WHERE id=".(int)$acceptedIncrement['calculationId'])->fetch_assoc()],'late-known effective 26.09 fact received 28.09 is included after receipt while prior snapshot remains byte-immutable');

    $staleNormExport=$http->request('GET','/pilot/otiz/calculations/'.$accepted30['calculationId'].'/export.xlsx?mode=payment',[],$fixture->cookies);
    assertSameValue(409,$staleNormExport['status'],'corrected norm requires replacement before current payment export');
    $xlsx = $http->request('GET', '/pilot/otiz/calculations/'.$accepted30['calculationId'].'/export.xlsx?mode=history', [], $fixture->cookies);
    assertSameValue([200, 'PK'], [$xlsx['status'], substr($xlsx['body'], 0, 2)], 'real accepted revision exports through HTTP');
    $files = correctionZip($xlsx['body']);
    $allXml = implode("\n", array_filter($files, static fn(string $name): bool => str_ends_with($name, '.xml'), ARRAY_FILTER_USE_KEY));
    $mainPath = correctionSheetPath($files, 'Расчёт ОТиЗ');
    $appendixPath = correctionSheetPath($files, 'Приложение к приказу');
    $metadataPath = correctionSheetPath($files, 'Метаданные');
    $workersPath = correctionSheetPath($files, 'Работники');
    $mainRows=correctionRows($files[$mainPath]);$mainA=correctionCells($mainRows[2]??'');$mainB=correctionCells($mainRows[3]??'');assertSameValue([['P3','n','48750','0.00'],['P4','n','48750','0.00']],[[$mainA[15]['ref']??null,$mainA[15]['type']??null,$mainA[15]['value']??null,correctionStyleFormat($files,$mainA[15]['attributes']??'')],[$mainB[15]['ref']??null,$mainB[15]['type']??null,$mainB[15]['value']??null,correctionStyleFormat($files,$mainB[15]['attributes']??'')]],'R07 main HTTP XLSX binds exact recipient money to numeric styled P3/P4 cells below the mode banner');
    assertSameValue([['C3','n','1','0.00'],['J3','n','0.5','0.00']],[[$mainA[2]['ref']??null,$mainA[2]['type']??null,$mainA[2]['value']??null,correctionStyleFormat($files,$mainA[2]['attributes']??'')],[$mainA[9]['ref']??null,$mainA[9]['type']??null,$mainA[9]['value']??null,correctionStyleFormat($files,$mainA[9]['attributes']??'')]],'R07 Ksh and Kss are exact numeric coefficients, not percentages');
    $appendixRows=correctionRows($files[$appendixPath]);$appendixA=correctionCells($appendixRows[1]??'');$appendixB=correctionCells($appendixRows[2]??'');assertSameValue([['C2','n','48750','0.00'],['C3','n','48750','0.00']],[[$appendixA[2]['ref']??null,$appendixA[2]['type']??null,$appendixA[2]['value']??null,correctionStyleFormat($files,$appendixA[2]['attributes']??'')],[$appendixB[2]['ref']??null,$appendixB[2]['type']??null,$appendixB[2]['value']??null,correctionStyleFormat($files,$appendixB[2]['attributes']??'')]],'R07 appendix binds the 97,500.00 aggregate to two exact stable-recipient cells');
    $workerRows=correctionRows($files[$workersPath]);$workerA=correctionCells($workerRows[1]??'');$workerB=correctionCells($workerRows[2]??'');assertSameValue(['7001','0.5','7002','0.5'],[$workerA[1]['value']??null,$workerA[4]['value']??null,$workerB[1]['value']??null,$workerB[4]['value']??null],'R07 Workers sheet exact stable identities and display-scale 50 percent shares');assertSameValue(['0.00%','0.00%'],[correctionStyleFormat($files,$workerA[4]['attributes']??''),correctionStyleFormat($files,$workerB[4]['attributes']??'')],'R07 HTTP worker share cells bind exact percentage styles');$metadataRows=array_map('correctionCells',correctionRows($files[$metadataPath]));$metadataMap=[];foreach($metadataRows as$row)if(count($row)>=2)$metadataMap[$row[0]['value']]=$row[1]['value'];assertSameValue(true,preg_match('/^[a-f0-9]{64}$/',$metadataMap['Хеш содержимого']??'')===1,'R07 exact metadata Content hash cell');assertSameValue((string)$acceptedRow['updated_at'],$metadataMap['Принят']??null,'R07 exact accepted timestamp cell comes from the persisted revision');assertSameValue([$expectedContentHash,(string)$acceptedRow['actor_user_id']],[$metadataMap['Хеш содержимого']??null,$metadataMap['Принял']??null],'R07 metadata binds exact saved projection hash and accepting actor');

    // Persisted #257 chronology: resolution does not revive an old accepted snapshot or a pre-incident draft.
    $preIncident = $owner->createDraft(73, $lateKnown, '00000000-0000-4000-8000-000000009306');
    $db->query("INSERT INTO {$prefix}fm2_otiz_admission_inputs(object_id,source_revision,decision,reason_code,observed_at,incident_id) VALUES(4512,'admission-v2','blocked','COMPOSITION_MISMATCH','2026-09-04T13:00:00+03:00','incident-4512'),(4512,'admission-v3','allow',NULL,'2026-09-04T14:00:00+03:00',NULL)");
    $db->query("INSERT INTO {$prefix}fm2_otiz_admission_events(object_id,source_revision,decision,reason_code,observed_at,incident_id) VALUES(4512,'admission-v2','blocked','COMPOSITION_MISMATCH','2026-09-04T13:00:00+03:00','incident-4512'),(4512,'admission-v3','allow',NULL,'2026-09-04T14:00:00+03:00',NULL)");
    $editedPreIncident=$owner->saveDeduction(73,$preIncident['calculationId'],$preIncident['revision'],4512,null,10000,'R03 allocation-only edit preserves prepared admission','R03-DOC','00000000-0000-4000-8000-000000009312');
    correctionIntegrationDeny('SNAPSHOT_REPLACEMENT_REQUIRED', fn() => $owner->accept(73, $editedPreIncident['calculationId'], $editedPreIncident['revision'], '00000000-0000-4000-8000-000000009307'), 'allocation edit cannot erase pre-incident draft admission snapshot');
    $staleSnapshot=static fn()=>[$db->query("SELECT input_json,projection_json,status,revision,updated_at FROM {$prefix}fm2_otiz_calculation_revisions WHERE id=".(int)$editedPreIncident['calculationId'])->fetch_assoc(),$db->query("SELECT * FROM {$prefix}fm2_otiz_deductions WHERE calculation_id=".(int)$editedPreIncident['calculationId'].' ORDER BY id')->fetch_all(MYSQLI_ASSOC),$db->query("SELECT * FROM {$prefix}fm2_otiz_payment_decisions WHERE calculation_id=".(int)$editedPreIncident['calculationId'].' ORDER BY id')->fetch_all(MYSQLI_ASSOC),$db->query("SELECT * FROM {$prefix}fm2_otiz_v2_events WHERE calculation_id=".(int)$editedPreIncident['calculationId'].' ORDER BY id')->fetch_all(MYSQLI_ASSOC),$db->query("SELECT * FROM {$prefix}fm2_otiz_v2_operations WHERE operation_id='00000000-0000-4000-8000-000000009322'")->fetch_all(MYSQLI_ASSOC)];$staleFacts=$staleSnapshot();correctionIntegrationDeny('STALE_REVISION',fn()=>$owner->refreshDraft(73,$editedPreIncident['calculationId'],$editedPreIncident['revision']-1,$lateKnown,'00000000-0000-4000-8000-000000009322'),'R03 stale refresh revision');assertSameValue($staleFacts,$staleSnapshot(),'R03 stale refresh preserves revision/input/projection/manual facts/events and writes no operation receipt');
    $freshAfterIncident=$owner->refreshDraft(73,$editedPreIncident['calculationId'],$editedPreIncident['revision'],$lateKnown,'00000000-0000-4000-8000-000000009315');$freshAccepted=$owner->accept(73,$freshAfterIncident['calculationId'],$freshAfterIncident['revision'],'00000000-0000-4000-8000-000000009316');assertSameValue(10000,array_sum(array_column($freshAccepted['deductions'],'amountCents')),'R03 true refresh atomically captures current facts/admission and preserves manual deduction');
    $blockedExport = $http->request('GET', '/pilot/otiz/calculations/'.$accepted30['calculationId'].'/export.xlsx?mode=payment', [], $fixture->cookies);
    assertSameValue(409, $blockedExport['status'], 'resolved #257 incident keeps old HTTP payment export blocked');
    $paymentFactsBefore = (int) $db->query("SELECT COUNT(*) FROM {$prefix}fm2_otiz_payment_facts")->fetch_column();
    $blockedPayment = $http->form('/pilot/otiz/calculations/'.$accepted30['calculationId'].'/payments', [
        '_csrf' => $http->token($fixture->cookies),
        'operationId' => '00000000-0000-4000-8000-000000009308',
        'expectedRevision' => $accepted30['revision'],
        'paymentDate' => '2026-09-04',
    ], $fixture->cookies);
    assertSameValue([409, $paymentFactsBefore], [$blockedPayment['status'], (int) $db->query("SELECT COUNT(*) FROM {$prefix}fm2_otiz_payment_facts")->fetch_column()], 'resolved #257 incident keeps direct HTTP payment blocked without facts');
    $history = $http->request('GET', '/pilot/otiz/calculations/'.$accepted30['calculationId'].'/export.xlsx?mode=history', [], $fixture->cookies);
    assertSameValue([200, true], [$history['status'], str_contains(implode("\n", correctionZip($history['body'])), 'Историческая выгрузка')], 'historical HTTP export remains available and labeled');
    $replacementResponse = $http->form('/pilot/otiz/calculations/'.$accepted30['calculationId'].'/replace', ['_csrf'=>$http->token($fixture->cookies),'operationId'=>'00000000-0000-4000-8000-000000009309','expectedRevision'=>$accepted30['revision'],'reportDate'=>'2026-09-04','reason'=>'Урегулирование #257'], $fixture->cookies);
    $location = $replacementResponse['headers']['location'][0] ?? '';
    assertSameValue([303,1],[$replacementResponse['status'],preg_match('#^/pilot/otiz/calculations/(\d+)\?replacement=1$#',$location,$replacementMatch)],'HTTP replacement creates a separate draft and redirects to its new ID');
    $replacementDraft = $owner->read((int)$replacementMatch[1]);
    assertSameValue(['accepted','draft',19500000],[$owner->read($accepted30['calculationId'])['status'],$replacementDraft['status'],$replacementDraft['totalCents']],'R02 no-progress HTTP replacement preserves original work with current corrected Kss 1 and excludes neighboring accepted increment');
    $replacementDraft=$owner->saveDecision(73,$replacementDraft['calculationId'],$replacementDraft['revision'],'7002','pay','Сохранить решение при refresh','00000000-0000-4000-8000-000000009317');$replacementDraft=$owner->saveDeduction(73,$replacementDraft['calculationId'],$replacementDraft['revision'],4512,'7002',10000,'Сохранить удержание при refresh','R02-REFRESH','00000000-0000-4000-8000-000000009318');$replacementRefresh=$http->form('/pilot/otiz/calculations/'.$replacementDraft['calculationId'].'/refresh',['_csrf'=>$http->token($fixture->cookies),'operationId'=>'00000000-0000-4000-8000-000000009319','expectedRevision'=>$replacementDraft['revision'],'reportDate'=>'2026-09-04'],$fixture->cookies);assertSameValue(303,$replacementRefresh['status'],'R02 HTTP replacement refresh uses replacement-aware builder');$replacementDraft=$owner->read($replacementDraft['calculationId']);assertSameValue([19490000,1,1],[$replacementDraft['totalCents'],count($replacementDraft['deductions']),count($replacementDraft['decisions'])],'R02 successful HTTP replacement refresh preserves complete old rights and manual facts');
    $replacementBefore=json_encode($owner->read($accepted30['calculationId']),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);correctionIntegrationDeny('INCOMPLETE_REPLACEMENT',fn()=>$owner->refreshDraft(73,$replacementDraft['calculationId'],$replacementDraft['revision'],$builder->build(73,'2026-09-04'),'00000000-0000-4000-8000-000000009314'),'R02 replacement refresh cannot discard old rights');assertSameValue($replacementBefore,json_encode($owner->read($accepted30['calculationId']),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'failed replacement refresh leaves original byte-equivalent');
    $replacement = $owner->accept(73, $replacementDraft['calculationId'], $replacementDraft['revision'], '00000000-0000-4000-8000-000000009310');
    $replacementExport = $http->request('GET', '/pilot/otiz/calculations/'.$replacement['calculationId'].'/export.xlsx?mode=payment', [], $fixture->cookies);
    assertSameValue([200, 'PK', 'cancelled'], [$replacementExport['status'], substr($replacementExport['body'], 0, 2), $owner->read($accepted30['calculationId'])['status']], 'only accepted replacement restores current HTTP payment export');
    assertSameValue([[['7001',9750000],['7002',9740000]],['7001','7002']], [array_map(static fn(array$row):array=>[(string)$row['employee_id'],(int)$row['amount_cents']],$db->query("SELECT employee_id,amount_cents FROM {$prefix}fm2_otiz_recipient_obligations WHERE calculation_id=".(int)$replacement['calculationId'].' ORDER BY employee_id')->fetch_all(MYSQLI_ASSOC)),array_column($db->query("SELECT employee_id FROM {$prefix}fm2_otiz_recipient_obligations WHERE calculation_id=".(int)$accepted30['calculationId'].' ORDER BY employee_id')->fetch_all(MYSQLI_ASSOC),'employee_id')], 'R02 replacement preserves original 30% A+B; correction of neighboring B work cannot transfer their money');
    $replacementFiles=correctionZip($replacementExport['body']);$replacementAppendixRows=correctionRows($replacementFiles[correctionSheetPath($replacementFiles,'Приложение к приказу')]);$replacementAppendix=array_map('correctionCells',array_slice($replacementAppendixRows,1));assertSameValue([['7001','97500'],['7002','97400']],array_map(static fn($cells)=>[$cells[0]['value'],$cells[2]['value']],$replacementAppendix),'R02 payment XLSX matches both saved original recipients and the personal deduction');
    $db->query("INSERT INTO {$prefix}fm2_otiz_admission_events(object_id,source_revision,decision,reason_code,observed_at,incident_id) VALUES(4512,'admission-v3','blocked','COMPOSITION_MISMATCH','2026-09-04T15:00:00+03:00','incident-after-replacement'),(4512,'admission-v3','allow',NULL,'2026-09-04T16:00:00+03:00',NULL)");
    $db->query("UPDATE {$prefix}fm2_otiz_admission_inputs SET observed_at='2026-09-04T16:00:00+03:00',incident_id=NULL WHERE object_id=4512 AND source_revision='admission-v3'");
    $replacementReblocked=$http->request('GET','/pilot/otiz/calculations/'.$replacement['calculationId'].'/export.xlsx?mode=payment',[],$fixture->cookies);assertSameValue(409,$replacementReblocked['status'],'accepted replacement is invalidated by a later incident even when resolution reuses its source and clears incident id');
    $replacementFacts=(int)$db->query("SELECT COUNT(*) FROM {$prefix}fm2_otiz_payment_facts")->fetch_column();$replacementPay=$http->form('/pilot/otiz/calculations/'.$replacement['calculationId'].'/payments',['_csrf'=>$http->token($fixture->cookies),'operationId'=>'00000000-0000-4000-8000-000000009311','expectedRevision'=>$replacement['revision'],'paymentDate'=>'2026-09-04'],$fixture->cookies);assertSameValue([409,$replacementFacts],[$replacementPay['status'],(int)$db->query("SELECT COUNT(*) FROM {$prefix}fm2_otiz_payment_facts")->fetch_column()],'later incident blocks direct payment of accepted replacement without facts');

    $fixture->queueFixtures();$cloneObject=function(int$objectId)use($http,$db,$prefix):int{$caseId=(int)$db->query("SELECT id FROM {$prefix}fm2_installation_cases WHERE legacy_installation_object_id={$objectId}")->fetch_column();$detail=$db->query("SELECT * FROM {$prefix}fm2_pilot_object_details WHERE object_id=4512")->fetch_assoc();$detail['object_id']=$objectId;$payload=json_decode($detail['payload_json'],true,flags:JSON_THROW_ON_ERROR);$payload['objectId']=$objectId;$detail['payload_json']=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$detail['content_sha256']=hash('sha256',$detail['payload_json']);$http->insert($prefix.'fm2_pilot_object_details',$detail);$association=$db->query("SELECT * FROM {$prefix}fm2_checklist_template_associations WHERE subject_kind='operational_case' AND subject_id='6101' LIMIT 1")->fetch_assoc();unset($association['id']);$association['subject_id']=(string)$caseId;$http->insert($prefix.'fm2_checklist_template_associations',$association);return$caseId;};$blockedCase=$cloneObject(4513);$unknownCase=$cloneObject(4514);$noDeltaCase=$cloneObject(4515);$db->query("UPDATE {$prefix}fm2_installation_cases SET process_state='working' WHERE id={$noDeltaCase}");correctionSeedProgress($fixture,[11],$sequence,$revision,['7001']);$sourceOperation=$db->query("SELECT * FROM {$prefix}fm2_checklist_operations WHERE item_id=11 ORDER BY id DESC LIMIT 1")->fetch_assoc();$sourceAttributionOperationId=(string)$sourceOperation['client_operation_id'];unset($sourceOperation['id']);$sourceOperation['installation_case_id']=$blockedCase;$sourceOperation['client_operation_id']='eeeeeeee-eeee-4eee-8eee-000000004513';$sourceOperation['base_revision']=0;$sourceOperation['accepted_revision']=1;$http->insert($prefix.'fm2_checklist_operations',$sourceOperation);$sourceAttrs=$db->query("SELECT * FROM {$prefix}fm2_checklist_operation_installers WHERE client_operation_id='".$db->real_escape_string($sourceAttributionOperationId)."' ORDER BY installer_tab_id")->fetch_all(MYSQLI_ASSOC);foreach($sourceAttrs as$attr){$attr['client_operation_id']=$sourceOperation['client_operation_id'];$http->insert($prefix.'fm2_checklist_operation_installers',$attr);}$unknownOperation=$sourceOperation;$unknownOperation['installation_case_id']=$unknownCase;$unknownOperation['client_operation_id']='eeeeeeee-eeee-4eee-8eee-000000004514';$http->insert($prefix.'fm2_checklist_operations',$unknownOperation);foreach($sourceAttrs as$attr){$attr['client_operation_id']=$unknownOperation['client_operation_id'];$http->insert($prefix.'fm2_checklist_operation_installers',$attr);}$db->query("INSERT INTO {$prefix}fm2_otiz_admission_events(object_id,source_revision,decision,reason_code,observed_at,incident_id)VALUES(4513,'r08-blocked','blocked','COMPOSITION_MISMATCH','2026-09-28T12:00:00+03:00','r08-incident')");$db->query("INSERT INTO {$prefix}fm2_otiz_admission_inputs(object_id,source_revision,decision,reason_code,observed_at,incident_id)VALUES(4513,'r08-blocked','blocked','COMPOSITION_MISMATCH','2026-09-28T12:00:00+03:00','r08-incident'),(4514,'r08-unknown','unknown','PRODUCER_UNKNOWN','2026-09-28T12:00:00+03:00','r08-unknown-incident')");$db->query("INSERT INTO {$prefix}fm2_otiz_admission_events(object_id,source_revision,decision,reason_code,observed_at,incident_id)VALUES(4514,'r08-unknown','unknown','PRODUCER_UNKNOWN','2026-09-28T12:00:00+03:00','r08-unknown-incident')");$r08Input=$lateBuilder->build(73,'2026-09-28');$r08Owner=new OtizSettlementV2($yii,$prefix,static fn()=>'2026-09-28T14:00:00+03:00',static fn(int$id,string$phase)=>$access->admission($id));$r08Draft=$r08Owner->createDraft(73,$r08Input,'00000000-0000-4000-8000-000000009320');$r08Draft=$r08Owner->refreshDraft(73,$r08Draft['calculationId'],$r08Draft['revision'],$lateBuilder->build(73,'2026-09-28'),'00000000-0000-4000-8000-000000009326');assertSameValue([[4512],[4513,4514],false],[array_column($r08Draft['objects'],'objectId'),array_column($r08Draft['excludedObjects']??[],'objectId'),in_array(4515,array_column($r08Draft['objects'],'objectId'),true)],'R03/R08 multi-object refresh atomically preserves allow, blocked, UNKNOWN and no-delta partitions');$r08Page=$http->request('GET','/pilot/otiz/calculations/'.$r08Draft['calculationId'],[],$fixture->cookies);assertSameValue(true,str_contains($r08Page['body'],'COMPOSITION_MISMATCH')&&str_contains($r08Page['body'],'PRODUCER_UNKNOWN'),'R08 UI exposes immutable blocked and UNKNOWN exclusion reasons');$r08Export=$http->request('GET','/pilot/otiz/calculations/'.$r08Draft['calculationId'].'/export.xlsx?mode=draft',[],$fixture->cookies);$r08Files=correctionZip($r08Export['body']);$controlPath=correctionSheetPath($r08Files,'Контроль');$paymentPath=correctionSheetPath($r08Files,'Приложение к приказу');$controlRows=array_map('correctionCells',array_slice(correctionRows($r08Files[$controlPath]),1));$controlReasons=[];foreach($controlRows as$row)$controlReasons[]=$row[0]['value']??'';$paymentRows=array_map('correctionCells',array_slice(correctionRows($r08Files[$paymentPath]),1));$paymentTotal=array_sum(array_map(static fn(array$row):float=>(float)($row[2]['value']??0),$paymentRows));sort($controlReasons);assertSameValue([['COMPOSITION_MISMATCH','PRODUCER_UNKNOWN'],(float)($r08Draft['totalCents']/100)],[array_values(array_intersect(['COMPOSITION_MISMATCH','PRODUCER_UNKNOWN'],$controlReasons)),$paymentTotal],'R08 XLSX exact Control reasons explain exclusions and payment cells total only eligible money');

    $r08Snapshot=static fn()=>[$db->query("SELECT input_json,projection_json,status,revision,updated_at FROM {$prefix}fm2_otiz_calculation_revisions WHERE id=".(int)$r08Draft['calculationId'])->fetch_assoc(),$db->query("SELECT * FROM {$prefix}fm2_otiz_entitlement_claims ORDER BY id")->fetch_all(MYSQLI_ASSOC),$db->query("SELECT * FROM {$prefix}fm2_otiz_recipient_obligations ORDER BY id")->fetch_all(MYSQLI_ASSOC),$db->query("SELECT * FROM {$prefix}fm2_otiz_deductions ORDER BY id")->fetch_all(MYSQLI_ASSOC),$db->query("SELECT * FROM {$prefix}fm2_otiz_payment_decisions ORDER BY id")->fetch_all(MYSQLI_ASSOC),$db->query("SELECT * FROM {$prefix}fm2_otiz_v2_events ORDER BY id")->fetch_all(MYSQLI_ASSOC),$db->query("SELECT * FROM {$prefix}fm2_otiz_v2_operations ORDER BY actor_user_id,operation_id")->fetch_all(MYSQLI_ASSOC)];$r08FactsBefore=$r08Snapshot();$db->query("INSERT INTO {$prefix}fm2_otiz_admission_events(object_id,source_revision,decision,reason_code,observed_at,incident_id)VALUES(4512,'r08-late-unknown','unknown','PRODUCER_UNKNOWN','2026-09-28T13:00:00+03:00','r08-late')");$db->query("INSERT INTO {$prefix}fm2_otiz_admission_inputs(object_id,source_revision,decision,reason_code,observed_at,incident_id)VALUES(4512,'r08-late-unknown','unknown','PRODUCER_UNKNOWN','2026-09-28T13:00:00+03:00','r08-late')");correctionIntegrationDeny('ADMISSION_UNKNOWN',fn()=>$r08Owner->accept(73,$r08Draft['calculationId'],$r08Draft['revision'],'00000000-0000-4000-8000-000000009321'),'R08 late UNKNOWN of included object rejects whole draft');assertSameValue($r08FactsBefore,$r08Snapshot(),'R08 late UNKNOWN rejection preserves revision, claims, obligations, manual facts and events byte-for-byte');

    echo "settlement_v2_corrections_integration_001_test: OK\n";
} finally {
    if ($yii instanceof yii\db\Connection) {
        $yii->close();
    }
    if ($fixture instanceof InspectionFixture) {
        $fixture->close();
    }
}
