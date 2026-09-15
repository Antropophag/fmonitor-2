<?php
// Root-authored retained v1 snapshot; no legacy application dependency.
declare(strict_types=1);
function excelHistoricalPublication(ExcelPublicationFixture $f):int
{
 $h=$f->http;$db=$h->db;$p=$h->p;$op=$f->operation();$date='2026-09-01';
 // Retained v1 shape, independent literal old amounts (9000 accrued minus2000 closed).
 $h->insert($p.'fm2_pilot_otiz_snapshots',['report_date'=>$date,'status'=>'draft','previous_snapshot_id'=>null,'rules_version'=>'premium-calculation-v1','calculated_at'=>'2026-09-01T12:00:00Z','calculated_by_user_id'=>96,'accepted_at'=>null,'accepted_by_user_id'=>null,'total_pool_cents'=>7000,'total_closed_cents'=>2000,'total_available_cents'=>7000,'content_hash'=>str_repeat('b',64)]);$id=(int)$db->insert_id;
 $h->insert($p.'fm2_pilot_otiz_snapshot_objects',['snapshot_id'=>$id,'object_id'=>4540,'regnumber'=>'HISTORICAL','address'=>'Old snapshot','previous_progress_bp'=>0,'current_progress_bp'=>10000,'progress_fact_date'=>$date,'premium_cents'=>10000,'shaft_bp'=>10000,'kss_bp'=>9000,'accrued_cents'=>9000,'fund_cents'=>10000,'closed_before_cents'=>2000,'remaining_cents'=>8000,'pool_cents'=>7000,'distributed_cents'=>7000,'undistributed_cents'=>0,'calculation_state'=>'ready','inputs_json'=>'{"historical":true}']);
 $snapshot=$db->query("SELECT * FROM {$p}fm2_pilot_otiz_snapshots WHERE id=$id")->fetch_assoc();unset($snapshot['status'],$snapshot['accepted_at'],$snapshot['accepted_by_user_id']);$objects=$db->query("SELECT * FROM {$p}fm2_pilot_otiz_snapshot_objects WHERE snapshot_id=$id ORDER BY object_id")->fetch_all(MYSQLI_ASSOC);$manifest=hash('sha256',json_encode(['snapshot'=>$snapshot,'objects'=>$objects,'allocations'=>[],'issues'=>[]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
 $h->insert($p.'fm2_otiz_publications',['snapshot_id'=>$id,'actor_user_id'=>96,'operation_id'=>$op,'request_sha256'=>hash('sha256','otiz-build-v1:'.$date),'manifest_version'=>'otiz-publication-v1','manifest_sha256'=>$manifest,'object_count'=>1,'allocation_count'=>0,'issue_count'=>0,'published_at'=>'2026-09-01T12:00:00Z']);
 $pub=$f->publication([]);$before=$h->facts();assertSameValue(7000,(int)$pub->read(96,$id)['objects'][0]['pool_cents'],'historical v1 amount unchanged');assertSameValue($id,$pub->buildAndPublish(96,$date,$op),'historical operation retains fingerprint');excelRefusal('STALE_CALCULATION',fn()=>$pub->accept(96,$id));assertSameValue($before,$h->facts(),'v1 read replay and refused accept preserve facts');
 $h->start();$cookies=[];$h->login($cookies,96);$csrf=$h->token($cookies);$before=$h->facts();
 $response=$h->request('POST','/pilot/otiz/snapshots/'.$id.'/accept',['_csrf'=>$csrf],$cookies);
 assertSameValue(409,$response['status'],'old draft acceptance HTTP409');
 assertSameValue(true,str_contains(mb_strtolower(strip_tags($response['body'])),'новый расчёт'),'old draft acceptance explains next action');
 assertSameValue($before,$h->facts(),'old draft HTTP refusal preserves facts');
 $db->query("UPDATE {$p}fm2_pilot_otiz_snapshots SET status='accepted',accepted_at='2026-09-01T12:00:00Z',accepted_by_user_id=96 WHERE id=$id");$before=$h->facts();excelRefusal('STALE_CALCULATION',fn()=>$f->settlement()->completeSnapshotPayments(96,$id,$f->operation()));excelRefusal('STALE_CALCULATION',fn()=>$f->settlement()->recordDiscipline(96,$id,4540,1,'Old version','',$f->operation()));assertSameValue($before,$h->facts(),'accepted historical version cannot create new money facts');
 return $id;
}
