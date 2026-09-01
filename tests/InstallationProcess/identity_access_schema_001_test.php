<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/identity_access_schema_001_green_application_contract.php';

// Specification: IDENTITY-ACCESS-SCHEMA-001 v0.1.
// Public seam: php bin/fmonitor2-migrate.php.

function iaDb(?string $database = null): mysqli
{
    $db = new mysqli(
        getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local',
        $database,
        (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306),
    );
    $db->set_charset('utf8mb4');
    return $db;
}

/** @return array{exitCode:int,stdout:string,stderr:string} */
function iaRun(string $database, string $prefix, ?string $host = null): array
{
    $environment = [
        'FMONITOR_DB_HOST' => $host ?? (getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1'),
        'FMONITOR_DB_PORT' => getenv('FMONITOR_TEST_DB_PORT') ?: '23306',
        'FMONITOR_DB_NAME' => $database,
        'FMONITOR_DB_USER' => getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        'FMONITOR_DB_PASSWORD' => getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local',
        'FMONITOR_PROCESS_TABLE_PREFIX' => $prefix,
    ];
    $command = ['/usr/bin/env', '-i'];
    foreach ($environment as $name => $value) {
        $command[] = $name . '=' . $value;
    }
    $command[] = PHP_BINARY;
    $command[] = dirname(__DIR__, 2) . '/bin/fmonitor2-migrate.php';
    $pipes = [];
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2));
    if (!is_resource($process)) {
        throw new TestFailure('Canonical runner process must start.');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['exitCode' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
}

/** @return list<string> */
function iaNames(string $prefix): array
{
    return array_map(static fn (string $name): string => $prefix . $name, [
        'fm2_pilot_users',
        'fm2_pilot_roles',
        'fm2_pilot_role_permissions',
        'fm2_pilot_user_roles',
        'fm2_pilot_auth_credentials',
        'fm2_pilot_invitations',
        'fm2_pilot_user_role_events',
        'fm2_pilot_auth_attempts',
        'fm2_pilot_user_status_events',
    ]);
}

/** Test-owned literals transcribed from IDENTITY-ACCESS-SCHEMA-001 section 4. */
function iaLiteralDdls(string $prefix, string $collation): array
{
    $fk = static fn (string $empty, string $short): string => $prefix === '' ? $empty : 'fk_' . $prefix . $short;
    $tail = " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE `{$collation}`";
    return [
        "CREATE TABLE `{$prefix}fm2_pilot_users`(user_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,full_name VARCHAR(300) NOT NULL,email VARCHAR(254) NOT NULL,phone VARCHAR(100) NOT NULL DEFAULT '',status TINYINT(1) NOT NULL DEFAULT 1,activation_state ENUM('invited','active','blocked') NOT NULL,session_version INT UNSIGNED NOT NULL DEFAULT 1,source_updated_at VARCHAR(40) NOT NULL,PRIMARY KEY(user_id),UNIQUE KEY uq_ia_users_email(email),KEY ix_ia_users_status_name(status,full_name)){$tail}",
        "CREATE TABLE `{$prefix}fm2_pilot_roles`(role_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,code VARCHAR(64) NOT NULL,name VARCHAR(300) NOT NULL,description VARCHAR(500) NOT NULL,status TINYINT(1) NOT NULL,source_updated_at VARCHAR(40) NOT NULL,PRIMARY KEY(role_id),UNIQUE KEY uq_ia_roles_code(code)){$tail}",
        "CREATE TABLE `{$prefix}fm2_pilot_role_permissions`(role_id BIGINT UNSIGNED NOT NULL,permission VARCHAR(100) NOT NULL,PRIMARY KEY(role_id,permission),CONSTRAINT `{$fk('fk_ia_role_permissions_role','ia_rp_role')}` FOREIGN KEY(role_id) REFERENCES `{$prefix}fm2_pilot_roles`(role_id) ON DELETE CASCADE ON UPDATE RESTRICT){$tail}",
        "CREATE TABLE `{$prefix}fm2_pilot_user_roles`(user_id BIGINT UNSIGNED NOT NULL,role_id BIGINT UNSIGNED NOT NULL,origin VARCHAR(40) NOT NULL,assigned_at VARCHAR(40) NOT NULL,assigned_by_user_id BIGINT UNSIGNED NULL DEFAULT NULL,PRIMARY KEY(user_id,role_id),KEY ix_ia_user_roles_role(role_id),CONSTRAINT `{$fk('fk_ia_user_roles_user','ia_ur_user')}` FOREIGN KEY(user_id) REFERENCES `{$prefix}fm2_pilot_users`(user_id) ON DELETE CASCADE ON UPDATE RESTRICT,CONSTRAINT `{$fk('fk_ia_user_roles_role','ia_ur_role')}` FOREIGN KEY(role_id) REFERENCES `{$prefix}fm2_pilot_roles`(role_id) ON DELETE RESTRICT ON UPDATE RESTRICT){$tail}",
        "CREATE TABLE `{$prefix}fm2_pilot_auth_credentials`(user_id BIGINT UNSIGNED NOT NULL,email_normalized VARCHAR(254) NOT NULL,password_hash VARCHAR(255) NULL DEFAULT NULL,password_set_at VARCHAR(40) NULL DEFAULT NULL,updated_at VARCHAR(40) NOT NULL,PRIMARY KEY(user_id),UNIQUE KEY uq_ia_auth_credentials_email(email_normalized),CONSTRAINT `{$fk('fk_ia_auth_credentials_user','ia_ac_user')}` FOREIGN KEY(user_id) REFERENCES `{$prefix}fm2_pilot_users`(user_id) ON DELETE CASCADE ON UPDATE RESTRICT){$tail}",
        "CREATE TABLE `{$prefix}fm2_pilot_invitations`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,user_id BIGINT UNSIGNED NOT NULL,token_hash BINARY(32) NOT NULL,expires_at DATETIME(6) NOT NULL,used_at DATETIME(6) NULL DEFAULT NULL,revoked_at DATETIME(6) NULL DEFAULT NULL,created_by_user_id BIGINT UNSIGNED NULL DEFAULT NULL,created_at DATETIME(6) NOT NULL,PRIMARY KEY(id),UNIQUE KEY uq_ia_invitations_token(token_hash),KEY ix_ia_invitations_user_expiry(user_id,expires_at),CONSTRAINT `{$fk('fk_ia_invitations_user','ia_inv_user')}` FOREIGN KEY(user_id) REFERENCES `{$prefix}fm2_pilot_users`(user_id) ON DELETE CASCADE ON UPDATE RESTRICT){$tail}",
        "CREATE TABLE `{$prefix}fm2_pilot_user_role_events`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,user_id BIGINT UNSIGNED NOT NULL,role_id BIGINT UNSIGNED NOT NULL,action VARCHAR(40) NOT NULL,occurred_at VARCHAR(40) NOT NULL,actor_user_id BIGINT UNSIGNED NULL DEFAULT NULL,PRIMARY KEY(id),KEY ix_ia_user_role_events_user(user_id,id)){$tail}",
        "CREATE TABLE `{$prefix}fm2_pilot_auth_attempts`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,email_normalized VARCHAR(254) NOT NULL,succeeded TINYINT(1) NOT NULL,attempted_at DATETIME(6) NOT NULL,PRIMARY KEY(id),KEY ix_ia_auth_attempts_email_time(email_normalized,attempted_at)){$tail}",
        "CREATE TABLE `{$prefix}fm2_pilot_user_status_events`(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,user_id BIGINT UNSIGNED NOT NULL,action VARCHAR(40) NOT NULL,occurred_at VARCHAR(40) NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,PRIMARY KEY(id),KEY ix_ia_user_status_events_user(user_id,id)){$tail}",
    ];
}

function iaDatabaseCollation(mysqli $db): string
{
    return (string) $db->query('SELECT DEFAULT_COLLATION_NAME c FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=DATABASE()')->fetch_assoc()['c'];
}

/**
 * Independently prove that MariaDB can apply the exact reported database
 * default to utf8mb4. MariaDB 11.4 exposes UCA defaults in COLLATIONS under
 * the prefix-less alias (for example uca1400_ai_ci) with a nullable charset.
 */
function iaAssertUsableUtf8mb4DatabaseDefault(mysqli $db, array $defaults): void
{
    assertSameValue('utf8mb4', $defaults['DEFAULT_CHARACTER_SET_NAME'], 'Valid fixture independently proves database-default utf8mb4 before first target DDL.');
    $collation = (string) $defaults['DEFAULT_COLLATION_NAME'];
    assertSameValue(1, preg_match('/^[A-Za-z0-9_]+$/D', $collation), 'Valid fixture collation name satisfies the approved safe grammar.');

    $escapedCollation = $db->real_escape_string($collation);
    $exactMembership = (int) $db->query("SELECT COUNT(*) n FROM information_schema.COLLATIONS WHERE COLLATION_NAME='{$escapedCollation}' AND CHARACTER_SET_NAME='utf8mb4'")->fetch_assoc()['n'];
    $aliasMembership = 0;
    if (str_starts_with($collation, 'utf8mb4_')) {
        $alias = substr($collation, strlen('utf8mb4_'));
        $escapedAlias = $db->real_escape_string($alias);
        $aliasMembership = (int) $db->query("SELECT COUNT(*) n FROM information_schema.COLLATIONS WHERE COLLATION_NAME='{$escapedAlias}' AND CHARACTER_SET_NAME IS NULL")->fetch_assoc()['n'];
    }
    assertSameValue(true, $exactMembership === 1 || $aliasMembership === 1, 'Valid fixture collation has an exact utf8mb4 row or the approved prefix-less nullable-charset UCA alias.');

    $trial = $db->query("SELECT _utf8mb4'identity-access-collation-trial' COLLATE `{$collation}` value")->fetch_assoc();
    assertSameValue('identity-access-collation-trial', $trial['value'], 'Exact reported default is safely applicable to utf8mb4 before first target DDL.');
}

function iaCreateLiteralFamily(mysqli $db, string $prefix): void
{
    foreach (iaLiteralDdls($prefix, iaDatabaseCollation($db)) as $ddl) $db->query($ddl);
}

function iaCreateGeneratedNameFamily(mysqli $db, string $prefix): void
{
    foreach (iaLiteralDdls($prefix, iaDatabaseCollation($db)) as $ddl) {
        $ddl = preg_replace('/UNIQUE KEY [A-Za-z0-9_]+\(([^)]+)\)/', 'UNIQUE KEY($1)', $ddl);
        $ddl = preg_replace('/\bKEY [A-Za-z0-9_]+\(([^)]+)\)/', 'KEY($1)', $ddl);
        $ddl = preg_replace('/CONSTRAINT `[^`]+` FOREIGN KEY/', 'FOREIGN KEY', $ddl);
        if (!is_string($ddl)) {
            throw new TestFailure('Generated-name fixture DDL transformation must succeed.');
        }
        $db->query($ddl);
    }
}

/** @return array<string,array<string,mixed>> */
function iaSemanticManifest(mysqli $db, string $prefix): array
{
    $out = [];
    foreach (iaNames($prefix) as $table) {
        $q = static fn (string $sql): array => $db->query($sql)->fetch_all(MYSQLI_ASSOC);
        $e = $db->real_escape_string($table);
        $out[$table] = [
            'table' => $q("SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$e}'"),
            'columns' => $q("SELECT ORDINAL_POSITION,COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$e}' ORDER BY ORDINAL_POSITION"),
            'indexes' => $q("SELECT NON_UNIQUE,INDEX_NAME,SEQ_IN_INDEX,COLUMN_NAME,INDEX_TYPE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$e}' ORDER BY INDEX_NAME,SEQ_IN_INDEX"),
            'fks' => $q("SELECT k.CONSTRAINT_NAME,k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.DELETE_RULE,r.UPDATE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.TABLE_NAME='{$e}' AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY k.CONSTRAINT_NAME,k.ORDINAL_POSITION"),
        ];
    }
    return $out;
}

/** Test-owned semantic tuples transcribed literally from spec section 4. */
function iaExpectedManifest(string $prefix, string $collation): array
{
    $c = static fn (string $name, string $type, string $null, mixed $default, string $extra = '', ?string $charset = null): array => [$name,$type,$null,$default,$extra,$charset,$charset === null ? null : $collation];
    $i = static fn (int $nonUnique, string $name, int $seq, string $column): array => [$nonUnique,$name,$seq,$column,'BTREE'];
    $f = static fn (string $name, string $column, string $target, string $targetColumn, string $delete): array => [$name,$column,$target,$targetColumn,$delete,'RESTRICT'];
    $fk = static fn (string $empty, string $short): string => $prefix === '' ? $empty : 'fk_' . $prefix . $short;
    $cset = 'utf8mb4';
    $table = static fn (array $columns, array $indexes, array $fks = []): array => ['table'=>[['InnoDB',$collation]], 'columns'=>$columns, 'indexes'=>$indexes, 'fks'=>$fks];
    return [
        $prefix.'fm2_pilot_users'=>$table([
            $c('user_id','bigint(20) unsigned','NO',null,'auto_increment'),$c('full_name','varchar(300)','NO',null,'',$cset),$c('email','varchar(254)','NO',null,'',$cset),$c('phone','varchar(100)','NO',"''",'',$cset),$c('status','tinyint(1)','NO','1'),$c('activation_state',"enum('invited','active','blocked')",'NO',null,'',$cset),$c('session_version','int(10) unsigned','NO','1'),$c('source_updated_at','varchar(40)','NO',null,'',$cset),
        ],[$i(1,'ix_ia_users_status_name',1,'status'),$i(1,'ix_ia_users_status_name',2,'full_name'),$i(0,'PRIMARY',1,'user_id'),$i(0,'uq_ia_users_email',1,'email')]),
        $prefix.'fm2_pilot_roles'=>$table([
            $c('role_id','bigint(20) unsigned','NO',null,'auto_increment'),$c('code','varchar(64)','NO',null,'',$cset),$c('name','varchar(300)','NO',null,'',$cset),$c('description','varchar(500)','NO',null,'',$cset),$c('status','tinyint(1)','NO',null),$c('source_updated_at','varchar(40)','NO',null,'',$cset),
        ],[$i(0,'PRIMARY',1,'role_id'),$i(0,'uq_ia_roles_code',1,'code')]),
        $prefix.'fm2_pilot_role_permissions'=>$table([
            $c('role_id','bigint(20) unsigned','NO',null),$c('permission','varchar(100)','NO',null,'',$cset),
        ],[$i(0,'PRIMARY',1,'role_id'),$i(0,'PRIMARY',2,'permission')],[
            $f($fk('fk_ia_role_permissions_role','ia_rp_role'),'role_id',$prefix.'fm2_pilot_roles','role_id','CASCADE'),
        ]),
        $prefix.'fm2_pilot_user_roles'=>$table([
            $c('user_id','bigint(20) unsigned','NO',null),$c('role_id','bigint(20) unsigned','NO',null),$c('origin','varchar(40)','NO',null,'',$cset),$c('assigned_at','varchar(40)','NO',null,'',$cset),$c('assigned_by_user_id','bigint(20) unsigned','YES',null),
        ],[$i(1,'ix_ia_user_roles_role',1,'role_id'),$i(0,'PRIMARY',1,'user_id'),$i(0,'PRIMARY',2,'role_id')],[
            $f($fk('fk_ia_user_roles_role','ia_ur_role'),'role_id',$prefix.'fm2_pilot_roles','role_id','RESTRICT'),
            $f($fk('fk_ia_user_roles_user','ia_ur_user'),'user_id',$prefix.'fm2_pilot_users','user_id','CASCADE'),
        ]),
        $prefix.'fm2_pilot_auth_credentials'=>$table([
            $c('user_id','bigint(20) unsigned','NO',null),$c('email_normalized','varchar(254)','NO',null,'',$cset),$c('password_hash','varchar(255)','YES',null,'',$cset),$c('password_set_at','varchar(40)','YES',null,'',$cset),$c('updated_at','varchar(40)','NO',null,'',$cset),
        ],[$i(0,'PRIMARY',1,'user_id'),$i(0,'uq_ia_auth_credentials_email',1,'email_normalized')],[
            $f($fk('fk_ia_auth_credentials_user','ia_ac_user'),'user_id',$prefix.'fm2_pilot_users','user_id','CASCADE'),
        ]),
        $prefix.'fm2_pilot_invitations'=>$table([
            $c('id','bigint(20) unsigned','NO',null,'auto_increment'),$c('user_id','bigint(20) unsigned','NO',null),$c('token_hash','binary(32)','NO',null),$c('expires_at','datetime(6)','NO',null),$c('used_at','datetime(6)','YES',null),$c('revoked_at','datetime(6)','YES',null),$c('created_by_user_id','bigint(20) unsigned','YES',null),$c('created_at','datetime(6)','NO',null),
        ],[$i(1,'ix_ia_invitations_user_expiry',1,'user_id'),$i(1,'ix_ia_invitations_user_expiry',2,'expires_at'),$i(0,'PRIMARY',1,'id'),$i(0,'uq_ia_invitations_token',1,'token_hash')],[
            $f($fk('fk_ia_invitations_user','ia_inv_user'),'user_id',$prefix.'fm2_pilot_users','user_id','CASCADE'),
        ]),
        $prefix.'fm2_pilot_user_role_events'=>$table([
            $c('id','bigint(20) unsigned','NO',null,'auto_increment'),$c('user_id','bigint(20) unsigned','NO',null),$c('role_id','bigint(20) unsigned','NO',null),$c('action','varchar(40)','NO',null,'',$cset),$c('occurred_at','varchar(40)','NO',null,'',$cset),$c('actor_user_id','bigint(20) unsigned','YES',null),
        ],[$i(1,'ix_ia_user_role_events_user',1,'user_id'),$i(1,'ix_ia_user_role_events_user',2,'id'),$i(0,'PRIMARY',1,'id')]),
        $prefix.'fm2_pilot_auth_attempts'=>$table([
            $c('id','bigint(20) unsigned','NO',null,'auto_increment'),$c('email_normalized','varchar(254)','NO',null,'',$cset),$c('succeeded','tinyint(1)','NO',null),$c('attempted_at','datetime(6)','NO',null),
        ],[$i(1,'ix_ia_auth_attempts_email_time',1,'email_normalized'),$i(1,'ix_ia_auth_attempts_email_time',2,'attempted_at'),$i(0,'PRIMARY',1,'id')]),
        $prefix.'fm2_pilot_user_status_events'=>$table([
            $c('id','bigint(20) unsigned','NO',null,'auto_increment'),$c('user_id','bigint(20) unsigned','NO',null),$c('action','varchar(40)','NO',null,'',$cset),$c('occurred_at','varchar(40)','NO',null,'',$cset),$c('actor_user_id','bigint(20) unsigned','NO',null),
        ],[$i(1,'ix_ia_user_status_events_user',1,'user_id'),$i(1,'ix_ia_user_status_events_user',2,'id'),$i(0,'PRIMARY',1,'id')]),
    ];
}

function iaComparableManifest(array $manifest): array
{
    $out = [];
    foreach ($manifest as $name => $m) {
        $out[$name] = [
            'table' => array_map(static fn(array $r): array => [$r['ENGINE'],$r['TABLE_COLLATION']], $m['table']),
            'columns' => array_map(static fn(array $r): array => [$r['COLUMN_NAME'],$r['COLUMN_TYPE'],$r['IS_NULLABLE'],$r['IS_NULLABLE'] === 'YES' && $r['COLUMN_DEFAULT'] === 'NULL' ? null : $r['COLUMN_DEFAULT'],$r['EXTRA'],$r['CHARACTER_SET_NAME'],$r['COLLATION_NAME']], $m['columns']),
            'indexes' => array_map(static fn(array $r): array => [(int)$r['NON_UNIQUE'],$r['INDEX_NAME'],(int)$r['SEQ_IN_INDEX'],$r['COLUMN_NAME'],$r['INDEX_TYPE']], $m['indexes']),
            'fks' => array_map(static fn(array $r): array => [$r['CONSTRAINT_NAME'],$r['COLUMN_NAME'],$r['REFERENCED_TABLE_NAME'],$r['REFERENCED_COLUMN_NAME'],$r['DELETE_RULE'],$r['UPDATE_RULE']], $m['fks']),
        ];
    }
    return $out;
}

/** @return array<string,mixed> */
function iaState(mysqli $db, string $prefix): array
{
    $escaped = $db->real_escape_string($prefix . 'fm2_pilot_%');
    $state = [];
    $tables = $db->query("SELECT TABLE_NAME,AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$escaped}' ORDER BY BINARY TABLE_NAME")->fetch_all(MYSQLI_ASSOC);
    foreach ($tables as $metadata) {
        $table = $metadata['TABLE_NAME'];
        $rows = $db->query("SELECT * FROM `{$table}`")->fetch_all(MYSQLI_ASSOC);
        $create = $db->query("SHOW CREATE TABLE `{$table}`")->fetch_assoc();
        $state[$table] = [$metadata, array_values($create), $rows];
    }
    return $state;
}

function iaPopulateLiteralFamily(mysqli $db, string $prefix, bool $generatedNames = false): void
{
    $generatedNames ? iaCreateGeneratedNameFamily($db, $prefix) : iaCreateLiteralFamily($db, $prefix);
    $db->query("INSERT INTO `{$prefix}fm2_pilot_users` VALUES(41,'Синтетический Пользователь','synthetic@example.test','',1,'active',7,'2026-09-01T10:00:00+03:00')");
    $db->query("INSERT INTO `{$prefix}fm2_pilot_roles` VALUES(51,'synthetic-role','Синтетическая роль','Только тестовые данные',1,'2026-09-01T10:00:00+03:00')");
    $db->query("INSERT INTO `{$prefix}fm2_pilot_role_permissions` VALUES(51,'objects.read')");
    $db->query("INSERT INTO `{$prefix}fm2_pilot_user_roles` VALUES(41,51,'synthetic','2026-09-01T10:00:00+03:00',NULL)");
    $db->query("INSERT INTO `{$prefix}fm2_pilot_auth_credentials` VALUES(41,'synthetic@example.test',NULL,NULL,'2026-09-01T10:00:00+03:00')");
    $db->query("INSERT INTO `{$prefix}fm2_pilot_invitations` VALUES(61,41,UNHEX(REPEAT('11',32)),'2026-09-02 10:00:00.000001',NULL,NULL,NULL,'2026-09-01 10:00:00.000001')");
    $db->query("INSERT INTO `{$prefix}fm2_pilot_user_role_events` VALUES(71,41,51,'attached','2026-09-01T10:00:00+03:00',NULL)");
    $db->query("INSERT INTO `{$prefix}fm2_pilot_auth_attempts`(email_normalized,succeeded,attempted_at) VALUES('synthetic@shlz.ru',0,'2026-09-01 07:00:00.000001')");
    $db->query("INSERT INTO `{$prefix}fm2_pilot_user_status_events` VALUES(81,41,'user_blocked','2026-09-01T10:01:00+03:00',41)");
    foreach (['users'=>101,'roles'=>111,'invitations'=>121,'user_role_events'=>131,'auth_attempts'=>141,'user_status_events'=>151] as $base => $next) $db->query("ALTER TABLE `{$prefix}fm2_pilot_{$base}` AUTO_INCREMENT={$next}");
}

function iaJson(array $result): array
{
    assertSameValue('', $result['stderr'], 'Canonical runner stderr must be empty.');
    $decoded = json_decode($result['stdout'], true, 512, JSON_THROW_ON_ERROR);
    assertSameValue(true, is_array($decoded), 'Canonical runner emits one JSON object.');
    return $decoded;
}

/** @param array{exitCode:int,result:array<string,mixed>} $outcome */
function iaApplicationOutput(array $outcome): array
{
    return [
        'exitCode' => $outcome['exitCode'],
        'stdout' => json_encode($outcome['result'], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
        'stderr' => '',
    ];
}

$admin = iaDb();
$database = 'fmonitor2_ia_' . bin2hex(random_bytes(6));
$admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4");

try {
    $redCase = getenv('FMONITOR_IA_RED_CASE') ?: 'all';
    $db = iaDb($database);
    $validDefaults = $db->query('SELECT DEFAULT_CHARACTER_SET_NAME,DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=DATABASE()')->fetch_assoc();
    iaAssertUsableUtf8mb4DatabaseDefault($db, $validDefaults);
    // Clean: literal v1..v6 result and exactly nine empty identity/access tables.
    $clean = iaRun($database, 'clean_');
    assertSameValue(0, $clean['exitCode'], 'Clean canonical runner exit.');
    assertSameValue(['ok' => true, 'schemaVersion' => 8, 'appliedVersions' => [1, 2, 3, 4, 5, 6, 7, 8]], iaJson($clean), 'Clean composed canonical result through inspection-evidence v8.');
    foreach (iaNames('clean_') as $table) {
        assertSameValue(1, (int) $db->query("SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'")->fetch_assoc()['n'], "{$table} must exist.");
        assertSameValue(0, (int) $db->query("SELECT COUNT(*) n FROM `{$table}`")->fetch_assoc()['n'], "{$table} must not be seeded.");
    }
    $cleanManifest = iaSemanticManifest($db, 'clean_');
    assertSameValue(iaExpectedManifest('clean_', iaDatabaseCollation($db)), iaComparableManifest($cleanManifest), 'All nine clean semantic manifests and deterministic symbols are test-owned literals.');
    $repeatBefore = iaState($db, 'clean_');
    $repeat = iaRun($database, 'clean_');
    assertSameValue(['ok' => true, 'schemaVersion' => 8, 'appliedVersions' => []], iaJson($repeat), 'Complete composed repeat result.');
    assertSameValue($repeatBefore, iaState($db, 'clean_'), 'Complete repeat preserves schema, rows and counters byte-observably.');

    // Fully populated, generated-name compatible source remains byte-observably unchanged.
    iaPopulateLiteralFamily($db, 'pop_', $redCase !== 'collation');
    if ($redCase !== 'collation') {
    $generatedManifest = iaSemanticManifest($db, 'pop_');
    $canonicalManifest = iaExpectedManifest('pop_', iaDatabaseCollation($db));
    $generatedIndexes = array_values(array_filter(
        array_merge(...array_values(array_map(static fn (array $member): array => array_column($member['indexes'], 'INDEX_NAME'), $generatedManifest))),
        static fn (string $name): bool => $name !== 'PRIMARY',
    ));
    $canonicalIndexes = array_values(array_filter(
        array_merge(...array_values(array_map(static fn (array $member): array => array_column($member['indexes'], 1), $canonicalManifest))),
        static fn (string $name): bool => $name !== 'PRIMARY',
    ));
    $generatedFks = array_merge(...array_values(array_map(static fn (array $member): array => array_column($member['fks'], 'CONSTRAINT_NAME'), $generatedManifest)));
    $canonicalFks = array_merge(...array_values(array_map(static fn (array $member): array => array_column($member['fks'], 0), $canonicalManifest)));
    assertSameValue(true, array_diff($generatedIndexes, $canonicalIndexes) !== [], 'MariaDB generated at least one non-canonical index name before runner.');
    assertSameValue(true, array_diff($generatedFks, $canonicalFks) !== [], 'MariaDB generated at least one non-canonical FK symbol before runner.');
    }
    $populatedBefore = iaState($db, 'pop_');
    assertSameValue(['ok' => true, 'schemaVersion' => 8, 'appliedVersions' => [7,8]], iaJson(iaRun($database, 'pop_')), 'Populated identity family receives v7-v8 successors.');
    assertSameValue($populatedBefore, iaState($db, 'pop_'), 'Populated compatible family is preserved exactly.');

    // Database-default charset/collation is an identity DDL precondition. A
    // non-utf8mb4 default is classified before v6 mutation, even when v1-v5
    // are already complete and therefore cannot obscure the observation.
    if ($redCase !== 'generated-names') {
        $nonUtfDatabase = $database . '_latin1';
        $admin->query("CREATE DATABASE `{$nonUtfDatabase}` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci");
        try {
            $nonUtfDb = iaDb($nonUtfDatabase);
            $databaseDefaults = $nonUtfDb->query('SELECT DEFAULT_CHARACTER_SET_NAME,DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=DATABASE()')->fetch_assoc();
            assertSameValue(['DEFAULT_CHARACTER_SET_NAME'=>'latin1','DEFAULT_COLLATION_NAME'=>'latin1_swedish_ci'], $databaseDefaults, 'Fixture independently proves non-utf8mb4 database default before runner.');
            $nonUtfBefore = iaState($nonUtfDb, 'latin_');
            $nonUtf = iaRun($nonUtfDatabase, 'latin_');
            assertSameValue(
                [69,"{\"ok\":false,\"reason\":\"DATABASE_UNAVAILABLE\"}\n",'',$nonUtfBefore],
                [$nonUtf['exitCode'],$nonUtf['stdout'],$nonUtf['stderr'],iaState($nonUtfDb, 'latin_')],
                'Non-utf8mb4 database default is rejected before first schema mutation.',
            );
        } finally {
            if (isset($nonUtfDb) && $nonUtfDb instanceof mysqli) $nonUtfDb->close();
            $admin->query("DROP DATABASE IF EXISTS `{$nonUtfDatabase}`");
        }
    }

    // Restartable 8/9 partial: only absent status-events is created, then repeat is a no-op.
    iaPopulateLiteralFamily($db, 'partial_');
    $db->query('DROP TABLE `partial_fm2_pilot_user_status_events`');
    $partialBefore = iaState($db, 'partial_');
    assertSameValue(['ok' => true, 'schemaVersion' => 8, 'appliedVersions' => [6,7,8]], iaJson(iaRun($database, 'partial_')), 'Identity partial recovery composes with v7-v8 successors.');
    assertSameValue($partialBefore, array_intersect_key(iaState($db, 'partial_'), $partialBefore), 'Existing partial members are unchanged.');
    assertSameValue(['ok' => true, 'schemaVersion' => 8, 'appliedVersions' => []], iaJson(iaRun($database, 'partial_')), 'Interrupted recovery repeat is a no-op.');

    // Dependency-safe recovery: roles and every dependent member are absent.
    iaCreateLiteralFamily($db, 'deps_');
    foreach (['fm2_pilot_invitations','fm2_pilot_auth_credentials','fm2_pilot_user_roles','fm2_pilot_role_permissions','fm2_pilot_roles'] as $base) $db->query("DROP TABLE `deps_{$base}`");
    $depsBefore = iaState($db, 'deps_');
    assertSameValue(['ok'=>true,'schemaVersion'=>8,'appliedVersions'=>[6,7,8]], iaJson(iaRun($database, 'deps_')), 'Identity dependency recovery within composed v8 catalogue.');
    assertSameValue($depsBefore, array_intersect_key(iaState($db, 'deps_'), $depsBefore), 'Dependency recovery preserves existing members.');
    foreach (iaNames('deps_') as $table) assertSameValue(1, (int)$db->query("SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'")->fetch_assoc()['n'], 'Dependency recovery creates every missing member in FK-safe order.');
    assertSameValue(['ok'=>true,'schemaVersion'=>8,'appliedVersions'=>[]], iaJson(iaRun($database, 'deps_')), 'Dependency recovery is restartable within composed v8 catalogue.');

    // Representative significant fingerprint defects: extra column and relationship rule.
    iaPopulateLiteralFamily($db, 'badcol_');
    $db->query('ALTER TABLE `badcol_fm2_pilot_users` ADD COLUMN unexpected_value INT NULL');
    $badBefore = iaState($db, 'badcol_');
    $bad = iaRun($database, 'badcol_');
    assertSameValue(2, $bad['exitCode'], 'Column conflict exit.');
    assertSameValue(['ok' => false, 'reason' => 'SCHEMA_MIGRATION_CONFLICT', 'schemaVersion' => 6], iaJson($bad), 'Column conflict literal redacted result.');
    assertSameValue($badBefore, iaState($db, 'badcol_'), 'Column conflict performs zero mutation.');

    iaPopulateLiteralFamily($db, 'badfk_');
    $fk = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='badfk_fm2_pilot_user_roles' AND REFERENCED_TABLE_NAME='badfk_fm2_pilot_roles'")->fetch_assoc()['CONSTRAINT_NAME'];
    $db->query("ALTER TABLE `badfk_fm2_pilot_user_roles` DROP FOREIGN KEY `{$fk}`, ADD CONSTRAINT `badfk_role_cascade` FOREIGN KEY(role_id) REFERENCES `badfk_fm2_pilot_roles`(role_id) ON DELETE CASCADE");
    $fkBefore = iaState($db, 'badfk_');
    assertSameValue(2, iaRun($database, 'badfk_')['exitCode'], 'Relationship conflict exit.');
    assertSameValue($fkBefore, iaState($db, 'badfk_'), 'Relationship conflict performs zero mutation.');

    // Category-complete fingerprint sensitivity. Each independent fixture must
    // return the exact redacted public result and preserve every byte-observable fact.
    $conflicts = [
        'ordinal_name_type' => "ALTER TABLE `%pfm2_pilot_users` CHANGE full_name renamed_name INT NOT NULL FIRST",
        'nullability' => "ALTER TABLE `%pfm2_pilot_users` MODIFY full_name VARCHAR(300) NULL",
        'default' => "ALTER TABLE `%pfm2_pilot_users` ALTER phone SET DEFAULT 'x'",
        'auto_increment' => "ALTER TABLE `%pfm2_pilot_users` MODIFY user_id BIGINT UNSIGNED NOT NULL",
        'enum' => "ALTER TABLE `%pfm2_pilot_users` MODIFY activation_state ENUM('invited','active','blocked','other') NOT NULL",
        'extra_column' => "ALTER TABLE `%pfm2_pilot_users` ADD extra_value INT NULL",
        'primary' => "ALTER TABLE `%pfm2_pilot_user_status_events` DROP PRIMARY KEY, ADD PRIMARY KEY(id,user_id)",
        'unique' => "ALTER TABLE `%pfm2_pilot_users` DROP INDEX uq_ia_users_email",
        'secondary' => "ALTER TABLE `%pfm2_pilot_auth_attempts` DROP INDEX ix_ia_auth_attempts_email_time",
        'engine' => "ALTER TABLE `%pfm2_pilot_user_status_events` ENGINE=MyISAM",
        'charset' => "ALTER TABLE `%pfm2_pilot_auth_attempts` CONVERT TO CHARACTER SET latin1 COLLATE latin1_swedish_ci",
        'collation' => "ALTER TABLE `%pfm2_pilot_auth_attempts` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin",
        'fk_target' => "ALTER TABLE `%pfm2_pilot_user_roles` DROP FOREIGN KEY `%ffk_user`, ADD CONSTRAINT `%ffk_target_defect` FOREIGN KEY(user_id) REFERENCES `%pdecoy_users`(user_id) ON DELETE CASCADE ON UPDATE RESTRICT",
        'fk_local_column' => "ALTER TABLE `%pfm2_pilot_user_roles` DROP FOREIGN KEY `%ffk_user`, ADD CONSTRAINT `%ffk_local_defect` FOREIGN KEY(assigned_by_user_id) REFERENCES `%pfm2_pilot_users`(user_id) ON DELETE CASCADE ON UPDATE RESTRICT",
        'fk_referenced_column' => "ALTER TABLE `%pfm2_pilot_user_roles` DROP FOREIGN KEY `%ffk_role`, ADD CONSTRAINT `%ffk_ref_defect` FOREIGN KEY(role_id) REFERENCES `%pfm2_pilot_roles`(alternate_role_id) ON DELETE RESTRICT ON UPDATE RESTRICT",
        'fk_update_rule' => "ALTER TABLE `%pfm2_pilot_user_roles` DROP FOREIGN KEY `%ffk_role`, ADD CONSTRAINT `%ffk_update_defect` FOREIGN KEY(role_id) REFERENCES `%pfm2_pilot_roles`(role_id) ON DELETE RESTRICT ON UPDATE CASCADE",
    ];
    foreach ($conflicts as $category => $mutation) {
        $prefix = 'c' . substr(hash('sha256', $category), 0, 5) . '_';
        iaCreateLiteralFamily($db, $prefix);
        if ($category === 'fk_target') $db->query("CREATE TABLE `{$prefix}decoy_users`(user_id BIGINT UNSIGNED NOT NULL,PRIMARY KEY(user_id)) ENGINE=InnoDB");
        if ($category === 'fk_referenced_column') $db->query("ALTER TABLE `{$prefix}fm2_pilot_roles` ADD alternate_role_id BIGINT UNSIGNED NOT NULL, ADD UNIQUE KEY uq_test_alternate_role(alternate_role_id)");
        $userFk = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}fm2_pilot_user_roles' AND COLUMN_NAME='user_id' AND REFERENCED_TABLE_NAME IS NOT NULL")->fetch_assoc()['CONSTRAINT_NAME'] ?? '';
        $roleFk = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}fm2_pilot_user_roles' AND COLUMN_NAME='role_id' AND REFERENCED_TABLE_NAME IS NOT NULL")->fetch_assoc()['CONSTRAINT_NAME'] ?? '';
        $mutation = str_replace(['%p','%ffk_user','%ffk_role'], [$prefix,$userFk,$roleFk], $mutation);
        $db->query($mutation);
        $before = iaState($db, $prefix);
        $result = iaRun($database, $prefix);
        assertSameValue([2,"{\"ok\":false,\"reason\":\"SCHEMA_MIGRATION_CONFLICT\",\"schemaVersion\":6}\n",''], [$result['exitCode'],$result['stdout'],$result['stderr']], "{$category} exact redacted conflict.");
        assertSameValue($before, iaState($db, $prefix), "{$category} zero mutation.");
    }

    // Ordered multi-conflict plus missing members: complete preflight, no missing
    // creation, and public output contains neither names nor catalog diagnostics.
    iaCreateLiteralFamily($db, 'multi_');
    foreach (['fm2_pilot_user_status_events','fm2_pilot_auth_attempts'] as $base) $db->query("DROP TABLE `multi_{$base}`");
    $db->query('ALTER TABLE `multi_fm2_pilot_users` ADD conflict_a INT NULL');
    $db->query('ALTER TABLE `multi_fm2_pilot_roles` ADD conflict_b INT NULL');
    $multiBefore = iaState($db, 'multi_');
    $multi = iaRun($database, 'multi_');
    assertSameValue([2,"{\"ok\":false,\"reason\":\"SCHEMA_MIGRATION_CONFLICT\",\"schemaVersion\":6}\n",''],[$multi['exitCode'],$multi['stdout'],$multi['stderr']],'Multi-conflict exact redacted CLI.');
    assertSameValue($multiBefore, iaState($db, 'multi_'), 'Multi-conflict short-circuits before creating ordered missing members.');

    // Owner-approved application diagnostic seam: exact internal lists are
    // observed before the CLI deliberately redacts them. These assertions
    // first become reachable when minimal GREEN supplies the public v6 object.
    $identityMigration = 'FMonitor2\\InstallationProcess\\IdentityAccessSchemaMigration';
    if (class_exists($identityMigration)) {
        assertSameValue([
            'applied'=>false,
            'schemaVersion'=>6,
            'reason'=>'SCHEMA_MIGRATION_CONFLICT',
            'conflictingTables'=>['multi_fm2_pilot_users','multi_fm2_pilot_roles'],
            'missingTables'=>['multi_fm2_pilot_auth_attempts','multi_fm2_pilot_user_status_events'],
            'tablesCreated'=>[],
        ], $identityMigration::apply($db, 'multi_'), 'Application diagnostic result returns complete ordered conflict/missing lists.');

        iaCreateLiteralFamily($db, 'created_');
        foreach (['fm2_pilot_invitations','fm2_pilot_user_status_events'] as $base) $db->query("DROP TABLE `created_{$base}`");
        assertSameValue([
            'applied'=>true,
            'schemaVersion'=>6,
            'tablesCreated'=>['created_fm2_pilot_invitations','created_fm2_pilot_user_status_events'],
        ], $identityMigration::apply($db, 'created_'), 'Application diagnostic result returns exact normative created order.');
    }

    // Immutable Gate 2 first-GREEN helper: unexpected v6 failure is redacted
    // and application orchestration never invokes a later migration.
    $application = 'FMonitor2\\InstallationProcess\\CanonicalMigrationApplication';
    iaAssertGreenApplicationFailureContract(
        static fn (): array => iaApplicationOutput($application::run(
            $db,
            'failure_',
            [6 => static function (): never { throw new RuntimeException('test-owned v6 failure'); }],
            6,
        )),
        static fn (callable $laterMigration): array => iaApplicationOutput($application::run(
            $db,
            'failure_',
            [
                6 => static function (): never { throw new RuntimeException('test-owned v6 failure'); },
                7 => static function () use ($laterMigration): array {
                    $laterMigration();
                    return ['applied' => true];
                },
            ],
            6,
        )),
    );

    // Prefix isolation and current composed 25/26 pre-DB-access contract.
    $decoyBefore = iaState($db, 'pop_');
    assertSameValue(0, iaRun($database, 'blue_')['exitCode'], 'Second non-empty prefix succeeds without FK symbol collision.');
    assertSameValue(0, iaRun($database, 'green_')['exitCode'], 'Third non-empty prefix coexists.');
    foreach (['blue_','green_'] as $prefix) foreach (iaSemanticManifest($db,$prefix) as $table => $manifest) foreach ($manifest['fks'] as $fkRow) assertSameValue(true, str_starts_with($fkRow['REFERENCED_TABLE_NAME'],$prefix), "{$table} FK remains in its namespace.");
    assertSameValue($decoyBefore, iaState($db, 'pop_'), 'Target migration ignores decoy namespace.');
    $maxPrefix = str_repeat('p', 25);
    assertSameValue(0, iaRun($database, $maxPrefix)['exitCode'], '25-byte prefix succeeds.');
    foreach (iaSemanticManifest($db,$maxPrefix) as $table => $manifest) {
        assertSameValue(true, strlen($table) <= 64, '25-byte table identifier fits MariaDB.');
        foreach (array_merge(array_column($manifest['indexes'],'INDEX_NAME'),array_column($manifest['fks'],'CONSTRAINT_NAME')) as $symbol) assertSameValue(true, strlen($symbol) <= 64, '25-byte derived symbol fits MariaDB.');
    }
    foreach ([str_repeat('p', 26), 'invalid-prefix!'] as $invalidPrefix) {
        $invalid = iaRun('database_must_not_be_contacted', $invalidPrefix, 'unresolvable.invalid');
        assertSameValue([64, "{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n", ''], [$invalid['exitCode'], $invalid['stdout'], $invalid['stderr']], 'Invalid prefix is rejected before database access.');
    }

    // Runtime code is a schema consumer: no request-path CREATE/ALTER/DROP remains.
    foreach (['rapid-pilot/UserAccessView.php', 'rapid-pilot/LocalAuth.php', 'app/PilotHttp/AccessPolicy.php'] as $runtimeFile) {
        $source = file_get_contents(dirname(__DIR__, 2) . '/' . $runtimeFile);
        assertSameValue(0, preg_match('/\b(?:CREATE|ALTER|DROP)\s+(?:TABLE|VIEW|INDEX)\b/i', $source), "{$runtimeFile} must not own runtime DDL.");
    }

    echo "PASS: IDENTITY-ACCESS-SCHEMA-001 canonical runner and runtime ownership\n";
} finally {
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
    $admin->query("DROP DATABASE IF EXISTS `{$database}`");
    $admin->close();
}
