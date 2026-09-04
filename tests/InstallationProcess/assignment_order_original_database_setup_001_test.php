<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/AssignmentOrderOriginalDatabaseSetupV1.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigration;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigrationStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationDatabaseFixture;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationFixtureConflict;
use FMonitor2\Tests\Support\AssignmentOrderOriginalDatabaseSetupV1 as Contract;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v12, setup Gate 2.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$user = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$password = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$token = bin2hex(random_bytes(6));
$databases = array_map(static fn (string $axis): string => "t_aoou_{$axis}_{$token}", ['clean','partial','populated','conflict','fixture']);
$prefix = 'aoou_';
$admin = new mysqli($host, $user, $password, '', $port);
$admin->set_charset('utf8mb4');

$quote = static fn (string $name): string => '`' . str_replace('`', '``', $name) . '`';
$connect = static function (string $database) use ($host, $port, $user, $password): mysqli {
    $db = new mysqli($host, $user, $password, $database, $port);
    $db->set_charset('utf8mb4');
    return $db;
};
$tableNames = static function (mysqli $db, string $like = '%'): array {
    $statement = $db->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE ? ORDER BY BINARY TABLE_NAME');
    $statement->bind_param('s', $like); $statement->execute();
    return array_column($statement->get_result()->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');
};
$columns = static function (mysqli $db, string $table): array {
    $statement = $db->prepare('SELECT COLUMN_NAME,LOWER(COLUMN_TYPE) COLUMN_TYPE,IS_NULLABLE,CHARACTER_SET_NAME,COLLATION_NAME,COLUMN_DEFAULT,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');
    $statement->bind_param('s', $table); $statement->execute();
    return array_map(static function(array$row):array{if($row['IS_NULLABLE']==='YES'&&$row['COLUMN_DEFAULT']==='NULL')$row['COLUMN_DEFAULT']=null;return array_values($row);},$statement->get_result()->fetch_all(MYSQLI_ASSOC));
};
$keys = static function (mysqli $db, string $table): array {
    $statement = $db->prepare("SELECT INDEX_NAME,NON_UNIQUE,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ',') COLUMNS FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? GROUP BY INDEX_NAME,NON_UNIQUE");
    $statement->bind_param('s', $table); $statement->execute();
    $rows = array_map(static fn (array $row): array => [$row['INDEX_NAME']==='PRIMARY'?'PRIMARY':((int)$row['NON_UNIQUE']===0?'UNIQUE':'INDEX'),$row['COLUMNS']], $statement->get_result()->fetch_all(MYSQLI_ASSOC));
    sort($rows); return $rows;
};
$foreignKeys = static function (mysqli $db, string $table, string $prefix): array {
    $statement = $db->prepare('SELECT k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.TABLE_NAME=? AND k.REFERENCED_TABLE_NAME IS NOT NULL');
    $statement->bind_param('s', $table); $statement->execute();
    $rows = array_map(static fn(array $row):array => [$row['COLUMN_NAME'],substr($row['REFERENCED_TABLE_NAME'],strlen($prefix)),$row['REFERENCED_COLUMN_NAME'],$row['UPDATE_RULE'].'/'.$row['DELETE_RULE']], $statement->get_result()->fetch_all(MYSQLI_ASSOC));
    sort($rows); return $rows;
};
$checkCount = static function (mysqli $db, string $table): int {
    $statement = $db->prepare('SELECT COUNT(*) n FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_TYPE=\'CHECK\'');
    $statement->bind_param('s', $table); $statement->execute();
    return (int) $statement->get_result()->fetch_assoc()['n'];
};
$normalizeCheck=static function(string$value):string{$value=strtolower(str_replace(['`',' ',"\n","\r","\t"],'',$value));$value=str_replace("'[[:cntrl:]/\\\\\\\\]'","'[[:cntrl:]/\\\\]'",$value);$value=(string)preg_replace("/!\\(([^()]+)regexp('[^']*')\\)/","$1notregexp$2",$value);while(str_starts_with($value,'(')&&str_ends_with($value,')')){$depth=0;$wrap=true;for($i=0,$n=strlen($value);$i<$n;$i++){if($value[$i]==='(')$depth++;elseif($value[$i]===')')$depth--;if($depth===0&&$i<$n-1){$wrap=false;break;}}if(!$wrap)break;$value=substr($value,1,-1);}return$value;};
$checks = static function (mysqli $db, string $table) use ($normalizeCheck): array {
    $statement=$db->prepare('SELECT CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY BINARY CHECK_CLAUSE');
    $statement->bind_param('s',$table);$statement->execute();
    $result=array_map(static fn(array$row):string=>$normalizeCheck($row['CHECK_CLAUSE']),$statement->get_result()->fetch_all(MYSQLI_ASSOC));sort($result,SORT_STRING);return$result;
};
$tableProperties = static function (mysqli $db, string $table): array {
    $statement = $db->prepare('SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $statement->bind_param('s', $table); $statement->execute();
    return array_values($statement->get_result()->fetch_assoc());
};
$snapshot = static function (mysqli $db) use ($tableNames,$columns,$keys,$foreignKeys,$checks,$tableProperties): string {
    $structure=[];
    foreach($tableNames($db)as$table){
        $structure[$table]=[
            'table'=>$tableProperties($db,$table),
            'columns'=>$columns($db,$table),
            'keys'=>$keys($db,$table),
            'foreignKeys'=>$foreignKeys($db,$table,''),
            'checks'=>$checks($db,$table),
        ];
    }
    return json_encode($structure,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
};
$stateSnapshot = static function (mysqli $db) use ($tableNames, $quote, $snapshot): string {
    $rows = [];
    foreach ($tableNames($db) as $table) {
        $tableRows = $db->query('SELECT * FROM ' . $quote($table))->fetch_all(MYSQLI_ASSOC);
        foreach ($tableRows as &$row) ksort($row, SORT_STRING);
        unset($row);
        usort($tableRows, static fn (array $left, array $right): int => json_encode($left, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) <=> json_encode($right, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $rows[$table] = $tableRows;
    }
    return json_encode(['schema' => $snapshot($db), 'rows' => $rows], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
};

$created = [];
try {
    foreach ($databases as $database) {
        assertSameValue(1, preg_match('/^t_aoou_[a-z]+_[0-9a-f]{12}$/D', $database), 'Database cleanup target is independently bounded.');
        $admin->query('CREATE DATABASE ' . $quote($database) . ' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $created[] = $database;
    }
    $preflight = $connect($databases[0]);
    assertSameValue('1', (string) $preflight->query('SELECT 1 ready')->fetch_assoc()['ready'], 'Isolated MariaDB setup is live before intended RED.');
    $preflight->query(Contract::rootsDdl('probe_'));
    $roundTripExpected=Contract::checks()[Contract::TABLES[0]];sort($roundTripExpected,SORT_STRING);
    assertSameValue($roundTripExpected,$checks($preflight,'probe_'.Contract::TABLES[0]),'Approved roots DDL CHECKs survive MariaDB SQL-literal round trip.');
    $wrongDdl=str_replace("'[[:cntrl:]/\\\\\\\\]'","'[[:cntrl:]/]'",Contract::rootsDdl('wrong_'));
    assertSameValue(false,$wrongDdl===Contract::rootsDdl('wrong_'),'Wrong-pattern round-trip fixture changes the exact regex only.');
    $preflight->query($wrongDdl);
    assertSameValue(false,$roundTripExpected===$checks($preflight,'wrong_'.Contract::TABLES[0]),'Same-count wrong regex remains observable after MariaDB round trip.');
    $preflight->query("CREATE TABLE `default_probe` (implicit_null VARCHAR(20) NULL, explicit_null VARCHAR(20) NULL DEFAULT NULL, wrong_default VARCHAR(20) NULL DEFAULT 'wrong') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $defaultProbe=$columns($preflight,'default_probe');
    assertSameValue(null,$defaultProbe[0][5],'Implicit nullable NULL default canonicalizes to PHP null.');
    assertSameValue(null,$defaultProbe[1][5],'Explicit nullable DEFAULT NULL canonicalizes to the same PHP null.');
    assertSameValue(true,is_string($defaultProbe[2][5])&&$defaultProbe[2][5]!==''&&$defaultProbe[2][5]!=='NULL','Non-null wrong default remains observable and distinct.');
    $preflight->close();

    foreach (Contract::PROJECTIONS as $name => [$expectedHash, $literal]) {
        assertSameValue($expectedHash, hash('sha256', $literal), "{$name} is independently derived from its literal projection.");
    }
    $approvedNotRegexp="root_original_idnotregexp'[[:cntrl:]/\\\\]'";
    assertSameValue($approvedNotRegexp,$normalizeCheck("!(`root_original_id` REGEXP '[[:cntrl:]/\\\\]')"),'MariaDB negated REGEXP normalizes to approved NOT REGEXP oracle.');
    assertSameValue(false,$approvedNotRegexp===$normalizeCheck("!(`root_original_id` REGEXP '[[:cntrl:]/]')"),'Normalizer preserves a materially wrong REGEXP pattern.');
    assertSameValue(true,function_exists('pcntl_fork')&&function_exists('pcntl_waitpid')&&function_exists('pcntl_wexitstatus')&&function_exists('posix_kill')&&defined('SIGKILL'),'Serializable contention verifier requires process control.');
    if (!class_exists(AssignmentOrderOriginalSchemaMigration::class)) {
        throw new TestFailure('INTENDED_RED: approved AssignmentOrderOriginalSchemaMigration production seam is absent.');
    }
    if (!class_exists(AssignmentOrderOriginalVerificationDatabaseFixture::class)) {
        throw new TestFailure('INTENDED_RED: approved AssignmentOrderOriginalVerificationDatabaseFixture production seam is absent.');
    }

    $clean = $connect($databases[0]);
    $result = AssignmentOrderOriginalSchemaMigration::apply($clean, $prefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::APPLIED, $result->status(), 'Clean schema applies.');
    assertSameValue(Contract::VERSION, $result->schemaVersion(), 'Migration reports exact version 1.');
    assertSameValue(Contract::TABLES, $result->affectedTables(), 'Clean migration reports exact manifest order.');
    $expectedOwnedTables = array_map(static fn ($name) => $prefix . $name, Contract::TABLES);
    sort($expectedOwnedTables, SORT_STRING);
    assertSameValue($expectedOwnedTables, $tableNames($clean, $prefix . '%'), 'Only exact owned manifest exists.');
    foreach (Contract::columnManifest() as $table => $expected) {
        assertSameValue($expected, $columns($clean, $prefix . $table), "Exact ordered columns for {$table}.");
        assertSameValue(Contract::keys()[$table], $keys($clean, $prefix . $table), "Exact PK/unique/index column order for {$table}.");
        assertSameValue(Contract::foreignKeys()[$table], $foreignKeys($clean, $prefix . $table, $prefix), "Exact FK columns/actions for {$table}.");
        assertSameValue(Contract::checkCounts()[$table], $checkCount($clean, $prefix . $table), "Exact complete CHECK expression count for {$table}.");
        $expectedChecks=Contract::checks()[$table];sort($expectedChecks,SORT_STRING);
        assertSameValue($expectedChecks,$checks($clean,$prefix.$table),"Every normalized CHECK expression for {$table} is exact.");
        assertSameValue(['InnoDB','utf8mb4_unicode_ci'], $tableProperties($clean, $prefix . $table), "Exact engine and database-default collation for {$table}.");
    }
    $cleanBeforeRepeat = $snapshot($clean);
    $repeat = AssignmentOrderOriginalSchemaMigration::apply($clean, $prefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::UNCHANGED, $repeat->status(), 'Exact repeat is unchanged.');
    assertSameValue([], $repeat->affectedTables(), 'Exact repeat has empty affected list.');
    assertSameValue($cleanBeforeRepeat, $snapshot($clean), 'Repeat preserves exact schema bytes.');
    $clean->close();

    $partial = $connect($databases[1]);
    $partial->query(Contract::rootsDdl($prefix));
    $partialBefore = $columns($partial, $prefix . Contract::TABLES[0]);
    $partialResult = AssignmentOrderOriginalSchemaMigration::apply($partial, $prefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::APPLIED, $partialResult->status(), 'Leading compatible partial reconciles.');
    assertSameValue(array_slice(Contract::TABLES, 1), $partialResult->affectedTables(), 'Only missing trailing tables are affected.');
    assertSameValue($partialBefore, $columns($partial, $prefix . Contract::TABLES[0]), 'Existing leading table is preserved.');
    $partial->close();

    $populated = $connect($databases[2]);
    AssignmentOrderOriginalSchemaMigration::apply($populated, $prefix);
    $populated->query("INSERT INTO `{$prefix}fm2_assignment_order_original_roots` VALUES ('pop-root',77,88,'pop-revision','pop-composition','" . str_repeat('1',64) . "','2026-09-02 09:00:00.000000')");
    $populated->query("INSERT INTO `{$prefix}fm2_assignment_order_original_revisions` VALUES ('pop-revision','pop-root',1,NULL,'2026-09-01','2026-09-02 09:00:00.000000',18,'" . str_repeat('2',64) . "',327,'pop-content',NULL,'00000000-0000-4000-8000-000000000099','" . str_repeat('3',64) . "','assignment_order_original_accepted')");
    $rowsBefore = json_encode([$populated->query("SELECT * FROM `{$prefix}fm2_assignment_order_original_roots`")->fetch_all(MYSQLI_ASSOC),$populated->query("SELECT * FROM `{$prefix}fm2_assignment_order_original_revisions`")->fetch_all(MYSQLI_ASSOC)], JSON_THROW_ON_ERROR);
    $populatedResult = AssignmentOrderOriginalSchemaMigration::apply($populated, $prefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::UNCHANGED, $populatedResult->status(), 'Populated exact schema is unchanged.');
    $rowsAfter = json_encode([$populated->query("SELECT * FROM `{$prefix}fm2_assignment_order_original_roots`")->fetch_all(MYSQLI_ASSOC),$populated->query("SELECT * FROM `{$prefix}fm2_assignment_order_original_revisions`")->fetch_all(MYSQLI_ASSOC)], JSON_THROW_ON_ERROR);
    assertSameValue($rowsBefore, $rowsAfter, 'Populated exact rows remain byte-identical.');
    $populated->close();

    $conflict = $connect($databases[3]);
    $conflict->query(Contract::rootsDdl($prefix));
    $conflict->query("ALTER TABLE `{$prefix}fm2_assignment_order_original_roots` ADD CONSTRAINT verifier_extra_semantic_check CHECK (installation_case_id > 0)");
    $conflict->query("CREATE TABLE `{$prefix}fm2_assignment_order_original_audits` (wrong INT NOT NULL) ENGINE=InnoDB");
    $conflictBefore = $snapshot($conflict);
    $conflictResult = AssignmentOrderOriginalSchemaMigration::apply($conflict, $prefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::CONFLICT, $conflictResult->status(), 'Near-equivalent and gross incompatible owned tables conflict together.');
    $expectedConflicts=[Contract::TABLES[4],Contract::TABLES[0]];sort($expectedConflicts,SORT_STRING);
    assertSameValue($expectedConflicts, $conflictResult->affectedTables(), 'Conflict returns every incompatible logical name in binary order.');
    assertSameValue($conflictBefore, $snapshot($conflict), 'Conflict beside five missing trailing tables performs zero DDL.');

    $nearPrefix='near_';AssignmentOrderOriginalSchemaMigration::apply($conflict,$nearPrefix);
    $conflict->query("ALTER TABLE `{$nearPrefix}fm2_assignment_order_original_roots` MODIFY current_revision_id VARCHAR(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'wrong-default'");
    $nearBefore=$snapshot($conflict);$nearResult=AssignmentOrderOriginalSchemaMigration::apply($conflict,$nearPrefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::CONFLICT,$nearResult->status(),'Opaque-ID collation/default near mismatch conflicts.');
    assertSameValue([Contract::TABLES[0]],$nearResult->affectedTables(),'Collation/default mismatch identifies roots.');
    assertSameValue($nearBefore,$snapshot($conflict),'Collation/default conflict performs zero DDL.');

    $checkPrefix='check_';AssignmentOrderOriginalSchemaMigration::apply($conflict,$checkPrefix);
    $checkName=$conflict->query("SELECT CONSTRAINT_NAME FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$checkPrefix}fm2_assignment_order_original_roots' AND CHECK_CLAUSE LIKE '%composition_sha256%' LIMIT 1")->fetch_assoc()['CONSTRAINT_NAME']??null;
    assertSameValue(true,is_string($checkName)&&preg_match('/^[A-Za-z0-9_$]{1,64}$/D',$checkName)===1,'Material CHECK sensitivity resolves one safe generated name.');
    $conflict->query("ALTER TABLE `{$checkPrefix}fm2_assignment_order_original_roots` DROP CONSTRAINT `{$checkName}`, ADD CONSTRAINT verifier_wrong_hash_check CHECK (composition_sha256 REGEXP '^[0-9A-F]{64}$')");
    $checkBefore=$snapshot($conflict);$checkResult=AssignmentOrderOriginalSchemaMigration::apply($conflict,$checkPrefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::CONFLICT,$checkResult->status(),'Wrong hash CHECK expression conflicts without count change.');
    assertSameValue([Contract::TABLES[0]],$checkResult->affectedTables(),'Wrong CHECK identifies roots.');
    assertSameValue($checkBefore,$snapshot($conflict),'Wrong CHECK conflict performs zero DDL.');
    $conflict->close();

    $fixture = $connect($databases[4]);
    FMonitor2\InstallationProcess\ProductionProcessSchemaMigration::apply($fixture, $prefix);
    FMonitor2\InstallationProcess\IdentityAccessSchemaMigration::apply($fixture, $prefix);
    FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration::apply($fixture, $prefix);
    FMonitor2\InstallationProcess\ProcessCommandCapabilitiesSchemaMigration::apply($fixture, $prefix);
    AssignmentOrderOriginalSchemaMigration::apply($fixture, $prefix);
    $fixtureBaseline = $stateSnapshot($fixture);
    $contendedFixtureCall=static function(string$mode,string$lockSql)use($fixture,$connect,$databases,$prefix):void{
        $pair=stream_socket_pair(STREAM_PF_UNIX,STREAM_SOCK_STREAM,STREAM_IPPROTO_IP);
        if($pair===false)throw new TestFailure('SETUP_FAILURE: contention socket pair.');
        [$parentPipe,$childPipe]=$pair;$childPid=null;$transaction=false;$observer=null;
        try{
            $fixture->query('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');$fixture->begin_transaction();$transaction=true;$fixture->query($lockSql);$blockingConnectionId=(int)$fixture->thread_id;
            $childPid=pcntl_fork();if($childPid===-1)throw new TestFailure('SETUP_FAILURE: contention fork.');
            if($childPid===0){fclose($parentPipe);try{$childDb=$connect($databases[4]);stream_set_timeout($childPipe,5);fwrite($childPipe,"READY {$mode} ".(int)$childDb->thread_id."\n");fflush($childPipe);$enter=fgets($childPipe);if($enter!=="ENTER {$mode}\n")throw new RuntimeException('barrier');fwrite($childPipe,"ENTERED {$mode}\n");fflush($childPipe);if($mode==='seed')AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA($childDb,$prefix);else AssignmentOrderOriginalVerificationDatabaseFixture::cleanupExampleA($childDb,$prefix);$childDb->close();fwrite($childPipe,"OK {$mode}\n");fclose($childPipe);exit(0);}catch(Throwable){fwrite($childPipe,"ERR {$mode}\n");fclose($childPipe);exit(70);}}
            fclose($childPipe);stream_set_blocking($parentPipe,true);stream_set_timeout($parentPipe,5);$ready=fgets($parentPipe);$readyMeta=stream_get_meta_data($parentPipe);assertSameValue(false,$readyMeta['timed_out'],"{$mode} READY is bounded.");assertSameValue(1,preg_match('/^READY '.preg_quote($mode,'/').' ([1-9][0-9]*)\n$/D',(string)$ready,$readyMatch),"{$mode} child reports exact MariaDB connection identity.");$childConnectionId=(int)$readyMatch[1];fwrite($parentPipe,"ENTER {$mode}\n");fflush($parentPipe);$entered=fgets($parentPipe);$enteredMeta=stream_get_meta_data($parentPipe);assertSameValue(false,$enteredMeta['timed_out'],"{$mode} ENTERED is bounded.");assertSameValue("ENTERED {$mode}\n",$entered,"{$mode} child entered the controlled fixture attempt.");
            $observer=$connect($databases[4]);$deadline=hrtime(true)+5_000_000_000;$waitRow=null;
            do{
                $wait=$observer->prepare("SELECT requesting.trx_state,requesting.trx_isolation_level,requesting.trx_query,process.STATE process_state FROM information_schema.INNODB_LOCK_WAITS waits JOIN information_schema.INNODB_TRX requesting ON requesting.trx_id=waits.requesting_trx_id JOIN information_schema.INNODB_TRX blocking ON blocking.trx_id=waits.blocking_trx_id JOIN information_schema.PROCESSLIST process ON process.ID=requesting.trx_mysql_thread_id WHERE requesting.trx_mysql_thread_id=? AND blocking.trx_mysql_thread_id=?");
                $wait->bind_param('ii',$childConnectionId,$blockingConnectionId);$wait->execute();$waitRow=$wait->get_result()->fetch_assoc();$wait->close();if(is_array($waitRow))break;usleep(10_000);
            }while(hrtime(true)<$deadline);
            assertSameValue(true,is_array($waitRow),"{$mode} exact child connection is independently observed in MariaDB lock wait.");
            assertSameValue('LOCK WAIT',$waitRow['trx_state'],"{$mode} transaction is actually waiting.");assertSameValue('SERIALIZABLE',$waitRow['trx_isolation_level'],"{$mode} public fixture transaction uses SERIALIZABLE.");
            $expectedLockedTable=$mode==='seed'?$prefix.'fm2_pilot_users':$prefix.'fm2_process_tasks';assertSameValue(true,str_contains((string)$waitRow['trx_query'],$expectedLockedTable),"{$mode} waits inside the exact fixture identity table operation.");
            stream_set_blocking($parentPipe,false);$read=[$parentPipe];$write=$except=[];
            assertSameValue(0,stream_select($read,$write,$except,0,200000),"{$mode} waits behind exact SERIALIZABLE identity lock.");
            $fixture->commit();$transaction=false;stream_set_blocking($parentPipe,true);stream_set_timeout($parentPipe,5);$line=fgets($parentPipe);$meta=stream_get_meta_data($parentPipe);
            assertSameValue(false,$meta['timed_out'],"{$mode} contention child is bounded.");assertSameValue("OK {$mode}\n",$line,"{$mode} succeeds after lock release.");
            $status=0;assertSameValue($childPid,pcntl_waitpid($childPid,$status),"{$mode} child is reaped.");assertSameValue(0,pcntl_wexitstatus($status),"{$mode} child exits cleanly.");$childPid=null;
        }finally{
            if($transaction){try{$fixture->rollback();}catch(Throwable){}}
            if($observer instanceof mysqli){try{$observer->close();}catch(Throwable){}}
            foreach([$parentPipe,$childPipe]as$pipe)if(is_resource($pipe))fclose($pipe);
            if(is_int($childPid)&&$childPid>0){@posix_kill($childPid,SIGKILL);pcntl_waitpid($childPid,$status);}
        }
    };
    $contendedFixtureCall('seed',"SELECT user_id FROM `{$prefix}fm2_pilot_users` WHERE user_id=18 FOR UPDATE");
    assertSameValue(
        [
            ['user_id'=>'18','full_name'=>'Тестовый Оператор ФКР','email'=>'test-fkr@example.invalid','status'=>'1','activation_state'=>'active','session_version'=>'1','source_updated_at'=>'2026-09-02T09:00:00Z'],
            ['user_id'=>'31','full_name'=>'Тестовый Инженер','email'=>'test-engineer@example.invalid','status'=>'1','activation_state'=>'active','session_version'=>'1','source_updated_at'=>'2026-09-02T09:00:00Z'],
        ],
        $fixture->query("SELECT user_id,full_name,email,status,activation_state,session_version,source_updated_at FROM `{$prefix}fm2_pilot_users` WHERE user_id IN (18,31) ORDER BY user_id")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact fictional users without credentials.',
    );
    assertSameValue(
        [
            ['role_id'=>'5301','code'=>'fkr_operator','name'=>'Сотрудник ФКР','status'=>'1','source_updated_at'=>'2026-09-02T09:00:00Z'],
            ['role_id'=>'5302','code'=>'control_engineer','name'=>'Инженер строительного контроля','status'=>'1','source_updated_at'=>'2026-09-02T09:00:00Z'],
        ],
        $fixture->query("SELECT role_id,code,name,status,source_updated_at FROM `{$prefix}fm2_pilot_roles` WHERE role_id IN (5301,5302) ORDER BY role_id")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact active role identities.',
    );
    assertSameValue(
        [['user_id'=>'18','role_id'=>'5301','assigned_at'=>'2026-09-02T09:00:00Z','assigned_by_user_id'=>null],['user_id'=>'31','role_id'=>'5302','assigned_at'=>'2026-09-02T09:00:00Z','assigned_by_user_id'=>null]],
        $fixture->query("SELECT user_id,role_id,assigned_at,assigned_by_user_id FROM `{$prefix}fm2_pilot_user_roles` WHERE user_id IN (18,31) ORDER BY user_id,role_id")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact actor and engineer role assignments.',
    );
    assertSameValue(0,(int)$fixture->query("SELECT COUNT(*) n FROM `{$prefix}fm2_pilot_auth_credentials` WHERE user_id IN (18,31)")->fetch_assoc()['n'],'Fictional fixture users have no credential.');
    assertSameValue(
        [
            ['user_id'=>'18','capability'=>'assignment_order.original.correct','position_snapshot'=>null],
            ['user_id'=>'18','capability'=>'assignment_order.original.upload','position_snapshot'=>null],
        ],
        $fixture->query("SELECT user_id,capability,position_snapshot FROM `{$prefix}fm2_process_user_capabilities` WHERE user_id=18 ORDER BY BINARY capability")->fetch_all(MYSQLI_ASSOC),
        'Actor has exactly upload and correct grants.',
    );
    assertSameValue(
        [['user_id'=>'31','capability'=>'construction_control_engineer','position_snapshot'=>'Инженер строительного контроля']],
        $fixture->query("SELECT user_id,capability,position_snapshot FROM `{$prefix}fm2_process_user_capabilities` WHERE user_id=31 ORDER BY BINARY capability")->fetch_all(MYSQLI_ASSOC),
        'Engineer has exact configured process capability and position.',
    );
    assertSameValue(
        [
            ['id'=>'4512','legacy_installation_object_id'=>'94512','process_state'=>'prepared','actual_start_date'=>null,'opened_at'=>null,'opened_by_user_id'=>null,'created_at'=>'2026-09-02T09:00:00Z','updated_at'=>'2026-09-02T09:00:00Z','lock_version'=>'1'],
            ['id'=>'9999','legacy_installation_object_id'=>'99999','process_state'=>'fixture-decoy-v1','actual_start_date'=>null,'opened_at'=>null,'opened_by_user_id'=>null,'created_at'=>'2026-09-02T09:00:00Z','updated_at'=>'2026-09-02T09:00:00Z','lock_version'=>'1'],
        ],
        $fixture->query("SELECT id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version FROM `{$prefix}fm2_installation_cases` WHERE id IN (4512,9999) ORDER BY id")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact target and unrelated decoy cases.',
    );
    assertSameValue(
        [['id'=>'81','installation_case_id'=>'4512','version_no'=>'1','kind'=>'initial','status'=>'prepared','order_date'=>'2026-09-01','registration_number'=>null,'registered_at'=>null,'registration_actor_type'=>null,'registration_actor_id'=>null,'registration_source'=>null,'external_registration_id'=>null,'control_engineer_user_id'=>'31','control_engineer_fio_snapshot'=>'Тестовый Инженер','control_engineer_position_snapshot'=>'Инженер строительного контроля','organization_form'=>'brigade','previous_assignment_order_id'=>null,'object_address_snapshot'=>'Тестовая улица, 1','entrance_snapshot'=>'1','object_registration_number_snapshot'=>'TEST-4512','planned_start_date_snapshot'=>'2026-10-01','planned_finish_date_snapshot'=>'2026-10-31','pto_act_date_snapshot'=>null,'prepared_at'=>'2026-09-01T09:00:00Z','prepared_by_user_id'=>'18']],
        $fixture->query("SELECT * FROM `{$prefix}fm2_assignment_orders` WHERE id=81")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact Example-A order.',
    );
    assertSameValue(
        [
            ['installer_tab_id'=>'7001','fio_snapshot'=>'Тестовый Монтажник 7001','position_snapshot'=>'Монтажник','employment_status_snapshot'=>'employed','employed_from_snapshot'=>'2026-01-01','employed_to_snapshot'=>null,'workforce_source_snapshot'=>'TEST-USER','workforce_source_updated_at_snapshot'=>'2026-09-01T00:00:00Z','valid_from'=>'2026-09-01','valid_to'=>null,'change_action'=>'assign'],
            ['installer_tab_id'=>'7002','fio_snapshot'=>'Тестовый Монтажник 7002','position_snapshot'=>'Монтажник','employment_status_snapshot'=>'employed','employed_from_snapshot'=>'2026-01-01','employed_to_snapshot'=>null,'workforce_source_snapshot'=>'TEST-USER','workforce_source_updated_at_snapshot'=>'2026-09-01T00:00:00Z','valid_from'=>'2026-09-01','valid_to'=>null,'change_action'=>'assign'],
        ],
        $fixture->query("SELECT installer_tab_id,fio_snapshot,position_snapshot,employment_status_snapshot,employed_from_snapshot,employed_to_snapshot,workforce_source_snapshot,workforce_source_updated_at_snapshot,valid_from,valid_to,change_action FROM `{$prefix}fm2_order_installers` WHERE assignment_order_id=81 ORDER BY installer_tab_id")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact installer snapshots in binary identity order.',
    );
    assertSameValue(
        [['id'=>'9001','installation_case_id'=>'4512','task_type'=>'assignment_order_original_upload','assignee_user_id'=>null,'assignee_role'=>'fkr_operator','due_date'=>null,'status'=>'open','completed_at'=>null,'completed_by_user_id'=>null,'created_at'=>'2026-09-02T09:00:00Z']],
        $fixture->query("SELECT * FROM `{$prefix}fm2_process_tasks` WHERE id=9001")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact open upload task.',
    );
    $projectionRows=[];
    $caseRow=$fixture->query("SELECT id,process_state,actual_start_date,opened_at,opened_by_user_id FROM `{$prefix}fm2_installation_cases` WHERE id=4512")->fetch_assoc();
    $orderRow=$fixture->query("SELECT id,installation_case_id,control_engineer_user_id FROM `{$prefix}fm2_assignment_orders` WHERE id=81")->fetch_assoc();
    $installerIds=array_map('intval',array_column($fixture->query("SELECT installer_tab_id FROM `{$prefix}fm2_order_installers` WHERE assignment_order_id=81 ORDER BY installer_tab_id")->fetch_all(MYSQLI_ASSOC),'installer_tab_id'));
    $projectionRows['orderCompositionSha256']=['caseId'=>(int)$orderRow['installation_case_id'],'compositionIdentity'=>'composition-81-v1','engineerUserId'=>(int)$orderRow['control_engineer_user_id'],'installers'=>$installerIds,'orderId'=>(int)$orderRow['id']];
    $projectionRows['caseSha256']=['actualStartDate'=>$caseRow['actual_start_date'],'caseId'=>(int)$caseRow['id'],'processState'=>$caseRow['process_state']];
    $projectionRows['openingSha256']=['actualStartDate'=>$caseRow['actual_start_date'],'openedAt'=>$caseRow['opened_at'],'openedByUserId'=>$caseRow['opened_by_user_id']===null?null:(int)$caseRow['opened_by_user_id']];
    $tasks=array_map(static fn(array$row):array=>['assigneeRole'=>$row['assignee_role'],'status'=>$row['status'],'taskId'=>(int)$row['id'],'taskType'=>$row['task_type']],$fixture->query("SELECT id,task_type,assignee_role,status FROM `{$prefix}fm2_process_tasks` WHERE installation_case_id=4512 ORDER BY id")->fetch_all(MYSQLI_ASSOC));
    $projectionRows['tasksSha256']=['items'=>$tasks];
    $available=$caseRow['process_state']==='working'&&$caseRow['actual_start_date']!==null&&$caseRow['opened_at']!==null&&$caseRow['opened_by_user_id']!==null;
    $projectionRows['checklistSha256']=['items'=>[['availability'=>$available?'available':'blocked_until_opening','checklistIdentity'=>'installation-case-'.(int)$caseRow['id']]]];
    $decoys=array_map(static fn(array$row):array=>['caseId'=>(int)$row['id'],'marker'=>$row['process_state']],$fixture->query("SELECT id,process_state FROM `{$prefix}fm2_installation_cases` WHERE id<>4512 ORDER BY id")->fetch_all(MYSQLI_ASSOC));
    $projectionRows['decoySha256']=['items'=>$decoys];
    foreach(Contract::PROJECTIONS as$name=>[$expectedDigest,$expectedJson]){
        $actualJson=json_encode($projectionRows[$name],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        assertSameValue($expectedJson,$actualJson,"{$name} canonical JSON is derived from seeded rows.");
        assertSameValue($expectedDigest,hash('sha256',$actualJson),"{$name} digest is derived from seeded rows.");
    }
    $seeded = $stateSnapshot($fixture);
    AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA($fixture, $prefix);
    assertSameValue($seeded, $stateSnapshot($fixture), 'Exact fixture repeat is a no-op.');
    foreach (Contract::TABLES as $originalTable) {
        $count = (int) $fixture->query('SELECT COUNT(*) n FROM ' . $quote($prefix . $originalTable))->fetch_assoc()['n'];
        assertSameValue(0, $count, "Fixture creates no original fact in {$originalTable}.");
    }

    $fixture->query("UPDATE `{$prefix}fm2_installation_cases` SET process_state='foreign-drift' WHERE id=4512");
    $drift = $stateSnapshot($fixture);
    try {
        AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA($fixture, $prefix);
        throw new TestFailure('Different occupied fixture identity must conflict.');
    } catch (AssignmentOrderOriginalVerificationFixtureConflict $error) {
        assertSameValue('AssignmentOrderOriginalVerificationFixtureConflict', $error->getMessage(), 'Conflict exception has fixed message.');
        assertSameValue(0, $error->getCode(), 'Conflict exception has fixed code.');
        assertSameValue(null, $error->getPrevious(), 'Conflict exception has no previous diagnostic.');
    }
    assertSameValue($drift, $stateSnapshot($fixture), 'Fixture conflict performs zero DML.');
    try {
        AssignmentOrderOriginalVerificationDatabaseFixture::cleanupExampleA($fixture,$prefix);
        throw new TestFailure('Cleanup must reject a drifted owned fixture row.');
    } catch (AssignmentOrderOriginalVerificationFixtureConflict $error) {
        assertSameValue('AssignmentOrderOriginalVerificationFixtureConflict',$error->getMessage(),'Cleanup conflict exception has fixed message.');
        assertSameValue(0,$error->getCode(),'Cleanup conflict exception has fixed code.');
        assertSameValue(null,$error->getPrevious(),'Cleanup conflict has no previous diagnostic.');
    }
    assertSameValue($drift,$stateSnapshot($fixture),'Cleanup drift conflict rolls back before deleting any owned row.');
    $fixture->query("UPDATE `{$prefix}fm2_installation_cases` SET process_state='prepared' WHERE id=4512");
    $contendedFixtureCall('cleanup',"SELECT id FROM `{$prefix}fm2_process_tasks` WHERE id=9001 FOR UPDATE");
    AssignmentOrderOriginalVerificationDatabaseFixture::cleanupExampleA($fixture, $prefix);
    assertSameValue($fixtureBaseline, $stateSnapshot($fixture), 'Cleanup and exact repeat restore the full prerequisite database byte-for-byte.');
    foreach ([['fm2_pilot_users','user_id IN (18,31)'],['fm2_pilot_roles','role_id IN (5301,5302)'],['fm2_process_user_capabilities','user_id IN (18,31)'],['fm2_installation_cases','id IN (4512,9999)'],['fm2_assignment_orders','id=81'],['fm2_order_installers','assignment_order_id=81'],['fm2_process_tasks','id=9001']] as [$logicalTable,$where]) {
        $remaining = (int) $fixture->query("SELECT COUNT(*) n FROM `{$prefix}{$logicalTable}` WHERE {$where}")->fetch_assoc()['n'];
        assertSameValue(0, $remaining, "Bounded cleanup removes exact {$logicalTable} fixture identities.");
    }
    $fixture->close();

    fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK\n");
} finally {
    $failures = [];
    foreach (array_reverse($created) as $database) {
        try { $admin->query('DROP DATABASE ' . $quote($database)); } catch (Throwable $error) { $failures[] = $database; }
    }
    $admin->close();
    if ($failures !== []) throw new TestFailure('SETUP_CLEANUP_FAILURE: owned databases remain.');
}
