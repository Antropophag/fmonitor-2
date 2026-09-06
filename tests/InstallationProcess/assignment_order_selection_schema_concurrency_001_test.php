<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/SelectionSchemaWorkerControl.php';
use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaMigration as Migration;
use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaMigrationVerification as Verification;
use FMonitor2\InstallationProcess\ProductionProcessSchemaMigration;
use FMonitor2\InstallationProcess\AssignmentOrderIdentityRegistryMigration as Registry;
use FMonitor2\Tests\Support\SelectionSchemaAssertions as Check;
use FMonitor2\Tests\Support\SelectionSchemaTestDatabase as Fixture;

// ASSIGNMENT-ORDER-SELECTION-SCHEMA-001 v0.2 §6–7. Exact owned native workers only.
function selectionWorkers(Fixture $f, callable $body): void
{
    $workers = []; $errors = [];
    try { $body($workers); } catch (Throwable $error) { $errors[] = $error->getMessage(); }
    foreach ($workers as &$worker) {
        try { aossCleanupWorker($worker); } catch (Throwable $error) { $errors[] = $error->getMessage(); }
    }
    unset($worker);
    if ($errors !== []) { throw new TestFailure(implode(' | ', $errors)); }
}
Check::run('native worker pipe/termination control', static function (Fixture $f): void {
    selectionWorkers($f, static function (array &$workers) use ($f): void {
        $before = $f->state(); $workers[] = aossStartWorker($f->source, '', 'control');
        aossWaitPhase($workers[0], 'control');
        assertSameValue(true, proc_terminate($workers[0]['process'], 15), 'terminate exact live owned worker');
        $result = aossReapWorker($workers[0], 1);
        assertSameValue([true,15,''], [$result['signaled'],$result['termsig'],$result['stderr']], 'native TERM/reap and pipe control');
        assertSameValue($before, $f->state(), 'control creates no schema/domain facts');
    });
});
Check::run('same-prefix timeout and other-prefix progress', static function (Fixture $f): void {
    ProductionProcessSchemaMigration::apply($f->db, 'other_');
    Registry::apply($f->db, 'other_'); $before = $f->state(); Check::missing();
    selectionWorkers($f, static function (array &$workers) use ($f, $before): void {
        $workers[] = aossStartWorker($f->source, '', 'lock_acquired'); aossWaitPhase($workers[0], 'lock_acquired');
        $workers[] = aossStartWorker($f->source, '', '');
        $workers[] = aossStartWorker($f->source, 'other_', '');
        $other = aossReapWorker($workers[2], 5);
        assertSameValue([0,''], [$other['exit'],$other['stderr']], 'other prefix completes before lock owner release');
        assertSameValue(true, str_contains($other['stdout'], 'RESULT {"applied":true}'), 'other prefix creates family');
        $second = aossReapWorker($workers[1], 8); $seconds = (hrtime(true) - $workers[1]['started']) / 1e9;
        assertSameValue(true, $seconds >= 3 && $seconds <= 7, 'same-prefix timeout5s±2');
        assertSameValue([2,''], [$second['exit'],$second['stderr']], 'lock timeout fails');
        assertSameValue(true, str_contains($second['stdout'], 'Assignment order selection schema unavailable.'), 'fixed lock failure');
        assertSameValue(false, in_array($f->name(0), $f->source->tables(), true), 'no same-prefix DDL under contention');
        assertSameValue($before, array_intersect_key($f->state(), $before), 'registry/source/decoy preserved');
        $otherBefore = serialize(Verification::snapshot($f->db, 'other_'));
        assertSameValue(9, fwrite($workers[0]['pipes'][0], "continue\n"), 'release owned barrier'); fflush($workers[0]['pipes'][0]);
        $first = aossReapWorker($workers[0]);
        assertSameValue([0,''], [$first['exit'],$first['stderr']], 'first creator completes');
        assertSameValue(['applied' => false], Migration::apply($f->db), 'following invocation is repeat');
        assertSameValue($otherBefore, serialize(Verification::snapshot($f->db, 'other_')), 'other family preserved');
    });
});
foreach (['selections_created', 'members_created', 'requests_created', 'events_created', 'audits_created'] as $index => $phase) {
    Check::run('native interruption after ' . $phase, static function (Fixture $f) use ($index, $phase): void {
        $before = $f->state(); Check::missing();
        selectionWorkers($f, static function (array &$workers) use ($f, $index, $phase, $before): void {
            $workers[] = aossStartWorker($f->source, '', $phase); aossWaitPhase($workers[0], $phase);
            $added = array_values(array_diff($f->source->tables(), array_keys($before)));
            $expected = []; for ($i = 0; $i <= $index; $i++) { $expected[] = $f->name($i); }
            sort($added); sort($expected); assertSameValue($expected, $added, 'observed actual durable CREATE prefix');
            assertSameValue(true, proc_terminate($workers[0]['process'], 15), 'terminate exact owned creator');
            $result = aossReapWorker($workers[0], 1);
            assertSameValue([true,15,''], [$result['signaled'],$result['termsig'],$result['stderr']], 'actual bounded native termination');
            assertSameValue(['applied' => $index < 4], Migration::apply($f->db), 'connection lock released and missing suffix recovered');
            assertSameValue(true, Migration::isReady($f->db), 'recovered schema ready');
            assertSameValue($before, array_intersect_key($f->state(), $before), 'original facts preserved across child death');
        });
    });
}
Check::finish();
