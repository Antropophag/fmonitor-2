<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/app/InstallationProcess/DatabaseUnavailable.php';
require_once dirname(__DIR__).'/app/InstallationProcess/MariaDbSchemaInspector.php';
require_once dirname(__DIR__).'/app/InstallationProcess/IdentityAccessDefinitionSchemaMigration.php';
require_once dirname(__DIR__).'/app/InstallationProcess/MariaDbExactSchemaFingerprint.php';
require_once dirname(__DIR__).'/app/InstallationProcess/InspectionEvidenceOperationDefinitionSchemaMigration.php';
require_once dirname(__DIR__).'/app/InstallationProcess/InspectionEvidenceDefinitionSchemaMigration.php';
require_once dirname(__DIR__).'/app/InstallationProcess/InspectionEvidenceSchemaMigration.php';
require_once dirname(__DIR__).'/app/InstallationProcess/MariaDbPilotLegacyObjectSchemaReadiness.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function seedCurrentOtizSchema(mysqli $db,string $prefix):void
{
    $ddl=[
        'fm2_pilot_otiz_snapshots'=>"(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,report_date DATE NOT NULL,status ENUM('draft','accepted') NOT NULL,previous_snapshot_id BIGINT UNSIGNED NULL,rules_version VARCHAR(80) NOT NULL,calculated_at VARCHAR(40) NOT NULL,calculated_by_user_id BIGINT UNSIGNED NOT NULL,accepted_at VARCHAR(40) NULL,accepted_by_user_id BIGINT UNSIGNED NULL,total_pool_cents BIGINT NOT NULL,total_closed_cents BIGINT NOT NULL,total_available_cents BIGINT NOT NULL,content_hash CHAR(64) NOT NULL,KEY(report_date,status))",
        'fm2_pilot_otiz_snapshot_objects'=>"(snapshot_id BIGINT UNSIGNED NOT NULL,object_id BIGINT UNSIGNED NOT NULL,regnumber VARCHAR(120) NOT NULL,address VARCHAR(500) NOT NULL,previous_progress_bp INT NOT NULL,current_progress_bp INT NOT NULL,progress_fact_date DATE NOT NULL,premium_cents BIGINT NOT NULL,shaft_bp INT NOT NULL,kss_bp INT NOT NULL,accrued_cents BIGINT NOT NULL,fund_cents BIGINT NOT NULL,closed_before_cents BIGINT NOT NULL,remaining_cents BIGINT NOT NULL,pool_cents BIGINT NOT NULL,distributed_cents BIGINT NOT NULL,undistributed_cents BIGINT NOT NULL,calculation_state VARCHAR(40) NOT NULL,inputs_json JSON NOT NULL,PRIMARY KEY(snapshot_id,object_id))",
        'fm2_pilot_otiz_snapshot_allocations'=>"(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,snapshot_id BIGINT UNSIGNED NOT NULL,object_id BIGINT UNSIGNED NOT NULL,tab_id VARCHAR(40) NOT NULL,full_name VARCHAR(300) NOT NULL,position_name VARCHAR(200) NOT NULL,contribution_bp INT NOT NULL,base_ktu_bp INT NOT NULL,adjustment_ktu_bp INT NOT NULL,effective_ktu_bp INT NOT NULL,share_bp INT NOT NULL,amount_cents BIGINT NOT NULL,employment_status VARCHAR(40) NOT NULL,participation_basis VARCHAR(300) NOT NULL,KEY(snapshot_id,object_id))",
        'fm2_pilot_otiz_snapshot_issues'=>"(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,snapshot_id BIGINT UNSIGNED NOT NULL,object_id BIGINT UNSIGNED NOT NULL,severity ENUM('blocker','warning') NOT NULL,issue_code VARCHAR(80) NOT NULL,message VARCHAR(600) NOT NULL,owner_role VARCHAR(120) NOT NULL,state ENUM('open','resolved') NOT NULL DEFAULT 'open',resolution VARCHAR(600) NULL,resolved_by_user_id BIGINT UNSIGNED NULL,resolved_at VARCHAR(40) NULL,KEY(snapshot_id,object_id,severity))",
        'fm2_pilot_otiz_snapshot_evidence'=>"(snapshot_id BIGINT UNSIGNED NOT NULL,legacy_object_id BIGINT UNSIGNED NOT NULL,admission_state ENUM('confirmed_not_mapped','excluded') NOT NULL,source_label VARCHAR(160) NOT NULL,source_locator VARCHAR(160) NOT NULL,snapshot_hash CHAR(64) NOT NULL,projection_hash CHAR(64) NOT NULL,evidence_grade CHAR(1) NOT NULL,payload_json JSON NOT NULL,PRIMARY KEY(snapshot_id,legacy_object_id))",
        'fm2_pilot_otiz_payment_closures'=>"(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,snapshot_id BIGINT UNSIGNED NOT NULL,object_id BIGINT UNSIGNED NOT NULL,closed_on DATE NOT NULL,paid_cents BIGINT NOT NULL,discipline_cents BIGINT NOT NULL,deadline_cents BIGINT NOT NULL,basis VARCHAR(500) NOT NULL,artifact VARCHAR(300) NOT NULL,created_by_user_id BIGINT UNSIGNED NOT NULL,created_at VARCHAR(40) NOT NULL,reverses_payment_closure_id BIGINT UNSIGNED NULL,KEY(object_id,closed_on),KEY(snapshot_id),UNIQUE KEY unique_reversal(reverses_payment_closure_id))",
        'fm2_pilot_otiz_events'=>"(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,snapshot_id BIGINT UNSIGNED NULL,object_id BIGINT UNSIGNED NULL,event_type VARCHAR(80) NOT NULL,payload_json JSON NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,occurred_at VARCHAR(40) NOT NULL,KEY(snapshot_id,id))",
    ];
    foreach($ddl as$table=>$definition)$db->query("CREATE TABLE `{$prefix}{$table}`{$definition} ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach(array_keys($ddl)as$table){$escaped=$db->real_escape_string($prefix.$table);if((int)$db->query("SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$escaped'")->fetch_assoc()['n']!==1)throw new RuntimeException('OTIZ fixture manifest missing table');}
    $closures=$db->real_escape_string($prefix.'fm2_pilot_otiz_payment_closures');$unique=$db->query("SELECT NON_UNIQUE,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) columns_list FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$closures' AND INDEX_NAME='unique_reversal' GROUP BY NON_UNIQUE")->fetch_assoc();if($unique!==['NON_UNIQUE'=>'0','columns_list'=>'reverses_payment_closure_id'])throw new RuntimeException('OTIZ fixture manifest missing exact unique_reversal');
}

$prefix = 'otiz_verify_' . bin2hex(random_bytes(5)) . '_';
$dbHost = getenv('FMONITOR_VERIFY_DB_HOST') ?: 'mariadb';
$dbPort = (int) (getenv('FMONITOR_VERIFY_DB_PORT') ?: '3306');
$dbName = getenv('FMONITOR_VERIFY_DB_NAME') ?: 'fmonitor2_demo';
$dbUser = getenv('FMONITOR_VERIFY_DB_USER') ?: 'fmonitor2_demo';
$dbPassword = getenv('FMONITOR_VERIFY_DB_PASSWORD') ?: 'fmonitor2_demo_local';
$db = new mysqli($dbHost, $dbUser, $dbPassword, $dbName, $dbPort);
$db->set_charset('utf8mb4');
$tables = [
    'fm2_pilot_otiz_events',
    'fm2_pilot_otiz_payment_closures',
    'fm2_pilot_otiz_snapshot_evidence',
    'fm2_pilot_otiz_snapshot_issues',
    'fm2_pilot_otiz_snapshot_allocations',
    'fm2_pilot_otiz_snapshot_objects',
    'fm2_pilot_otiz_snapshots',
    'fm2_pilot_user_roles',
    'fm2_pilot_role_permissions',
    'fm2_pilot_roles',
    'fm2_pilot_users',
    'fm2_migrated_evidence_decision_state',
    'fm2_migrated_evidence_conflicts',
    'fm2_migrated_evidence_projection',
    'fm2_migrated_evidence_decisions',
    'fm2_migration_quarantine_decisions',
    'fm2_pilot_object_details',
    'fm2_checklist_operation_installers',
    'fm2_checklist_operations',
    'fm2_checklist_photos',
    'fm2_checklist_revisions',
    'fm2_checklist_template_snapshots',
    'fm2_order_installers',
    'fm2_assignment_orders',
    'fm_maintable',
    'fm2_migration_classification_provenance',
    'fm2_installation_cases',
];

$expect = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
    echo "ok - {$message}\n";
};

