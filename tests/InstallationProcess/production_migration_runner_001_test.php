<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Support/ProductionMigrationRunnerCatalogContract.php';
// Specification: PRODUCTION-MIGRATION-RUNNER-001 v0.5, examples A-C and failures.

function pmrDb(?string $db=null):mysqli{$c=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_demo_local',$db,(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));$c->set_charset('utf8mb4');return $c;}
function pmrRows(mysqli $c,string $sql):array{return $c->query($sql)->fetch_all(MYSQLI_ASSOC);}
function pmrRun(array $env,array $argv=[],string $stdin='',array $phpOptions=[]):array
{
    $command = ['/usr/bin/env', '-i'];
    foreach ($env as $name => $value) {
        if (preg_match('/^[A-Z0-9_]+$/D', (string) $name) !== 1 || str_contains((string) $value, "\0")) {
            throw new TestFailure('Test environment names and values must be safe exec arguments.');
        }
        $command[] = $name . '=' . $value;
    }
    $command = [...$command, PHP_BINARY, ...$phpOptions, dirname(__DIR__,2).'/bin/fmonitor2-migrate.php', ...$argv];
    $pipes=[];
    $p=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));
    if(!is_resource($p))throw new TestFailure('CLI process must start.');
    fwrite($pipes[0],$stdin);fclose($pipes[0]);
    $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);
    fclose($pipes[1]);fclose($pipes[2]);
    return ['exitCode'=>proc_close($p),'stdout'=>$out,'stderr'=>$err];
}
function pmrResult(array $expected,array $actual,string $why,array $secret=[]):void{assertSameValue($expected,$actual,$why);assertSameValue(1,substr_count($actual['stdout'],"\n"),$why.' emits one line');assertSameValue("\n",substr($actual['stdout'],-1),$why.' newline');json_decode(rtrim($actual['stdout'],"\n"),true,512,JSON_THROW_ON_ERROR);foreach($secret as $s)if($s!=='')assertSameValue(false,str_contains($actual['stdout'].$actual['stderr'],$s),$why.' redacts literals');}
function pmrFingerprint(mysqli $c,string $p):array{$like=$c->real_escape_string($p.'fm2\\_%');return ['tables'=>pmrRows($c,"SELECT TABLE_NAME,ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$like}' ORDER BY TABLE_NAME"),'columns'=>pmrRows($c,"SELECT TABLE_NAME,COLUMN_NAME,ORDINAL_POSITION,COLUMN_TYPE,IS_NULLABLE,EXTRA,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$like}' ORDER BY TABLE_NAME,ORDINAL_POSITION"),'constraints'=>pmrRows($c,"SELECT tc.TABLE_NAME,tc.CONSTRAINT_TYPE,tc.CONSTRAINT_NAME,k.COLUMN_NAME,k.ORDINAL_POSITION,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,rc.DELETE_RULE FROM information_schema.TABLE_CONSTRAINTS tc LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON k.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND k.TABLE_NAME=tc.TABLE_NAME AND k.CONSTRAINT_NAME=tc.CONSTRAINT_NAME LEFT JOIN information_schema.REFERENTIAL_CONSTRAINTS rc ON rc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND rc.TABLE_NAME=tc.TABLE_NAME AND rc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME LIKE '{$like}' ORDER BY tc.TABLE_NAME,tc.CONSTRAINT_TYPE,tc.CONSTRAINT_NAME,k.ORDINAL_POSITION"),'checks'=>pmrRows($c,"SELECT tc.TABLE_NAME,cc.CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS cc JOIN information_schema.TABLE_CONSTRAINTS tc ON tc.CONSTRAINT_SCHEMA=cc.CONSTRAINT_SCHEMA AND tc.TABLE_NAME=cc.TABLE_NAME AND tc.CONSTRAINT_NAME=cc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME LIKE '{$like}' ORDER BY tc.TABLE_NAME,cc.CHECK_CLAUSE"),'indexes'=>pmrRows($c,"SELECT TABLE_NAME,INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,SUB_PART,COLLATION,IGNORED FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$like}' ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX")];}

function pmrNormalizeCheck(string $clause): string
{
    $normalized = '';
    $quoted = false;
    $length = strlen($clause);
    for ($index = 0; $index < $length; $index++) {
        $character = $clause[$index];
        if ($character === "'") {
            $normalized .= $character;
            if ($quoted && $index + 1 < $length && $clause[$index + 1] === "'") {
                $normalized .= "'";
                $index++;
            } else {
                $quoted = !$quoted;
            }
            continue;
        }
        if ($quoted) {
            $normalized .= $character;
            continue;
        }
        if ($character === '`' || ctype_space($character)) {
            continue;
        }
        $normalized .= strtolower($character);
    }
    if (str_starts_with($normalized, '(') && str_ends_with($normalized, ')')) {
        $depth = 0;
        $quoted = false;
        $outerPair = true;
        $last = strlen($normalized) - 1;
        for ($index = 0; $index <= $last; $index++) {
            if ($normalized[$index] === "'") {
                $quoted = !$quoted;
            } elseif (!$quoted && $normalized[$index] === '(') {
                $depth++;
            } elseif (!$quoted && $normalized[$index] === ')') {
                $depth--;
                if ($depth === 0 && $index !== $last) {
                    $outerPair = false;
                    break;
                }
            }
        }
        if ($outerPair) {
            $normalized = substr($normalized, 1, -1);
        }
    }
    return $normalized;
}

