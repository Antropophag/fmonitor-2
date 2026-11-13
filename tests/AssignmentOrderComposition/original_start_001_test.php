<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\Tests\Support\OriginalStartFixture as F;
use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\AssignmentOrderOriginal as O;

// ACTUAL-START-FROM-ORDER-001 A1–A6/A9. Expected dates come from owner examples, not production projections.
foreach (['2026-09-20','2026-09-01'] as $date) {
    $f=new F();try {
        $owner=$f->owner();$result=$owner->submit($f->command(),4512,$date);
        assertSameValue('accepted',$result['status'],'signed appendix opens immediately');
        assertSameValue(['working',$date,'18'],array_map('strval',[$f->case()['process_state'],$f->case()['actual_start_date'],$f->case()['opened_by_user_id']]),'date independent of plan/document/click');
        assertSameValue('2026-09-05T10:00:00Z',$f->case()['opened_at'],'opening clock separate from appendix date');
        assertSameValue([1,1,1],[count($f->rows('fm2_assignment_order_applications')),$f->openingCount(),count($f->rows('fm2_checklist_template_associations'))],'one application/opening/checklist');
        $before=$f->snapshot();$retry=$owner->submit($f->command(),4512,$date);
        assertSameValue('replayed',$retry['status'],'exact repeat succeeds');assertSameValue($before,$f->snapshot(),'repeat adds no facts');
        $f->db->query("UPDATE start_fm_maintable SET workdatestart='2027-01-01' WHERE id=4512");
        assertSameValue($date,$f->case()['actual_start_date'],'later plan edit cannot rewrite actual start');
        echo "PASS signed start $date immediate open and replay\n";
    }finally{$f->close();}
}

foreach (['','2026-02-30','05.09.2026'] as $date) {
    $f=new F();try{$before=$f->snapshot();$r=$f->owner()->submit($f->command(),4512,$date);assertSameValue('rejected',$r['status'],'invalid appendix date rejected');assertSameValue($before,$f->snapshot(),'invalid date preserves business facts');}finally{$f->close();}
}

$f=new F();try{
    $f->db->query("DELETE FROM start_fm2_pilot_role_permissions WHERE permission='installation.open'");
    $before=$f->snapshot();$r=$f->owner()->submit($f->command(),4512,'2026-09-20');
    assertSameValue(['rejected','authorization_denied'],[$r['status'],$r['reasonCode']],'upload-only user cannot create automatic opening');assertSameValue($before,$f->snapshot(),'denied upload finalizes no original or facts');
}finally{$f->close();}

$f=new F();try{
    $owner=$f->owner();$first=$owner->submit($f->command(),4512,'2026-09-20');$before=$f->snapshot();
    foreach ([[$f->command(),'2026-09-21'],[$f->command(1,F::otherPdf()),'2026-09-20']] as [$command,$date]) {
        $r=$owner->submit($command,4512,$date);assertSameValue('conflict',$r['status'],'same request with changed semantics conflicts');assertSameValue($before,$f->snapshot(),'changed request cannot reuse accepted facts');
    }
    $r=$owner->submit($f->command(2),4512,'2026-09-20');assertSameValue('replayed',$r['status'],'equivalent cross-request low-level replay succeeds');assertSameValue($before,$f->snapshot(),'cross-request replay no duplicate opening');
    $r=$owner->submit($f->command(3),4512,'2026-09-21');assertSameValue('conflict',$r['status'],'cross-request identical PDF with different date cannot borrow canonical original');assertSameValue($before,$f->snapshot(),'cross-request different date preserves canonical facts');
}finally{$f->close();}

// Genuine post-original-commit failure, recovered through the SAME public upload seam.
$f=new F(false);try{
    $owner=$f->owner();$pending=$owner->submit($f->command(),4512,'2026-09-20');
    assertSameValue(['pending',true],[$pending['status'],$pending['retryable']],'late opening failure is explicit retryable partial');
    assertSameValue(1,count($f->rows('fm2_assignment_order_original_revisions')),'signed evidence survives opening failure');
    assertSameValue([null,0,0],[$f->case()['actual_start_date'],$f->openingCount(),count($f->rows('fm2_assignment_order_applications'))],'late failure rolls back application/opening');
    $f->addTemplate();$r=$owner->submit($f->command(),4512,'2026-09-20');
    assertSameValue(true,in_array($r['status'],['accepted','replayed'],true),'exact retry resumes pending opening');
    assertSameValue([1,1],[count($f->rows('fm2_assignment_order_original_revisions')),$f->openingCount()],'recovered operation has one original and opening');
}finally{$f->close();}

// Interrupted after intent, before original owner; durable retry still completes.
$f=new F();try{
    $interrupted=new class implements O\AssignmentOrderOriginalApplication {
        public function submitAssignmentOrderOriginal(O\SubmitAssignmentOrderOriginalCommand $c): O\AssignmentOrderOriginalResult { throw new RuntimeException('synthetic interruption'); }
    };
    try{$f->owner($interrupted)->submit($f->command(),4512,'2026-09-20');}catch(RuntimeException){}
    assertSameValue([0,0],[count($f->rows('fm2_assignment_order_original_revisions')),$f->openingCount()],'intent alone grants no signed/opened facts');
    $r=$f->owner()->submit($f->command(),4512,'2026-09-20');assertSameValue(true,in_array($r['status'],['accepted','replayed'],true),'retry after intent interruption completes');assertSameValue(1,$f->openingCount(),'intent recovery opens once');
}finally{$f->close();}

