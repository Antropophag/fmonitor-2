<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

// Approved seam: a raw GET /pilot/objects. These probes deliberately make all
// downstream CSS/business reads unusable; an admission result must precede them.
function lraPort(): int
{
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    if ($socket === false) throw new TestFailure('allocate route probe port');
    $name = (string) stream_socket_get_name($socket, false);
    fclose($socket);
    return (int) substr($name, strrpos($name, ':') + 1);
}

function lraStart(array $environment): array
{
    $port = lraPort();
    $command = ['/usr/bin/env', '-i'];
    foreach ($environment as $name => $value) $command[] = $name . '=' . $value;
    $command = [...$command, PHP_BINARY, '-d', 'expose_php=0', '-S', '127.0.0.1:' . $port, dirname(__DIR__, 2) . '/public/router.php'];
    $process = proc_open($command, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, dirname(__DIR__, 2));
    if (!is_resource($process)) throw new TestFailure('start real PHP route probe');
    fclose($pipes[0]); stream_set_blocking($pipes[1], false); stream_set_blocking($pipes[2], false);
    $deadline = microtime(true) + 5;
    while (microtime(true) < $deadline) {
        $probe = @stream_socket_client('tcp://127.0.0.1:' . $port, $errno, $error, .1);
        if ($probe !== false) { fclose($probe); return compact('process', 'pipes', 'port'); }
        usleep(50000);
    }
    throw new TestFailure('real PHP route probe did not listen');
}

function lraStop(array $server): string
{
    if (proc_get_status($server['process'])['running']) proc_terminate($server['process']);
    $output = '';
    foreach ([1, 2] as $fd) { $output .= stream_get_contents($server['pipes'][$fd]); fclose($server['pipes'][$fd]); }
    proc_close($server['process']);
    return $output;
}

function lraGet(int $port, string $target, array $requestHeaders = []): array
{
    $socket = stream_socket_client('tcp://127.0.0.1:' . $port, $errno, $error, 5);
    if ($socket === false) throw new TestFailure('connect real GET route probe');
    $request = "GET {$target} HTTP/1.1\r\nHost: local-rbac.example\r\nConnection: close\r\n";
    foreach ($requestHeaders as $name => $value) $request .= $name . ': ' . $value . "\r\n";
    fwrite($socket, $request . "\r\n");
    stream_socket_shutdown($socket, STREAM_SHUT_WR);
    $raw = stream_get_contents($socket); fclose($socket);
    [$head, $body] = array_pad(explode("\r\n\r\n", $raw, 2), 2, '');
    preg_match('/^HTTP\/\d\.\d (\d{3})/', $head, $match); $headers=[];
    foreach (array_slice(explode("\r\n", $head), 1) as $line) if (str_contains($line, ':')) { [$name,$value]=explode(':',$line,2); $headers[strtolower(trim($name))]=trim($value); }
    return ['status'=>(int)($match[1]??0), 'headers'=>$headers, 'body'=>$body];
}

