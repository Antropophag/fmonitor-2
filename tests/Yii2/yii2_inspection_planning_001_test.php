<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/ObjectQueueFixture.php';
// YII2-OBJECT-QUEUE-001: public scheduling owner, exact independent facts, rejection and rollback.
$f=null;
try {
    $f=new ObjectQueueFixture(dirname(__DIR__,2));$owner=$f->planning();$p=$f->p;
    $before=$f->facts();$result=$owner->scheduleInspection(9101,451201,'2026-09-12');
    assertSameValue('scheduled',$result['status'],'accepted schedule');$id=$result['scheduleId'];
    assertSameValue(true,is_int($id)&&$id>0,'positive schedule identity');
    assertSameValue([['id'=>(string)$id,'installation_case_id'=>'6101','legacy_object_id'=>'451201','control_engineer_user_id'=>'7301','inspection_date'=>'2026-09-12','scheduled_by_user_id'=>'9101','scheduled_at'=>'2026-09-10T09:30:00+03:00']],$f->rows('fm2_pilot_inspection_schedules'),'exact schedule all columns');
    $events=$f->rows('fm2_pilot_inspection_schedule_events');assertSameValue(1,count($events),'one event');
    $expected=['id'=>$events[0]['id'],'schedule_id'=>(string)$id,'installation_case_id'=>'6101','event_type'=>'inspection_scheduled','payload_json'=>'{"scheduleId":'.$id.',"inspectionDate":"2026-09-12","controlEngineerUserId":7301}','actor_user_id'=>'9101','occurred_at'=>'2026-09-10T09:30:00+03:00'];
    assertSameValue($expected,$events[0],'exact event references payload actor time');
    $after=$f->facts();foreach($before as$table=>$state)if(!in_array($table,[$p.'fm2_pilot_inspection_schedules',$p.'fm2_pilot_inspection_schedule_events'],true))assertSameValue($state,$after[$table],'unrelated history unchanged '.$table);
    $before=$f->facts();assertSameValue($result,$owner->scheduleInspection(9101,451201,'2026-09-12'),'exact duplicate response');assertSameValue($before,$f->facts(),'duplicate all history exact');
    foreach(['','2026-02-30','2026-9-12','2026-09-12junk','2026-09-09']as$date){$before=$f->facts();assertSameValue('invalid_date',$owner->scheduleInspection(9101,451201,$date)['status'],'invalid/past date '.$date);assertSameValue($before,$f->facts(),'date denial no writes');}
    assertSameValue('scheduled',$owner->scheduleInspection(9101,451201,'2026-09-10')['status'],'today allowed');
    $f->order(6113,6101,3,7302);assertSameValue('scheduled',$owner->scheduleInspection(9101,451201,'2026-09-12')['status'],'new engineer has distinct tuple');
    $rows=$f->rows('fm2_pilot_inspection_schedules');assertSameValue(3,count($rows),'new day and engineer preserved as facts');assertSameValue('7301',$rows[0]['control_engineer_user_id'],'old engineer retained');assertSameValue('7302',$rows[2]['control_engineer_user_id'],'new tuple engineer');
    foreach([9401,0,999999]as$actor){$before=$f->facts();assertSameValue('access_denied',$owner->scheduleInspection($actor,451201,'invalid')['status'],'authority precedes input');assertSameValue($before,$f->facts(),'denial no writes');}
    foreach([['status','0'],['activation_state',"'invited'"]]as[$column,$value]){$f->db->query("UPDATE {$p}fm2_pilot_users SET $column=$value WHERE user_id=9101");$before=$f->facts();assertSameValue('access_denied',$owner->scheduleInspection(9101,451201,'invalid')['status'],'inactive identity denies before date');assertSameValue($before,$f->facts(),'inactive unchanged');$f->db->query("UPDATE {$p}fm2_pilot_users SET status=1,activation_state='active' WHERE user_id=9101");}
    $f->http->auth->setRoleStatus(0);$before=$f->facts();assertSameValue('access_denied',$owner->scheduleInspection(9101,451201,'invalid')['status'],'inactive role');assertSameValue($before,$f->facts(),'role denial exact');$f->http->auth->setRoleStatus(1);
    $f->db->query("UPDATE {$p}fm2_pilot_role_permissions SET permission='Inspection.Schedule' WHERE permission='inspection.schedule'");$before=$f->facts();assertSameValue('access_denied',$owner->scheduleInspection(9101,451201,'invalid')['status'],'exact capability case sensitive');assertSameValue($before,$f->facts(),'near permission exact');$f->db->query("UPDATE {$p}fm2_pilot_role_permissions SET permission='inspection.schedule' WHERE permission='Inspection.Schedule'");
    $f->object(451202,6102);$f->order(6121,6102,1,7301);
    $f->object(451203,6103,'working');$f->order(6131,6103,1,7301);$f->order(6132,6103,2,7301,'prepared');
    $f->object(451204,6104,'working');$f->order(6141,6104,1,0);
    $f->object(451205,6105,'working');
    foreach([999999,451202,451203,451204,451205]as$object){$before=$f->facts();assertSameValue('ineligible',$owner->scheduleInspection(9101,$object,'2026-09-12')['status'],'missing/latest/state/engineer ineligible');assertSameValue($before,$f->facts(),'ineligible exact facts');}
    $f->object(451206,6106,'needs_assignment_change');$f->order(6161,6106,1,7301);assertSameValue('scheduled',$owner->scheduleInspection(9101,451206,'2026-09-12')['status'],'change state remains eligible');
    $f->db->query("CREATE TRIGGER {$p}deny_schedule_event BEFORE INSERT ON {$p}fm2_pilot_inspection_schedule_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='isolated schedule event failure'");
    $before=$f->facts();$failed=false;try{$owner->scheduleInspection(9101,451201,'2026-09-15');}catch(Throwable){$failed=true;}assertSameValue(true,$failed,'event infrastructure failure reported');assertSameValue($before,$f->facts(),'event failure rolls schedule back');$f->db->query("DROP TRIGGER {$p}deny_schedule_event");
    // Exact uniqueness and check drift must fail closed without repair; fresh owners prevent cache masking.
    $f->db->query("ALTER TABLE {$p}fm2_pilot_inspection_schedules DROP INDEX unique_planned_inspection");$before=$f->facts();$failed=false;try{$f->planning()->scheduleInspection(9101,451201,'2026-09-15');}catch(Throwable){$failed=true;}assertSameValue(true,$failed,'drift unavailable');assertSameValue($before,$f->facts(),'drift never repaired');
    echo "PASS: YII2-OBJECT-QUEUE-001 scheduling owner history authorization replay rollback readiness\n";
}finally{if($f instanceof ObjectQueueFixture)$f->close();}
