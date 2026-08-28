<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/BitrixWorkforceSchemaV5Contract.php';

use FMonitor2\InstallationProcess\BitrixWorkforceHistorySchemaMigration;
use FMonitor2\InstallationProcess\ProcessCommandCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration;
use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;
use FMonitor2\InstallationProcess\WorkforceCatalogSchemaMigration;

// Specification: BITRIX-WORKFORCE-SCHEMA-001 v0.3.

function bwSchemaConnection(?string $database = null): mysqli
{
    $connection = new mysqli(getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1', getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root', getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_demo_local', $database, (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306));
    $connection->set_charset('utf8mb4');
    return $connection;
}

function bwSchemaRows(mysqli $connection, string $sql): array
{
    return $connection->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function bwSchemaNormalizeCheck(string $clause): string
{
    $normalized = '';
    $inLiteral = false;
    $length = strlen($clause);

    for ($index = 0; $index < $length; $index++) {
        $character = $clause[$index];
        if ($inLiteral) {
            $normalized .= $character;
            if ($character === "'" && $index + 1 < $length && $clause[$index + 1] === "'") {
                $normalized .= $clause[++$index];
            } elseif ($character === "'") {
                $inLiteral = false;
            }
            continue;
        }
        if ($character === "'") {
            $inLiteral = true;
            $normalized .= $character;
        } elseif ($character !== '`' && !ctype_space($character)) {
            $normalized .= strtolower($character);
        }
    }

    if ($inLiteral) {
        throw new InvalidArgumentException('Unterminated CHECK literal.');
    }

    while (str_starts_with($normalized, '(') && str_ends_with($normalized, ')')) {
        $depth = 0;
        $inLiteral = false;
        $wrapsWholeExpression = true;
        $length = strlen($normalized);
        for ($index = 0; $index < $length; $index++) {
            $character = $normalized[$index];
            if ($inLiteral) {
                if ($character === "'" && $index + 1 < $length && $normalized[$index + 1] === "'") {
                    $index++;
                } elseif ($character === "'") {
                    $inLiteral = false;
                }
                continue;
            }
            if ($character === "'") {
                $inLiteral = true;
            } elseif ($character === '(') {
                $depth++;
            } elseif ($character === ')') {
                $depth--;
                if ($depth < 0) {
                    throw new InvalidArgumentException('Unbalanced CHECK parentheses.');
                }
                if ($depth === 0 && $index !== $length - 1) {
                    $wrapsWholeExpression = false;
                }
            }
        }
        if ($depth !== 0) {
            throw new InvalidArgumentException('Unbalanced CHECK parentheses.');
        }
        if (!$wrapsWholeExpression) {
            break;
        }
        $normalized = substr($normalized, 1, -1);
    }

    $depth = 0;
    $inLiteral = false;
    $length = strlen($normalized);
    for ($index = 0; $index < $length; $index++) {
        $character = $normalized[$index];
        if ($inLiteral) {
            if ($character === "'" && $index + 1 < $length && $normalized[$index + 1] === "'") {
                $index++;
            } elseif ($character === "'") {
                $inLiteral = false;
            }
        } elseif ($character === "'") {
            $inLiteral = true;
        } elseif ($character === '(') {
            $depth++;
        } elseif ($character === ')' && --$depth < 0) {
            throw new InvalidArgumentException('Unbalanced CHECK parentheses.');
        }
    }
    if ($depth !== 0) {
        throw new InvalidArgumentException('Unbalanced CHECK parentheses.');
    }

    return $normalized;
}

function bwSchemaAssertCheckNormalizerSensitivity(): void
{
    $nullableList = "dismissal_time_quality IS NULL OR dismissal_time_quality IN ('observed_only','effective_from_source')";
    assertSameValue(
        bwSchemaNormalizeCheck($nullableList),
        bwSchemaNormalizeCheck(" ( ( `DISMISSAL_TIME_QUALITY` is null OR `dismissal_time_quality` in ('observed_only', 'effective_from_source') ) ) "),
        'Only redundant whole-expression wrappers and SQL formatting are insignificant.'
    );
    assertSameValue(
        "statusin('started','completed','failed')",
        bwSchemaNormalizeCheck("STATUS IN ('started', 'completed', 'failed')"),
        'IN-list parentheses must survive CHECK normalization.'
    );

    foreach ([
        "status IN ('started','completed')",
        "status IN ('started','completed','failed','cancelled')",
        "status IN ('started','completed','completed')",
        "status IN ('started','completed','FAILED')",
        "status NOT IN ('started','completed','failed')",
    ] as $changedRunStatus) {
        assertSameValue(false, bwSchemaNormalizeCheck("status IN ('started','completed','failed')") === bwSchemaNormalizeCheck($changedRunStatus), 'Changed run-status CHECK semantics must remain observable.');
    }

    foreach ([
        "dismissal_time_quality IS NOT NULL OR dismissal_time_quality IN ('observed_only','effective_from_source')",
        "(dismissal_time_quality IS NULL OR dismissal_time_quality) IN ('observed_only','effective_from_source')",
        "dismissal_time_quality IS NULL OR (dismissal_time_quality IN ('observed_only','effective_from_source') AND authority_system='one_c_zup')",
        "dismissal_time_quality IS NULL OR dismissal_time_quality IN ('observed_only','effective_from_source','unknown')",
    ] as $changedNullableList) {
        assertSameValue(false, bwSchemaNormalizeCheck($nullableList) === bwSchemaNormalizeCheck($changedNullableList), 'Changed operator, grouping, branch or literal must remain observable.');
    }
    assertSameValue(false, bwSchemaNormalizeCheck("status IN ('started')") === bwSchemaNormalizeCheck("status IN (' started')"), 'Whitespace inside quoted literals is significant.');

    foreach (["(status IN ('started','completed','failed')", "status IN ('started','completed','failed'))"] as $unbalanced) {
        try {
            bwSchemaNormalizeCheck($unbalanced);
            throw new TestFailure('Unbalanced CHECK parentheses must be rejected.');
        } catch (InvalidArgumentException) {
        }
    }
}

function bwSchemaAssertLiteralPrefixSymbols(): void
{
    assertSameValue(
        [
            'fm2_workforce_catalog'=>["ck_blue_wf_cat_dq|dismissal_time_qualityisnullordismissal_time_qualityin('observed_only','effective_from_source')","ck_blue_wf_cat_emp|employment_statusin('employed','dismissed')","ck_blue_wf_cat_rec|reconciliation_stateisnullorreconciliation_statein('delivered','missing_from_delivery')"],
            'fm2_workforce_observations'=>["ck_blue_wf_obs_dq|dismissal_time_qualityin('observed_only','effective_from_source')","ck_blue_wf_obs_rec|reconciliation_statein('delivered','missing_from_delivery')","ck_blue_wf_obs_status|employment_statusin('employed','dismissed')"],
            'fm2_workforce_sync_runs'=>["ck_blue_wf_run_status|statusin('started','completed','failed')"],
            'fm2_workforce_sync_metadata'=>['ck_blue_wf_meta_one|singleton_id=1'],
        ],
        BitrixWorkforceSchemaV5Contract::checks('blue_'),
        'Blue prefix CHECK symbols are a literal independent v0.3 oracle.'
    );
    assertSameValue(
        ['PRIMARY|0|BTREE|singleton_id:FULL:A:NO','fk_green_wf_meta_run|1|BTREE|last_successful_run_id:FULL:A:NO'],
        BitrixWorkforceSchemaV5Contract::indexes('green_')['fm2_workforce_sync_metadata'],
        'Green metadata supporting-index symbol is literal and prefix-derived.'
    );
    assertSameValue(
        ['fk_green_wf_obs_run|sync_run_id|green_fm2_workforce_sync_runs|run_id|RESTRICT|RESTRICT'],
        BitrixWorkforceSchemaV5Contract::foreignKeys('green_')['fm2_workforce_observations'],
        'Green observations FK symbol and referenced table are literal and prefix-derived.'
    );
    assertSameValue(
        ['fk_green_wf_meta_run|last_successful_run_id|green_fm2_workforce_sync_runs|run_id|RESTRICT|RESTRICT'],
        BitrixWorkforceSchemaV5Contract::foreignKeys('green_')['fm2_workforce_sync_metadata'],
        'Green metadata FK symbol and referenced table are literal and prefix-derived.'
    );
}

function bwSchemaAssertSharedPrefixRange(mysqli $closed): void
{
    $prefix = str_repeat('a', 38);

    foreach ([
        ProductionProcessSchemaMigration::class,
        WorkforceCatalogSchemaMigration::class,
        ProcessUserCapabilitiesSchemaMigration::class,
        ProcessCommandCapabilitiesSchemaMigration::class,
    ] as $migration) {
        try {
            $migration::apply($closed, $prefix);
            throw new TestFailure("{$migration} must reach DB access for its approved 38-byte prefix.");
        } catch (InvalidArgumentException) {
            throw new TestFailure("{$migration} must not inherit the workforce-v5-only 37-byte prefix ceiling.");
        } catch (Throwable) {
        }
    }
}

function bwSchemaApplyV1V4(mysqli $connection, string $prefix): void
{
    ProductionProcessSchemaMigration::apply($connection, $prefix);
    WorkforceCatalogSchemaMigration::apply($connection, $prefix);
    ProcessUserCapabilitiesSchemaMigration::apply($connection, $prefix);
    ProcessCommandCapabilitiesSchemaMigration::apply($connection, $prefix);
}

function bwSchemaCatalog(mysqli $connection, string $prefix): array
{
    $actual = [];
    $databaseCollation = bwSchemaRows($connection, 'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=DATABASE()')[0]['DEFAULT_COLLATION_NAME'];
    foreach (array_keys(BitrixWorkforceSchemaV5Contract::columns()) as $logical) {
        $table = $prefix . $logical;
        $props = bwSchemaRows($connection, "SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'")[0] ?? null;
        $columns = bwSchemaRows($connection, "SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,EXTRA,COLUMN_DEFAULT,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' ORDER BY ORDINAL_POSITION");
        $indexes = bwSchemaRows($connection, "SELECT INDEX_NAME,NON_UNIQUE,INDEX_TYPE,GROUP_CONCAT(CONCAT(COLUMN_NAME,':',COALESCE(SUB_PART,'FULL'),':',COALESCE(COLLATION,'NULL'),':',COALESCE(IGNORED,'NO')) ORDER BY SEQ_IN_INDEX) COLUMNS FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' GROUP BY INDEX_NAME,NON_UNIQUE,INDEX_TYPE");
        $checks = bwSchemaRows($connection, "SELECT tc.CONSTRAINT_NAME,cc.CHECK_CLAUSE FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.CHECK_CONSTRAINTS cc ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME AND cc.TABLE_NAME=tc.TABLE_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME='{$table}' AND tc.CONSTRAINT_TYPE='CHECK'");
        $fks = bwSchemaRows($connection, "SELECT k.CONSTRAINT_NAME,k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.TABLE_NAME=k.TABLE_NAME AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.TABLE_NAME='{$table}' AND k.REFERENCED_TABLE_NAME IS NOT NULL");
        $actual[$logical] = ['properties'=>$props, 'columns'=>array_map(static fn(array $c):array=>[$c['COLUMN_NAME'],preg_replace('/^(bigint|int|tinyint)\(\d+\)/','$1',$c['COLUMN_TYPE']),$c['IS_NULLABLE'],$c['EXTRA'],$c['COLUMN_DEFAULT'] === null ? 'NULL' : (string)$c['COLUMN_DEFAULT']],$columns)];
        $actual[$logical]['indexes'] = array_map(static fn(array $i):string=>$i['INDEX_NAME'].'|'.$i['NON_UNIQUE'].'|'.$i['INDEX_TYPE'].'|'.$i['COLUMNS'],$indexes);
        sort($actual[$logical]['indexes'], SORT_STRING);
        $actual[$logical]['checks'] = array_map(static fn(array $c):string=>$c['CONSTRAINT_NAME'].'|'.bwSchemaNormalizeCheck((string)$c['CHECK_CLAUSE']),$checks);
        sort($actual[$logical]['checks'], SORT_STRING);
        $actual[$logical]['foreignKeys'] = array_map(static fn(array $f):string=>implode('|',[$f['CONSTRAINT_NAME'],$f['COLUMN_NAME'],$f['REFERENCED_TABLE_NAME'],$f['REFERENCED_COLUMN_NAME'],$f['UPDATE_RULE'],$f['DELETE_RULE']]),$fks);
        sort($actual[$logical]['foreignKeys'], SORT_STRING);
        foreach ($columns as $column) {
            if ($column['CHARACTER_SET_NAME'] !== null) {
                assertSameValue('utf8mb4',$column['CHARACTER_SET_NAME'],"{$table}.{$column['COLUMN_NAME']} charset must be utf8mb4.");
                assertSameValue($databaseCollation,$column['COLLATION_NAME'],"{$table}.{$column['COLUMN_NAME']} collation must be the database default.");
            }
        }
    }
    return $actual;
}

function bwSchemaAssertExactV2(mysqli $connection, string $prefix): void
{
    $table=$prefix.'fm2_workforce_catalog';
    $properties=bwSchemaRows($connection,"SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'")[0]??null;
    assertSameValue(true,$properties!==null,'Exact v2 source catalog must exist.');
    assertSameValue('InnoDB',$properties['ENGINE'],'Exact v2 source engine.');
    assertSameValue(true,str_starts_with((string)$properties['TABLE_COLLATION'],'utf8mb4_'),'Exact v2 source charset.');
    $columns=bwSchemaRows($connection,"SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,EXTRA,COLUMN_DEFAULT,CHARACTER_SET_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' ORDER BY ORDINAL_POSITION");
    $columns=array_map(static fn(array $c):array=>[$c['COLUMN_NAME'],preg_replace('/^(bigint|int|tinyint)\(\d+\)/','$1',$c['COLUMN_TYPE']),$c['IS_NULLABLE'],$c['EXTRA'],$c['COLUMN_DEFAULT']===null?'NULL':(string)$c['COLUMN_DEFAULT']],$columns);
    assertSameValue(BitrixWorkforceSchemaV5Contract::v2Columns(),$columns,'Independent exact-v2 source column oracle.');
    $indexes=bwSchemaRows($connection,"SELECT INDEX_NAME,NON_UNIQUE,INDEX_TYPE,GROUP_CONCAT(CONCAT(COLUMN_NAME,':',COALESCE(SUB_PART,'FULL'),':',COALESCE(COLLATION,'NULL'),':',COALESCE(IGNORED,'NO')) ORDER BY SEQ_IN_INDEX) COLUMNS FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' GROUP BY INDEX_NAME,NON_UNIQUE,INDEX_TYPE");
    $indexes=array_map(static fn(array $i):string=>$i['INDEX_NAME'].'|'.$i['NON_UNIQUE'].'|'.$i['INDEX_TYPE'].'|'.$i['COLUMNS'],$indexes); sort($indexes,SORT_STRING);
    $expected=BitrixWorkforceSchemaV5Contract::v2Indexes(); sort($expected,SORT_STRING);
    assertSameValue($expected,$indexes,'Independent exact-v2 source index oracle includes type, full length, direction and usability.');
    $checks=bwSchemaRows($connection,"SELECT cc.CHECK_CLAUSE FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.CHECK_CONSTRAINTS cc ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME AND cc.TABLE_NAME=tc.TABLE_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME='{$table}' AND tc.CONSTRAINT_TYPE='CHECK'");
    assertSameValue(["employment_statusin('employed','dismissed')"],array_map(static fn(array $r):string=>bwSchemaNormalizeCheck((string)$r['CHECK_CLAUSE']),$checks),'Independent exact-v2 unnamed CHECK oracle.');
    assertSameValue([],bwSchemaRows($connection,"SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$table}' AND REFERENCED_TABLE_NAME IS NOT NULL"),'Exact v2 has no foreign keys.');
}

function bwSchemaAssertExact(mysqli $connection, string $prefix): void
{
    $actual=bwSchemaCatalog($connection,$prefix);
    foreach (BitrixWorkforceSchemaV5Contract::columns() as $table=>$columns) {
        assertSameValue('InnoDB',$actual[$table]['properties']['ENGINE'],"{$table} engine.");
        assertSameValue(true,str_starts_with((string)$actual[$table]['properties']['TABLE_COLLATION'],'utf8mb4_'),"{$table} charset.");
        assertSameValue($columns,$actual[$table]['columns'],"{$table} exact ordered columns/types/nullability/defaults/extra.");
        $indexes=BitrixWorkforceSchemaV5Contract::indexes($prefix)[$table]; sort($indexes,SORT_STRING);
        assertSameValue($indexes,$actual[$table]['indexes'],"{$table} exact named indexes.");
        assertSameValue(BitrixWorkforceSchemaV5Contract::checks($prefix)[$table],$actual[$table]['checks'],"{$table} exact named checks.");
        assertSameValue(BitrixWorkforceSchemaV5Contract::foreignKeys($prefix)[$table],$actual[$table]['foreignKeys'],"{$table} exact named foreign keys.");
    }
    $observationRunIndexes=array_values(array_filter(
        $actual['fm2_workforce_observations']['indexes'],
        static fn(string $index):bool=>str_ends_with($index,'|sync_run_id:FULL:A:NO,delivery_system:FULL:A:NO,delivery_person_id:FULL:A:NO')
            || str_ends_with($index,'|sync_run_id:FULL:A:NO')
    ));
    assertSameValue(
        ['uq_fm2_workforce_observation_run_person|0|BTREE|sync_run_id:FULL:A:NO,delivery_system:FULL:A:NO,delivery_person_id:FULL:A:NO'],
        $observationRunIndexes,
        'Observations unique run/person key is the sole FK-supporting index; MariaDB must not expose an extra generated FK index.'
    );
}

function bwSchemaState(mysqli $connection, string $prefix): array
{
    $state=[];
    foreach (bwSchemaRows($connection,"SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE '{$prefix}fm2\\_%' ORDER BY BINARY TABLE_NAME") as $row) {
        $table=$row['TABLE_NAME'];
        $state[$table]=['create'=>bwSchemaRows($connection,"SHOW CREATE TABLE `{$table}`")[0]['Create Table'],'rows'=>bwSchemaRows($connection,"SELECT * FROM `{$table}`"),'autoIncrement'=>bwSchemaRows($connection,"SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'")[0]['AUTO_INCREMENT']];
    }
    return $state;
}

function bwSchemaCreateRuns(mysqli $c,string $p):void {$c->query("CREATE TABLE {$p}fm2_workforce_sync_runs (run_id CHAR(36) NOT NULL,status VARCHAR(20) NOT NULL,started_at VARCHAR(40) NOT NULL,observed_at VARCHAR(40) NULL,completed_at VARCHAR(40) NULL,failure_code VARCHAR(80) NULL,page_count INT UNSIGNED NULL,delivered_count INT UNSIGNED NULL,material_change_count INT UNSIGNED NULL,missing_count INT UNSIGNED NULL,normalized_checksum CHAR(64) NULL,PRIMARY KEY(run_id),CONSTRAINT ck_{$p}wf_run_status CHECK(status IN ('started','completed','failed'))) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}
function bwSchemaCreateMetadata(mysqli $c,string $p):void {$c->query("CREATE TABLE {$p}fm2_workforce_sync_metadata (singleton_id TINYINT UNSIGNED NOT NULL,last_successful_run_id CHAR(36) NULL,last_successful_at VARCHAR(40) NULL,PRIMARY KEY(singleton_id),KEY fk_{$p}wf_meta_run(last_successful_run_id),CONSTRAINT ck_{$p}wf_meta_one CHECK(singleton_id=1),CONSTRAINT fk_{$p}wf_meta_run FOREIGN KEY(last_successful_run_id) REFERENCES {$p}fm2_workforce_sync_runs(run_id) ON UPDATE RESTRICT ON DELETE RESTRICT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}

$database='t_bw_schema_001_'.bin2hex(random_bytes(6));
bwSchemaAssertCheckNormalizerSensitivity();
bwSchemaAssertLiteralPrefixSymbols();
$admin=bwSchemaConnection(); $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4"); $admin->close();
$connection=bwSchemaConnection($database);
$clean='blue_'; $second='green_'; $partial='partial_'; $legacyNames='old_'; $conflict='bad_'; $missing='missing_'; $collationConflict='collation_'; $checkConflict='checks_';
try {
    $closed=bwSchemaConnection($database); $closed->close();
    bwSchemaAssertSharedPrefixRange($closed);
    foreach (['invalid-prefix;',str_repeat('a',38)] as $invalidPrefix) {
        try {
            BitrixWorkforceHistorySchemaMigration::apply($closed,$invalidPrefix);
            throw new TestFailure('Invalid or 38-byte prefix must throw before any DB access.');
        } catch (InvalidArgumentException) {
        } catch (Throwable) {
            throw new TestFailure('Invalid or 38-byte prefix reached DB access instead of being rejected.');
        }
    }
    try { BitrixWorkforceHistorySchemaMigration::apply($closed,str_repeat('a',37)); throw new TestFailure('A valid 37-byte prefix must proceed to DB access.'); } catch (InvalidArgumentException) { throw new TestFailure('A valid 37-byte prefix must not be rejected.'); } catch (Throwable) {}

    bwSchemaApplyV1V4($connection,$clean);
    bwSchemaAssertExactV2($connection,$clean);
    $connection->query("INSERT INTO {$clean}fm2_installation_cases (legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version) VALUES (4512,'preparation',NULL,NULL,NULL,'2026-08-28T09:00:00+03:00','2026-08-28T09:00:00+03:00',0)");
    $connection->query("INSERT INTO {$clean}fm2_workforce_catalog VALUES (1042,'Иванов Иван Иванович','Электромеханик по лифтам','employed','2024-02-01',NULL,'one_c_zup_via_bitrix','2026-08-26T18:00:00+03:00')");
    $before=bwSchemaRows($connection,"SELECT * FROM {$clean}fm2_workforce_catalog");
    $v1v4Before=bwSchemaState($connection,$clean);
    assertSameValue(['applied'=>true,'schemaVersion'=>5,'tablesCreated'=>[$clean.'fm2_workforce_observations',$clean.'fm2_workforce_sync_runs',$clean.'fm2_workforce_sync_metadata'],'tablesAltered'=>[$clean.'fm2_workforce_catalog']],BitrixWorkforceHistorySchemaMigration::apply($connection,$clean),'Clean v1-v4 to v5 result.');
    bwSchemaAssertExact($connection,$clean);
    assertSameValue($before,bwSchemaRows($connection,"SELECT installer_tab_id,fio,position,employment_status,employed_from,employed_to,workforce_source,workforce_source_updated_at FROM {$clean}fm2_workforce_catalog"),'All v2 values must be byte-for-byte preserved.');
    assertSameValue([['delivery_system'=>null,'delivery_person_id'=>null,'dismissal_effective_at'=>null,'first_observed_dismissed_at'=>null,'dismissal_time_quality'=>null,'reconciliation_state'=>null,'authority_system'=>null,'last_successful_sync_run_id'=>null,'last_successful_sync_at'=>null]],bwSchemaRows($connection,"SELECT delivery_system,delivery_person_id,dismissal_effective_at,first_observed_dismissed_at,dismissal_time_quality,reconciliation_state,authority_system,last_successful_sync_run_id,last_successful_sync_at FROM {$clean}fm2_workforce_catalog"),'Migration must not invent metadata for v2 rows.');
    assertSameValue([],bwSchemaRows($connection,"SELECT * FROM {$clean}fm2_workforce_sync_runs"),'Fresh runs empty.');
    assertSameValue([],bwSchemaRows($connection,"SELECT * FROM {$clean}fm2_workforce_observations"),'Fresh observations empty.');
    assertSameValue([['singleton_id'=>'1','last_successful_run_id'=>null,'last_successful_at'=>null]],bwSchemaRows($connection,"SELECT * FROM {$clean}fm2_workforce_sync_metadata"),'Fresh singleton.');
    $v1v4After=bwSchemaState($connection,$clean); foreach (array_keys($v1v4Before) as $table) { if ($table!==$clean.'fm2_workforce_catalog') assertSameValue($v1v4Before[$table],$v1v4After[$table],"Clean apply preserves complete v1-v4 table {$table}, including bytes and AUTO_INCREMENT."); }
    assertSameValue(array_map(static fn(string $t):string=>$clean.$t,['fm2_assignment_orders','fm2_installation_cases','fm2_order_artifacts','fm2_order_installers','fm2_process_events','fm2_process_tasks','fm2_process_user_capabilities','fm2_workforce_catalog','fm2_workforce_observations','fm2_workforce_sync_metadata','fm2_workforce_sync_runs']),array_column(bwSchemaRows($connection,'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY BINARY TABLE_NAME'),'TABLE_NAME'),'Clean apply creates exactly the full approved table set and no auxiliary tables.');

    $checkDecoy='decoy_fm2_check_owner';
    $connection->query("CREATE TABLE {$checkDecoy} (sentinel INT NOT NULL,CONSTRAINT ck_blue_wf_run_status CHECK(sentinel > 0)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("INSERT INTO {$checkDecoy} VALUES (7)");
    $targetBeforeDecoyRepeat=bwSchemaState($connection,$clean);
    $decoyBefore=bwSchemaState($connection,'decoy_');
    assertSameValue(['applied'=>false,'schemaVersion'=>5,'tablesCreated'=>[],'tablesAltered'=>[]],BitrixWorkforceHistorySchemaMigration::apply($connection,$clean),'A same-named CHECK owned by another table must not contaminate exact target-table inspection.');
    bwSchemaAssertExact($connection,$clean);
    assertSameValue($targetBeforeDecoyRepeat,bwSchemaState($connection,$clean),'Table-qualified compatible preflight leaves the exact target namespace unchanged.');
    assertSameValue($decoyBefore,bwSchemaState($connection,'decoy_'),'Table-qualified compatible preflight leaves the same-named CHECK decoy table and row unchanged.');

    $connection->query("INSERT INTO {$clean}fm2_workforce_sync_runs VALUES ('11111111-1111-1111-1111-111111111111','completed','2026-08-28T10:00:00+03:00','2026-08-28T10:01:00+03:00','2026-08-28T10:02:00+03:00',NULL,1,1,1,0,'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa')");
    $connection->query("INSERT INTO {$clean}fm2_workforce_observations (sync_run_id,delivery_person_id,employee_number,full_name,position,employment_status,employed_from,dismissal_effective_at,authority_system,delivery_system,source_modified_at,reconciliation_state,observed_at,dismissal_time_quality) VALUES ('11111111-1111-1111-1111-111111111111',501,1042,'Иванов Иван Иванович','Электромеханик по лифтам','employed','2024-02-01',NULL,'one_c_zup','bitrix','2026-08-28T09:00:00+03:00','delivered','2026-08-28T10:01:00+03:00','observed_only')");
    $connection->query("UPDATE {$clean}fm2_workforce_sync_metadata SET last_successful_run_id='11111111-1111-1111-1111-111111111111',last_successful_at='2026-08-28T10:02:00+03:00' WHERE singleton_id=1");
    $repeatBefore=bwSchemaState($connection,$clean);
    assertSameValue(['applied'=>false,'schemaVersion'=>5,'tablesCreated'=>[],'tablesAltered'=>[]],BitrixWorkforceHistorySchemaMigration::apply($connection,$clean),'Populated compatible repeat result.');
    assertSameValue($repeatBefore,bwSchemaState($connection,$clean),'Populated compatible repeat must perform no DDL/DML.');

    bwSchemaApplyV1V4($connection,$second);
    $connection->query("INSERT INTO {$second}fm2_workforce_catalog VALUES (2048,'Петров Пётр Петрович','Монтажник электрических подъемников','employed','2025-03-04',NULL,'one_c_zup_via_bitrix','2026-08-27T17:30:00+03:00')");
    $secondCatalogBefore=bwSchemaRows($connection,"SELECT * FROM {$second}fm2_workforce_catalog");
    assertSameValue(['applied'=>true,'schemaVersion'=>5,'tablesCreated'=>[$second.'fm2_workforce_observations',$second.'fm2_workforce_sync_runs',$second.'fm2_workforce_sync_metadata'],'tablesAltered'=>[$second.'fm2_workforce_catalog']],BitrixWorkforceHistorySchemaMigration::apply($connection,$second),'Second non-empty prefix clean apply in the same schema.');
    bwSchemaAssertExact($connection,$clean);
    bwSchemaAssertExact($connection,$second);
    assertSameValue($secondCatalogBefore,bwSchemaRows($connection,"SELECT installer_tab_id,fio,position,employment_status,employed_from,employed_to,workforce_source,workforce_source_updated_at FROM {$second}fm2_workforce_catalog"),'Second prefix preserves its independent populated v2 catalog.');
    $connection->query("INSERT INTO {$second}fm2_workforce_sync_runs VALUES ('33333333-3333-3333-3333-333333333333','completed','2026-08-28T12:00:00+03:00','2026-08-28T12:01:00+03:00','2026-08-28T12:02:00+03:00',NULL,1,1,0,0,'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb')");
    $connection->query("INSERT INTO {$second}fm2_workforce_observations (sync_run_id,delivery_person_id,employee_number,full_name,position,employment_status,employed_from,dismissal_effective_at,authority_system,delivery_system,source_modified_at,reconciliation_state,observed_at,dismissal_time_quality) VALUES ('33333333-3333-3333-3333-333333333333',777,2048,'Петров Пётр Петрович','Монтажник электрических подъемников','employed','2025-03-04',NULL,'one_c_zup','bitrix',NULL,'delivered','2026-08-28T12:01:00+03:00','observed_only')");
    $connection->query("UPDATE {$second}fm2_workforce_sync_metadata SET last_successful_run_id='33333333-3333-3333-3333-333333333333',last_successful_at='2026-08-28T12:02:00+03:00' WHERE singleton_id=1");
    $bothBefore=[$clean=>bwSchemaState($connection,$clean),$second=>bwSchemaState($connection,$second)];
    assertSameValue(['applied'=>false,'schemaVersion'=>5,'tablesCreated'=>[],'tablesAltered'=>[]],BitrixWorkforceHistorySchemaMigration::apply($connection,$second),'Second populated namespace repeat result.');
    assertSameValue(['applied'=>false,'schemaVersion'=>5,'tablesCreated'=>[],'tablesAltered'=>[]],BitrixWorkforceHistorySchemaMigration::apply($connection,$clean),'First populated namespace remains independently repeatable.');
    assertSameValue($bothBefore,[$clean=>bwSchemaState($connection,$clean),$second=>bwSchemaState($connection,$second)],'Repeating either complete prefix preserves both populated namespaces independently.');

    bwSchemaApplyV1V4($connection,$partial); bwSchemaAssertExactV2($connection,$partial); bwSchemaCreateRuns($connection,$partial); bwSchemaCreateMetadata($connection,$partial);
    $connection->query("INSERT INTO {$partial}fm2_workforce_catalog VALUES (1042,'Иванов Иван Иванович','Электромеханик по лифтам','employed','2024-02-01',NULL,'one_c_zup_via_bitrix','2026-08-26T18:00:00+03:00')");
    $connection->query("INSERT INTO {$partial}fm2_workforce_sync_runs (run_id,status,started_at) VALUES ('22222222-2222-2222-2222-222222222222','started','2026-08-28T11:00:00+03:00')");
    $partialBefore=bwSchemaState($connection,$partial);
    assertSameValue(['applied'=>true,'schemaVersion'=>5,'tablesCreated'=>[$partial.'fm2_workforce_observations'],'tablesAltered'=>[$partial.'fm2_workforce_catalog']],BitrixWorkforceHistorySchemaMigration::apply($connection,$partial),'Compatible partial recovery result.');
    bwSchemaAssertExact($connection,$partial);
    assertSameValue([['singleton_id'=>'1','last_successful_run_id'=>null,'last_successful_at'=>null]],bwSchemaRows($connection,"SELECT * FROM {$partial}fm2_workforce_sync_metadata"),'Partial recovery inserts missing singleton.');
    $partialAfter=bwSchemaState($connection,$partial);
    assertSameValue($partialBefore[$partial.'fm2_workforce_sync_runs'],$partialAfter[$partial.'fm2_workforce_sync_runs'],'Partial recovery preserves populated pre-existing runs bytes, definition and AUTO_INCREMENT.');
    assertSameValue($partialBefore[$partial.'fm2_workforce_sync_metadata']['create'],$partialAfter[$partial.'fm2_workforce_sync_metadata']['create'],'Partial recovery preserves pre-existing metadata definition.');

    bwSchemaApplyV1V4($connection,$legacyNames);
    $connection->query("CREATE TABLE {$legacyNames}fm2_workforce_sync_runs (run_id CHAR(36) NOT NULL,status VARCHAR(20) NOT NULL,started_at VARCHAR(40) NOT NULL,observed_at VARCHAR(40) NULL,completed_at VARCHAR(40) NULL,failure_code VARCHAR(80) NULL,page_count INT UNSIGNED NULL,delivered_count INT UNSIGNED NULL,material_change_count INT UNSIGNED NULL,missing_count INT UNSIGNED NULL,normalized_checksum CHAR(64) NULL,PRIMARY KEY(run_id),CONSTRAINT ck_fm2_workforce_sync_run_status CHECK(status IN ('started','completed','failed'))) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $connection->query("INSERT INTO {$legacyNames}fm2_workforce_sync_runs (run_id,status,started_at) VALUES ('44444444-4444-4444-4444-444444444444','started','2026-08-28T13:00:00+03:00')");
    $legacyNamesBefore=bwSchemaState($connection,$legacyNames);
    assertSameValue(['applied'=>false,'schemaVersion'=>5,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$legacyNames.'fm2_workforce_sync_runs']],BitrixWorkforceHistorySchemaMigration::apply($connection,$legacyNames),'Pre-v0.3 prefix-independent CHECK symbol is an explicit conflict.');
    assertSameValue($legacyNamesBefore,bwSchemaState($connection,$legacyNames),'Pre-v0.3 prefixed schema conflict performs no DDL/DML.');

    bwSchemaApplyV1V4($connection,$collationConflict);
    BitrixWorkforceHistorySchemaMigration::apply($connection,$collationConflict);
    $databaseCollation=(string)bwSchemaRows($connection,'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=DATABASE()')[0]['DEFAULT_COLLATION_NAME'];
    $wrongCollation=$databaseCollation==='utf8mb4_bin'?'utf8mb4_general_ci':'utf8mb4_bin';
    $connection->query("ALTER TABLE {$collationConflict}fm2_workforce_catalog MODIFY fio VARCHAR(300) CHARACTER SET utf8mb4 COLLATE {$wrongCollation} NOT NULL");
    $connection->query("ALTER TABLE {$collationConflict}fm2_workforce_sync_runs MODIFY started_at VARCHAR(40) CHARACTER SET utf8mb4 COLLATE {$wrongCollation} NOT NULL");
    $collationBefore=bwSchemaState($connection,$collationConflict);
    assertSameValue(['applied'=>false,'schemaVersion'=>5,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$collationConflict.'fm2_workforce_catalog',$collationConflict.'fm2_workforce_sync_runs']],BitrixWorkforceHistorySchemaMigration::apply($connection,$collationConflict),'Every character column must use the exact database-default collation; explicit alternate collations are sorted conflicts.');
    assertSameValue($collationBefore,bwSchemaState($connection,$collationConflict),'Character-column collation conflicts perform no DDL/DML.');

    bwSchemaApplyV1V4($connection,$checkConflict);
    BitrixWorkforceHistorySchemaMigration::apply($connection,$checkConflict);
    $connection->query("ALTER TABLE {$checkConflict}fm2_workforce_catalog DROP CONSTRAINT ck_{$checkConflict}wf_cat_dq, ADD CONSTRAINT ck_{$checkConflict}wf_cat_dq CHECK ((dismissal_time_quality IS NULL OR dismissal_time_quality) IN ('observed_only','effective_from_source'))");
    $connection->query("ALTER TABLE {$checkConflict}fm2_workforce_sync_runs DROP CONSTRAINT ck_{$checkConflict}wf_run_status, ADD CONSTRAINT ck_{$checkConflict}wf_run_status CHECK (status IN ('(started)','completed','failed'))");
    $connection->query("ALTER TABLE {$checkConflict}fm2_workforce_sync_metadata DROP CONSTRAINT ck_{$checkConflict}wf_meta_one, ADD CONSTRAINT ck_{$checkConflict}wf_meta_one CHECK (singleton_id >= 1)");
    $checkBefore=bwSchemaState($connection,$checkConflict);
    assertSameValue(['applied'=>false,'schemaVersion'=>5,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$checkConflict.'fm2_workforce_catalog',$checkConflict.'fm2_workforce_sync_metadata',$checkConflict.'fm2_workforce_sync_runs']],BitrixWorkforceHistorySchemaMigration::apply($connection,$checkConflict),'Literal-aware CHECK compatibility rejects changed grouping, quoted literal bytes and operators in binary-sorted order.');
    assertSameValue($checkBefore,bwSchemaState($connection,$checkConflict),'Semantic CHECK conflicts perform no DDL/DML.');

    foreach (['catalog','observations','sync_runs','sync_metadata'] as $suffix) {$connection->query("CREATE TABLE {$conflict}fm2_workforce_{$suffix} (sentinel INT NOT NULL PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); $connection->query("INSERT INTO {$conflict}fm2_workforce_{$suffix} VALUES (7)");}
    $conflictBefore=bwSchemaState($connection,$conflict);
    assertSameValue(['applied'=>false,'schemaVersion'=>5,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$conflict.'fm2_workforce_catalog',$conflict.'fm2_workforce_observations',$conflict.'fm2_workforce_sync_metadata',$conflict.'fm2_workforce_sync_runs']],BitrixWorkforceHistorySchemaMigration::apply($connection,$conflict),'All incompatible targets must be reported in binary sorted order.');
    assertSameValue($conflictBefore,bwSchemaState($connection,$conflict),'Complete conflict preflight must perform no DDL/DML.');

    bwSchemaApplyV1V4($connection,$missing); $connection->query("DROP TABLE {$missing}fm2_workforce_catalog");
    $missingBefore=bwSchemaState($connection,$missing);
    assertSameValue(['applied'=>false,'schemaVersion'=>5,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$missing.'fm2_workforce_catalog']],BitrixWorkforceHistorySchemaMigration::apply($connection,$missing),'Absent required v2 catalog fails closed as the sole conflict.');
    assertSameValue($missingBefore,bwSchemaState($connection,$missing),'Absent catalog conflict performs zero DDL/DML and leaves all three v5 tables absent.');
} finally {
    $connection->close();
    $admin=bwSchemaConnection(); $admin->query("DROP DATABASE `{$database}`"); $admin->close();
}

echo "BITRIX-WORKFORCE-SCHEMA-001 v0.3 tests passed.\n";
