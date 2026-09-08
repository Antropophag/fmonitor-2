<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
use FMonitor2\InstallationProcess\AssignmentOrderIdentityRegistryMigration as Migration;
use FMonitor2\InstallationProcess\AssignmentOrderIdentityRegistryMigrationVerification as Verification;
use FMonitor2\InstallationProcess\AssignmentOrderIdentityRegistryObserver as Observer;
use FMonitor2\InstallationProcess\AssignmentOrderIdentityRegistryPhase as Phase;
use FMonitor2\InstallationProcess\DatabaseUnavailable;
use FMonitor2\Tests\Support\IdentityRegistryTestDatabase;

// ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001 v0.1; isolated FMONITOR_TEST_DB recovery.
function aoirRecoveryObserver(callable $callback): Observer
{
    return new class($callback) implements Observer {
        public function __construct(private $callback) {}
        public function observe(Phase $phase): void { ($this->callback)($phase); }
    };
}
function aoirRecoveryFixture(callable $scenario): void
{
    $f=new IdentityRegistryTestDatabase(); $error=null;
    try { $scenario($f); } catch(Throwable $e) { $error=$e; }
    try { $f->close(); } catch(Throwable $e) { throw new TestFailure(($error?$error->getMessage().' | ':'').$e->getMessage()); }
    if($error) { throw $error; }
}
try {
    $phases=['lock_acquired','registry_created','receipts_created','frontier_ready','before_backfill_commit','backfill_committed'];
    foreach($phases as $index=>$stop) {
        aoirRecoveryFixture(static function($f)use($phases,$index,$stop):void {
            $f->seedOrder(); $source=$f->allState(); $observed=[];
            assertSameValue(true,class_exists(Migration::class),'RED_ASSERTION: registry recovery engine is missing');
            $observer=aoirRecoveryObserver(static function(Phase $phase)use(&$observed,$stop):void {
                $observed[]=$phase->value;
                if($phase->value===$stop) { throw new RuntimeException('controlled schema-phase interruption'); }
            });
            try { Verification::apply($f->connection,'',$observer); throw new TestFailure('observer interruption became success'); }
            catch(DatabaseUnavailable $e) {
                assertSameValue('Assignment order identity registry unavailable.',$e->getMessage(),'fixed interrupted outcome');
                assertSameValue(null,$e->getPrevious(),'observer exception not exposed');
            }
            assertSameValue(array_slice($phases,0,$index+1),$observed,'exact independent phase prefix');
            $state=$f->allState();
            foreach($source as $table=>$before) { assertSameValue($before,$state[$table],'source/decoy unchanged through interruption '.$table); }
            $hasRegistry=in_array('fm2_assignment_order_identities',$f->tables(),true);
            $hasReceipts=in_array('fm2_assignment_order_id_receipts',$f->tables(),true);
            assertSameValue($index>=1,$hasRegistry,'registry DDL phase truthful');
            assertSameValue($index>=2,$hasReceipts,'receipt DDL phase truthful');
            if($hasRegistry) { assertSameValue($stop==='backfill_committed'?'1':'0',(string)$f->connection->query('SELECT COUNT(*) n FROM fm2_assignment_order_identities')->fetch_assoc()['n'],'uncommitted registry rows absent'); }
            if($hasReceipts) { assertSameValue($stop==='backfill_committed'?'1':'0',(string)$f->connection->query('SELECT COUNT(*) n FROM fm2_assignment_order_id_receipts')->fetch_assoc()['n'],'receipt and rows atomic'); }
            assertSameValue('0',(string)$f->connection->query('SELECT @@in_transaction n')->fetch_assoc()['n'],'owned transaction released after observer failure');
            $lock='fm2_aoir_'.substr(hash('sha256',$f->name."\0"),0,48);
            $q=$f->connection->prepare('SELECT IS_FREE_LOCK(?) n');$q->bind_param('s',$lock);$q->execute();assertSameValue('1',(string)$q->get_result()->fetch_assoc()['n'],'owned named lock released');$q->close();
            assertSameValue($stop==='backfill_committed',Migration::isBackfillComplete($f->connection),'only confirmed commit has completed receipt');
            assertSameValue(['applied'=>$stop!=='backfill_committed'],Migration::apply($f->connection),'retry preserves or completes exact owned state');
            $snap=Verification::snapshot($f->connection,'');assertSameValue('81',$snap->nextId,'frontier retained through interruption');
            assertSameValue(1,count($snap->identities),'one historical identity after recovery');assertSameValue('2',$snap->identities[0]->id,'original ID remains2');
            $repeatPhases=[];
            assertSameValue(['applied'=>false],Verification::apply($f->connection,'',aoirRecoveryObserver(static function(Phase $phase)use(&$repeatPhases):void{$repeatPhases[]=$phase->value;})),'repeat no-op');
            assertSameValue(['lock_acquired'],$repeatPhases,'repeat does not emit fictitious DDL/commit');
        });
    }
    aoirRecoveryFixture(static function($f):void {
        // Exact empty receipts-only state: unlike a committed receipt, this is recoverable.
        Migration::apply($f->connection);
        $f->connection->query('DELETE FROM fm2_assignment_order_id_receipts');
        $f->connection->query('DROP TABLE fm2_assignment_order_identities');
        assertSameValue(false,Migration::isBackfillComplete($f->connection),'empty one-table family incomplete');
        assertSameValue(['applied'=>true],Migration::apply($f->connection),'receipts-only empty partial is recoverable');
        assertSameValue('81',Verification::snapshot($f->connection,'')->nextId,'partial recovery preserves legacy frontier');
    });
    aoirRecoveryFixture(static function($f):void {
        Migration::apply($f->connection);
        $f->connection->query('DELETE FROM fm2_assignment_order_id_receipts');
        $f->connection->query('ALTER TABLE fm2_assignment_order_identities AUTO_INCREMENT=101');
        assertSameValue(['applied'=>true],Migration::apply($f->connection),'empty interrupted family with larger current frontier resumes');
        $snapshot=Verification::snapshot($f->connection,'');
        assertSameValue(['81','101','101'],[$snapshot->receipt->legacyNextId,$snapshot->receipt->preservedNextId,$snapshot->nextId],'current registry allocator gap is never lowered');
    });
    aoirRecoveryFixture(static function($f):void {
        $f->seedOrder();
        $observer=aoirRecoveryObserver(static function(Phase $phase)use($f):void {
            if($phase===Phase::FRONTIER_READY) { $f->connection->query("UPDATE fm2_assignment_orders SET prepared_at='2026-08-27T12:30:01+03:00'"); }
        });
        try { Verification::apply($f->connection,'',$observer);throw new TestFailure('changed source capture became success'); }
        catch(DatabaseUnavailable $e) { assertSameValue('Assignment order identity registry unavailable.',$e->getMessage(),'changed capture fails before backfill commit'); }
        assertSameValue('2026-08-27T12:30:01+03:00',$f->connection->query('SELECT prepared_at FROM fm2_assignment_orders WHERE id=2')->fetch_assoc()['prepared_at'],'engine does not repair fixture-owned source change');
        assertSameValue('0',(string)$f->connection->query('SELECT COUNT(*) n FROM fm2_assignment_order_identities')->fetch_assoc()['n'],'no stale registry rows committed');
        assertSameValue('0',(string)$f->connection->query('SELECT COUNT(*) n FROM fm2_assignment_order_id_receipts')->fetch_assoc()['n'],'no stale receipt committed');
        assertSameValue(['applied'=>true],Migration::apply($f->connection),'fresh controlled invocation can capture stable changed source');
        assertSameValue('2026-08-27T09:30:01.000000Z',Verification::snapshot($f->connection,'')->identities[0]->allocatedAtUtc,'new capture uses exact source instant');
    });
    aoirRecoveryFixture(static function($f):void {
        $f->seedOrder();
        $lock='fm2_aoir_'.substr(hash('sha256',$f->name."\0"),0,48);
        $observer=aoirRecoveryObserver(static function(Phase $phase)use($f,$lock):void {
            if($phase===Phase::BACKFILL_COMMITTED) {
                $q=$f->connection->prepare('SELECT RELEASE_LOCK(?) n');$q->bind_param('s',$lock);$q->execute();assertSameValue('1',(string)$q->get_result()->fetch_assoc()['n'],'fixture releases exact own lock');$q->close();
            }
        });
        try { Verification::apply($f->connection,'',$observer);throw new TestFailure('non1 engine RELEASE_LOCK became success'); }
        catch(DatabaseUnavailable $e) { assertSameValue('Assignment order identity registry unavailable.',$e->getMessage(),'release failure redacted'); }
        assertSameValue(true,Migration::isBackfillComplete($f->connection),'release failure does not erase committed receipt');
        assertSameValue(['applied'=>false],Migration::apply($f->connection),'followup acknowledges immutable backfill');
    });
    echo "ASSIGNMENT_ORDER_IDENTITY_REGISTRY_RECOVERY_001_OK\n";
} catch(TestFailure $e) { fwrite(STDERR,$e->getMessage()."\n");exit(1); }
