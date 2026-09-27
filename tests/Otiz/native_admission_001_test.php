<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Yii2/InspectionFixture.php';
use FMonitor2\Otiz\{OtizSettlementV2,OtizSettlementV2DraftBuilder,MariaDbOtizSettlementV2Access};
$fixture=null;$yii=null;
try {
    $fixture=new InspectionFixture(dirname(__DIR__,2));$fixture->open();$http=$fixture->http;$db=$http->db;$p=$http->p;
    $db->query("INSERT INTO {$p}fm2_pilot_role_permissions(role_id,permission)VALUES(7,'otiz.manage')");
    $card=json_decode($db->query("SELECT payload_json FROM {$p}fm2_pilot_object_details WHERE object_id=4512")->fetch_column(),true,flags:JSON_THROW_ON_ERROR);
    $card['fields']=array_replace($card['fields'],['floors'=>['raw'=>'9'],'weight'=>['raw'=>'630'],'lift_type'=>['raw'=>'1','display'=>'Пассажирский'],'pitmaterial'=>['raw'=>'41','display'=>'41']]);
    $json=json_encode($card,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$db->prepare("UPDATE {$p}fm2_pilot_object_details SET payload_json=?,content_sha256=? WHERE object_id=4512")->execute([$json,hash('sha256',$json)]);
    $db->query("UPDATE {$p}fm_maintable SET plan_finish_date='2026-10-01' WHERE id=4512");
    $csrf=InspectionFixture::csrf($fixture->page());$sequence=20000;$revision=0;
    $send=static function(int$item,array$tabs=[7001,7002],string$type='item_completed')use($fixture,$csrf,&$sequence,&$revision):array{
        $o=InspectionFixture::operation($sequence++,$revision,$item);$o['type']=$type;$o['deviceTime']='2026-09-03T10:00:00+03:00';$o['installerTabIds']=$tabs;$o['sectionId']=$item>=37?2:1;
        $result=InspectionFixture::result($fixture->send($o,$csrf),200,'accepted');$revision=$result['revision'];return$o;
    };
    foreach([28,29,30,31,32,33,34,35,36]as$item)$send($item);
    $env=$http->environment();$yii=new yii\db\Connection(['dsn'=>'mysql:host='.$env['FMONITOR_DB_HOST'].';port='.$env['FMONITOR_DB_PORT'].';dbname='.$http->database,'username'=>$env['FMONITOR_DB_USER'],'password'=>$env['FMONITOR_DB_PASSWORD'],'charset'=>'utf8mb4']);$yii->open();
    $clock=static fn()=>(new DateTimeImmutable('now',new DateTimeZone('Europe/Moscow')))->modify('+1 day')->format(DATE_ATOM);$access=new MariaDbOtizSettlementV2Access($yii,$p);$builder=new OtizSettlementV2DraftBuilder($yii,$p,$p,$clock);$owner=new OtizSettlementV2($yii,$p,$clock,fn(int$o,string$phase)=>$access->admission($o),$p);
    $uuidCounter=30000;$uuid=static function()use(&$uuidCounter):string{return sprintf('dddddddd-dddd-4ddd-8ddd-%012d',$uuidCounter++);};
    $denied=static function(string$reason,callable$f):void{try{$f();throw new TestFailure('Expected '.$reason);}catch(DomainException$e){assertSameValue($reason,$e->getMessage(),'exact rejection');}};
    assertSameValue(0,(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_otiz_admission_events")->fetch_column(),'fixture has no seeded external allow');
    $native=$access->admission(4512);
    assertSameValue('allow',$native['decision'],'real original/application/checklist evidence supplies native admission without an external producer');
    assertSameValue('unknown',$access->admission(999999)['decision'],'missing case never allowed');
    $input=$builder->build(73,'2026-09-10');assertSameValue(9750000,array_sum(array_column($input['objects'][0]['entitlements'],'grossCents')),'independent 650000-ruble norm times 15 percent');
    $created=$http->form('/pilot/otiz/calculations',['_csrf'=>$http->token($fixture->cookies),'reportDate'=>'2026-09-10','operationId'=>$uuid()],$fixture->cookies);
    assertSameValue(303,$created['status'],'real HTTP creates nonempty draft');preg_match('#/calculations/(\d+)#',$created['headers']['location'][0],$match);$id=(int)($match[1]??0);assertSameValue(true,$id>0,'draft route returns its identity');
    $draft=$owner->read($id);assertSameValue([9750000,1,2],[$draft['totalCents'],count($draft['objects']),count($draft['recipients'])],'actual draft includes the proven work and both people');
    $accepted=$http->form('/pilot/otiz/calculations/'.$id.'/accept',['_csrf'=>$http->token($fixture->cookies),'expectedRevision'=>$draft['revision'],'operationId'=>$uuid()],$fixture->cookies);assertSameValue(303,$accepted['status'],'real HTTP accepts native-proven draft');
    $accepted=$owner->read($id);assertSameValue('accepted',$accepted['status'],'accepted state persisted');$owner->assertPaymentExportAllowed(73,$id);
    $send(37,[7001]);assertSameValue($native['sourceRevision'],$access->admission(4512)['sourceRevision'],'ordinary new same-crew work keeps admission revision stable');$owner->assertPaymentExportAllowed(73,$id);
    assertSameValue(0,(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_otiz_admission_events")->fetch_column(),'native read fabricated no producer event');
    $emptyBefore=(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_otiz_calculation_revisions")->fetch_column();
    $denied('EMPTY_CALCULATION',fn()=>$owner->createDraft(73,['reportDate'=>'2026-09-10','objects'=>[]],$uuid()));
    assertSameValue($emptyBefore,(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_otiz_calculation_revisions")->fetch_column(),'no empty financial document created');
    $application=$db->query("SELECT * FROM {$p}fm2_assignment_order_applications WHERE object_id=4512 ORDER BY application_sequence DESC LIMIT 1")->fetch_assoc();
    $appId=(int)$application['application_id'];$caseId=(int)$application['installation_case_id'];
    $snapshot=$yii->beginTransaction();$yii->createCommand("SELECT COUNT(*) FROM `{$p}fm2_assignment_order_applications`")->queryScalar();
    $db->query("UPDATE {$p}fm2_assignment_order_applications SET original_revision_id='missing-original-proof' WHERE application_id={$appId}");
    assertSameValue('unknown',$access->admission(4512)['decision'],'applied row without accepted original proof is not allow');
    $snapshot->rollBack();
    $db->prepare("UPDATE {$p}fm2_assignment_order_applications SET original_revision_id=? WHERE application_id=?")->execute([$application['original_revision_id'],$appId]);
    $template=$db->query("SELECT t.* FROM {$p}fm2_checklist_template_snapshots t JOIN {$p}fm2_checklist_template_associations a ON a.template_snapshot_id=t.id WHERE a.subject_kind='operational_case' AND a.subject_id='{$caseId}'")->fetch_assoc();
    $db->prepare("UPDATE {$p}fm2_checklist_template_snapshots SET payload_json=? WHERE id=?")->execute([$template['payload_json'].' ',$template['id']]);
    assertSameValue('unknown',$access->admission(4512)['decision'],'invalid template digest never becomes permission');
    $db->prepare("UPDATE {$p}fm2_checklist_template_snapshots SET payload_json=? WHERE id=?")->execute([$template['payload_json'],$template['id']]);
    $send(38,[7001]);
    $appendCorrection=static function(?int$tab)use($db,$http,$p,$caseId,$uuid,&$revision):string{
        // Adversarial immutable historical record; repair below uses the real public correction command.
        $row=$db->query("SELECT * FROM {$p}fm2_checklist_operations WHERE installation_case_id={$caseId} AND operation_type='item_completed' ORDER BY id DESC LIMIT 1")->fetch_assoc();
        unset($row['id']);$op=$uuid();$row['client_operation_id']=$op;$row['operation_type']='item_installers_changed';$row['item_id']=38;$row['section_id']=2;$row['base_revision']=$revision;$row['accepted_revision']=++$revision;$row['payload_json']=json_encode(['installerTabIds'=>[$tab??7001]],JSON_THROW_ON_ERROR);$http->insert($p.'fm2_checklist_operations',$row);
        if($tab!==null){$installer=$db->query("SELECT * FROM {$p}fm2_checklist_operation_installers WHERE installer_tab_id='7001' LIMIT 1")->fetch_assoc();$installer['client_operation_id']=$op;$installer['installer_tab_id']=(string)$tab;$installer['assignment_source']='correction';$http->insert($p.'fm2_checklist_operation_installers',$installer);}
        $db->query("UPDATE {$p}fm2_checklist_revisions SET revision_no={$revision} WHERE installation_case_id={$caseId}");return$op;
    };
    $bad=$appendCorrection(9999);assertSameValue('blocked',$access->admission(4512)['decision'],'actual undocumented active installer blocks whole object');
    $denied('COMPOSITION_MISMATCH',fn()=>$owner->markPaid(73,$id,$accepted['revision'],'2026-09-10',$uuid()));
    assertSameValue(0,(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_otiz_payment_facts")->fetch_column(),'native mismatch writes no payment');
    $send(38,[7001],'item_installers_changed');$resolved=$access->admission(4512);assertSameValue('allow',$resolved['decision'],'real corrective operation restores provable composition');assertSameValue(false,$native['sourceRevision']===$resolved['sourceRevision'],'append-only contradictory history permanently changes admission frontier');
    assertSameValue(1,(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_checklist_operations WHERE client_operation_id='{$bad}'")->fetch_column(),'repair retains contradictory history');
    $denied('SNAPSHOT_REPLACEMENT_REQUIRED',fn()=>$owner->assertPaymentExportAllowed(73,$id));
    $missing=$appendCorrection(null);assertSameValue('unknown',$access->admission(4512)['decision'],'missing active attribution fails closed');
    $denied('ADMISSION_UNKNOWN',fn()=>$owner->markPaid(73,$id,$accepted['revision'],'2026-09-10',$uuid()));
    $send(38,[7001],'item_installers_changed');$restored=$access->admission(4512);assertSameValue('allow',$restored['decision'],'new valid correction repairs active unknown while retaining old evidence');assertSameValue(false,$resolved['sourceRevision']===$restored['sourceRevision'],'unknown history also changes the frontier');
    $replacement=$owner->createReplacementDraft(73,$id,$accepted['revision'],$builder->buildReplacement(73,'2026-09-10',$id),'Обновлён подтверждённый состав',$uuid());$replacement=$owner->accept(73,$replacement['calculationId'],$replacement['revision'],$uuid());
    assertSameValue(13650000,$replacement['totalCents'],'replacement includes the same15 percent plus independently confirmed6 percent, once');
    $postAdmission=static function(string$decision,int$expected=200)use($http,$fixture,$application,$uuid):void{
        $r=$http->form('/pilot/otiz/admission',['_csrf'=>$http->token($fixture->cookies),'operationId'=>$uuid(),'incidentId'=>'external-real-incident','objectId'=>'4512','sourceRevision'=>'external-'.$decision,'decision'=>$decision,'reasonCode'=>$decision==='blocked'?'COMPOSITION_MISMATCH':'','acceptedOriginalId'=>$application['original_revision_id'],'applicationId'=>$application['application_id'],'effectiveAttributionRevision'=>$application['composition_sha256']],$fixture->cookies);assertSameValue($expected,$r['status'],'real producer HTTP checks exact explicit permission');
    };
    $postAdmission('blocked',403);assertSameValue(0,(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_otiz_admission_events")->fetch_column(),'ungranted actor cannot publish admission');
    $db->query("INSERT INTO {$p}fm2_pilot_role_permissions(role_id,permission)VALUES(7,'composition_mismatch.produce')");
    $postAdmission('blocked');assertSameValue('blocked',$access->admission(4512)['decision'],'external incident overrides valid native proof');$denied('COMPOSITION_MISMATCH',fn()=>$owner->markPaid(73,$replacement['calculationId'],$replacement['revision'],'2026-09-10',$uuid()));
    $postAdmission('allow');$denied('SNAPSHOT_REPLACEMENT_REQUIRED',fn()=>$owner->assertPaymentExportAllowed(73,$replacement['calculationId']));
    $final=$owner->createReplacementDraft(73,$replacement['calculationId'],$replacement['revision'],$builder->buildReplacement(73,'2026-09-10',$replacement['calculationId']),'Урегулировано внешнее несоответствие',$uuid());$final=$owner->accept(73,$final['calculationId'],$final['revision'],$uuid());
    $paid=$owner->markPaid(73,$final['calculationId'],$final['revision'],'2026-09-10',$uuid());assertSameValue(13650000,$paid['paidCents'],'exact proven replacement is payable through unchanged owner');
    assertSameValue(1,(int)$db->query("SELECT COUNT(*) FROM {$p}fm2_otiz_payment_facts")->fetch_column(),'only the explicit successful payment exists');
    echo "native_admission_001_test: OK\n";
} finally {if($yii instanceof yii\db\Connection)$yii->close();if($fixture instanceof InspectionFixture)$fixture->close();}