function pmrNormalizeEngineerCheck(string $clause): string
{
    $normalized = pmrNormalizeCheck($clause);
    $a = "capability<>'construction_control_engineer'";
    $b = 'position_snapshotisnotnull';
    $c = "trim(position_snapshot)<>''";
    if ($normalized === $a . 'or(' . $b . 'and' . $c . ')'
        || $normalized === $a . 'or' . $b . 'and' . $c) {
        return 'OR(' . $a . ',AND(' . $b . ',' . $c . '))';
    }
    return $normalized;
}

function pmrAssertEngineerCheckNormalizerSensitivity(): void
{
    $expected = "OR(capability<>'construction_control_engineer',AND(position_snapshotisnotnull,trim(position_snapshot)<>''))";
    assertSameValue($expected, pmrNormalizeEngineerCheck("capability <> 'construction_control_engineer' OR (position_snapshot IS NOT NULL AND trim(position_snapshot) <> '')"), 'Approved parenthesized engineer CHECK must map to the exact parse tree.');
    assertSameValue($expected, pmrNormalizeEngineerCheck("(capability <> 'construction_control_engineer' OR (position_snapshot IS NOT NULL AND trim(position_snapshot) <> ''))"), 'Whole-expression parentheses may be removed without changing the exact parse tree.');
    assertSameValue($expected, pmrNormalizeEngineerCheck("capability <> 'construction_control_engineer' OR position_snapshot IS NOT NULL AND trim(position_snapshot) <> ''"), 'MariaDB redundant-parentheses serialization must map to the same exact parse tree.');

    $rejected = [
        "capability <> 'CONSTRUCTION_CONTROL_ENGINEER' OR position_snapshot IS NOT NULL AND trim(position_snapshot) <> ''",
        "capability <> 'construction_control_engineer' OR position_snapshot IS NOT NULL OR trim(position_snapshot) <> ''",
        "capability <> 'construction_control_engineer' OR trim(position_snapshot) <> ''",
        "capability <> 'construction_control_engineer' OR position_snapshot IS NULL AND trim(position_snapshot) <> ''",
        "(capability <> 'construction_control_engineer' OR position_snapshot IS NOT NULL) AND trim(position_snapshot) <> ''",
    ];
    foreach ($rejected as $expression) {
        assertSameValue(false, pmrNormalizeEngineerCheck($expression) === $expected, 'Changed engineer CHECK parse tree/literal/operator must remain distinguishable.');
    }
}

