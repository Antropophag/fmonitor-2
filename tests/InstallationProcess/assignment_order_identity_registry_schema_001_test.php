<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\AssignmentOrderIdentityRegistryMigration as Migration;
use FMonitor2\InstallationProcess\AssignmentOrderIdentityRegistryMigrationVerification as Verification;
use FMonitor2\InstallationProcess\DatabaseUnavailable;
use FMonitor2\Tests\Support\IdentityRegistryTestDatabase;

// ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001 v0.1; real FMONITOR_TEST_DB matrix.
function aoirSchemaFixture(string $prefix, callable $scenario): void
{
    $fixture = new IdentityRegistryTestDatabase($prefix);
    $error = null;
    try { $scenario($fixture); } catch (Throwable $failure) { $error = $failure; }
    try { $fixture->close(); } catch (Throwable $cleanup) {
        throw new TestFailure(($error ? $error->getMessage() . ' | ' : '') . $cleanup->getMessage());
    }
    if ($error) { throw $error; }
}

function aoirExpectedConflict(IdentityRegistryTestDatabase $f): void
{
    $before = $f->allState();
    assertSameValue(['applied' => false, 'reason' => 'SCHEMA_MIGRATION_CONFLICT'],
        Migration::apply($f->connection, $f->prefix), 'exact fail-closed conflict');
    assertSameValue($before, $f->allState(), 'conflict is zero mutation across family/source/decoy');
    assertSameValue(false, Migration::isBackfillComplete($f->connection, $f->prefix), 'conflict not complete');
}

/** Normative family predicates contain only AND/BETWEEN/IN/REGEXP, no OR. */
function aoirCheckKey(string $sql): string
{
    $result = ''; $quoted = false;
    for ($i=0,$n=strlen($sql);$i<$n;$i++) {
        $c=$sql[$i];
        if ($quoted) {
            $result.=$c;
            if ($c==='\\' && $i+1<$n) { $result.=$sql[++$i]; continue; }
            if ($c==="'") {
                if ($i+1<$n && $sql[$i+1]==="'") { $result.=$sql[++$i]; continue; }
                $quoted=false;
            }
        } elseif ($c==="'") { $quoted=true; $result.=$c; }
        elseif (!str_contains(" \t\r\n`()",$c)) { $result.=strtolower($c); }
    }
    assertSameValue(false,$quoted,'valid quoted predicate metadata');
    return $result;
}