$token = bin2hex(random_bytes(5));
$database = 't_lra_' . $token;
$reader = 'lra_' . $token;
$authReader = 'lra_auth_' . $token;
$password = 'read-' . $token;
$dbHost = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$dbPort = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$admin = @new mysqli($dbHost, getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root', getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local', null, $dbPort);
if ($admin->connect_errno !== 0) throw new TestFailure('SETUP_FAILURE: test MariaDB unavailable; run make test-env-up');
$admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4");
$admin->query("CREATE USER `{$reader}`@`%` IDENTIFIED BY '{$password}'");
$admin->query("CREATE USER `{$authReader}`@`%` IDENTIFIED BY '{$password}'");
$db = new mysqli($dbHost, getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root', getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local', $database, $dbPort);
$db->query("CREATE TABLE fm2_pilot_users(user_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,full_name VARCHAR(300) NOT NULL,email VARCHAR(254) NOT NULL,phone VARCHAR(100) NOT NULL DEFAULT '',status TINYINT(1) NOT NULL DEFAULT 1,activation_state ENUM('invited','active','blocked') NOT NULL,session_version INT UNSIGNED NOT NULL DEFAULT 1,source_updated_at VARCHAR(40) NOT NULL,PRIMARY KEY(user_id),UNIQUE KEY uq_ia_users_email(email),KEY ix_ia_users_status_name(status,full_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci");
$db->query("CREATE TABLE fm2_pilot_roles(role_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,code VARCHAR(64) NOT NULL,name VARCHAR(300) NOT NULL,description VARCHAR(500) NOT NULL,status TINYINT(1) NOT NULL,source_updated_at VARCHAR(40) NOT NULL,PRIMARY KEY(role_id),UNIQUE KEY uq_ia_roles_code(code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci");
$db->query("CREATE TABLE fm2_pilot_role_permissions(role_id BIGINT UNSIGNED NOT NULL,permission VARCHAR(100) NOT NULL,PRIMARY KEY(role_id,permission),CONSTRAINT fk_ia_role_permissions_role FOREIGN KEY(role_id) REFERENCES fm2_pilot_roles(role_id) ON DELETE CASCADE ON UPDATE RESTRICT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci");
$db->query("CREATE TABLE fm2_pilot_user_roles(user_id BIGINT UNSIGNED NOT NULL,role_id BIGINT UNSIGNED NOT NULL,origin VARCHAR(40) NOT NULL,assigned_at VARCHAR(40) NOT NULL,assigned_by_user_id BIGINT UNSIGNED NULL DEFAULT NULL,PRIMARY KEY(user_id,role_id),KEY ix_ia_user_roles_role(role_id),CONSTRAINT fk_ia_user_roles_user FOREIGN KEY(user_id) REFERENCES fm2_pilot_users(user_id) ON DELETE CASCADE ON UPDATE RESTRICT,CONSTRAINT fk_ia_user_roles_role FOREIGN KEY(role_id) REFERENCES fm2_pilot_roles(role_id) ON DELETE RESTRICT ON UPDATE RESTRICT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci");
$db->query("CREATE TABLE fm2_installation_cases(id BIGINT UNSIGNED PRIMARY KEY,legacy_installation_object_id BIGINT UNSIGNED NOT NULL UNIQUE,process_state VARCHAR(80) NOT NULL,actual_start_date DATE NULL,opened_at VARCHAR(40) NULL,opened_by_user_id BIGINT UNSIGNED NULL,created_at VARCHAR(40) NOT NULL,updated_at VARCHAR(40) NOT NULL,lock_version INT UNSIGNED NOT NULL) ENGINE=InnoDB");
$db->query("CREATE TABLE legacy_fm_maintable(id BIGINT UNSIGNED PRIMARY KEY,ordadr_address VARCHAR(500),entrance VARCHAR(80),regnumber VARCHAR(120),workdatestart VARCHAR(40),workdateendadjusted VARCHAR(40),plan_finish_date VARCHAR(40)) ENGINE=InnoDB");
$db->query("INSERT INTO fm2_pilot_users VALUES(7301,'Тестовый пользователь','rbac@example.test','',1,'active',1,'2026-09-02T10:00:00+03:00')");
$db->query("INSERT INTO fm2_pilot_roles VALUES(701,'preparer','Подготовка распоряжений','Fixture',1,'2026-09-02T10:00:00+03:00'),(702,'objects_reader','Читатель объектов','Fixture',1,'2026-09-02T10:00:00+03:00')");
$db->query("INSERT INTO fm2_pilot_user_roles VALUES(7301,701,'fixture','2026-09-02T10:00:00+03:00',NULL),(7301,702,'fixture','2026-09-02T10:00:00+03:00',NULL)");
$db->query("INSERT INTO fm2_installation_cases VALUES(4512,4512,'working',NULL,NULL,NULL,'2026-09-02T10:00:00+03:00','2026-09-02T10:00:00+03:00',1)");
$db->query("INSERT INTO legacy_fm_maintable VALUES(4512,'Москва, тестовый адрес','1','77-4512','2026-09-01','2026-12-01',NULL)");
$admin->query("GRANT SELECT ON `{$database}`.* TO `{$reader}`@`%`");
foreach (['fm2_pilot_users','fm2_pilot_roles','fm2_pilot_role_permissions','fm2_pilot_user_roles'] as $authTable) {
    $admin->query("GRANT SELECT ON `{$database}`.`{$authTable}` TO `{$authReader}`@`%`");
}
register_shutdown_function(static function() use ($admin, $database, $reader, $authReader, $db): void {
    try { $db->close(); } catch (Throwable) {}
    try { $admin->query("DROP DATABASE IF EXISTS `{$database}`"); } catch (Throwable) {}
    try { $admin->query("DROP USER IF EXISTS `{$reader}`@`%`"); } catch (Throwable) {}
    try { $admin->query("DROP USER IF EXISTS `{$authReader}`@`%`"); } catch (Throwable) {}
    try { $admin->close(); } catch (Throwable) {}
});

