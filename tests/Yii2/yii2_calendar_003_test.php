<?php
declare(strict_types=1);
// YII2-CALENDAR-003 A1-A5 — real Yii HTTP, independently seeded ordering and no-write proof.
require dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/PreopeningFixture.php';

$f = null;
try {
    $f = new PreopeningFixture(dirname(__DIR__, 2));
    $p = $f->p;
    $f->db->query("DELETE FROM {$p}fm2_pilot_role_permissions WHERE role_id=5 AND permission='objects.read'");
    foreach ([
        [7103, 6101, 42, '2026-11-03'],
        [7102, 6102, 19, '2026-10-15'],
        [7101, 6103, 7, '2026-10-15'],
        [7104, 6104, 99, '2027-05-01'],
    ] as [$id, $case, $object, $date]) {
        $f->insert($p . 'fm2_pilot_inspection_schedules', [
            'id' => $id, 'installation_case_id' => $case, 'legacy_object_id' => $object,
            'control_engineer_user_id' => 73, 'inspection_date' => $date,
            'scheduled_by_user_id' => 18, 'scheduled_at' => '2026-09-19T12:00:00+03:00',
        ]);
    }
    $f->start(['FMONITOR_NOW' => '2026-09-19T12:00:00+03:00']);
    $allowed = []; $denied = []; $guest = [];
    assertSameValue(303, $f->login($allowed, 18)['status'], 'allowed login');
    assertSameValue(303, $f->login($denied, 95)['status'], 'denied login');
    $before = $f->facts();

    $page = $f->request('GET', '/pilot/calendar?date=2026-10-15', [], $allowed);
    assertSameValue(200, $page['status'], 'INTENDED_RED YII2-CALENDAR-003 route currently 404');
    assertSameValue(['no-store'], $page['headers']['cache-control'] ?? [], 'calendar no-store');
    foreach (['data-calendar-page', 'data-shlz-calendar-grid', 'Календарь работ', '15.10.2026'] as $needle) {
        assertSameValue(true, str_contains($page['body'], $needle), 'calendar markup ' . $needle);
    }
    assertSameValue(1, substr_count($page['body'], 'href="/pilot/calendar" aria-current="page"'), 'one current calendar link');
    assertSameValue(true, strpos($page['body'], 'href="/pilot/objects" aria-label="Объекты монтажа"') < strpos($page['body'], 'href="/pilot/calendar" aria-current="page"'), 'calendar follows objects');
    assertSameValue(true, strpos($page['body'], 'data-object-id="7"') < strpos($page['body'], 'data-object-id="19"'), 'same-day numeric object order independent of insertion');
    assertSameValue(true, strpos($page['body'], '2026-10-15') < strpos($page['body'], '2026-11-03'), 'date/month chronology');
    assertSameValue(false, str_contains($page['body'], '2027-05-01'), 'out-of-range schedule hidden');
    foreach ([7101, 7102] as $id) assertSameValue(true, str_contains($page['body'], 'data-schedule-id="' . $id . '"'), 'schedule visible ' . $id);

    $slash = $f->request('GET', '/pilot/calendar/', [], $allowed);
    assertSameValue(200, $slash['status'], 'slash route');
    assertSameValue($before, $f->facts(), 'repeated calendar GET is byte-equivalent no-write');
    assertSameValue(403, $f->request('GET', '/pilot/calendar', [], $denied)['status'], 'objects.read required');
    $login = $f->request('GET', '/pilot/calendar', [], $guest);
    assertSameValue([303, '/pilot/login'], [$login['status'], $login['headers']['location'][0] ?? null], 'guest login flow');
    foreach (['date=bad', 'date=2026-01-01', 'date%5B%5D=2026-10-15', 'date=2026-10-15&extra=1'] as $query) {
        $bad = $f->request('GET', '/pilot/calendar?' . $query, [], $allowed);
        assertSameValue(400, $bad['status'], 'invalid query ' . $query);
        assertSameValue(false, str_contains($bad['body'], 'data-calendar-page'), 'no partial HTML');
    }
    assertSameValue($before, $f->facts(), 'all reads and rejections preserve facts/schema');
    $head = $f->request('HEAD', '/pilot/calendar', [], $allowed);
    assertSameValue([200, ''], [$head['status'], $head['body']], 'HEAD route and empty body');
    $f->noLegacy();
    echo "PASS YII2-CALENDAR-003 HTTP deterministic read-only calendar\n";
} finally {
    if ($f instanceof PreopeningFixture) $f->close();
}