function pmrCatalog(mysqli $connection, string $prefix): void
{
    $contract = ProductionMigrationRunnerCatalogContract::columns();
    $like = $connection->real_escape_string($prefix . 'fm2\\_%');
    $tables = pmrRows($connection, "SELECT TABLE_NAME,ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$like}' ORDER BY BINARY TABLE_NAME");

    $expectedTables = array_map(static fn (string $table): string => $prefix . $table, array_keys($contract));
    sort($expectedTables, SORT_STRING);
    assertSameValue(
        $expectedTables,
        array_column($tables, 'TABLE_NAME'),
        'The catalog must contain exactly the twenty-six approved v1-v8 tables.',
    );
    foreach ($tables as $table) {
        assertSameValue('InnoDB', $table['ENGINE'], 'Every approved table must use InnoDB.');
        assertSameValue(true, str_starts_with((string) $table['TABLE_COLLATION'], 'utf8mb4_'), 'Every approved table must use utf8mb4.');
    }

    foreach ($contract as $table => $expected) {
        $actual = pmrRows($connection, "SELECT COLUMN_NAME,DATA_TYPE,COLUMN_TYPE,IS_NULLABLE,EXTRA,CHARACTER_SET_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$prefix}{$table}' ORDER BY ORDINAL_POSITION");
        $actual = array_map(static function (array $column) use ($table): array {
            $type = strtolower((string) $column['COLUMN_TYPE']);
            $type = preg_replace('/^(bigint|int|smallint|tinyint)\\(\\d+\\)/', '$1', $type);
            if ($table === 'fm2_process_events' && $column['COLUMN_NAME'] === 'payload_json' && $column['DATA_TYPE'] === 'longtext') {
                $type = 'json';
            }
            $textual = str_starts_with($type, 'varchar(') || str_starts_with($type, 'char(') || str_starts_with($type, 'enum(') || $type === 'text' || $type === 'longtext' || $type === 'json';
            assertSameValue($textual ? 'utf8mb4' : null, $column['CHARACTER_SET_NAME'], $column['COLUMN_NAME'] . ' character contract.');
            return [$column['COLUMN_NAME'], $type, $column['IS_NULLABLE'], $column['EXTRA']];
        }, $actual);
        assertSameValue($expected, $actual, $table . ' exact types, nullability, order and extras.');
    }

    $indexes = pmrRows($connection, "SELECT TABLE_NAME,INDEX_NAME,NON_UNIQUE,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS COLUMNS FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$like}' GROUP BY TABLE_NAME,INDEX_NAME,NON_UNIQUE");
    $indexes = array_map(static function (array $row) use ($prefix): string {
        $kind = $row['INDEX_NAME'] === 'PRIMARY'
            ? 'PRIMARY'
            : ($row['NON_UNIQUE'] === '0' ? 'UNIQUE' : 'INDEX');
        return substr((string) $row['TABLE_NAME'], strlen($prefix)) . '|' . $kind . '|' . $row['COLUMNS'];
    }, $indexes);
    sort($indexes);
    $expectedIndexes = ProductionMigrationRunnerCatalogContract::indexes();
    sort($expectedIndexes);
    assertSameValue($expectedIndexes, $indexes, 'All primary, unique, secondary and FK-support indexes must match the approved contract.');

    $foreignKeys = pmrRows($connection, "SELECT k.TABLE_NAME,k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.TABLE_NAME=k.TABLE_NAME AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.TABLE_NAME LIKE '{$like}' ORDER BY k.TABLE_NAME,k.COLUMN_NAME");
    $foreignKeys = array_map(static fn (array $row): string => substr((string) $row['TABLE_NAME'], strlen($prefix)) . '|' . $row['COLUMN_NAME'] . '|' . substr((string) $row['REFERENCED_TABLE_NAME'], strlen($prefix)) . '|' . $row['REFERENCED_COLUMN_NAME'] . '|' . $row['DELETE_RULE'], $foreignKeys);
    assertSameValue(ProductionMigrationRunnerCatalogContract::foreignKeys(), $foreignKeys, 'All thirteen foreign keys and delete rules must match the approved contract.');

    $checks = pmrRows($connection, "SELECT tc.TABLE_NAME,tc.CONSTRAINT_NAME,cc.CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS cc JOIN information_schema.TABLE_CONSTRAINTS tc ON tc.CONSTRAINT_SCHEMA=cc.CONSTRAINT_SCHEMA AND tc.TABLE_NAME=cc.TABLE_NAME AND tc.CONSTRAINT_NAME=cc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME LIKE '{$like}' ORDER BY tc.TABLE_NAME,cc.CHECK_CLAUSE");
    $checks = array_map(static function (array $check) use ($prefix): array {
        $clause = stripos((string) $check['CHECK_CLAUSE'], 'position_snapshot') !== false
            ? pmrNormalizeEngineerCheck((string) $check['CHECK_CLAUSE'])
            : pmrNormalizeCheck((string) $check['CHECK_CLAUSE']);
        return [
            'table' => substr((string) $check['TABLE_NAME'], strlen($prefix)),
            'constraint' => $check['CONSTRAINT_NAME'] === 'ck_fm2_process_user_capability' ? $check['CONSTRAINT_NAME'] : null,
            'clause' => $clause,
        ];
    }, $checks);
    assertSameValue(ProductionMigrationRunnerCatalogContract::checks(), $checks, 'All eleven normalized CHECK tuples and the normative v4 constraint name must match literally, with no extras.');
}
function pmrState(mysqli $c):array{$s=[];foreach(pmrRows($c,'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME') as $r){$t=$r['TABLE_NAME'];$q='`'.str_replace('`','``',$t).'`';$s[$t]=['create'=>pmrRows($c,"SHOW CREATE TABLE {$q}")[0]['Create Table'],'rows'=>pmrRows($c,"SELECT * FROM {$q}")];}return $s;}

function pmrReplaceCompletedV4Checks(mysqli $connection, string $prefix, string $capabilityExpression, string $engineerExpression): void
{
    $table = $prefix . 'fm2_process_user_capabilities';
    $checks = pmrRows($connection, "SELECT tc.CONSTRAINT_NAME,cc.CHECK_CLAUSE FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.CHECK_CONSTRAINTS cc ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND cc.TABLE_NAME=tc.TABLE_NAME AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME='{$table}' ORDER BY tc.CONSTRAINT_NAME");
    $capabilityName = null;
    $engineerName = null;
    foreach ($checks as $check) {
        if ($check['CONSTRAINT_NAME'] === 'ck_fm2_process_user_capability') {
            $capabilityName = $check['CONSTRAINT_NAME'];
        } elseif (stripos((string) $check['CHECK_CLAUSE'], 'position_snapshot') !== false) {
            $engineerName = $check['CONSTRAINT_NAME'];
        }
    }
    foreach ([$capabilityName, $engineerName] as $name) {
        if (!is_string($name) || preg_match('/^[A-Za-z0-9_$]{1,64}$/D', $name) !== 1) {
            throw new TestFailure('Completed-v4 fixture must find two safe exact CHECK identifiers.');
        }
    }
    $connection->query("ALTER TABLE `{$table}` DROP CONSTRAINT `{$capabilityName}`, DROP CONSTRAINT `{$engineerName}`, ADD CONSTRAINT `ck_fm2_process_user_capability` CHECK ({$capabilityExpression}), ADD CONSTRAINT `fixture_engineer_position` CHECK ({$engineerExpression})");
}