$base = [
    'FMONITOR_AUTH_USER_ID' => '7301',
    'FMONITOR_SHLZ_CSS_PATH' => dirname(__DIR__, 2) . '/rapid-pilot/pilot.css',
    'FMONITOR_DB_HOST' => $dbHost, 'FMONITOR_DB_PORT' => (string) $dbPort,
    'FMONITOR_DB_NAME' => $database, 'FMONITOR_DB_USER' => $reader,
    'FMONITOR_DB_PASSWORD' => $password, 'FMONITOR_LEGACY_TABLE_PREFIX' => 'legacy_',
    'FMONITOR_PROCESS_TABLE_PREFIX' => '',
];
$authOnly = $base;
$authOnly['FMONITOR_DB_USER'] = $authReader;

// A real positive route invocation prevents an always-deny implementation from
// satisfying all negative examples and proves the protected handler runs for
// the canonical exact grant.
$db->query("INSERT INTO fm2_pilot_role_permissions VALUES(701,'assignment_order.prepare'),(702,'objects.read')");
$server = lraStart($base);
try {
    $allowed = lraGet($server['port'], '/pilot/objects?requiredPermission=assignment_order.prepare&permission=assignment_order.prepare');
    assertSameValue(200, $allowed['status'], 'exact objects.read grant admits real objects handler despite client-selected alternatives');
    assertSameValue(true, str_contains($allowed['body'], '77-4512'), 'successful handler returns canonical object projection');
} finally { lraStop($server); }

// The grant is deliberately carried only by the second active assigned role.
// Deactivating that carrier proves both union and active-role semantics through
// the real MariaDB-backed route.
$db->query('UPDATE fm2_pilot_roles SET status=0 WHERE role_id=702');
$server = lraStart($authOnly);
try { assertSameValue(403, lraGet($server['port'], '/pilot/objects')['status'], 'deactivated second-role carrier denies before inaccessible object read'); }
finally { lraStop($server); }
$db->query('UPDATE fm2_pilot_roles SET status=1 WHERE role_id=702');

// Wrong and near-match grants must not pass; these cases also make a fixed
// wrong permission mapping and case/space/wildcard normalization observable.
foreach (['assignment_order.prepare','Objects.Read','objects.read ','objects.*'] as $wrongGrant) {
    $db->query('DELETE FROM fm2_pilot_role_permissions');
    $escaped = $db->real_escape_string($wrongGrant);
    $db->query("INSERT INTO fm2_pilot_role_permissions VALUES(701,'assignment_order.prepare'),(702,'{$escaped}')");
    $server = lraStart($authOnly);
    try {
        $denied = lraGet($server['port'], '/pilot/objects?requiredPermission=' . rawurlencode($wrongGrant));
        assertSameValue(403, $denied['status'], 'objects route denies non-exact grant '.$wrongGrant);
        assertSameValue("Access denied.\n", $denied['body'], 'generic exact-grant denial body');
    } finally { lraStop($server); }
}

$db->query('DELETE FROM fm2_pilot_role_permissions');
$db->query("INSERT INTO fm2_pilot_role_permissions VALUES(701,'assignment_order.prepare'),(702,'objects.read')");
$db->query('UPDATE fm2_pilot_roles SET status=0 WHERE role_id=702');
$server = lraStart($authOnly);
try { assertSameValue(403, lraGet($server['port'], '/pilot/objects')['status'], 'inactive role cannot contribute exact grant'); }
finally { lraStop($server); }
$db->query('UPDATE fm2_pilot_roles SET status=1 WHERE role_id=702');

