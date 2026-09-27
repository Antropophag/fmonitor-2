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

function correctionSeedProgress(InspectionFixture $fixture, array $items, int &$sequence, int &$revision): void
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
    correctionSeedProgress($fixture, [2,3,4,5,6,7,9], $sequence, $revision);
    $db->query("UPDATE {$prefix}fm2_checklist_operations SET server_received_at='2026-09-03T11:00:00+03:00'");
    $forty = $builder->build(73, '2026-09-04');
    assertSameValue(4000, $forty['objects'][0]['confirmedBp'], 'real canonical fixture reaches 40%');
    assertSameValue([65000000, 19500000, 5000], [(int) $thirty['objects'][0]['fundCents'], (int) $thirty['objects'][0]['entitlements'][0]['cumulativeGrossCents'], (int) $thirty['objects'][0]['kssBp']], 'independent literal oracle inputs: 65,000,000-cent fund × 30% × Kss 0.5 = 9,750,000 payable cents');

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
    assertSameValue([$forty['objects'][0]['entitlements'][0]['rightKey'], 26000000], [$correctedForty['objects'][0]['entitlements'][0]['rightKey'], $correctedForty['objects'][0]['entitlements'][0]['cumulativeGrossCents']], 'correction/re-registration keeps one stable right and the same literal cumulative gross');

    $db->query("INSERT INTO {$prefix}fm2_otiz_admission_inputs(object_id,source_revision,decision,reason_code,observed_at,incident_id) VALUES(4512,'admission-v1','allow',NULL,'2026-09-03T08:00:00+03:00',NULL)");
    $db->query("INSERT INTO {$prefix}fm2_otiz_admission_events(object_id,source_revision,decision,reason_code,observed_at,incident_id) VALUES(4512,'admission-v1','allow',NULL,'2026-09-03T08:00:00+03:00',NULL)");
    // Use the same persisted producer rows as HTTP, without a second admission oracle.
    $access = new FMonitor2\Otiz\MariaDbOtizSettlementV2Access($yii, $prefix);
    $application=$db->query("SELECT application_id,original_revision_id,composition_sha256 FROM {$prefix}fm2_assignment_order_applications WHERE object_id=4512 ORDER BY application_sequence DESC LIMIT 1")->fetch_assoc();$producer=new FMonitor2\Otiz\OtizSettlementV2Admission($yii,$prefix);$producerEvidence=['acceptedOriginalId'=>(string)$application['original_revision_id'],'applicationId'=>(string)$application['application_id'],'effectiveAttributionRevision'=>(string)$application['composition_sha256']];$producerBlocked=$producer->record(73,'00000000-0000-4000-8000-000000009290','producer-incident',4512,'producer-same-source','blocked','COMPOSITION_MISMATCH',$producerEvidence,'2026-09-03T08:10:00+03:00');$producerAllow=$producer->record(73,'00000000-0000-4000-8000-000000009291','producer-incident',4512,'producer-same-source','allow',null,$producerEvidence,'2026-09-03T08:20:00+03:00');$producerReplay=$producer->record(73,'00000000-0000-4000-8000-000000009290','producer-incident',4512,'producer-same-source','blocked','COMPOSITION_MISMATCH',$producerEvidence,'2099-01-01T00:00:00+03:00');assertSameValue(['recorded','recorded','no_change',$producerBlocked['generation'],$producerAllow['generation'],2,'allow'],[$producerBlocked['status'],$producerAllow['status'],$producerReplay['status'],$producerReplay['generation'],$producerAllow['generation'],(int)$db->query("SELECT COUNT(*) FROM {$prefix}fm2_otiz_admission_events WHERE object_id=4512 AND source_revision='producer-same-source'")->fetch_column(),$access->admission(4512)['decision']],'same-source producer sequence and old-operation replay preserve two append-only events and current allow');try{$producer->record(73,'00000000-0000-4000-8000-000000009290','producer-incident',4512,'producer-same-source','allow',null,$producerEvidence,'2026-09-03T08:30:00+03:00');throw new TestFailure('producer replay conflict accepted');}catch(DomainException$e){assertSameValue('OPERATION_CONFLICT',$e->getMessage(),'same operation different payload conflicts without event');}
    $owner = new OtizSettlementV2($yii, $prefix, static fn(): string => '2026-09-04T12:00:00+03:00', static fn(int $objectId, string $phase): array => $access->admission($objectId));

    $zeroDraft = $owner->createDraft(73, $kssZero, '00000000-0000-4000-8000-000000009298');
    $zeroAccepted = $owner->accept(73, $zeroDraft['calculationId'], $zeroDraft['revision'], '00000000-0000-4000-8000-000000009299');
    assertSameValue([0, 1, 0], [$zeroAccepted['totalCents'], (int) $db->query("SELECT COUNT(*) FROM {$prefix}fm2_otiz_entitlement_claims WHERE calculation_id=".(int) $zeroAccepted['calculationId'])->fetch_column(), (int) $db->query("SELECT COUNT(*) FROM {$prefix}fm2_otiz_recipient_obligations WHERE calculation_id=".(int) $zeroAccepted['calculationId'])->fetch_column()], 'Kss zero recognizes the right without a fake payment obligation');
    $owner->cancel(73, $zeroAccepted['calculationId'], $zeroAccepted['revision'], 'Fixture releases right for remaining scenarios', '00000000-0000-4000-8000-000000009300');

    $draft30 = $owner->createDraft(73, $thirty, '00000000-0000-4000-8000-000000009301');
    $draft40 = $owner->createDraft(73, $forty, '00000000-0000-4000-8000-000000009302');
    $accepted30 = $owner->accept(73, $draft30['calculationId'], $draft30['revision'], '00000000-0000-4000-8000-000000009303');
    $expectedGross30 = 19500000;
    $expectedPayable30 = 9750000;
    assertSameValue([$expectedGross30, $expectedPayable30], [$accepted30['objects'][0]['grossCents'], $accepted30['totalCents']], 'builder to owner preserves independently calculated literal Kss cents');
    assertSameValue($expectedPayable30, (int) $db->query("SELECT SUM(amount_cents) FROM {$prefix}fm2_otiz_recipient_obligations WHERE calculation_id=".(int) $accepted30['calculationId'])->fetch_column(), 'accepted DB obligations equal the independent literal cents');
    correctionIntegrationDeny('STALE_ENTITLEMENT_BASELINE', fn() => $owner->accept(73, $draft40['calculationId'], $draft40['revision'], '00000000-0000-4000-8000-000000009304'), 'prebuilt 40% draft after 30% acceptance');
    $refreshed40 = $owner->refreshDraft(73, $draft40['calculationId'], $draft40['revision'], $builder->build(73, '2026-09-04'), '00000000-0000-4000-8000-000000009305');
    assertSameValue((int) ($forty['objects'][0]['entitlements'][0]['cumulativeGrossCents'] - $expectedGross30), $refreshed40['objects'][0]['grossCents'], 'refresh exposes only genuine 10% increment');

    $xlsx = $http->request('GET', '/pilot/otiz/calculations/'.$accepted30['calculationId'].'/export.xlsx?mode=payment', [], $fixture->cookies);
    assertSameValue([200, 'PK'], [$xlsx['status'], substr($xlsx['body'], 0, 2)], 'real accepted revision exports through HTTP');
    $files = correctionZip($xlsx['body']);
    $allXml = implode("\n", array_filter($files, static fn(string $name): bool => str_ends_with($name, '.xml'), ARRAY_FILTER_USE_KEY));
    $mainPath = correctionSheetPath($files, 'Расчёт ОТиЗ');
    $appendixPath = correctionSheetPath($files, 'Приложение к приказу');
    assertSameValue(true, substr_count($files[$mainPath], '<v>48750</v>') >= 2, 'designated main HTTP XLSX sheet contains two literal 48,750.00 stable-recipient rows');
    assertSameValue(2, substr_count($files[$appendixPath], '<v>48750</v>'), 'payment appendix aggregates the 97,500.00 total into two distinct stable recipients of 48,750.00 each');

    // Persisted #257 chronology: resolution does not revive an old accepted snapshot or a pre-incident draft.
    $preIncident = $owner->createDraft(73, $builder->build(73, '2026-09-04'), '00000000-0000-4000-8000-000000009306');
    $db->query("INSERT INTO {$prefix}fm2_otiz_admission_inputs(object_id,source_revision,decision,reason_code,observed_at,incident_id) VALUES(4512,'admission-v2','blocked','COMPOSITION_MISMATCH','2026-09-04T13:00:00+03:00','incident-4512'),(4512,'admission-v3','allow',NULL,'2026-09-04T14:00:00+03:00',NULL)");
    $db->query("INSERT INTO {$prefix}fm2_otiz_admission_events(object_id,source_revision,decision,reason_code,observed_at,incident_id) VALUES(4512,'admission-v2','blocked','COMPOSITION_MISMATCH','2026-09-04T13:00:00+03:00','incident-4512'),(4512,'admission-v3','allow',NULL,'2026-09-04T14:00:00+03:00',NULL)");
    correctionIntegrationDeny('SNAPSHOT_REPLACEMENT_REQUIRED', fn() => $owner->accept(73, $preIncident['calculationId'], $preIncident['revision'], '00000000-0000-4000-8000-000000009307'), 'pre-incident real builder draft after resolution');
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
    assertSameValue(['accepted','draft'],[$owner->read($accepted30['calculationId'])['status'],$replacementDraft['status']],'HTTP replacement preview leaves original accepted');
    $replacement = $owner->accept(73, $replacementDraft['calculationId'], $replacementDraft['revision'], '00000000-0000-4000-8000-000000009310');
    $replacementExport = $http->request('GET', '/pilot/otiz/calculations/'.$replacement['calculationId'].'/export.xlsx?mode=payment', [], $fixture->cookies);
    assertSameValue([200, 'PK', 'cancelled'], [$replacementExport['status'], substr($replacementExport['body'], 0, 2), $owner->read($accepted30['calculationId'])['status']], 'only accepted replacement restores current HTTP payment export');
    $db->query("INSERT INTO {$prefix}fm2_otiz_admission_events(object_id,source_revision,decision,reason_code,observed_at,incident_id) VALUES(4512,'admission-v3','blocked','COMPOSITION_MISMATCH','2026-09-04T15:00:00+03:00','incident-after-replacement'),(4512,'admission-v3','allow',NULL,'2026-09-04T16:00:00+03:00',NULL)");
    $db->query("UPDATE {$prefix}fm2_otiz_admission_inputs SET observed_at='2026-09-04T16:00:00+03:00',incident_id=NULL WHERE object_id=4512 AND source_revision='admission-v3'");
    $replacementReblocked=$http->request('GET','/pilot/otiz/calculations/'.$replacement['calculationId'].'/export.xlsx?mode=payment',[],$fixture->cookies);assertSameValue(409,$replacementReblocked['status'],'accepted replacement is invalidated by a later incident even when resolution reuses its source and clears incident id');
    $replacementFacts=(int)$db->query("SELECT COUNT(*) FROM {$prefix}fm2_otiz_payment_facts")->fetch_column();$replacementPay=$http->form('/pilot/otiz/calculations/'.$replacement['calculationId'].'/payments',['_csrf'=>$http->token($fixture->cookies),'operationId'=>'00000000-0000-4000-8000-000000009311','expectedRevision'=>$replacement['revision'],'paymentDate'=>'2026-09-04'],$fixture->cookies);assertSameValue([409,$replacementFacts],[$replacementPay['status'],(int)$db->query("SELECT COUNT(*) FROM {$prefix}fm2_otiz_payment_facts")->fetch_column()],'later incident blocks direct payment of accepted replacement without facts');

    echo "settlement_v2_corrections_integration_001_test: OK\n";
} finally {
    if ($yii instanceof yii\db\Connection) {
        $yii->close();
    }
    if ($fixture instanceof InspectionFixture) {
        $fixture->close();
    }
}