$run = static function (string $path, string $method = 'GET', array $post = [], string $email = 'otiz.verify@shlz.ru', string $csrf = 'verified-csrf-token') use ($prefix, $dbHost, $dbPort, $dbName, $dbUser, $dbPassword): array {
    $worker = <<<'PHP'
require getcwd() . '/app/InstallationProcess/DatabaseUnavailable.php';
require getcwd() . '/app/InstallationProcess/MariaDbSchemaInspector.php';
require getcwd() . '/app/InstallationProcess/IdentityAccessDefinitionSchemaMigration.php';
require getcwd() . '/app/InstallationProcess/MariaDbExactSchemaFingerprint.php';
require getcwd() . '/app/InstallationProcess/MariaDbPilotLegacyObjectSchemaReadiness.php';
require getcwd() . '/rapid-pilot/Otiz.php';
$_SERVER['REQUEST_METHOD'] = getenv('VERIFY_METHOD');
$_SERVER['REMOTE_USER'] = getenv('VERIFY_EMAIL');
$_SERVER['FMONITOR_AUTH_USER_ID'] = getenv('VERIFY_USER_ID');
$_SERVER['FMONITOR_AUTH_CSRF'] = getenv('VERIFY_CSRF');
$_POST = json_decode(base64_decode((string) getenv('VERIFY_POST')), true, flags: JSON_THROW_ON_ERROR);
(new RapidPilotOtiz())->handle((string) getenv('VERIFY_PATH'));
PHP;
    $environment = array_merge($_ENV, [
        'FMONITOR_PROCESS_TABLE_PREFIX' => $prefix,
        'FMONITOR_DB_HOST' => $dbHost,
        'FMONITOR_DB_PORT' => (string) $dbPort,
        'FMONITOR_DB_NAME' => $dbName,
        'FMONITOR_DB_USER' => $dbUser,
        'FMONITOR_DB_PASSWORD' => $dbPassword,
        'VERIFY_PATH' => $path,
        'VERIFY_METHOD' => $method,
        'VERIFY_EMAIL' => $email,
        'VERIFY_USER_ID' => $email === 'viewer.verify@shlz.ru' ? '102' : '101',
        'VERIFY_CSRF' => $csrf,
        'VERIFY_POST' => base64_encode(json_encode($post, JSON_THROW_ON_ERROR)),
    ]);
    $process = proc_open([PHP_BINARY, '-r', $worker], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, getcwd(), $environment);
    if (!is_resource($process)) throw new RuntimeException('Unable to start OTIZ request worker');
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    $status = proc_close($process);
    return ['status' => $status, 'stdout' => $stdout, 'stderr' => $stderr];
};