$f=new F();try{
    $owner=$f->owner();$r=$owner->submit($f->command(),4512,'2026-09-20');$before=$f->case();$correction=$f->correction($r);
    $r=$owner->submit($f->command(2,F::otherPdf(),$correction),4512,'2026-09-20');assertSameValue('accepted',$r['status'],'same-date corrected PDF is accepted');
    assertSameValue($before,$f->case(),'correction preserves original opening tuple and case');assertSameValue(1,$f->openingCount(),'correction creates no second opening');
    $apps=$f->rows('fm2_assignment_order_applications');assertSameValue($r['original']->currentRevisionId(),end($apps)['original_revision_id'],'corrected current original has current application');
    $snapshot=$f->snapshot();$bad=$owner->submit($f->command(3,null,$f->correction($r)),4512,'2026-09-21');assertSameValue('rejected',$bad['status'],'opened order date cannot silently change');assertSameValue($snapshot,$f->snapshot(),'rejected date correction accepts no PDF');
}finally{$f->close();}

foreach (['2026-09-01','2026-09-20'] as $initialDate) {
$f=new F();try{
    $owner=$f->owner();$owner->submit($f->command(),4512,$initialDate);$opening=$f->case();
    $select=new C\SelectAssignmentOrderCompositionCommand(new C\SelectionRequestId('13131313-1313-4313-8313-000000000001'),C\AssignmentOrderCompositionMode::NEW_ORDER,new C\InstallationObjectId(4512),new C\UserId(18),new C\InstallerTabIdList([new C\InstallerTabId(7002)]),new C\UserId(73),new C\SelectionRevision(1));
    $selection=$f->original->selection->app()->selectAssignmentOrderComposition($select);assertSameValue('selected',$selection->status()->value,'next composition selected through public seam');
    $order=(int)$f->db->query('SELECT MAX(assignment_order_id) FROM start_fm2_assignment_order_selections')->fetch_column();
    $r=$owner->submit($f->command(2,null,null,$order,'2026-09-05'),4512,'2026-10-01');assertSameValue('accepted',$r['status'],'later order with different appendix date applied');
    assertSameValue([$opening['actual_start_date'],$opening['opened_at'],$opening['opened_by_user_id']],[$f->case()['actual_start_date'],$f->case()['opened_at'],$f->case()['opened_by_user_id']],'later order retains first opening');assertSameValue(1,$f->openingCount(),'later order never opens twice');
    $apps=$f->rows('fm2_assignment_order_applications');assertSameValue($order,(int)end($apps)['assignment_order_id'],'latest composition applied');
    $r=$owner->submit($f->command(3,F::otherPdf(),$f->correction($r),$order,'2026-09-05'),4512,'2026-10-01');assertSameValue('accepted',$r['status'],'later-order correction compares its own date, not case start');assertSameValue($initialDate,$f->case()['actual_start_date'],'later correction preserves initial start');
}finally{$f->close();}
}
// Permission can disappear AFTER original acceptance; it cannot be inherited by opening.
$f=new F();try{
    $wrapped=new class($f->original->app(),$f->db) implements O\AssignmentOrderOriginalApplication {
        public function __construct(private O\AssignmentOrderOriginalApplication $original,private mysqli $db){}
        public function submitAssignmentOrderOriginal(O\SubmitAssignmentOrderOriginalCommand $c): O\AssignmentOrderOriginalResult {
            $r=$this->original->submitAssignmentOrderOriginal($c);
            $this->db->query("DELETE FROM start_fm2_pilot_role_permissions WHERE permission='installation.open'");return $r;
        }
    };
    $r=$f->owner($wrapped)->submit($f->command(),4512,'2026-09-20');assertSameValue('pending',$r['status'],'revoked opening permission produces honest partial');assertSameValue([1,0],[count($f->rows('fm2_assignment_order_original_revisions')),$f->openingCount()],'accepted evidence retained without unauthorized opening');
    $f->db->query("INSERT INTO start_fm2_pilot_role_permissions(role_id,permission) VALUES(1,'installation.open')");
    $r=$f->owner()->submit($f->command(),4512,'2026-09-20');assertSameValue(true,in_array($r['status'],['accepted','replayed'],true),'exact retry rechecks restored authority');assertSameValue(1,$f->openingCount(),'revocation retry opens once');
}finally{$f->close();}
$f=new F();try{
    $before=$f->snapshot();$r=$f->owner()->submit($f->command(1,'not a PDF'),4512,'2026-09-20');assertSameValue('rejected',$r['status'],'unsafe/non-PDF rejected by original owner');assertSameValue($before,$f->snapshot(),'invalid PDF does not open or persist original');
    $r=$f->owner()->submit($f->command(2),9999,'2026-09-20');assertSameValue(true,in_array($r['status'],['rejected','conflict'],true),'object identity cannot be substituted');assertSameValue($before,$f->snapshot(),'wrong object leaves business facts untouched');
}finally{$f->close();}
echo "PASS ACTUAL-START-FROM-ORDER-001 domain acceptance\n";
