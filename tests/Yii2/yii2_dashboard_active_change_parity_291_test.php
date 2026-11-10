<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/ObjectQueueFixture.php';

// MINIMAL-OPERATIONAL-DASHBOARD-001: active = installation + document_closeout.
$f = new ObjectQueueFixture(dirname(__DIR__, 2));
try {
    $f->object(451202, 6102, 'working', '2026-09-01', 'Монтаж');
    $f->order(6122, 6102, 1, 7301);
    $f->checklist(6102, 1, 1);
    $f->object(451203, 6103, 'working', '2026-09-01', 'Документарное закрытие');
    $f->order(6133, 6103, 1, 7301);
    foreach (range(1, 41) as $item) $f->checklist(6103, $item, $item);
    $f->object(451204, 6104, 'needs_assignment_change', '2026-09-01', 'Ожидает изменения состава');
    $f->order(6144, 6104, 1, 7301, 'registered');

    $before = $f->facts();
    $queue = $f->queue();
    $counts = [];
    foreach (['installation', 'document_closeout', 'needs_assignment_change'] as $stage) {
        $counts[$stage] = $queue->read(9101, '', $stage, 1)['filters']['total'];
    }
    assertSameValue([2, 1, 1], array_values($counts), 'independent four-object stage fixture retains the change case');
    $changeRows=$queue->read(9101, '', 'needs_assignment_change', 1)['objects'];
    assertSameValue([[451204,6104,'Требуется изменение']],array_map(static fn(array$row):array=>[$row['id'],$row['caseId'],$row['status']],$changeRows),'exact registered change case remains visible in its canonical filter');
    $owner = FMonitor2\YiiRuntime\InstallationProcessFactory::dashboard($f->connection(), $f->p, '');
    $dashboard = $owner->read(9101, '2026-09-20');
    assertSameValue(4, $dashboard['total'], 'change case remains in the total population');
    assertSameValue(3, $dashboard['active'], 'INTENDED_RED: registered change case is excluded from active installation and closeout');
    assertSameValue($counts['installation'] + $counts['document_closeout'], $dashboard['active'], 'dashboard and canonical queue have exact active parity');
    assertSameValue($before, $f->facts(), 'stage and dashboard reads create no facts');
    echo "PASS: dashboard active excludes registered assignment-change cases\n";
} finally {
    $f->close();
}