// Both non-active activation states and an inactive user deny at the route
// seam. The DB principal cannot read object tables, so a 403 also proves the
// denial returned before the protected read model was touched.
foreach (['invited', 'blocked'] as $activationState) {
    $db->query("UPDATE fm2_pilot_users SET activation_state='{$activationState}' WHERE user_id=7301");
    $server = lraStart($authOnly);
    try { assertSameValue(403, lraGet($server['port'], '/pilot/objects')['status'], $activationState.' actor denied before inaccessible object read'); }
    finally { lraStop($server); }
}
$db->query("UPDATE fm2_pilot_users SET activation_state='active',status=0 WHERE user_id=7301");
$server = lraStart($authOnly);
try { assertSameValue(403, lraGet($server['port'], '/pilot/objects')['status'], 'inactive user denied before inaccessible object read'); }
finally { lraStop($server); }
$db->query('UPDATE fm2_pilot_users SET status=1 WHERE user_id=7301');

// Every HTTP invocation re-reads committed RBAC state.
$server = lraStart($base);
try { assertSameValue(200, lraGet($server['port'], '/pilot/objects')['status'], 'grant is effective before committed revoke'); }
finally { lraStop($server); }
$db->query('DELETE FROM fm2_pilot_role_permissions');
$server = lraStart($base);
try { assertSameValue(403, lraGet($server['port'], '/pilot/objects')['status'], 'new invocation denies after committed revoke'); }
finally { lraStop($server); }

// Missing trusted local actor is 401, irrespective of client-selected values
// and before every protected read.
$server = lraStart(array_diff_key($base, ['FMONITOR_AUTH_USER_ID'=>true]));
try {
    $unauthenticated = lraGet($server['port'], '/pilot/objects?requiredPermission=objects.read');
    assertSameValue(401, $unauthenticated['status'], 'missing trusted local actor is 401 before handler reads');
    assertSameValue("Authentication required.\n", $unauthenticated['body'], 'generic authentication body');
} finally { lraStop($server); }

// Every client-controlled identity/permission channel is hostile input. Even a
// valid local ID and exact permission in query, cookies, headers and REMOTE_USER
// cannot replace a missing or wrong authenticated local-session identity.
$hostileHeaders = [
    'Cookie' => 'actorUserId=7301; requiredPermission=objects.read',
    'X-FMonitor-Auth-User-Id' => '7301',
    'X-Required-Permission' => 'objects.read',
    'Remote-User' => 'rbac@example.test',
];
$hostileTarget = '/pilot/objects?actorUserId=7301&authenticatedLocalUserId=7301&requiredPermission=objects.read&permission=objects.read';
$missingTrustedActor = $authOnly;
unset($missingTrustedActor['FMONITOR_AUTH_USER_ID']);
$missingTrustedActor['REMOTE_USER'] = 'rbac@example.test';
$server = lraStart($missingTrustedActor);
try { assertSameValue(401, lraGet($server['port'], $hostileTarget, $hostileHeaders)['status'], 'hostile client identity and permission cannot replace missing local-session actor'); }
finally { lraStop($server); }
$wrongTrustedActor = $authOnly;
$wrongTrustedActor['FMONITOR_AUTH_USER_ID'] = '9999';
$wrongTrustedActor['REMOTE_USER'] = 'rbac@example.test';
$server = lraStart($wrongTrustedActor);
try { assertSameValue(403, lraGet($server['port'], $hostileTarget, $hostileHeaders)['status'], 'hostile client identity and permission cannot replace wrong local-session actor'); }
finally { lraStop($server); }

