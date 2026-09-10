<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/PreopeningFixture.php';
use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\AssignmentOrderOriginal as O;
// YII2-PREOPENING-JOURNEY-001: current card versions reach the unchanged compound owner.
foreach([false,true] as $correct) {
    $f=null;
    try {
        $f=new PreopeningFixture(dirname(__DIR__,2));
        assertSameValue('selected',$f->base->selection->app()->selectAssignmentOrderComposition(FMonitor2\Tests\Support\SelectionNativeFixture::command())->status()->value,'native selection');
        $original=$f->nativeOriginal();assertSameValue('accepted',$original->status()->value,'native original');
        $prior=C\ProductionAssignmentOrderApplicationFactory::create($f->db,$f->p)->applyAssignmentOrderOriginal(new C\ApplyAssignmentOrderOriginalCommand('44444444-4444-4444-8444-000000000001',4512,81,$original->currentRevisionId(),0,18));
        assertSameValue('applied',$prior->status,'prior native application');
        $applications=$f->rows('fm2_assignment_order_applications');$oldOriginals=$f->rows('fm2_assignment_order_original_revisions');
        $f->start();$cookies=[];$f->login($cookies);$card=$f->request('GET','/pilot/objects/4512',[],$cookies);
        assertSameValue(200,$card['status'],'INTENDED_RED Yii prior application card');
        assertSameValue(true,str_contains($card['body'],'name="sequence" value="1"'),'card exposes current sequence');
        $open=['_csrf'=>$f->csrf($card['body']),'action'=>'open_confirmed','requestId'=>'33333333-3333-4333-8333-000000000088','orderId'=>'81','revisionId'=>$original->currentRevisionId(),'sequence'=>'1','actualStartDate'=>'2026-09-03'];
        if($correct) {
            $metadata=$f->metadata($cookies,'22222222-2222-4222-8222-000000000002');
            $metadata=array_replace($metadata,['mode'=>'correction','documentDate'=>'2026-09-02','rootOriginalId'=>$original->rootOriginalId(),'targetRevisionId'=>$original->currentRevisionId(),'expectedCurrentRevisionId'=>$original->currentRevisionId(),'correctionReason'=>'Уточнение даты']);
            $response=$f->upload($cookies,$metadata);assertSameValue(201,$response['status'],'intervening real HTTP correction');
            $current=json_decode($response['body'],true,flags:JSON_THROW_ON_ERROR)['currentRevisionId'];
            $before=$f->facts();assertSameValue(422,$f->form('/pilot/objects/4512/execution',$open,$cookies)['status'],'stale GET original rejected at command');assertSameValue($before,$f->facts(),'stale opening no mutation');
            $card=$f->request('GET','/pilot/objects/4512',[],$cookies);assertSameValue(true,str_contains($card['body'],'name="revisionId" value="'.$current.'"'),'card uses corrected original');
            $open['_csrf']=$f->csrf($card['body']);$open['revisionId']=$current;$open['requestId']='33333333-3333-4333-8333-000000000089';
        }
        assertSameValue(303,$f->form('/pilot/objects/4512/execution',$open,$cookies)['status'],'compound reuse/reapplication HTTP success');
        $after=$f->rows('fm2_assignment_order_applications');assertSameValue($correct?2:1,count($after),'only correction adds application');assertSameValue($applications[0],$after[0],'prior application immutable');
        assertSameValue($oldOriginals[0],$f->rows('fm2_assignment_order_original_revisions')[0],'prior original immutable');
        if($correct)assertSameValue('reapplication',$after[1]['kind'],'explicit reapplication lineage');
        assertSameValue(['working','2026-09-03','18'],array_map(static fn($key)=>$f->rows('fm2_installation_cases')[0][$key],['process_state','actual_start_date','opened_by_user_id']),'durable opening');
        $before=$f->facts();assertSameValue(303,$f->form('/pilot/objects/4512/execution',$open,$cookies)['status'],'exact compound replay');assertSameValue($before,$f->facts(),'replay immutable');$f->noLegacy();
    } finally {if($f instanceof PreopeningFixture)$f->close();}
}
foreach(['selection','application']as$intervening) {
    $f=null;
    try {
        $f=new PreopeningFixture(dirname(__DIR__,2));$f->base->selection->app()->selectAssignmentOrderComposition(FMonitor2\Tests\Support\SelectionNativeFixture::command());$original=$f->nativeOriginal();$f->start();$cookies=[];$f->login($cookies);
        $card=$f->request('GET','/pilot/objects/4512',[],$cookies);assertSameValue(200,$card['status'],'render before intervening '.$intervening);
        $open=['_csrf'=>$f->csrf($card['body']),'action'=>'open_confirmed','requestId'=>'33333333-3333-4333-8333-000000000080','orderId'=>'81','revisionId'=>$original->currentRevisionId(),'sequence'=>'0','actualStartDate'=>'2026-09-02'];
        if($intervening==='selection')assertSameValue(303,$f->selection($cookies,'11111111-1111-4111-8111-000000000080',[7002],'new_order',1)['status'],'intervening selected order');
        else { $result=C\ProductionAssignmentOrderApplicationFactory::create($f->db,$f->p)->applyAssignmentOrderOriginal(new C\ApplyAssignmentOrderOriginalCommand('44444444-4444-4444-8444-000000000080',4512,81,$original->currentRevisionId(),0,18));assertSameValue('applied',$result->status,'intervening application'); }
        $before=$f->facts();$files=$f->base->privateFiles();assertSameValue(422,$f->form('/pilot/objects/4512/execution',$open,$cookies)['status'],'stale '.$intervening.' form rejected');assertSameValue($before,$f->facts(),'stale expected facts not rewritten');assertSameValue($files,$f->base->privateFiles(),'stale files preserved');$f->noLegacy();
    }finally{if($f instanceof PreopeningFixture)$f->close();}
}
echo "PASS: YII2-PREOPENING-JOURNEY-001 HTTP reuse, stale original and reapplication\n";
