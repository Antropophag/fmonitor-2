<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';

use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaMigration as Migration;
use FMonitor2\InstallationProcess\AssignmentOrderIdentityRegistryMigration as Registry;
use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaMigrationVerification as Verification;
use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaObserver as Observer;
use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaPhase as Phase;
use FMonitor2\Tests\Support\SelectionSchemaAssertions as Check;
use FMonitor2\Tests\Support\SelectionSchemaTestDatabase as Fixture;

// ASSIGNMENT-ORDER-SELECTION-SCHEMA-001 v0.2 §§2,6,7: public rejection/resource contracts.
function selectionInvalid(callable $action): void
{
    try { $action(); throw new TestFailure('Expected invalid configuration'); }
    catch (InvalidArgumentException $error) {
        assertSameValue('Invalid selection schema migration configuration.', $error->getMessage(), 'fixed invalid configuration');
    }
}
foreach (['apply', 'isReady', 'snapshot'] as $method) {
    Check::run('prefix26 beforeSQL ' . $method, static function (Fixture $f) use ($method): void {
        $closed = $f->source->connect(); $closed->close(); Check::missing();
        $class = $method === 'snapshot' ? Verification::class : Migration::class;
        selectionInvalid(fn () => $class::$method($closed, str_repeat('p', 26)));
    });
    Check::run('closed connection ' . $method, static function (Fixture $f) use ($method): void {
        $closed = $f->source->connect(); $closed->close(); Check::missing();
        if ($method === 'isReady') { assertSameValue(false, Migration::isReady($closed), 'closed read is not ready'); }
        else {
            $class = $method === 'snapshot' ? Verification::class : Migration::class;
            Check::unavailable(fn () => $class::$method($closed, ''));
        }
    });
}
foreach (['charset', 'isolation', 'database', 'transaction'] as $kind) {
    Check::run('borrowed configuration ' . $kind, static function (Fixture $f) use ($kind): void {
        $f->create(); $before = $f->state();
        $db = $f->source->connect($kind === 'database' ? null : $f->source->name);
        try {
            $db->query('SET SESSION TRANSACTION ISOLATION LEVEL ' . ($kind === 'isolation' ? 'READ COMMITTED' : 'REPEATABLE READ'));
            if ($kind === 'charset') { $db->set_charset('latin1'); }
            if ($kind === 'transaction') {
                $db->begin_transaction(); $db->query("UPDATE other_prefix_marker SET value='pending-caller-write' WHERE id=1");
                $db->query('SAVEPOINT caller_owned');
            }
            Check::missing(); selectionInvalid(fn () => Migration::apply($db));
            assertSameValue(false, Migration::isReady($db), 'invalid borrowed state not ready');
            Check::unavailable(fn () => Verification::snapshot($db, ''));
            if ($kind === 'transaction') {
                assertSameValue('1', (string)$db->query('SELECT @@in_transaction n')->fetch_assoc()['n'], 'caller transaction remains');
                $db->query('ROLLBACK TO SAVEPOINT caller_owned');
                assertSameValue('pending-caller-write', $db->query('SELECT value FROM other_prefix_marker WHERE id=1')->fetch_assoc()['value'], 'no implicit rollback/commit');
                $db->rollback();
            }
            assertSameValue($kind === 'charset' ? 'latin1' : 'utf8mb4', $db->character_set_name(), 'charset not reset');
            assertSameValue($kind === 'isolation' ? 'READ-COMMITTED' : 'REPEATABLE-READ', $db->query('SELECT @@tx_isolation n')->fetch_assoc()['n'], 'isolation not reset');
            assertSameValue($before, $f->state(), 'configuration rejection does not mutate shared state');
        } finally { $db->rollback(); $db->close(); }
    });
}
foreach ([false, true] as $populated) {
    Check::run($populated ? 'repeat with DML denied' : 'native DDL denied', static function (Fixture $f) use ($populated): void {
        if ($populated) { $f->create(); $f->populate(); }
        $before = $f->state(); $restricted = $f->source->restricted('SELECT,REFERENCES');
        try {
            $restricted->query('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            assertSameValue(true, Registry::isBackfillComplete($restricted), 'restricted principal can prove prerequisite before target action');
            try {
                $restricted->query("UPDATE other_prefix_marker SET value='forbidden' WHERE id=1");
                throw new TestFailure('Fixture unexpectedly permits DML');
            } catch (mysqli_sql_exception $error) { assertSameValue(1142, $error->getCode(), 'actual native DML denial'); }
            Check::missing();
            if ($populated) {
                assertSameValue(['applied' => false], Migration::apply($restricted), 'read-only grants sufficient for repeat');
                assertSameValue(true, Migration::isReady($restricted), 'read-only grants prove compatible data');
                assertSameValue('2', Verification::snapshot($restricted, '')->tables[0]->rowCount, 'real read-only snapshot');
            } else { Check::unavailable(fn () => Migration::apply($restricted)); }
            assertSameValue($before, $f->state(), 'denied grants never mutate fixture');
        } finally { $restricted->close(); }
    });
}
foreach (['lock_acquired', 'selections_created', 'members_created', 'requests_created', 'events_created', 'audits_created', 'family_verified'] as $index => $stop) {
    Check::run('observer throws ' . $stop, static function (Fixture $f) use ($index, $stop): void {
        $before = $f->state(); Check::missing();
        $observer = new class($stop) implements Observer {
            public array $seen = [];
            public function __construct(private string $stop) {}
            public function observe(Phase $phase): void {
                $this->seen[] = $phase->value;
                if ($phase->value === $this->stop) { throw new RuntimeException('synthetic observer failure'); }
            }
        };
        Check::unavailable(fn () => Verification::apply($f->db, '', $observer));
        $phases = ['lock_acquired','selections_created','members_created','requests_created','events_created','audits_created','family_verified'];
        assertSameValue(array_slice($phases, 0, $index + 1), $observer->seen, 'exact actual phases before failure');
        $created = array_values(array_diff(array_keys($f->state()), array_keys($before)));
        $expected = []; for ($i = 0; $i < min($index, 5); $i++) { $expected[] = $f->name($i); }
        sort($created); sort($expected); assertSameValue($expected, $created, 'durable DDL prefix retained');
        assertSameValue($before, array_intersect_key($f->state(), $before), 'source/registry untouched');
        assertSameValue('0', (string)$f->db->query('SELECT @@in_transaction n')->fetch_assoc()['n'], 'owned read transaction released');
        $name = 'fm2_aoss_' . substr(hash('sha256', $f->source->name . "\0"), 0, 48);
        $other = $f->source->connect();
        try {
            $q = $other->prepare('SELECT GET_LOCK(?,0) n'); $q->execute([$name]);
            assertSameValue('1', (string)$q->get_result()->fetch_assoc()['n'], 'migration lock released after observer failure'); $q->close();
            $q = $other->prepare('SELECT RELEASE_LOCK(?) n'); $q->execute([$name]); $q->close();
        } finally { $other->close(); }
        assertSameValue(['applied' => $index < 5], Migration::apply($f->db), 'recovery creates only remaining suffix');
        assertSameValue(true, Migration::isReady($f->db), 'recovered family ready');
    });
}
Check::run('release failure after completed proof', static function (Fixture $f): void {
    Check::missing();
    $observer = new class($f->db, $f->source->name) implements Observer {
        public function __construct(private mysqli $db, private string $database) {}
        public function observe(Phase $phase): void {
            if ($phase !== Phase::FAMILY_VERIFIED) { return; }
            $name = 'fm2_aoss_' . substr(hash('sha256', $this->database . "\0"), 0, 48);
            $q = $this->db->prepare('SELECT RELEASE_LOCK(?) n');
            $q->execute([$name]); assertSameValue('1', (string)$q->get_result()->fetch_assoc()['n'], 'observer releases its own lock'); $q->close();
        }
    };
    Check::unavailable(fn () => Verification::apply($f->db, '', $observer));
    $before = $f->state(); assertSameValue(['applied' => false], Migration::apply($f->db), 'completed DDL retained after release failure');
    assertSameValue($before, $f->state(), 'no repair after uncertain release');
});
Check::finish();