function lraUnavailable(array $environment, string $expectedCategory, string $label): void
{
    global $database, $reader, $authReader, $password;
    assertSameValue(
        true,
        in_array($expectedCategory, ['AUTHORIZATION_CONFIGURATION_INVALID', 'AUTHORIZATION_SCHEMA_INVALID', 'AUTHORIZATION_READ_FAILED'], true),
        $label.' expected category belongs to the closed safe allowlist'
    );
    $server = lraStart($environment);
    try {
        $response = lraGet($server['port'], '/pilot/objects');
        assertSameValue(503, $response['status'], $label.' is generic 503');
        assertSameValue("Service unavailable.\n", $response['body'], $label.' generic body');
        $correlation = $response['headers']['x-correlation-id'] ?? '';
        assertSameValue(1, preg_match('/^[0-9a-f]{12}$/D', $correlation), $label.' opaque 12-hex correlation');
    } finally { $log = lraStop($server); }
    preg_match_all(
        '/^FMONITOR_AUTHORIZATION_UNAVAILABLE category=([A-Z_]+) correlation_id=([0-9a-f]{12})$/m',
        str_replace("\r", '', $log),
        $events,
        PREG_SET_ORDER
    );
    assertSameValue(1, count($events), $label.' emits exactly one strict unavailable event');
    assertSameValue($expectedCategory, $events[0][1] ?? null, $label.' emits exactly the expected safe category');
    assertSameValue($correlation, $events[0][2] ?? null, $label.' external/internal correlation agrees');

    preg_match_all('/\bAUTHORIZATION_[A-Z_]+\b/', $log, $categoryTokens);
    assertSameValue([$expectedCategory], $categoryTokens[0] ?? [], $label.' emits no extra authorization categories');

    $forbiddenLiterals = [
        'Тестовый пользователь', 'Подготовка распоряжений', 'Читатель объектов',
        'preparer', 'objects_reader', 'Fixture', 'rbac@example.test',
        'a@example.test', 'b@example.test', 'objects.read', 'assignment_order.prepare',
        'fm2_pilot_users', 'fm2_pilot_roles', 'fm2_pilot_role_permissions',
        'fm2_pilot_user_roles', 'fm2_installation_cases', 'legacy_fm_maintable',
        $database ?? '', $reader ?? '', $authReader ?? '',
        $password ?? '', 'FMONITOR_DB_', 'FMONITOR_PROCESS_TABLE_PREFIX',
        'fmonitor2_test_root_local', 'invalid-prefix!', 'legacy_',
    ];
    foreach (array_filter($forbiddenLiterals, static fn(string $value): bool => $value !== '') as $secret) {
        assertSameValue(false, str_contains($log, $secret), $label.' log redacts '.$secret);
    }

    // Closed, test-owned transcription of every identifier belonging to the
    // four authoritative RBAC tables in approved IDENTITY-ACCESS-SCHEMA-001
    // v6 sections 4.1-4.4. This oracle must not come from GREEN code or from
    // information_schema: an exception may disclose a column/key while still
    // avoiding the table literals checked above.
    $canonicalRbacIdentifiers = [
        'fm2_pilot_users',
        'user_id', 'full_name', 'email', 'phone', 'status',
        'activation_state', 'session_version', 'source_updated_at',
        'PRIMARY', 'uq_ia_users_email', 'ix_ia_users_status_name',
        'fm2_pilot_roles',
        'role_id', 'code', 'name', 'description',
        'uq_ia_roles_code',
        'fm2_pilot_role_permissions',
        'permission', 'fk_ia_role_permissions_role',
        'fm2_pilot_user_roles',
        'origin', 'assigned_at', 'assigned_by_user_id',
        'ix_ia_user_roles_role', 'fk_ia_user_roles_user',
        'fk_ia_user_roles_role',
    ];
    foreach (array_values(array_unique($canonicalRbacIdentifiers)) as $identifier) {
        assertSameValue(
            0,
            preg_match('/(?<![A-Za-z0-9_])'.preg_quote($identifier, '/').'(?![A-Za-z0-9_])/i', $log),
            $label.' log redacts canonical RBAC identifier '.$identifier
        );
    }

    // Closed diagnostic fragments cover MariaDB/catalog wording capable of
    // revealing which canonical identifier failed even without logging SQL.
    $forbiddenSchemaErrorFragments = [
        'information_schema', 'unknown column', 'unknown key',
        'unknown constraint', 'undefined column', 'invalid column',
        'duplicate column', 'duplicate key', 'no such table',
        'base table or view not found', "doesn't exist", 'does not exist',
        'foreign key constraint', 'constraint fails', 'key column',
        'TABLES', 'COLUMNS', 'STATISTICS', 'TABLE_CONSTRAINTS',
        'KEY_COLUMN_USAGE', 'REFERENTIAL_CONSTRAINTS',
    ];
    foreach ($forbiddenSchemaErrorFragments as $fragment) {
        assertSameValue(
            false,
            str_contains(strtolower($log), strtolower($fragment)),
            $label.' log redacts schema/error fragment '.$fragment
        );
    }
    assertSameValue(0, preg_match('/(?<!\d)(?:7301|701|702)(?!\d)/', $log), $label.' log redacts user and role identifiers');
    assertSameValue(0, preg_match('/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/i', $log), $label.' log contains no email value');
    assertSameValue(
        0,
        preg_match('/\b(?:SELECT|INSERT|UPDATE|DELETE|REPLACE|CREATE|ALTER|DROP|TRUNCATE|GRANT|REVOKE|CALL|EXECUTE|WITH)\b/i', $log),
        $label.' log contains no SQL verb or statement'
    );
}

