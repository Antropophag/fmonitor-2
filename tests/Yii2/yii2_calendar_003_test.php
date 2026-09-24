<?php
declare(strict_types=1);
// YII2-CALENDAR-003 A1-A5 — real Yii HTTP, independently seeded ordering and no-write proof.
require dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/PreopeningFixture.php';

$f = null;
try {
    $f = new PreopeningFixture(dirname(__DIR__, 2));
    $p = $f->p;
    $f->db->query("INSERT IGNORE INTO {$p}fm2_pilot_user_roles(user_id,role_id,origin,assigned_at,assigned_by_user_id) VALUES(18,7,'fixture','2026-09-19T12:00:00+03:00',18)");
    $f->db->query("DELETE FROM {$p}fm2_pilot_role_permissions WHERE role_id=5 AND permission='objects.read'");
    foreach ([
        [7103, 6101, 42, '2026-11-03'],
        [7102, 6102, 19, '2026-10-15'],
        [7101, 6103, 7, '2026-10-15'],
        [7105, 6105, 20, '2026-10-15'],
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

    $f->db->query("UPDATE {$p}fm_maintable SET workdatestart='2026-10-15',workdatefinish='2026-11-03',plan_finish_date='2026-11-04' WHERE id=4512");

    $page = $f->request('GET', '/pilot/calendar?date=2026-10-15', [], $allowed);
    assertSameValue(200, $page['status'], 'INTENDED_RED YII2-CALENDAR-003 route currently 404');
    assertSameValue(['no-store'], $page['headers']['cache-control'] ?? [], 'calendar no-store');
    foreach (['data-calendar-page', 'data-shlz-calendar-grid', 'Календарь работ', '15.10.2026'] as $needle) {
        assertSameValue(true, str_contains($page['body'], $needle), 'calendar markup ' . $needle);
    }
    $dom=new DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$page['body']);$xp=new DOMXPath($dom);
    $rows=[];foreach($xp->query('//tbody/tr/th[@scope="row"]/span[1]')as$node)$rows[]=trim($node->textContent);
    assertSameValue(['Плановое начало','Плановое завершение','Инспекции'],$rows,'INTENDED_RED three canonical calendar rows');
    foreach(['accent','warning','success']as$tone)assertSameValue(true,$xp->query('//*[@data-tone="'.$tone.'"]')->length>0,'INTENDED_RED distinct shlz tone '.$tone);
    assertSameValue(1,$xp->query('//tr[th[@id="calendar-row-planned_start"]]/td[@headers="calendar-row-planned_start calendar-day-2026-10-15"]//*[@data-object-id="4512" and @data-tone="accent"]')->length,'planned start exact row/date/object/tone');
    assertSameValue(1,$xp->query('//tr[th[@id="calendar-row-planned_end"]]/td[@headers="calendar-row-planned_end calendar-day-2026-11-03"]//*[@data-object-id="4512" and @data-tone="warning"]')->length,'planned finish exact row/date/object/tone');
    assertSameValue(true,$xp->query('//tr[th[@id="calendar-row-inspection"]]//*[@data-schedule-id="7101" and @data-tone="success"]')->length>0,'inspection exact row/type/tone');
    $disclosure=$xp->query('//td[@headers="calendar-row-inspection calendar-day-2026-10-15"]//button[@data-shlz-calendar-grid-disclosure="cell" and @aria-expanded="false"]')->item(0);assertSameValue(true,$disclosure!==null&&trim($disclosure->textContent)==='Ещё 1','calendar cell disclosure after first two events');$overflowId=$disclosure->getAttribute('aria-controls');assertSameValue(1,$xp->query('//*[@id="'.$overflowId.'" and @hidden]//*[@data-schedule-id="7105"]')->length,'third event starts hidden in nested list');
    $calendarCss=(string)file_get_contents(dirname(__DIR__,2).'/app/YiiRuntime/Assets/pilot.css');assertSameValue(true,str_contains($calendarCss,'.shlz-calendar-grid__disclosure + .shlz-calendar-grid__items:not([hidden])')&&str_contains($calendarCss,'margin-block-start'),'expanded disclosure list has vertical separation');
    $startAgendaNode=$xp->query('//*[@data-calendar-agenda]//*[@data-object-id="4512" and @data-event-type="planned_start"]')->item(0);assertSameValue(true,$startAgendaNode!==null&&$xp->query('./*[@data-tone="accent"]',$startAgendaNode)->length===1&&str_contains($startAgendaNode->textContent,'Плановое начало'),'INTENDED_RED planned-start agenda exact text/type/tone');
    $finishAgenda=$f->request('GET','/pilot/calendar?date=2026-11-03',[],$allowed);$finishDom=new DOMDocument();@$finishDom->loadHTML('<?xml encoding="UTF-8">'.$finishAgenda['body']);$finishXp=new DOMXPath($finishDom);$finishAgendaNode=$finishXp->query('//*[@data-calendar-agenda]//*[@data-object-id="4512" and @data-event-type="planned_end"]')->item(0);assertSameValue(true,$finishAgendaNode!==null&&$finishXp->query('./*[@data-tone="warning"]',$finishAgendaNode)->length===1&&str_contains($finishAgendaNode->textContent,'Плановое завершение'),'INTENDED_RED planned-end agenda exact text/type/tone');
    $inspectionAgendaNode=$xp->query('//*[@data-calendar-agenda]//*[@data-schedule-id="7101" and @data-event-type="inspection"]')->item(0);assertSameValue(true,$inspectionAgendaNode!==null&&$xp->query('./*[@data-tone="success"]',$inspectionAgendaNode)->length===1&&str_contains($inspectionAgendaNode->textContent,'Инспекции'),'INTENDED_RED inspection agenda exact text/type/tone');
    $f->db->query("UPDATE {$p}fm_maintable SET workdatefinish='',plan_finish_date='2026-11-04' WHERE id=4512");
    $fallback=$f->request('GET','/pilot/calendar?date=2026-11-04',[],$allowed);$fallbackDom=new DOMDocument();@$fallbackDom->loadHTML('<?xml encoding="UTF-8">'.$fallback['body']);$fallbackXp=new DOMXPath($fallbackDom);
    assertSameValue(1,$fallbackXp->query('//tr[th[@id="calendar-row-planned_end"]]/td[@headers="calendar-row-planned_end calendar-day-2026-11-04"]//*[@data-object-id="4512" and @data-event-type="planned_end" and @data-tone="warning"]')->length,'planned finish fallback exact object/type/tone');
    assertSameValue(0,$fallbackXp->query('//td[@headers="calendar-row-planned_end calendar-day-2026-11-03"]//*[@data-object-id="4512" and @data-event-type="planned_end"]')->length,'superseded finish date absent');
    $f->db->query("UPDATE {$p}fm_maintable SET workdatestart=NULL,workdatefinish=NULL,plan_finish_date=NULL WHERE id=4512");
    $missing=$f->request('GET','/pilot/calendar',[],$allowed);
    assertSameValue(false,str_contains($missing['body'],'data-object-id="4512" data-event-type="planned_'),'missing planned dates fabricate no event');
    assertSameValue(1, substr_count($page['body'], 'href="/pilot/calendar" aria-current="page"'), 'one current calendar link');
    assertSameValue(true, strpos($page['body'], 'href="/pilot/objects" aria-label="Объекты монтажа"') < strpos($page['body'], 'href="/pilot/calendar" aria-current="page"'), 'calendar follows objects');
    assertSameValue(true, strpos($page['body'], 'data-object-id="7"') < strpos($page['body'], 'data-object-id="19"'), 'same-day numeric object order independent of insertion');
    assertSameValue(true, strpos($page['body'], '2026-10-15') < strpos($page['body'], '2026-11-03'), 'date/month chronology');
    assertSameValue(false, str_contains($page['body'], '2027-05-01'), 'out-of-range schedule hidden');
    foreach ([7101, 7102] as $id) assertSameValue(true, str_contains($page['body'], 'data-schedule-id="' . $id . '"'), 'schedule visible ' . $id);

    $slash = $f->request('GET', '/pilot/calendar/', [], $allowed);
    assertSameValue(200, $slash['status'], 'slash route');
    $before=$f->facts();$repeat=$f->request('GET','/pilot/calendar',[],$allowed);assertSameValue(200,$repeat['status'],'repeat calendar GET');assertSameValue($before, $f->facts(), 'repeated calendar GET is byte-equivalent no-write');
    assertSameValue(403, $f->request('GET', '/pilot/calendar', [], $denied)['status'], 'objects.read required');
    $login = $f->request('GET', '/pilot/calendar', [], $guest);
    assertSameValue([303, '/pilot/login'], [$login['status'], $login['headers']['location'][0] ?? null], 'guest login flow');
    foreach (['date=bad', 'date=2026-01-01', 'date%5B%5D=2026-10-15', 'date=2026-10-15&date=2026-11-03', 'date=2026-10-15&extra=1'] as $query) {
        $bad = $f->request('GET', '/pilot/calendar?' . $query, [], $allowed);
        assertSameValue(400, $bad['status'], 'invalid query ' . $query);
        assertSameValue(false, str_contains($bad['body'], 'data-calendar-page'), 'no partial HTML');
    }
    assertSameValue($before, $f->facts(), 'all reads and rejections preserve facts/schema');
    $head = $f->request('HEAD', '/pilot/calendar', [], $allowed);
    assertSameValue([200, ''], [$head['status'], $head['body']], 'HEAD route and empty body');

    $insertPlanned=static function(int$count,int$base)use($f,$p):void{for($offset=0;$offset<$count;$offset+=400){$values=[];for($i=$offset;$i<min($count,$offset+400);$i++){$id=$base+$i;$values[]="($id,'Плановый адрес $id','1','P-$id','2026-10-20',NULL,NULL)";}$f->db->query("INSERT INTO {$p}fm_maintable(id,ordadr_address,entrance,regnumber,workdatestart,workdatefinish,plan_finish_date) VALUES".implode(',',$values));}};
    $insertSchedules=static function(int$count,int$base)use($f,$p):void{for($offset=0;$offset<$count;$offset+=400){$values=[];for($i=$offset;$i<min($count,$offset+400);$i++){$id=$base+$i;$object=$base+$i;$values[]="($id,$id,$object,73,'2026-10-20',18,'2026-09-19T12:00:00+03:00')";}$f->db->query("INSERT INTO {$p}fm2_pilot_inspection_schedules(id,installation_case_id,legacy_object_id,control_engineer_user_id,inspection_date,scheduled_by_user_id,scheduled_at) VALUES".implode(',',$values));}};
    $insertPlanned(5001,200000);$plannedCount=(int)$f->db->query("SELECT COUNT(*) FROM {$p}fm_maintable WHERE id>=200000 AND id<210000")->fetch_column();$plannedFacts=$f->facts();$plannedOverflow=$f->request('GET','/pilot/calendar',[],$allowed);assertSameValue(503,$plannedOverflow['status'],'INTENDED_RED planned-only overflow fails closed');assertSameValue(false,str_contains($plannedOverflow['body'],'data-calendar-page'),'planned-only overflow no partial HTML');assertSameValue(false,str_contains($plannedOverflow['body'],'SQLSTATE')||str_contains($plannedOverflow['body'],$p),'planned-only overflow safe');assertSameValue($plannedCount,(int)$f->db->query("SELECT COUNT(*) FROM {$p}fm_maintable WHERE id>=200000 AND id<210000")->fetch_column(),'planned-only overflow source stable');assertSameValue($plannedFacts,$f->facts(),'planned-only overflow full facts no-write');$f->db->query("DELETE FROM {$p}fm_maintable WHERE id>=200000 AND id<210000");
    $insertPlanned(2000,220000);$insertSchedules(3000,230000);$mixedPlanned=(int)$f->db->query("SELECT COUNT(*) FROM {$p}fm_maintable WHERE id>=220000 AND id<222000")->fetch_column();$mixedSchedules=(int)$f->db->query("SELECT COUNT(*) FROM {$p}fm2_pilot_inspection_schedules WHERE id>=230000 AND id<233000")->fetch_column();$mixedFacts=$f->facts();$mixedOverflow=$f->request('GET','/pilot/calendar',[],$allowed);assertSameValue(503,$mixedOverflow['status'],'INTENDED_RED mixed combined overflow fails closed');assertSameValue(false,str_contains($mixedOverflow['body'],'data-calendar-page'),'mixed overflow no partial HTML');assertSameValue(false,str_contains($mixedOverflow['body'],'SQLSTATE')||str_contains($mixedOverflow['body'],$p),'mixed overflow safe');assertSameValue([$mixedPlanned,$mixedSchedules],[(int)$f->db->query("SELECT COUNT(*) FROM {$p}fm_maintable WHERE id>=220000 AND id<222000")->fetch_column(),(int)$f->db->query("SELECT COUNT(*) FROM {$p}fm2_pilot_inspection_schedules WHERE id>=230000 AND id<233000")->fetch_column()],'mixed overflow sources stable');assertSameValue($mixedFacts,$f->facts(),'mixed overflow full facts no-write');$f->db->query("DELETE FROM {$p}fm2_pilot_inspection_schedules WHERE id>=230000 AND id<233000");$f->db->query("DELETE FROM {$p}fm_maintable WHERE id>=220000 AND id<222000");

    $planningState = static function () use ($f, $p): array {
        $tables = [];
        foreach (['fm2_pilot_inspection_schedules', 'fm2_pilot_inspection_schedule_events'] as $suffix) {
            $table = $p . $suffix;
            $ddl = $f->db->query("SHOW CREATE TABLE `$table`")->fetch_row()[1];
            $rows = $f->db->query("SELECT * FROM `$table` ORDER BY 1")->fetch_all(MYSQLI_ASSOC);
            $tables[$suffix] = [$ddl, $rows];
        }
        return $tables;
    };
    for ($offset = 0; $offset < 4998; $offset += 400) {
        $values = [];
        for ($i = $offset; $i < min(4998, $offset + 400); $i++) {
            $id = 8000 + $i; $date = (new DateTimeImmutable('2026-09-20'))->modify('+' . ($i % 170) . ' days')->format('Y-m-d');
            $values[] = "($id,$id," . (100000 + $i) . ",73,'$date',18,'2026-09-19T12:00:00+03:00')";
        }
        $f->db->query("INSERT INTO {$p}fm2_pilot_inspection_schedules(id,installation_case_id,legacy_object_id,control_engineer_user_id,inspection_date,scheduled_by_user_id,scheduled_at) VALUES" . implode(',', $values));
    }
    $overflowBefore = $planningState();
    $overflow = $f->request('GET', '/pilot/calendar', [], $allowed);
    assertSameValue(503, $overflow['status'], 'bounded source overflow fails closed');
    assertSameValue(false, str_contains($overflow['body'], 'data-calendar-page'), 'overflow has no partial calendar HTML');
    assertSameValue(false, str_contains($overflow['body'], 'SQLSTATE') || str_contains($overflow['body'], $p), 'overflow response is safe');
    assertSameValue($overflowBefore, $planningState(), 'overflow preserves planning facts and schema byte-for-byte');

    $f->db->query("DELETE FROM {$p}fm2_pilot_inspection_schedules WHERE id>=8000");
    $f->db->query("ALTER TABLE {$p}fm2_pilot_inspection_schedules DROP COLUMN scheduled_at");
    $schemaBefore = $planningState();
    $unavailable = $f->request('GET', '/pilot/calendar', [], $allowed);
    assertSameValue(503, $unavailable['status'], 'incompatible planning schema fails closed');
    assertSameValue(false, str_contains($unavailable['body'], 'data-calendar-page'), 'schema failure has no partial calendar HTML');
    assertSameValue(false, str_contains($unavailable['body'], 'SQLSTATE') || str_contains($unavailable['body'], $p), 'schema response is safe');
    assertSameValue($schemaBefore, $planningState(), 'schema failure performs no repair or fact write');
    $f->noLegacy();
    echo "PASS YII2-CALENDAR-003 HTTP deterministic read-only calendar\n";
} finally {
    if ($f instanceof PreopeningFixture) $f->close();
}
