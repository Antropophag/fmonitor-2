<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Yii2/PreopeningFixture.php';
use FMonitor2\InstallationProcess\ObjectDetailsEditingSchemaMigration;
if(getenv('FMONITOR_FIXTURE_REACHABILITY')==='object-details-recovery-fixture'){assertSameValue(true,is_file(dirname(__DIR__,2).'/app/RuntimeRestore/RuntimeRecovery.php'),'recovery fixture');echo"FIXTURE_REACHABLE: object-details-recovery-fixture\n";exit(0);}
assertSameValue(true,class_exists(ObjectDetailsEditingSchemaMigration::class),'INTENDED_RED OBJECT-DETAILS-EDITING-001 additive schema owner exists');
$source=(string)file_get_contents(dirname(__DIR__,2).'/app/RuntimeRestore/RuntimeRecovery.php');$inventory=FMonitor2\RuntimeRestore\RuntimeRecoverySchemaV35::tables('');
assertSameValue(true,str_contains($source,'RuntimeRecoverySchemaV35::tables'),'A15 current recovery consumes the canonical inventory owner');
foreach(['fm2_object_detail_edits','fm2_object_detail_edit_events','fm2_object_detail_edit_requests']as$table)assertSameValue(true,in_array($table,$inventory,true),'A15 current recovery inventory '.$table);
$f=null;try{$f=new PreopeningFixture(dirname(__DIR__,2));$p=$f->p;$before=[];foreach(['fm2_object_detail_edits','fm2_object_detail_edit_events','fm2_object_detail_edit_requests']as$table)$before[$table]=(int)$f->db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$p}{$table}'")->fetch_column();assertSameValue([1,1,1],array_values($before),'A15 fresh canonical migration created all tables');$repeat=ObjectDetailsEditingSchemaMigration::apply($f->db,$p);assertSameValue(false,$repeat['applied'],'A15 exact repeat no-op');$f->db->query("ALTER TABLE {$p}fm2_object_detail_edit_requests DROP INDEX object_requests");$thrown=false;try{ObjectDetailsEditingSchemaMigration::apply($f->db,$p);}catch(RuntimeException){$thrown=true;}assertSameValue(true,$thrown,'A15 incompatible index fails closed');assertSameValue(1,(int)$f->db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$p}fm2_object_detail_edit_requests'")->fetch_column(),'A15 incompatible state not rebuilt');}finally{if($f instanceof PreopeningFixture)$f->close();}
echo "PASS: OBJECT-DETAILS-EDITING-001 recovery inventory\n";