try {
    $db->query("CREATE TABLE `{$prefix}fm2_pilot_users`(user_id BIGINT UNSIGNED PRIMARY KEY,full_name VARCHAR(300),email VARCHAR(254),status TINYINT,activation_state VARCHAR(40))");
    $db->query("CREATE TABLE `{$prefix}fm2_pilot_roles`(role_id BIGINT UNSIGNED PRIMARY KEY,code VARCHAR(64),name VARCHAR(300),description VARCHAR(500),status TINYINT)");
    $db->query("CREATE TABLE `{$prefix}fm2_pilot_role_permissions`(role_id BIGINT UNSIGNED,permission VARCHAR(100),PRIMARY KEY(role_id,permission))");
    $db->query("CREATE TABLE `{$prefix}fm2_pilot_user_roles`(user_id BIGINT UNSIGNED,role_id BIGINT UNSIGNED,origin VARCHAR(40),assigned_at VARCHAR(40),assigned_by_user_id BIGINT UNSIGNED NULL,PRIMARY KEY(user_id,role_id))");
    $db->query("INSERT INTO `{$prefix}fm2_pilot_users` VALUES(101,'OTIZ Verifier','otiz.verify@shlz.ru',1,'active'),(102,'Unauthorized Verifier','viewer.verify@shlz.ru',1,'active')");
    $db->query("INSERT INTO `{$prefix}fm2_pilot_roles` VALUES(19,'otiz_specialist','Финансовый контролер','OTIZ verifier',1),(2,'user','Пользователь','Read-only verifier',1)");
    $db->query("INSERT INTO `{$prefix}fm2_pilot_role_permissions` VALUES(19,'otiz.manage'),(2,'objects.read')");
    $db->query("INSERT INTO `{$prefix}fm2_pilot_user_roles` VALUES(101,19,'fixture','2026-08-31T00:00:00+03:00',NULL),(102,2,'fixture','2026-08-31T00:00:00+03:00',NULL)");
    $db->query("CREATE TABLE `{$prefix}fm2_installation_cases`(id BIGINT PRIMARY KEY,legacy_installation_object_id BIGINT,process_state VARCHAR(40),actual_start_date DATE)");
    $db->query("CREATE TABLE `{$prefix}fm2_migration_classification_provenance`(output_kind VARCHAR(40),output_id BIGINT,legacy_object_id BIGINT,category VARCHAR(40))");
    $db->query("CREATE TABLE `{$prefix}fm_maintable`(id BIGINT PRIMARY KEY,regnumber VARCHAR(40),ordadr_address VARCHAR(100))");
    $db->query("CREATE TABLE `{$prefix}fm2_assignment_orders`(id BIGINT PRIMARY KEY,installation_case_id BIGINT,status VARCHAR(40),version_no INT,order_date DATE,planned_finish_date_snapshot DATE,pto_act_date_snapshot DATE NULL)");
    $db->query("CREATE TABLE `{$prefix}fm2_order_installers`(assignment_order_id BIGINT,installer_tab_id BIGINT,fio_snapshot VARCHAR(80),position_snapshot VARCHAR(80))");
    $db->query("CREATE TABLE `{$prefix}fm2_checklist_template_snapshots`(id BIGINT PRIMARY KEY,content_sha256 CHAR(64),payload_json LONGTEXT)");
    \FMonitor2\InstallationProcess\InspectionEvidenceSchemaMigration::apply($db,$prefix);
    $db->query("CREATE TABLE `{$prefix}fm2_pilot_object_details`(object_id BIGINT PRIMARY KEY,content_sha256 CHAR(64),payload_json LONGTEXT,captured_at VARCHAR(40))");
    $templateHash = hash('sha256', 'HARNESS-OTIZ-ISOLATION-001 checklist template v0.1');
    $templatePayload = $db->real_escape_string(json_encode(['definitions' => [['id' => 1, 'share' => 50]]], JSON_THROW_ON_ERROR));
    $objectPayload = json_encode(['fields' => ['floors' => ['raw' => '5'], 'weight' => ['raw' => '320'], 'pitmaterial' => ['display' => 'металлокаркас + стекло'], 'lift_type' => ['display' => 'пассажирский']]], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $objectPayloadSql = $db->real_escape_string($objectPayload);
    $objectHash = hash('sha256', $objectPayload);
    $db->query("INSERT INTO `{$prefix}fm2_installation_cases` VALUES(7001,7001,'working','2026-08-01')");
    $db->query("INSERT INTO `{$prefix}fm2_migration_classification_provenance` VALUES('operational_case',7001,7001,'native_candidate')");
    $db->query("INSERT INTO `{$prefix}fm_maintable` VALUES(7001,'OTIZ-VERIFY-7001','Проверочный объект ОТиЗ')");
    $db->query("INSERT INTO `{$prefix}fm2_assignment_orders` VALUES(7101,7001,'registered',1,'2026-08-01','2026-09-30',NULL)");
    $db->query("INSERT INTO `{$prefix}fm2_order_installers` VALUES(7101,7201,'Монтажник Проверочный','Монтажник')");
    $db->query("INSERT INTO `{$prefix}fm2_checklist_template_snapshots` VALUES(7301,'{$templateHash}','{$templatePayload}')");
    $db->query("INSERT INTO `{$prefix}fm2_pilot_object_details` VALUES(7001,'{$objectHash}','{$objectPayloadSql}','2026-08-01T09:00:00+03:00')");
    require_once __DIR__ . '/Otiz.php';
    seedCurrentOtizSchema($db,$prefix);
    RapidPilotOtiz::bootstrap($db, $prefix);

    $unauthorized = $run('/pilot/otiz', email: 'viewer.verify@shlz.ru');
    $expect(str_contains($unauthorized['stdout'], 'Раздел доступен'), 'authorization rejects a user outside OTIZ');
    $csrfFailure = $run('/pilot/otiz/calculate', 'POST', ['csrfToken' => 'wrong', 'reportDate' => '2026-09-15']);
    $expect(str_contains($csrfFailure['stdout'], 'Недопустимый запрос'), 'CSRF rejects a mutating request: '.json_encode($csrfFailure,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    $run('/pilot/otiz/calculate', 'POST', ['csrfToken' => 'verified-csrf-token', 'reportDate' => 'not-a-date']);
    $expect((int) $db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_pilot_otiz_snapshots`")->fetch_assoc()['n'] === 0, 'malformed report date creates no snapshot');

    $run('/pilot/otiz/calculate', 'POST', ['csrfToken' => 'verified-csrf-token', 'reportDate' => '2026-08-31']);
    $blockedId = (int) $db->query("SELECT MAX(id) id FROM `{$prefix}fm2_pilot_otiz_snapshots`")->fetch_assoc()['id'];
    $expect($blockedId > 0, 'calculation creates a deterministic draft');
    $run("/pilot/otiz/snapshots/{$blockedId}/accept", 'POST', ['csrfToken' => 'verified-csrf-token']);
    $blockedStatus = $db->query("SELECT status FROM `{$prefix}fm2_pilot_otiz_snapshots` WHERE id={$blockedId}")->fetch_assoc()['status'];
    $expect($blockedStatus === 'draft', 'open blockers prevent acceptance');

    $db->query("INSERT INTO `{$prefix}fm2_checklist_operations`(installation_case_id,client_operation_id,device_installation_id,operation_type,section_id,item_id,actor_user_id,device_time,server_received_at,base_revision,accepted_revision,payload_json,template_snapshot_id,template_snapshot_version,template_content_sha256) VALUES(7001,'74000000-0000-4000-8000-000000000001','75000000-0000-4000-8000-000000000001','item_completed',1,1,101,'2026-09-01T10:00:00+03:00','2026-09-01T10:01:00+03:00',0,1,'{}',7301,'fixture','{$templateHash}')");
    $db->query("INSERT INTO `{$prefix}fm2_checklist_operation_installers` VALUES('74000000-0000-4000-8000-000000000001',7201,'Монтажник Проверочный','Монтажник','employed',NULL,'2026-09-01','completion')");
    $run('/pilot/otiz/calculate', 'POST', ['csrfToken' => 'verified-csrf-token', 'reportDate' => '2026-09-15']);
    $acceptedId = (int) $db->query("SELECT MAX(id) id FROM `{$prefix}fm2_pilot_otiz_snapshots`")->fetch_assoc()['id'];
    $run("/pilot/otiz/snapshots/{$acceptedId}/accept", 'POST', ['csrfToken' => 'verified-csrf-token']);
    $snapshotBefore = $db->query("SELECT * FROM `{$prefix}fm2_pilot_otiz_snapshots` WHERE id={$acceptedId}")->fetch_assoc();
    $expect($snapshotBefore['status'] === 'accepted', 'blocker-free draft can be accepted');
    $expect($snapshotBefore['rules_version'] === PremiumCalculation::VERSION, 'snapshot persists shared premium calculation version');
    $calculatedInputs = json_decode((string) $db->query("SELECT inputs_json FROM `{$prefix}fm2_pilot_otiz_snapshot_objects` WHERE snapshot_id={$acceptedId} ORDER BY object_id LIMIT 1")->fetch_assoc()['inputs_json'], true, flags: JSON_THROW_ON_ERROR);
    $expect(($calculatedInputs['premiumCalculation']['calculationVersion'] ?? null) === PremiumCalculation::VERSION, 'snapshot object persists exact shared calculation result');
    $expect(count($calculatedInputs['premiumCalculation']['formulaTrace'] ?? []) === 5, 'snapshot object persists exact five-step formula trace');
    $amounts=$calculatedInputs['premiumCalculation']['amounts']??[];$expect(array_key_exists('payoutDiscrepancyCents',$amounts)&&$amounts['payoutDiscrepancyCents']===null, 'absent actual payout produces no discrepancy');
    $expect(($calculatedInputs['premiumCalculation']['paymentEvidence']['actualPayouts'] ?? null) === [], 'current synthetic calculation declares no actual payout evidence');
    $run("/pilot/otiz/snapshots/{$acceptedId}/accept", 'POST', ['csrfToken' => 'verified-csrf-token']);
    $snapshotAfter = $db->query("SELECT * FROM `{$prefix}fm2_pilot_otiz_snapshots` WHERE id={$acceptedId}")->fetch_assoc();
    $expect($snapshotBefore === $snapshotAfter, 'accepted snapshot is immutable on repeated acceptance');
    $allocationMismatch = (int) $db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_pilot_otiz_snapshot_objects` o WHERE o.snapshot_id={$acceptedId} AND o.distributed_cents<>(SELECT COALESCE(SUM(a.amount_cents),0) FROM `{$prefix}fm2_pilot_otiz_snapshot_allocations` a WHERE a.snapshot_id=o.snapshot_id AND a.object_id=o.object_id)")->fetch_assoc()['n'];
    $expect($allocationMismatch === 0, 'deterministic worker allocations reconcile to every distributed object pool');
    $snapshotContent = static function () use ($db, $prefix, $acceptedId): string {
        $content = [];
        foreach (['snapshot_objects' => 'object_id', 'snapshot_allocations' => 'id', 'snapshot_issues' => 'id'] as $suffix => $order) {
            $content[$suffix] = $db->query("SELECT * FROM `{$prefix}fm2_pilot_otiz_{$suffix}` WHERE snapshot_id={$acceptedId} ORDER BY {$order}")->fetch_all(MYSQLI_ASSOC);
        }
        return hash('sha256', serialize($content));
    };
    $acceptedContentBefore = $snapshotContent();

    $object = $db->query("SELECT object_id,pool_cents FROM `{$prefix}fm2_pilot_otiz_snapshot_objects` WHERE snapshot_id={$acceptedId} AND calculation_state<>'blocked' AND pool_cents>20000 ORDER BY object_id LIMIT 1")->fetch_assoc();
    $objectId = (int) $object['object_id'];
    $closurePath = "/pilot/otiz/snapshots/{$acceptedId}/closures";
    $run($closurePath, 'POST', ['csrfToken' => 'verified-csrf-token', 'objectId' => $objectId, 'discipline' => '100.00', 'basis' => 'Verifier discipline hold']);
    $run($closurePath, 'POST', ['csrfToken' => 'verified-csrf-token', 'objectId' => $objectId, 'discipline' => 'oops', 'basis' => 'Malformed money']);
    $closureCount = (int) $db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_pilot_otiz_payment_closures` WHERE snapshot_id={$acceptedId} AND object_id={$objectId}")->fetch_assoc()['n'];
    $expect($closureCount === 1, 'malformed money cannot create a closure');
    $remaining = (int) $object['pool_cents'] - 10000;
    $run($closurePath, 'POST', ['csrfToken' => 'verified-csrf-token', 'objectId' => $objectId, 'discipline' => number_format(($remaining + 1) / 100, 2, '.', ''), 'basis' => 'Over-close attempt']);
    $closureCount = (int) $db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_pilot_otiz_payment_closures` WHERE snapshot_id={$acceptedId} AND object_id={$objectId}")->fetch_assoc()['n'];
    $expect($closureCount === 1, 'cumulative closures cannot exceed the accepted object pool');

    $closureId = (int) $db->query("SELECT id FROM `{$prefix}fm2_pilot_otiz_payment_closures` WHERE snapshot_id={$acceptedId} AND object_id={$objectId} LIMIT 1")->fetch_assoc()['id'];
    $run("/pilot/otiz/closures/{$closureId}/reverse", 'POST', ['csrfToken' => 'verified-csrf-token', 'basis' => 'Verifier reversal']);
    $run("/pilot/otiz/closures/{$closureId}/reverse", 'POST', ['csrfToken' => 'verified-csrf-token', 'basis' => 'Duplicate reversal']);
    $reversalCount = (int) $db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_pilot_otiz_payment_closures` WHERE reverses_payment_closure_id={$closureId}")->fetch_assoc()['n'];
    $expect($reversalCount === 1, 'reversal is append-only and idempotent');

    $run($closurePath, 'POST', ['csrfToken' => 'verified-csrf-token', 'objectId' => $objectId, 'paid' => '999999.00', 'discipline' => '100.00', 'deadline' => '999999.00', 'basis' => 'Discipline hold for next period']);
    $run('/pilot/otiz/calculate', 'POST', ['csrfToken' => 'verified-csrf-token', 'reportDate' => '2026-09-30']);
    $nextId = (int) $db->query("SELECT MAX(id) id FROM `{$prefix}fm2_pilot_otiz_snapshots`")->fetch_assoc()['id'];
    $closedBefore = (int) $db->query("SELECT closed_before_cents FROM `{$prefix}fm2_pilot_otiz_snapshot_objects` WHERE snapshot_id={$nextId} AND object_id={$objectId}")->fetch_assoc()['closed_before_cents'];
    $expect($closedBefore === 10000, 'next calculation subtracts only the recorded discipline hold; spoofed payment and deadline fields are ignored');
    $expect($snapshotContent() === $acceptedContentBefore, 'closures and later calculations do not rewrite accepted snapshot content');

    $xlsx = $run("/pilot/otiz/snapshots/{$acceptedId}/export.xlsx");
    $bytes = $xlsx['stdout']; $offset = 0; $entries = [];
    while (substr($bytes, $offset, 4) === "PK\x03\x04") {
        $header = unpack('vversion/vflags/vmethod/vtime/vdate/Vcrc/Vcompressed/Vsize/vnameLength/vextraLength', substr($bytes, $offset + 4, 26));
        if (!is_array($header) || $header['method'] !== 0 || $header['compressed'] !== $header['size']) break;
        $name = substr($bytes, $offset + 30, $header['nameLength']);
        $dataOffset = $offset + 30 + $header['nameLength'] + $header['extraLength'];
        $entries[$name] = substr($bytes, $dataOffset, $header['size']);
        $offset = $dataOffset + $header['size'];
    }
    $xmlIsValid = static function (string $xml): bool { $document = new DOMDocument(); return @$document->loadXML($xml); };
    $expect(
        str_contains($bytes, "PK\x05\x06")
        && isset($entries['xl/workbook.xml'], $entries['[Content_Types].xml'], $entries['_rels/.rels'])
        && $xmlIsValid($entries['xl/workbook.xml'])
        && $xmlIsValid($entries['[Content_Types].xml']),
        'accepted export is a structurally valid XLSX ZIP package'
    );
} finally {
    foreach ($tables as $table) $db->query("DROP TABLE IF EXISTS `{$prefix}{$table}`");
    $db->close();
}