try {
    foreach (['', str_repeat('p', 25)] as $prefix) {
        aoirSchemaFixture($prefix, static function ($f): void {
            $db = $f->connection; $p = $f->prefix;
            assertSameValue(true, class_exists(Migration::class), 'RED_ASSERTION: registry schema engine is missing');
            assertSameValue(['applied' => true], Migration::apply($db, $p), 'empty/prefix family created');
            $snapshot = Verification::snapshot($db, $p);
            assertSameValue([], $snapshot->identities, 'empty source creates no fictional order');
            assertSameValue(['0', '81', '81', '0',
                'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
                'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'],
                [$snapshot->receipt->legacyMaxId, $snapshot->receipt->legacyNextId,
                    $snapshot->receipt->preservedNextId, $snapshot->receipt->legacyRowCount,
                    $snapshot->receipt->tupleSha256, $snapshot->receipt->preparedSha256], 'literal empty receipt');
            $expectedColumns = [
                'fm2_assignment_order_identities' => [
                    ['assignment_order_id','bigint unsigned','auto_increment'],
                    ['installation_case_id','bigint unsigned',''],['order_version','smallint unsigned',''],
                    ['source_kind','varchar(24)',''],['allocated_at_utc','datetime(6)','']],
                'fm2_assignment_order_id_receipts' => [
                    ['singleton_id','tinyint unsigned',''],['format_version','smallint unsigned',''],
                    ['legacy_max_id','bigint unsigned',''],['legacy_next_id','bigint unsigned',''],
                    ['preserved_next_id','bigint unsigned',''],['legacy_row_count','bigint unsigned',''],
                    ['legacy_tuple_sha256','char(64)',''],['legacy_prepared_sha256','char(64)','']],
            ];
            foreach ($expectedColumns as $base => $expected) {
                $table = $p . $base;
                $q = $db->prepare('SELECT COLUMN_NAME,COLUMN_TYPE,EXTRA,IS_NULLABLE,CHARACTER_SET_NAME,COLLATION_NAME,COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');
                $q->bind_param('s', $table); $q->execute(); $rows = $q->get_result()->fetch_all(MYSQLI_ASSOC); $q->close();
                assertSameValue($expected, array_map(static fn ($r) => [$r['COLUMN_NAME'],
                    preg_replace('/^(tinyint|smallint|bigint)\([0-9]+\)/', '$1', strtolower($r['COLUMN_TYPE'])),
                    $r['EXTRA']], $rows), 'literal ordinal schema ' . $base);
                foreach ($rows as $row) {
                    assertSameValue('NO', $row['IS_NULLABLE'], 'all family fields required');
                    assertSameValue(null, $row['COLUMN_DEFAULT'], 'no invented column default');
                    $isText = in_array($row['COLUMN_NAME'], ['source_kind','legacy_tuple_sha256','legacy_prepared_sha256'], true);
                    assertSameValue($isText ? ['ascii','ascii_bin'] : [null,null],
                        [$row['CHARACTER_SET_NAME'],$row['COLLATION_NAME']], 'exact text encoding');
                }
            }
            $expectedKeys = [
                [$p.'fm2_assignment_order_identities','PRIMARY','0','assignment_order_id'],
                [$p.'fm2_assignment_order_identities',$p.'fm2_aoir_ix_case_source','1','installation_case_id,source_kind,order_version'],
                [$p.'fm2_assignment_order_identities',$p.'fm2_aoir_uq_case_version','0','installation_case_id,order_version'],
                [$p.'fm2_assignment_order_identities',$p.'fm2_aoir_uq_id_case','0','assignment_order_id,installation_case_id'],
                [$p.'fm2_assignment_order_id_receipts','PRIMARY','0','singleton_id'],
            ];
            $actualKeys = $db->query("SELECT TABLE_NAME,INDEX_NAME,CAST(NON_UNIQUE AS CHAR) n,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) cols FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('{$p}fm2_assignment_order_identities','{$p}fm2_assignment_order_id_receipts') GROUP BY TABLE_NAME,INDEX_NAME,NON_UNIQUE")->fetch_all(MYSQLI_NUM);
            sort($expectedKeys); sort($actualKeys); assertSameValue($expectedKeys, $actualKeys, 'exact index manifest, no implicit extras');
            $foreign = $db->query("SELECT k.CONSTRAINT_NAME,k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.TABLE_NAME='{$p}fm2_assignment_order_identities' AND k.REFERENCED_TABLE_NAME IS NOT NULL")->fetch_all(MYSQLI_NUM);
            assertSameValue([[$p.'fm2_aoir_fk_case','installation_case_id',$p.'fm2_installation_cases','id','RESTRICT','RESTRICT']], $foreign, 'exact FK and bounded explicit name');
            $checks = [
                'fm2_aoir_ck_case'=>'installation_case_id BETWEEN 1 AND 9223372036854775807',
                'fm2_aoir_ck_version'=>'order_version BETWEEN 1 AND 65535',
                'fm2_aoir_ck_source'=>"source_kind IN ('legacy_order','selection')",
                'fm2_aoir_receipt_ck_one'=>'singleton_id=1 AND format_version=1',
                'fm2_aoir_receipt_ck_bounds'=>'legacy_max_id BETWEEN 0 AND 9223372036854775807 AND legacy_next_id BETWEEN 1 AND 9223372036854775807 AND preserved_next_id BETWEEN 1 AND 9223372036854775807 AND legacy_row_count BETWEEN 0 AND 9223372036854775807',
                'fm2_aoir_receipt_ck_frontier'=>'preserved_next_id>=legacy_next_id AND preserved_next_id>legacy_max_id',
                'fm2_aoir_receipt_ck_tuple'=>"legacy_tuple_sha256 REGEXP '^[0-9a-f]{64}$'",
                'fm2_aoir_receipt_ck_prepared'=>"legacy_prepared_sha256 REGEXP '^[0-9a-f]{64}$'",
            ];
            $expectedChecks=[]; foreach($checks as $name=>$predicate) { $expectedChecks[$p.$name]=aoirCheckKey($predicate); }
            $actualChecks=[];
            foreach($db->query("SELECT CONSTRAINT_NAME,CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME IN ('{$p}fm2_assignment_order_identities','{$p}fm2_assignment_order_id_receipts')") as $row) { $actualChecks[$row['CONSTRAINT_NAME']]=aoirCheckKey($row['CHECK_CLAUSE']); }
            ksort($expectedChecks); ksort($actualChecks); assertSameValue($expectedChecks,$actualChecks,'literal named predicates, no CHECK on AUTO_INCREMENT');
            $tableMetadata=$db->query("SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('{$p}fm2_assignment_order_identities','{$p}fm2_assignment_order_id_receipts')")->fetch_all(MYSQLI_NUM);
            assertSameValue([['InnoDB','utf8mb4_unicode_ci'],['InnoDB','utf8mb4_unicode_ci']],$tableMetadata,'engine/default collation');
            $before = $f->allState();
            assertSameValue(['applied'=>false], Migration::apply($db,$p), 'empty repeat');
            assertSameValue($before,$f->allState(),'complete repeat no catalog/frontier/row mutation');
        });
    }
    aoirSchemaFixture('', static function ($f): void {
        $before = $f->allState();
        foreach ([str_repeat('p',26), 'bad-prefix', "bad\0prefix"] as $prefix) {
            try { Migration::apply($f->connection,$prefix); throw new TestFailure('invalid prefix accepted'); }
            catch (InvalidArgumentException $e) { assertSameValue('Invalid registry migration configuration.',$e->getMessage(),'fixed invalid prefix error'); }
            assertSameValue($before,$f->allState(),'invalid prefix before mutation');
        }
        $f->connection->begin_transaction();
        try {
            try { Migration::apply($f->connection); throw new TestFailure('caller transaction accepted'); }
            catch (InvalidArgumentException $e) { assertSameValue('Invalid registry migration configuration.',$e->getMessage(),'fixed caller transaction error'); }
            assertSameValue('1',(string)$f->connection->query('SELECT @@in_transaction n')->fetch_assoc()['n'],'caller transaction retained');
        } finally { $f->connection->rollback(); }
    });
    foreach (['fm2_assignment_order_identities','fm2_assignment_order_id_receipts'] as $bad) {
        aoirSchemaFixture('', static function ($f) use ($bad): void {
            $f->connection->query("CREATE TABLE `$bad` (wrong INT PRIMARY KEY)"); aoirExpectedConflict($f);
        });
    }
    foreach (['prepared_at'=>"'2026-02-30T10:00:00Z'",'version_no'=>'0'] as $column=>$value) {
        aoirSchemaFixture('', static function ($f) use ($column,$value): void {
            $f->seedOrder(); $f->connection->query("UPDATE fm2_assignment_orders SET `$column`=$value"); aoirExpectedConflict($f);
        });
    }
    aoirSchemaFixture('', static function ($f):void {
        $f->seedOrder(); $f->connection->query('SET FOREIGN_KEY_CHECKS=0');
        try { $f->connection->query('UPDATE fm2_assignment_orders SET installation_case_id=99999'); }
        finally { $f->connection->query('SET FOREIGN_KEY_CHECKS=1'); }
        aoirExpectedConflict($f);
    });
    foreach (["2026-08-27T10:00:00-00:00", "2026-08-27T10:00:00+14:01", "2026-08-27T10:00:00Z trailing"] as $at) {
        aoirSchemaFixture('', static function ($f) use($at):void {
            $f->seedOrder(); $q=$f->connection->prepare('UPDATE fm2_assignment_orders SET prepared_at=?');$q->bind_param('s',$at);$q->execute();$q->close();aoirExpectedConflict($f);
        });
    }
    foreach (['hash','prepared','unreceipted','wrong-predicate','frontier-regression'] as $fault) {
        aoirSchemaFixture('',static function($f)use($fault):void {
            $f->seedOrder(); assertSameValue(['applied'=>true],Migration::apply($f->connection),'prepare exact completed family');
            if($fault==='hash') { $f->connection->query("UPDATE fm2_assignment_order_id_receipts SET legacy_tuple_sha256=REPEAT('a',64)"); }
            if($fault==='prepared') { $f->connection->query("UPDATE fm2_assignment_orders SET prepared_at='2026-08-27T12:30:01+03:00'"); }
            if($fault==='unreceipted') { $f->connection->query('DELETE FROM fm2_assignment_order_id_receipts'); }
            if($fault==='wrong-predicate') {
                $f->connection->query('ALTER TABLE fm2_assignment_order_identities DROP CONSTRAINT fm2_aoir_ck_source');
                $f->connection->query("ALTER TABLE fm2_assignment_order_identities ADD CONSTRAINT fm2_aoir_ck_source CHECK(source_kind IN ('legacy_order','wrong_kind'))");
            }
            if($fault==='frontier-regression') { $f->connection->query('ALTER TABLE fm2_assignment_order_identities AUTO_INCREMENT=8'); }
            aoirExpectedConflict($f);
        });
    }
    aoirSchemaFixture('',static function($f):void {
        Migration::apply($f->connection);
        $f->connection->query('INSERT INTO fm2_installation_cases (id,legacy_installation_object_id,process_state,created_at,updated_at,lock_version) VALUES(4512,4512,\'needs_assignment_order\',\'2026-08-20T09:00:00Z\',\'2026-08-20T09:00:00Z\',1)');
        $f->connection->query("INSERT INTO fm2_assignment_order_identities VALUES(9223372036854775807,4512,1,'selection','2026-09-05 00:00:00.000000')");
        assertSameValue('9223372036854775808',Verification::snapshot($f->connection,'')->nextId,'late exhausted frontier remains lossless');
        assertSameValue(true,Migration::isBackfillComplete($f->connection),'empty frozen history remains complete at later exhaustion sentinel');
        $before=$f->allState(); assertSameValue(['applied'=>false],Migration::apply($f->connection),'exhaustion is not a reason to rewrite completed history');assertSameValue($before,$f->allState(),'exhausted repeat unchanged');
    });
    foreach (['9223372036854775807'=>true,'9223372036854775808'=>false] as $frontier=>$valid) {
        $frontier = (string) $frontier;
        aoirSchemaFixture('', static function ($f) use ($frontier,$valid): void {
            $f->connection->query("ALTER TABLE fm2_assignment_orders AUTO_INCREMENT=$frontier");
            if (!$valid) { aoirExpectedConflict($f); return; }
            assertSameValue(['applied'=>true],Migration::apply($f->connection),'largest allowed initial frontier');
            assertSameValue($frontier,Verification::snapshot($f->connection,'')->nextId,'lossless maximum frontier');
        });
    }
    aoirSchemaFixture('',static function($f):void {
        $f->connection->query('ALTER TABLE fm2_assignment_orders ADD COLUMN extra_column INT NULL');
        aoirExpectedConflict($f);
    });
    aoirSchemaFixture('',static function($f):void {
        Migration::apply($f->connection);
        $f->connection->query('DROP TABLE fm2_assignment_order_identities');
        aoirExpectedConflict($f); // A committed receipt never authorizes DDL repair.
    });
    aoirSchemaFixture('',static function($f):void {
        $f->seedOrder();Migration::apply($f->connection);
        $f->connection->query("INSERT INTO fm2_assignment_order_identities VALUES(9223372036854775808,4512,2,'selection','2026-09-05 00:00:00.000000')");
        aoirExpectedConflict($f); // No impossible AUTO_INCREMENT CHECK; integrity still rejects overflow.
    });
    foreach (['SELECT','SELECT,CREATE,ALTER,REFERENCES'] as $grants) {
        aoirSchemaFixture('', static function ($f) use ($grants): void {
            $restricted=$f->restricted($grants);
            try {
                try { Migration::apply($restricted); throw new TestFailure('denied DDL/DML produced success'); }
                catch (DatabaseUnavailable $e) { assertSameValue('Assignment order identity registry unavailable.',$e->getMessage(),'fixed denied infrastructure error'); assertSameValue(null,$e->getPrevious(),'no raw previous exception'); }
                assertSameValue(false,Migration::isBackfillComplete($restricted),'denial not complete');
            } finally { $restricted->close(); }
            $family=array_values(array_filter($f->tables(),static fn($table)=>in_array($table,['fm2_assignment_order_identities','fm2_assignment_order_id_receipts'],true)));
            assertSameValue($grants==='SELECT'?0:2,count($family),'DDL denial is distinguished from post-DDL DML denial');
            assertSameValue(['applied'=>true],Migration::apply($f->connection),'privileged recovery after denied operation');
        });
    }
    echo "ASSIGNMENT_ORDER_IDENTITY_REGISTRY_SCHEMA_001_OK\n";
} catch (TestFailure $failure) {
    fwrite(STDERR,$failure->getMessage()."\n"); exit(1);
}
