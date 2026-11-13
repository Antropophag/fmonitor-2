<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use FMonitor2\InstallationProcess\AssignmentOrderOriginalStartSchemaMigration as M;
use FMonitor2\Tests\Support\IdentityRegistryTestDatabase as D;
use FMonitor2\RuntimeRestore\RuntimeRecoverySchemaV39 as R;
// ACTUAL-START-FROM-ORDER-001 A8: additive upgrade, idempotency, conflict and recovery inventory.
$f=new D('s39_');try {
    assertSameValue(true,class_exists(M::class),'INTENDED_RED: appendix date migration missing');
    $before=$f->allState();$first=M::apply($f->connection,'s39_');assertSameValue([true,39],[$first['applied'],$first['schemaVersion']],'additive migration39');
    foreach($before as$table=>$state)assertSameValue($state,$f->allState()[$table],'existing tables unchanged by migration');
    assertSameValue(true,in_array('s39_fm2_assignment_order_original_starts',$f->tables(),true),'durable date/intent table');
    $after=$f->allState();assertSameValue(false,M::apply($f->connection,'s39_')['applied'],'migration replay');assertSameValue($after,$f->allState(),'schema replay is unchanged');
    assertSameValue(true,class_exists(R::class),'current recovery profile39 exists');assertSameValue(true,in_array('s39_fm2_assignment_order_original_starts',R::tables('s39_'),true),'backup inventory contains durable date binding');
}finally{$f->close();}
$f=new D('bad_');try{
    $f->connection->query('CREATE TABLE bad_fm2_assignment_order_original_starts(id INT PRIMARY KEY) ENGINE=InnoDB');$before=$f->allState();$rejected=false;
    try{M::apply($f->connection,'bad_');}catch(Throwable){$rejected=true;}
    assertSameValue(true,$rejected,'incompatible table fails closed');assertSameValue($before,$f->allState(),'conflict performs no schema mutation');
}finally{$f->close();}
$f=new D('chk_');try{
    M::apply($f->connection,'chk_');
    $constraint=$f->connection->query("SELECT CONSTRAINT_NAME FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='chk_fm2_assignment_order_original_starts' AND CHECK_CLAUSE LIKE '%byte_size%' LIMIT 1")->fetch_column();
    assertSameValue(true,is_string($constraint)&&preg_match('/^[A-Za-z0-9_]+$/D',$constraint)===1,'byte bound belongs to schema contract');
    $f->connection->query('ALTER TABLE chk_fm2_assignment_order_original_starts DROP CONSTRAINT `'.$constraint.'`');$before=$f->allState();$rejected=false;
    try{M::apply($f->connection,'chk_');}catch(Throwable){$rejected=true;}
    assertSameValue(true,$rejected,'same columns without required byte bound fail closed');assertSameValue($before,$f->allState(),'constraint conflict does not rewrite data/schema');
}finally{$f->close();}
echo "PASS ACTUAL-START-FROM-ORDER-001 migration/recovery inventory\n";
