<?php

declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$prefix = 'otiz_verify_' . bin2hex(random_bytes(5)) . '_';
$dbHost = getenv('FMONITOR_VERIFY_DB_HOST') ?: 'mariadb';
$dbPort = (int) (getenv('FMONITOR_VERIFY_DB_PORT') ?: '3306');
$db = new mysqli($dbHost, 'fmonitor2_demo', 'fmonitor2_demo_local', 'fmonitor2_demo', $dbPort);
$db->set_charset('utf8mb4');
$tables = [
    'fm2_pilot_otiz_events',
    'fm2_pilot_otiz_payment_closures',
    'fm2_pilot_otiz_snapshot_issues',
    'fm2_pilot_otiz_snapshot_allocations',
    'fm2_pilot_otiz_snapshot_objects',
    'fm2_pilot_otiz_snapshots',
    'fm2_pilot_user_roles',
    'fm2_pilot_roles',
    'fm2_pilot_users',
];

$expect = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
    echo "ok - {$message}\n";
};

$run = static function (string $path, string $method = 'GET', array $post = [], string $email = 'otiz.verify@shlz.ru', string $csrf = 'verified-csrf-token') use ($prefix, $dbHost, $dbPort): array {
    $worker = <<<'PHP'
require getcwd() . '/rapid-pilot/Otiz.php';
$_SERVER['REQUEST_METHOD'] = getenv('VERIFY_METHOD');
$_SERVER['REMOTE_USER'] = getenv('VERIFY_EMAIL');
$_SERVER['FMONITOR_AUTH_CSRF'] = getenv('VERIFY_CSRF');
$_POST = json_decode(base64_decode((string) getenv('VERIFY_POST')), true, flags: JSON_THROW_ON_ERROR);
(new RapidPilotOtiz())->handle((string) getenv('VERIFY_PATH'));
PHP;
    $environment = array_merge($_ENV, [
        'FMONITOR_PROCESS_TABLE_PREFIX' => $prefix,
        'FMONITOR_DB_HOST' => $dbHost,
        'FMONITOR_DB_PORT' => (string) $dbPort,
        'FMONITOR_DB_NAME' => 'fmonitor2_demo',
        'FMONITOR_DB_USER' => 'fmonitor2_demo',
        'FMONITOR_DB_PASSWORD' => 'fmonitor2_demo_local',
        'VERIFY_PATH' => $path,
        'VERIFY_METHOD' => $method,
        'VERIFY_EMAIL' => $email,
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
    $db->query("CREATE TABLE `{$prefix}fm2_pilot_users`(user_id BIGINT UNSIGNED PRIMARY KEY,full_name VARCHAR(300),email VARCHAR(254),status TINYINT)");
    $db->query("CREATE TABLE `{$prefix}fm2_pilot_roles`(role_id BIGINT UNSIGNED PRIMARY KEY,name VARCHAR(300),status TINYINT)");
    $db->query("CREATE TABLE `{$prefix}fm2_pilot_user_roles`(user_id BIGINT UNSIGNED,role_id BIGINT UNSIGNED,PRIMARY KEY(user_id,role_id))");
    $db->query("INSERT INTO `{$prefix}fm2_pilot_users` VALUES(101,'OTIZ Verifier','otiz.verify@shlz.ru',1),(102,'Unauthorized Verifier','viewer.verify@shlz.ru',1)");
    $db->query("INSERT INTO `{$prefix}fm2_pilot_roles` VALUES(19,'Финансовый контролер',1),(2,'Пользователь',1)");
    $db->query("INSERT INTO `{$prefix}fm2_pilot_user_roles` VALUES(101,19),(102,2)");
    require_once __DIR__ . '/Otiz.php';
    RapidPilotOtiz::bootstrap($db, $prefix);

    $unauthorized = $run('/pilot/otiz', email: 'viewer.verify@shlz.ru');
    $expect(str_contains($unauthorized['stdout'], 'Раздел доступен'), 'authorization rejects a user outside OTIZ');
    $csrfFailure = $run('/pilot/otiz/calculate', 'POST', ['csrfToken' => 'wrong', 'reportDate' => '2026-09-15']);
    $expect(str_contains($csrfFailure['stdout'], 'Недопустимый запрос'), 'CSRF rejects a mutating request');
    $run('/pilot/otiz/calculate', 'POST', ['csrfToken' => 'verified-csrf-token', 'reportDate' => 'not-a-date']);
    $expect((int) $db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_pilot_otiz_snapshots`")->fetch_assoc()['n'] === 0, 'malformed report date creates no snapshot');

    $run('/pilot/otiz/calculate', 'POST', ['csrfToken' => 'verified-csrf-token', 'reportDate' => '2026-08-31']);
    $blockedId = (int) $db->query("SELECT MAX(id) id FROM `{$prefix}fm2_pilot_otiz_snapshots`")->fetch_assoc()['id'];
    $expect($blockedId > 0, 'calculation creates a deterministic draft');
    $run("/pilot/otiz/snapshots/{$blockedId}/accept", 'POST', ['csrfToken' => 'verified-csrf-token']);
    $blockedStatus = $db->query("SELECT status FROM `{$prefix}fm2_pilot_otiz_snapshots` WHERE id={$blockedId}")->fetch_assoc()['status'];
    $expect($blockedStatus === 'draft', 'open blockers prevent acceptance');

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
    $run($closurePath, 'POST', ['csrfToken' => 'verified-csrf-token', 'objectId' => $objectId, 'paid' => '100.00', 'discipline' => '0', 'deadline' => '0', 'basis' => 'Verifier payment']);
    $run($closurePath, 'POST', ['csrfToken' => 'verified-csrf-token', 'objectId' => $objectId, 'paid' => 'oops', 'discipline' => '1.01', 'deadline' => '0', 'basis' => 'Malformed money']);
    $closureCount = (int) $db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_pilot_otiz_payment_closures` WHERE snapshot_id={$acceptedId} AND object_id={$objectId}")->fetch_assoc()['n'];
    $expect($closureCount === 1, 'malformed money cannot create a closure');
    $remaining = (int) $object['pool_cents'] - 10000;
    $run($closurePath, 'POST', ['csrfToken' => 'verified-csrf-token', 'objectId' => $objectId, 'paid' => number_format(($remaining + 1) / 100, 2, '.', ''), 'discipline' => '0', 'deadline' => '0', 'basis' => 'Over-close attempt']);
    $closureCount = (int) $db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_pilot_otiz_payment_closures` WHERE snapshot_id={$acceptedId} AND object_id={$objectId}")->fetch_assoc()['n'];
    $expect($closureCount === 1, 'cumulative closures cannot exceed the accepted object pool');

    $closureId = (int) $db->query("SELECT id FROM `{$prefix}fm2_pilot_otiz_payment_closures` WHERE snapshot_id={$acceptedId} AND object_id={$objectId} LIMIT 1")->fetch_assoc()['id'];
    $run("/pilot/otiz/closures/{$closureId}/reverse", 'POST', ['csrfToken' => 'verified-csrf-token', 'basis' => 'Verifier reversal']);
    $run("/pilot/otiz/closures/{$closureId}/reverse", 'POST', ['csrfToken' => 'verified-csrf-token', 'basis' => 'Duplicate reversal']);
    $reversalCount = (int) $db->query("SELECT COUNT(*) n FROM `{$prefix}fm2_pilot_otiz_payment_closures` WHERE reverses_payment_closure_id={$closureId}")->fetch_assoc()['n'];
    $expect($reversalCount === 1, 'reversal is append-only and idempotent');

    $run($closurePath, 'POST', ['csrfToken' => 'verified-csrf-token', 'objectId' => $objectId, 'paid' => '75.00', 'discipline' => '25.00', 'deadline' => '0', 'basis' => 'Closed for next period']);
    $run('/pilot/otiz/calculate', 'POST', ['csrfToken' => 'verified-csrf-token', 'reportDate' => '2026-09-30']);
    $nextId = (int) $db->query("SELECT MAX(id) id FROM `{$prefix}fm2_pilot_otiz_snapshots`")->fetch_assoc()['id'];
    $closedBefore = (int) $db->query("SELECT closed_before_cents FROM `{$prefix}fm2_pilot_otiz_snapshot_objects` WHERE snapshot_id={$nextId} AND object_id={$objectId}")->fetch_assoc()['closed_before_cents'];
    $expect($closedBefore === 10000, 'next calculation subtracts net closed payments and holds');
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
