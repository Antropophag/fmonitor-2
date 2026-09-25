<?php
declare(strict_types=1);

require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/ObjectQueueFixture.php';

use FMonitor2\IdentityAccess\LocalRoleCatalog;

$fixture = null;
try {
    $fixture = new ObjectQueueFixture(dirname(__DIR__, 2));
    $db = $fixture->db;
    $prefix = $fixture->p;

    $catalog = LocalRoleCatalog::roles();
    foreach (['manager', 'construction_control_engineer'] as $code) {
        assertSameValue(true, in_array('inspection.schedule', $catalog[$code]['permissions'] ?? [], true), 'INTENDED_RED catalog grants '.$code);
    }
    foreach (array_diff(array_keys($catalog), ['manager', 'construction_control_engineer', 'construction_control_coordinator']) as $code) {
        assertSameValue(false, in_array('inspection.schedule', $catalog[$code]['permissions'] ?? [], true), 'catalog does not broaden '.$code);
    }
    $exact = [];
    foreach ($catalog as $code => $role) if (in_array('inspection.schedule', $role['permissions'], true)) $exact[] = $code;
    assertSameValue(['construction_control_engineer','construction_control_coordinator','manager'], $exact, 'only approved scheduling roles in canonical catalogue');

    echo "PASS: INSPECTION-VISIBLE-SCHEDULING-ACTION-001 exact role catalogue\n";
} finally {
    if ($fixture instanceof ObjectQueueFixture) $fixture->close();
}