function pmrCompletedV4Environment(string $database, string $prefix): array
{
    return [
        'FMONITOR_DB_HOST' => getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
        'FMONITOR_DB_PORT' => getenv('FMONITOR_TEST_DB_PORT') ?: '23306',
        'FMONITOR_DB_NAME' => $database,
        'FMONITOR_DB_USER' => getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
        'FMONITOR_DB_PASSWORD' => getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_demo_local',
        'FMONITOR_PROCESS_TABLE_PREFIX' => $prefix,
    ];
}

/** @return array{process:resource,pipes:array,port:string,transcript:string,directory:string,error:string} */
function pmrStartCharsetFaultProxy(string $token): array
{
    $artifactRoot = dirname(__DIR__, 2) . '/.test-artifacts';
    if (!file_exists($artifactRoot) && !mkdir($artifactRoot, 0700)) {
        throw new TestFailure('A bounded test-artifact root must be creatable.');
    }
    $rootStat = lstat($artifactRoot);
    if ($rootStat === false || is_link($artifactRoot) || ($rootStat['mode'] & 0170000) !== 0040000 || $rootStat['uid'] !== posix_geteuid() || ($rootStat['mode'] & 0022) !== 0) {
        throw new TestFailure('The test-artifact root must be a trusted owner-controlled real directory.');
    }
    $directory = $artifactRoot . '/pmr-charset-' . $token;
    if (!mkdir($directory, 0700)) {
        throw new TestFailure('A unique charset-proxy directory must be creatable.');
    }
    $transcript = $directory . '/transcript.json';
    $injectedError = 'injected-charset-error-' . $token;
    $pipes = [];
    $process = proc_open(
        [PHP_BINARY, dirname(__DIR__) . '/Support/mysql_wire_charset_fault_proxy.php'],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        dirname(__DIR__, 2),
        [
            'PMR_BACKEND_HOST' => getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1',
            'PMR_BACKEND_PORT' => getenv('FMONITOR_TEST_DB_PORT') ?: '23306',
            'PMR_TRANSCRIPT_PATH' => $transcript,
            'PMR_INJECTED_ERROR' => $injectedError,
        ],
    );
    if (!is_resource($process)) {
        rmdir($directory);
        throw new TestFailure('Charset fault proxy must start.');
    }
    fclose($pipes[0]);
    $port = trim((string) fgets($pipes[1]));
    if (!ctype_digit($port)) {
        proc_terminate($process);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        rmdir($directory);
        throw new TestFailure('Charset fault proxy must publish a loopback port.');
    }
    return ['process' => $process, 'pipes' => $pipes, 'port' => $port, 'transcript' => $transcript, 'directory' => $directory, 'error' => $injectedError];
}

function pmrFinishCharsetFaultProxy(array $proxy): array
{
    try {
        $stdout = stream_get_contents($proxy['pipes'][1]);
        $stderr = stream_get_contents($proxy['pipes'][2]);
        fclose($proxy['pipes'][1]);
        fclose($proxy['pipes'][2]);
        $exit = proc_close($proxy['process']);
        $commands = json_decode((string) file_get_contents($proxy['transcript']), true, 512, JSON_THROW_ON_ERROR);
        assertSameValue(['exit' => 0, 'stdout' => '', 'stderr' => ''], compact('exit', 'stdout', 'stderr'), 'Charset proxy fixture must complete cleanly.');
        return $commands;
    } finally {
        if (is_resource($proxy['pipes'][1])) {
            fclose($proxy['pipes'][1]);
        }
        if (is_resource($proxy['pipes'][2])) {
            fclose($proxy['pipes'][2]);
        }
        if (is_resource($proxy['process'])) {
            $status = proc_get_status($proxy['process']);
            if ($status['running']) {
                proc_terminate($proxy['process']);
            }
            proc_close($proxy['process']);
        }
        if (is_file($proxy['transcript'])) {
            unlink($proxy['transcript']);
        }
        if (is_dir($proxy['directory'])) {
            rmdir($proxy['directory']);
        }
    }
}