// Missing canonical authorization schema is a schema fault, not a transient
// read failure.
$db->query('DROP TABLE fm2_pilot_role_permissions');
lraUnavailable($base, 'AUTHORIZATION_SCHEMA_INVALID', 'missing role-permissions schema');
$db->query("CREATE TABLE fm2_pilot_role_permissions(role_id BIGINT UNSIGNED NOT NULL,permission VARCHAR(100) NOT NULL,PRIMARY KEY(role_id,permission),CONSTRAINT fk_ia_role_permissions_role FOREIGN KEY(role_id) REFERENCES fm2_pilot_roles(role_id) ON DELETE CASCADE ON UPDATE RESTRICT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci");
$db->query("INSERT INTO fm2_pilot_role_permissions VALUES(702,'objects.read')");

// Invalid trusted composition is separately classified before authorization IO.
$invalidConfiguration = $base;
$invalidConfiguration['FMONITOR_PROCESS_TABLE_PREFIX'] = 'invalid-prefix!';
lraUnavailable($invalidConfiguration, 'AUTHORIZATION_CONFIGURATION_INVALID', 'invalid authorization configuration');

// A healthy canonical schema with denied SELECT is an operational read fault,
// distinct from a missing/incompatible schema.
$admin->query("REVOKE SELECT ON `{$database}`.* FROM `{$reader}`@`%`");
lraUnavailable($base, 'AUTHORIZATION_READ_FAILED', 'authorization DB read failure');
$admin->query("GRANT SELECT ON `{$database}`.* TO `{$reader}`@`%`");

// A canonical-looking identity source that can return two rows for one actor is
// incompatible/ambiguous schema, never a denial or arbitrary selected identity.
$db->query('SET FOREIGN_KEY_CHECKS=0');
$db->query('DROP TABLE fm2_pilot_user_roles');
$db->query('DROP TABLE fm2_pilot_users');
$db->query("CREATE TABLE fm2_pilot_users(user_id BIGINT UNSIGNED NOT NULL,full_name VARCHAR(300) NOT NULL,email VARCHAR(254) NOT NULL,phone VARCHAR(100) NOT NULL,status TINYINT NOT NULL,activation_state VARCHAR(32) NOT NULL,session_version INT UNSIGNED NOT NULL,source_updated_at VARCHAR(40) NOT NULL) ENGINE=InnoDB");
$db->query("CREATE TABLE fm2_pilot_user_roles(user_id BIGINT UNSIGNED NOT NULL,role_id BIGINT UNSIGNED NOT NULL,origin VARCHAR(40) NOT NULL,assigned_at VARCHAR(40) NOT NULL,assigned_by_user_id BIGINT UNSIGNED NULL) ENGINE=InnoDB");
$db->query("INSERT INTO fm2_pilot_users VALUES(7301,'A','a@example.test','',1,'active',1,'x'),(7301,'B','b@example.test','',1,'active',1,'x')");
$db->query("INSERT INTO fm2_pilot_user_roles VALUES(7301,701,'fixture','x',NULL)");
$db->query('SET FOREIGN_KEY_CHECKS=1');
lraUnavailable($base, 'AUTHORIZATION_SCHEMA_INVALID', 'ambiguous canonical identity');

echo "PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission\n";
