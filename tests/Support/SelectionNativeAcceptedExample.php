<?php
declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderComposition as C;

/** Literal accepted port payload from SELECT-001 section4, using native allocated identity. */
final class SelectionNativeAcceptedExample
{
    public static function payload(C\SelectionIdentityAllocation $allocation,string $axis='valid'): C\SelectionAcceptedPersistence
    {
        \assertSameValue([81,4512,1],[$allocation->assignmentOrderId,$allocation->caseId,$allocation->orderVersion],'real allocation matches normative example');
        $c=SelectionNativeFixture::command();$at=new C\SelectionInstant('2026-09-05T09:00:00Z');$date=$axis==='wrong_date'?'2026-09-06':'2026-09-05';
        $hash='5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a';
        $r=C\SelectionResult::selected($c->requestId,new C\SelectionSuccessPayload(4512,81,1,1,'composition-81-v1',$hash,$date,$at->utcRfc3339Seconds));
        $engineer=new C\EngineerSnapshot(73,$axis==='bad_engineer'?' Инженер теста':'Инженер теста','Инженер');
        $member=new C\InstallerSnapshot(7001,'Монтажник теста','Монтажник',$axis==='dismissed'?'dismissed':'employed',$axis==='future_employment'?'2026-09-06':'2020-01-01',null,'synthetic-hr',$axis==='bad_source_time'?'not-an-instant':'2026-09-01T06:00:00Z');
        $event=new C\SelectionSelectedEvent($c->requestId,4512,81,1,1,null,null,$hash,$at,$c->actorUserId);
        $audit=new C\SelectionSafeAttemptAudit($c->requestId,$c->actorUserId,$c->installationObjectId,$c->mode,$r->status(),null,$at);
        return new C\SelectionAcceptedPersistence($allocation,1,$c->mode,null,null,$engineer,[$member],$date,$at,$c->actorUserId,C\SelectionIntent::fromCommand($c),$r,$event,$audit);
    }
}
