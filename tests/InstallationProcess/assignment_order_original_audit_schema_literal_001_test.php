<?php

declare(strict_types=1);
// FMONITOR_TEST_DB: ATTEMPT-AUDIT-001 v0.4 exact schema drift, Gate5 finding.
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityCommits.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalIntegrityDatabase.php';
require dirname(__DIR__).'/Support/AssignmentOrderOriginalAttemptAuditDatabase.php';
use FMonitor2\Tests\Support\OriginalAttemptAuditDatabase as A;

foreach(['formatting','quoted-case','quoted-space','quoted-backtick'] as $variant)A::case($variant,function($f)use($variant){
 assertSameValue(['applied'=>true,'reason'=>null],A::migrate($f),'valid v3 setup control');
 $table=$f->prefix.'fm2_assignment_order_original_audits';
 $rows=$f->db->query("SELECT CONSTRAINT_NAME,CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'")->fetch_all(MYSQLI_ASSOC);
 $pairs=array_values(array_filter($rows,fn($row)=>str_contains($row['CHECK_CLAUSE'],'authorization_denied')));assertSameValue(1,count($pairs),'one exact pair constraint');
 $name=$pairs[0]['CONSTRAINT_NAME'];assertSameValue(1,preg_match('/^[A-Za-z0-9_$]{1,64}$/D',$name),'fixture safe identifier');$clause=$pairs[0]['CHECK_CLAUSE'];
 $changed=match($variant){'formatting'=>' ( '.$clause.' ) ','quoted-case'=>str_replace('authorization_denied','Authorization_denied',$clause),'quoted-space'=>str_replace('authorization_denied','author ization_denied',$clause),'quoted-backtick'=>str_replace('authorization_denied','author`ization_denied',$clause)};
 assertSameValue(false,$changed===$clause,'fixture changes actual SQL text');
 $f->db->query("ALTER TABLE `{$table}` DROP CONSTRAINT `{$name}`, ADD CONSTRAINT `{$name}` CHECK ({$changed})");
 $before=$f->db->query("SHOW CREATE TABLE `{$table}`")->fetch_assoc()['Create Table'];$facts=$f->facts();
 assertSameValue(['applied'=>false,'reason'=>$variant==='formatting'?null:'SCHEMA_MIGRATION_CONFLICT'],A::migrate($f),'quoted values exact; only SQL formatting may normalize');
 assertSameValue([$before,$facts],[$f->db->query("SHOW CREATE TABLE `{$table}`")->fetch_assoc()['Create Table'],$f->facts()],'repeat/conflict never repairs or rewrites schema/facts');
});
integrityDone('ASSIGNMENT_ORDER_ORIGINAL_AUDIT_SCHEMA_LITERAL_OK');
