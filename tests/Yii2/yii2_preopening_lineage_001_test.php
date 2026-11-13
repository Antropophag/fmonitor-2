<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/PreopeningFixture.php';
use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\AssignmentOrderOriginal as O;
// YII2-PREOPENING-JOURNEY-001: current card versions reach the unchanged compound owner.
// Public read-owner regression: a new pending selection cannot inherit an older applied original.
$f = null;
$cardDb = null;
try {
    $f = new PreopeningFixture(dirname(__DIR__, 2));
    assertSameValue('selected', $f->base->selection->app()->selectAssignmentOrderComposition(FMonitor2\Tests\Support\SelectionNativeFixture::command())->status()->value, 'prior selection');
    $original = $f->nativeOriginal();
    assertSameValue('accepted', $original->status()->value, 'prior original');
    $applied = C\ProductionAssignmentOrderApplicationFactory::create($f->db, $f->p)->applyAssignmentOrderOriginal(
        new C\ApplyAssignmentOrderOriginalCommand('44444444-4444-4444-8444-000000000091', 4512, 81, $original->currentRevisionId(), 0, 18)
    );
    assertSameValue('applied', $applied->status, 'prior application');
    $environment = $f->environment();
    $cardDb = new yii\db\Connection([
        'dsn' => 'mysql:host='.$environment['FMONITOR_DB_HOST'].';port='.$environment['FMONITOR_DB_PORT'].';dbname='.$f->database,
        'username' => $f->dmlUser, 'password' => $f->dmlPassword, 'charset' => 'utf8mb4',
    ]);
    $reader = FMonitor2\YiiRuntime\InstallationProcessFactory::card($cardDb, $f->p, $f->p);
    assertSameValue('Готов к открытию', $reader->read(18, 4512)['status'], 'prior applied original is ready');
    $next = new C\SelectAssignmentOrderCompositionCommand(
        new C\SelectionRequestId('11111111-1111-4111-8111-000000000091'), C\AssignmentOrderCompositionMode::NEW_ORDER,
        new C\InstallationObjectId(4512), new C\UserId(18), new C\InstallerTabIdList([new C\InstallerTabId(7002)]),
        new C\UserId(73), new C\SelectionRevision(1)
    );
    assertSameValue('selected', $f->base->selection->app()->selectAssignmentOrderComposition($next)->status()->value, 'new pending selection');
    $before = $f->facts();
    $files = $f->base->privateFiles();
    $pending = $reader->read(18, 4512);
    assertSameValue('Требуется распоряжение', $pending['status'], 'INTENDED_RED pending selection cannot inherit applied readiness');
    assertSameValue(false, isset($pending['confirmedOriginal']), 'pending selection has no confirmed opening basis');
    assertSameValue($before, $f->facts(), 'public card read preserves prior application and all facts');
    assertSameValue($files, $f->base->privateFiles(), 'public card read preserves original bytes');
} finally {
    if ($cardDb instanceof yii\db\Connection) $cardDb->close();
    if ($f instanceof PreopeningFixture) $f->close();
}
foreach([false,true] as $correct) {
    $f=null;
    try {
        $f=new PreopeningFixture(dirname(__DIR__,2));$f->start();$cookies=[];$f->login($cookies);assertSameValue(303,$f->selection($cookies)['status'],'native selection');
        $metadata=array_replace($f->metadata($cookies),['appendixStartDate'=>'2026-09-03']);
        $response=$f->upload($cookies,$metadata);assertSameValue(201,$response['status'],'initial compound upload');$original=json_decode($response['body'],true,512,JSON_THROW_ON_ERROR);
        $applications=$f->rows('fm2_assignment_order_applications');$oldOriginals=$f->rows('fm2_assignment_order_original_revisions');$firstCase=$f->rows('fm2_installation_cases')[0];
        if($correct){
            $metadata=array_replace($metadata,['requestId'=>'22222222-2222-4222-8222-000000000002','mode'=>'correction','documentDate'=>'2026-09-02','rootOriginalId'=>$original['rootOriginalId'],'targetRevisionId'=>$original['currentRevisionId'],'expectedCurrentRevisionId'=>$original['currentRevisionId'],'correctionReason'=>'Уточнение даты документа']);
            $response=$f->upload($cookies,$metadata);assertSameValue(201,$response['status'],'correction applies automatically');
            $before=$f->facts();$stale=array_replace($metadata,['requestId'=>'22222222-2222-4222-8222-000000000003','documentDate'=>'2026-09-03']);$rejected=$f->upload($cookies,$stale);assertSameValue(409,$rejected['status'],'stale correction target rejected');
            assertSameValue($oldOriginals[0],$f->rows('fm2_assignment_order_original_revisions')[0],'stale target preserves original');
        }
        $after=$f->rows('fm2_assignment_order_applications');assertSameValue($correct?2:1,count($after),'only correction adds application');assertSameValue($applications[0],$after[0],'prior application immutable');
        assertSameValue($oldOriginals[0],$f->rows('fm2_assignment_order_original_revisions')[0],'prior original immutable');if($correct)assertSameValue('reapplication',$after[1]['kind'],'explicit reapplication lineage');
        foreach(['actual_start_date','opened_at','opened_by_user_id']as$key)assertSameValue($firstCase[$key],$f->rows('fm2_installation_cases')[0][$key],'opening immutable '.$key);
        assertSameValue(['working','2026-09-03','18'],array_map(static fn($key)=>$f->rows('fm2_installation_cases')[0][$key],['process_state','actual_start_date','opened_by_user_id']),'durable opening');
        $before=$f->facts();assertSameValue(200,$f->upload($cookies,$metadata)['status'],'exact compound replay');assertSameValue($before,$f->facts(),'replay immutable');$f->noLegacy();
        // Opening attribution is a durable fact, not an inference from the most recent eight events.
        $openedAt=$f->rows('fm2_installation_cases')[0]['opened_at'];
        for ($i = 0; $i < 9; $i++) {
            $f->insert($f->p.'fm2_process_events', ['installation_case_id'=>6101, 'event_type'=>'inspection_scheduled',
                'occurred_at'=>$openedAt, 'actor_user_id'=>97, 'payload_json'=>'{}']);
        }
        $f->db->query("UPDATE {$f->p}fm2_pilot_users SET full_name='Открывший <Автор>',status=0 WHERE user_id=18");
        $viewer=[];assertSameValue(303,$f->login($viewer,95)['status'],'independent current viewer');
        $beforeRead=$f->facts();$openedPage=$f->request('GET','/pilot/objects/4512',[],$viewer);
        assertSameValue(200,$openedPage['status'],'opened card beyond recent history window');
        assertSameValue(1,preg_match('/data-opening-actor[^>]*>\s*Открывший &lt;Автор&gt;\s*</u',$openedPage['body']),'INTENDED_RED durable opening actor survives history window and inactive author');
        $openedAt=$f->rows('fm2_installation_cases')[0]['opened_at'];
        assertSameValue(1,preg_match('/<time\b(?=[^>]*data-opening-time)(?=[^>]*datetime="'.preg_quote($openedAt,'/').'")[^>]*>/',$openedPage['body']),'exact stored opening instant remains visible');
        assertSameValue(false,str_contains($openedPage['body'],'Открывший <Автор>'),'opening actor escaped');
        assertSameValue($beforeRead,$f->facts(),'opening attribution read writes no facts');

    } finally {if($f instanceof PreopeningFixture)$f->close();}
}
$f=null;try{
 $f=new PreopeningFixture(dirname(__DIR__,2));$f->start();$cookies=[];$f->login($cookies);$f->selection($cookies);$raw=$f->nativeOriginal();$metadata=array_replace($f->metadata($cookies,'22222222-2222-4222-8222-000000000080'),['mode'=>'correction','documentDate'=>'2026-09-02','rootOriginalId'=>$raw->rootOriginalId(),'targetRevisionId'=>$raw->currentRevisionId(),'expectedCurrentRevisionId'=>$raw->currentRevisionId(),'correctionReason'=>'Stale selection']);
 assertSameValue(303,$f->selection($cookies,'11111111-1111-4111-8111-000000000080',[7002],'new_order',1)['status'],'intervening selected order');
 $priorApplications=$f->rows('fm2_assignment_order_applications');$priorCases=$f->rows('fm2_installation_cases');$result=$f->upload($cookies,$metadata);assertSameValue(503,$result['status'],'superseded upload cannot open current selection');$pending=json_decode($result['body'],true,512,JSON_THROW_ON_ERROR);assertSameValue(['pending','target_not_current'],[$pending['status'],$pending['reasonCode']],'stale selection preserves accepted PDF with explicit pending reason');
 assertSameValue($priorApplications,$f->rows('fm2_assignment_order_applications'),'stale selection not applied');assertSameValue($priorCases,$f->rows('fm2_installation_cases'),'stale selection cannot open');$f->noLegacy();
}finally{if($f instanceof PreopeningFixture)$f->close();}
echo "PASS: HTTP upload lineage, stale original and reapplication\n";
