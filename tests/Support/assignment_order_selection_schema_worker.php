<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaMigrationVerification as Verification;
use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaObserver as Observer;
use FMonitor2\InstallationProcess\AssignmentOrderSelectionSchemaPhase as Phase;
use FMonitor2\InstallationProcess\DatabaseUnavailable;

$name = $argv[1] ?? ''; $prefix = $argv[2] ?? ''; $stop = $argv[3] ?? '';
if (preg_match('/^t_aoir_matrix_[0-9a-f]{12}$/D', $name) !== 1 || preg_match('/^[A-Za-z0-9_]{0,25}$/D', $prefix) !== 1) { exit(64); }
function selectionBarrier(string $phase): void
{
    echo 'PHASE ' . $phase . "\n"; fflush(STDOUT);
    $read = [STDIN]; $write = null; $except = null;
    if (stream_select($read, $write, $except, 15) !== 1 || fgets(STDIN, 10) !== "continue\n") {
        throw new RuntimeException('Selection worker barrier failed.');
    }
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli(getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1', getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root',
    getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local', $name, (int)(getenv('FMONITOR_TEST_DB_PORT') ?: 23306));
$db->set_charset('utf8mb4'); $db->query('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ'); $code = 0;
try {
    if ($stop === 'control') { selectionBarrier('control'); echo "CONTROL_OK\n"; }
    else {
        $observer = new class($stop) implements Observer {
            public function __construct(private string $stop) {}
            public function observe(Phase $phase): void {
                if ($phase->value === $this->stop) { selectionBarrier($phase->value); }
                else { echo 'PHASE ' . $phase->value . "\n"; fflush(STDOUT); }
            }
        };
        echo 'RESULT ' . json_encode(Verification::apply($db, $prefix, $observer), JSON_THROW_ON_ERROR) . "\n";
    }
} catch (DatabaseUnavailable $error) {
    echo 'ERROR ' . json_encode(['class' => DatabaseUnavailable::class, 'message' => $error->getMessage(), 'previous' => $error->getPrevious() === null], JSON_THROW_ON_ERROR) . "\n";
    $code = 2;
} finally { $db->close(); }
exit($code);