function pmrAbortCharsetFaultProxy(array $proxy): void
{
    foreach ([1, 2] as $descriptor) {
        if (isset($proxy['pipes'][$descriptor]) && is_resource($proxy['pipes'][$descriptor])) {
            fclose($proxy['pipes'][$descriptor]);
        }
    }
    if (isset($proxy['process']) && is_resource($proxy['process'])) {
        $status = proc_get_status($proxy['process']);
        if ($status['running']) {
            proc_terminate($proxy['process']);
        }
        proc_close($proxy['process']);
    }
    if (isset($proxy['transcript']) && is_file($proxy['transcript'])) {
        unlink($proxy['transcript']);
    }
    if (isset($proxy['directory']) && is_dir($proxy['directory'])) {
        rmdir($proxy['directory']);
    }
}

pmrAssertEngineerCheckNormalizerSensitivity();

$tok=bin2hex(random_bytes(6));$dbs=[];$users=[];$files=[];$admin=pmrDb();
try{
 $base=['FMONITOR_DB_HOST'=>'secret-host-'.$tok,'FMONITOR_DB_PORT'=>'23306','FMONITOR_DB_NAME'=>'secret-db-'.$tok,'FMONITOR_DB_USER'=>'secret-user-'.$tok,'FMONITOR_DB_PASSWORD'=>'secret-password-'.$tok,'FMONITOR_PROCESS_TABLE_PREFIX'=>'secret_prefix_'.$tok.'_'];
 $bad=['missing host'=>array_diff_key($base,['FMONITOR_DB_HOST'=>1]),'missing port'=>array_diff_key($base,['FMONITOR_DB_PORT'=>1]),'missing db'=>array_diff_key($base,['FMONITOR_DB_NAME'=>1]),'missing user'=>array_diff_key($base,['FMONITOR_DB_USER'=>1]),'empty host'=>array_replace($base,['FMONITOR_DB_HOST'=>'']),'empty port'=>array_replace($base,['FMONITOR_DB_PORT'=>'']),'empty db'=>array_replace($base,['FMONITOR_DB_NAME'=>'']),'empty user'=>array_replace($base,['FMONITOR_DB_USER'=>'']),'missing password'=>array_diff_key($base,['FMONITOR_DB_PASSWORD'=>1]),'missing prefix'=>array_diff_key($base,['FMONITOR_PROCESS_TABLE_PREFIX'=>1]),'missing password and prefix together'=>array_diff_key($base,['FMONITOR_DB_PASSWORD'=>1,'FMONITOR_PROCESS_TABLE_PREFIX'=>1]),'port syntax'=>array_replace($base,['FMONITOR_DB_PORT'=>'2x']),'port zero'=>array_replace($base,['FMONITOR_DB_PORT'=>'0']),'port high'=>array_replace($base,['FMONITOR_DB_PORT'=>'65536']),'prefix syntax'=>array_replace($base,['FMONITOR_PROCESS_TABLE_PREFIX'=>'bad-prefix']),'prefix length'=>array_replace($base,['FMONITOR_PROCESS_TABLE_PREFIX'=>str_repeat('a',33)])];
 foreach($bad as $label=>$env)pmrResult(['exitCode'=>64,'stdout'=>"{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n",'stderr'=>''],pmrRun($env,['--password=argv-'.$tok],'stdin-'.$tok),$label,array_values($base));
 $unavailable=array_replace($base,['FMONITOR_DB_HOST'=>'127.0.0.1','FMONITOR_DB_PORT'=>'1','FMONITOR_PROCESS_TABLE_PREFIX'=>'unavailable_']);pmrResult(['exitCode'=>69,'stdout'=>"{\"ok\":false,\"reason\":\"DATABASE_UNAVAILABLE\"}\n",'stderr'=>''],pmrRun($unavailable),'unavailable DB with a valid composed prefix',array_values($unavailable));

 $charsetDatabase = 't_pmr_charset_' . $tok;
 $dbs[] = $charsetDatabase;
 $admin->query("CREATE DATABASE `{$charsetDatabase}` DEFAULT CHARSET=utf8mb4");
 $proxy = pmrStartCharsetFaultProxy($tok);
 try {
     $charsetEnvironment = [
         'FMONITOR_DB_HOST' => '127.0.0.1',
         'FMONITOR_DB_PORT' => $proxy['port'],
         'FMONITOR_DB_NAME' => $charsetDatabase,
         'FMONITOR_DB_USER' => getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
         'FMONITOR_DB_PASSWORD' => getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_demo_local',
         'FMONITOR_PROCESS_TABLE_PREFIX' => 'charset_' . $tok . '_',
     ];
     pmrResult(
         ['exitCode'=>69,'stdout'=>"{\"ok\":false,\"reason\":\"DATABASE_UNAVAILABLE\"}\n",'stderr'=>''],
         pmrRun($charsetEnvironment),
         'successful handshake followed by rejected utf8mb4 confirmation',
         [...array_values($charsetEnvironment), $proxy['error']],
     );
     $commands = pmrFinishCharsetFaultProxy($proxy);
     $proxy = null;
     assertSameValue(true, count($commands) >= 1, 'Proxy must capture the rejected charset command.');
     assertSameValue(true, str_contains(strtolower($commands[0]), 'utf8mb4'), 'The first database command must request exact utf8mb4.');
     assertSameValue(false, (bool) preg_match('/information_schema|create|alter|insert|update|delete/i', implode(' ', $commands)), 'No schema inspection or DDL/DML may follow the charset ERR.');
     $charsetObservation = pmrDb($charsetDatabase);
     assertSameValue([], pmrRows($charsetObservation, 'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()'), 'Charset failure must leave the database empty.');
     $charsetObservation->close();
 } finally {
     if ($proxy !== null) {
         pmrAbortCharsetFaultProxy($proxy);
     }
 }

 $db='t_pmr_a_'.$tok;$dbs[]=$db;$admin->query("CREATE DATABASE `{$db}` DEFAULT CHARSET=utf8mb4");$env=['FMONITOR_DB_HOST'=>getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1','FMONITOR_DB_PORT'=>getenv('FMONITOR_TEST_DB_PORT')?:'23306','FMONITOR_DB_NAME'=>$db,'FMONITOR_DB_USER'=>getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root','FMONITOR_DB_PASSWORD'=>getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_demo_local','FMONITOR_PROCESS_TABLE_PREFIX'=>'pilot_'];
 pmrResult(['exitCode'=>0,'stdout'=>"{\"ok\":true,\"schemaVersion\":8,\"appliedVersions\":[1,2,3,4,5,6,7,8]}\n",'stderr'=>''],pmrRun($env),'example A');$c=pmrDb($db);pmrCatalog($c,'pilot_');
 $c->query("INSERT INTO pilot_fm2_workforce_catalog (installer_tab_id,fio,position,employment_status,employed_from,employed_to,workforce_source,workforce_source_updated_at) VALUES (1042,'Иванов Иван Иванович','Электромеханик по лифтам','employed','2024-02-01',NULL,'one_c_zup_via_bitrix','2026-08-26T18:00:00+03:00')");$c->query("INSERT INTO pilot_fm2_process_user_capabilities VALUES (18,'assignment_order.prepare',NULL)");$c->query("INSERT INTO pilot_fm2_installation_cases (legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES (4512,'needs_assignment_order','2026-08-28T00:00:00+03:00','2026-08-28T00:00:00+03:00',1)");$fp=pmrFingerprint($c,'pilot_');$rows=[pmrRows($c,'SELECT * FROM pilot_fm2_workforce_catalog'),pmrRows($c,'SELECT * FROM pilot_fm2_process_user_capabilities'),pmrRows($c,'SELECT * FROM pilot_fm2_installation_cases')];pmrResult(['exitCode'=>0,'stdout'=>"{\"ok\":true,\"schemaVersion\":8,\"appliedVersions\":[]}\n",'stderr'=>''],pmrRun($env),'example B');assertSameValue($fp,pmrFingerprint($c,'pilot_'),'full catalog unchanged');assertSameValue($rows,[pmrRows($c,'SELECT * FROM pilot_fm2_workforce_catalog'),pmrRows($c,'SELECT * FROM pilot_fm2_process_user_capabilities'),pmrRows($c,'SELECT * FROM pilot_fm2_installation_cases')],'sentinels unchanged');$c->close();

 $completedV4Cases = [
     'whole-wrapper engineer CHECK' => [
         'capability' => "capability IN ('assignment_order.prepare','assignment_order.confirm_registration','installation.open','construction_control_engineer')",
         'engineer' => "(capability <> 'construction_control_engineer' OR (position_snapshot IS NOT NULL AND trim(position_snapshot) <> ''))",
         'accepted' => true,
     ],
     'permuted exact capability IN literals' => [
         'capability' => "capability IN ('installation.open','construction_control_engineer','assignment_order.prepare','assignment_order.confirm_registration')",
         'engineer' => "capability <> 'construction_control_engineer' OR (position_snapshot IS NOT NULL AND trim(position_snapshot) <> '')",
         'accepted' => true,
     ],
     'quoted whitespace near-match' => [
         'capability' => "capability IN ('assignment_order. prepare','assignment_order.confirm_registration','installation.open','construction_control_engineer')",
         'engineer' => "capability <> 'construction_control_engineer' OR (position_snapshot IS NOT NULL AND trim(position_snapshot) <> '')",
         'accepted' => false,
     ],
     'quoted capability case near-match' => [
         'capability' => "capability IN ('assignment_order.prepare','assignment_order.confirm_registration','Installation.open','construction_control_engineer')",
         'engineer' => "capability <> 'construction_control_engineer' OR (position_snapshot IS NOT NULL AND trim(position_snapshot) <> '')",
         'accepted' => false,
     ],
     'quoted engineer case near-match' => [
         'capability' => "capability IN ('assignment_order.prepare','assignment_order.confirm_registration','installation.open','construction_control_engineer')",
         'engineer' => "capability <> 'Construction_control_engineer' OR (position_snapshot IS NOT NULL AND trim(position_snapshot) <> '')",
         'accepted' => false,
     ],
     'extra capability literal' => [
         'capability' => "capability IN ('assignment_order.prepare','assignment_order.confirm_registration','installation.open','construction_control_engineer','unexpected.capability')",
         'engineer' => "capability <> 'construction_control_engineer' OR (position_snapshot IS NOT NULL AND trim(position_snapshot) <> '')",
         'accepted' => false,
     ],
     'duplicate capability literal' => [
         'capability' => "capability IN ('assignment_order.prepare','assignment_order.confirm_registration','installation.open','construction_control_engineer','assignment_order.prepare')",
         'engineer' => "capability <> 'construction_control_engineer' OR (position_snapshot IS NOT NULL AND trim(position_snapshot) <> '')",
         'accepted' => false,
     ],
     'engineer (A OR B) AND C parse tree' => [
         'capability' => "capability IN ('assignment_order.prepare','assignment_order.confirm_registration','installation.open','construction_control_engineer')",
         'engineer' => "(capability <> 'construction_control_engineer' OR position_snapshot IS NOT NULL) AND trim(position_snapshot) <> ''",
         'accepted' => false,
     ],
 ];
 $completedIndex = 0;
 foreach ($completedV4Cases as $label => $fixture) {
     $completedIndex++;
     $completedDatabase = 't_pmr_v4_' . $completedIndex . '_' . $tok;
     $completedPrefix = 'completed_' . $completedIndex . '_';
     $dbs[] = $completedDatabase;
     $admin->query("CREATE DATABASE `{$completedDatabase}` DEFAULT CHARSET=utf8mb4");
     $completedEnvironment = pmrCompletedV4Environment($completedDatabase, $completedPrefix);
     pmrResult(
         ['exitCode'=>0,'stdout'=>"{\"ok\":true,\"schemaVersion\":8,\"appliedVersions\":[1,2,3,4,5,6,7,8]}\n",'stderr'=>''],
         pmrRun($completedEnvironment),
         $label . ' fixture setup',
     );
     $completedConnection = pmrDb($completedDatabase);
     pmrReplaceCompletedV4Checks($completedConnection, $completedPrefix, $fixture['capability'], $fixture['engineer']);
     $completedConnection->query("INSERT INTO {$completedPrefix}fm2_process_user_capabilities VALUES (700{$completedIndex},'construction_control_engineer','Sentinel engineer')");
     $completedBefore = pmrState($completedConnection);
     if ($fixture['accepted']) {
         pmrResult(
             ['exitCode'=>0,'stdout'=>"{\"ok\":true,\"schemaVersion\":8,\"appliedVersions\":[]}\n",'stderr'=>''],
             pmrRun($completedEnvironment),
             $label . ' must remain a completed-v6 no-op after the v4 compatibility check',
         );
     } else {
         $completedMarker = dirname(__DIR__,2).'/.test-artifacts/pmr-v4-nearmatch-'.$completedIndex.'-'.$tok;
         $files[] = $completedMarker;
         $instrumentedEnvironment = $completedEnvironment + ['PMR_V4_INVOCATION_MARKER' => $completedMarker];
         pmrResult(
             ['exitCode'=>2,'stdout'=>"{\"ok\":false,\"reason\":\"SCHEMA_MIGRATION_CONFLICT\",\"schemaVersion\":3}\n",'stderr'=>''],
             pmrRun($instrumentedEnvironment, [], '', ['-d','auto_prepend_file='.dirname(__DIR__).'/Support/forbid_v4_migration_invocation.php']),
             $label . ' must fail at the v3 boundary',
             [$completedMarker, $completedDatabase, $completedPrefix],
         );
         assertSameValue(false, is_file($completedMarker), $label . ' must not invoke the v4 seam after v3 rejection.');
     }
     assertSameValue($completedBefore, pmrState($completedConnection), $label . ' must preserve the exact schema and sentinel row.');
     $completedConnection->close();
 }

 $db='t_pmr_c_'.$tok;
 $dbs[]=$db;
 $admin->query("CREATE DATABASE `{$db}` DEFAULT CHARSET=utf8mb4");
 $c=pmrDb($db);
 $c->query('CREATE TABLE pilot_fm2_process_user_capabilities (unexpected_column INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
 $c->query('INSERT INTO pilot_fm2_process_user_capabilities VALUES (731)');
 $c->query('CREATE TABLE legacy_sentinel (payload VARCHAR(80) NOT NULL) ENGINE=InnoDB');
 $c->query("INSERT INTO legacy_sentinel VALUES ('legacy_{$tok}')");
 $c->query('CREATE TABLE unrelated_sentinel (payload VARCHAR(80) NOT NULL) ENGINE=InnoDB');
 $c->query("INSERT INTO unrelated_sentinel VALUES ('other_{$tok}')");
 $before=pmrState($c);
 $v4Marker=dirname(__DIR__,2).'/.test-artifacts/pmr-v4-invoked-'.$tok;
 $files[]=$v4Marker;
 $ce=array_replace($env,['FMONITOR_DB_NAME'=>$db,'PMR_V4_INVOCATION_MARKER'=>$v4Marker]);
 pmrResult(
     ['exitCode'=>2,'stdout'=>"{\"ok\":false,\"reason\":\"SCHEMA_MIGRATION_CONFLICT\",\"schemaVersion\":3}\n",'stderr'=>''],
     pmrRun($ce,[], '', ['-d','auto_prepend_file='.dirname(__DIR__).'/Support/forbid_v4_migration_invocation.php']),
     'example C',
     [$db,'pilot_','unexpected_column','legacy_'.$tok,$v4Marker],
 );
 assertSameValue(false,is_file($v4Marker),'A v3 conflict must stop before invoking the independently instrumented v4 seam.');
 $after=pmrState($c);
 foreach(['pilot_fm2_process_user_capabilities','legacy_sentinel','unrelated_sentinel'] as $t)assertSameValue($before[$t],$after[$t],$t.' unchanged');
 assertSameValue(8,count(pmrRows($c,"SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE 'pilot_fm2\\_%'")),'seven v1/v2 tables plus conflicting v3 table remain and v4 stops');
 $c->query('DROP TABLE pilot_fm2_process_user_capabilities');
 $recoveryEnvironment=array_diff_key($ce,['PMR_V4_INVOCATION_MARKER'=>true]);
 pmrResult(['exitCode'=>0,'stdout'=>"{\"ok\":true,\"schemaVersion\":8,\"appliedVersions\":[3,4,5,6,7,8]}\n",'stderr'=>''],pmrRun($recoveryEnvironment),'recovery');
 pmrCatalog($c,'pilot_');
 $state=pmrState($c);
 foreach(['legacy_sentinel','unrelated_sentinel'] as $t)assertSameValue($before[$t],$state[$t],$t.' survives recovery');
 $c->close();

 $db='t_pmr_fail_'.$tok;$dbs[]=$db;$u='pmr_limited_'.$tok;$users[]=$u;$pw='limited_'.$tok;$admin->query("CREATE DATABASE `{$db}` DEFAULT CHARSET=utf8mb4");$admin->query("CREATE USER `{$u}`@'%' IDENTIFIED BY '{$pw}'");$admin->query("GRANT SELECT,CREATE ON `{$db}`.* TO `{$u}`@'%'");$fe=array_replace($env,['FMONITOR_DB_NAME'=>$db,'FMONITOR_DB_USER'=>$u,'FMONITOR_DB_PASSWORD'=>$pw]);pmrResult(['exitCode'=>70,'stdout'=>"{\"ok\":false,\"reason\":\"MIGRATION_FAILED\"}\n",'stderr'=>''],pmrRun($fe),'unexpected DDL failure',[$db,$u,$pw,'pilot_','ALTER','denied']);$c=pmrDb($db);assertSameValue(8,count(pmrRows($c,"SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE 'pilot_fm2\\_%'")),'v1-v3 DDL remains');$checks=implode(' ',array_column(pmrRows($c,"SELECT CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='pilot_fm2_process_user_capabilities'"),'CHECK_CLAUSE'));assertSameValue(false,str_contains($checks,'installation.open'),'v4 did not complete');$c->close();

 $db='t_pmr_empty_'.$tok;$dbs[]=$db;$u='pmr_empty_'.$tok;$users[]=$u;$admin->query("CREATE DATABASE `{$db}` DEFAULT CHARSET=utf8mb4");$admin->query("CREATE USER `{$u}`@'%' IDENTIFIED BY ''");$admin->query("GRANT ALL PRIVILEGES ON `{$db}`.* TO `{$u}`@'%'");$ee=array_replace($env,['FMONITOR_DB_NAME'=>$db,'FMONITOR_DB_USER'=>$u,'FMONITOR_DB_PASSWORD'=>'','FMONITOR_PROCESS_TABLE_PREFIX'=>'']);pmrResult(['exitCode'=>0,'stdout'=>"{\"ok\":true,\"schemaVersion\":8,\"appliedVersions\":[1,2,3,4,5,6,7,8]}\n",'stderr'=>''],pmrRun($ee,['ignored-'.$tok],'ignored-'.$tok),'valid empty password/prefix');$c=pmrDb($db);pmrCatalog($c,'');$c->close();echo "PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract\n";
}finally{foreach($dbs as $db)$admin->query("DROP DATABASE IF EXISTS `{$db}`");foreach($users as $u)$admin->query("DROP USER IF EXISTS `{$u}`@'%'");foreach($files as $file)if(is_file($file))unlink($file);$admin->close();}
