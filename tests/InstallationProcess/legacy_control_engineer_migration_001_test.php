<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require dirname(__DIR__).'/Yii2/PreopeningFixture.php';

function lcmRun(PreopeningFixture $f,array $args):array{
    $env=array_replace(getenv(),$f->environment());$cmd=array_merge([PHP_BINARY,$f->root.'/bin/fmonitor2-control-engineer-migration.php'],$args);
    $p=proc_open($cmd,[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$f->root,$env);if(!is_resource($p))throw new TestFailure('SETUP_FAILURE process');$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return['exit'=>proc_close($p),'out'=>$out,'err'=>$err];
}
function lcmJson(array $r):array{assertSameValue('', $r['err'],'stderr empty');return json_decode($r['out'],true,flags:JSON_THROW_ON_ERROR);}
$f=null;
try{
    $f=new PreopeningFixture(dirname(__DIR__,2));$p=$f->p;
    assertSameValue(true,is_file($f->root.'/bin/fmonitor2-control-engineer-migration.php'),'INTENDED_RED migration CLI absent');
    $f->db->query("INSERT IGNORE INTO {$p}users_roles(id,name,status) VALUES(42,'Строительный контроль',1)");
    $f->db->query("INSERT IGNORE INTO {$p}users(id,name,email,role_id,status) VALUES(73,'Legacy 73','legacy73@example.test',42,1),(74,'Legacy 74','legacy74@example.test',42,1)");
    $f->db->query("UPDATE {$p}fm_maintable SET responsstroicontrol=73 WHERE id=4512");
    $f->db->query("DELETE FROM {$p}fm2_control_engineer_assignments");
    $f->db->query("INSERT INTO {$p}fm2_legacy_identity_links(local_user_id,legacy_user_id,legacy_name_snapshot,legacy_email_snapshot,legacy_status_snapshot,legacy_role_id_snapshot,legacy_role_status_snapshot,linked_by_user_id,linked_at_utc,request_id,request_fingerprint) VALUES(73,73,'Legacy 73','legacy73@example.test',1,42,1,94,'2026-09-16 12:00:00','20202020-0003-4020-8020-000000000001','".str_repeat('a',64)."')");
    $legacyBefore=json_encode($f->db->query("SELECT * FROM {$p}fm_maintable ORDER BY id")->fetch_all(MYSQLI_ASSOC),JSON_THROW_ON_ERROR);$factsBefore=$f->facts();
    $operation='20202020-0004-4020-8020-000000000001';$preview=$f->artifacts.'/preview.json';
    $first=lcmRun($f,['preview','--all-imported','--operation-id='.$operation,'--output='.$preview]);assertSameValue(0,$first['exit'],'preview exit');$one=lcmJson($first);
    assertSameValue(['ready',4512,73,73],[$one['rows'][0]['status'],$one['rows'][0]['objectId'],$one['rows'][0]['legacyUserId'],$one['rows'][0]['localUserId']],'ready mapping');assertSameValue(true,preg_match('/^[a-f0-9]{64}$/D',$one['digest'])===1,'digest');
    assertSameValue($factsBefore,$f->facts(),'preview process read-only');assertSameValue($legacyBefore,json_encode($f->db->query("SELECT * FROM {$p}fm_maintable ORDER BY id")->fetch_all(MYSQLI_ASSOC),JSON_THROW_ON_ERROR),'preview legacy read-only');
    $repeat=lcmJson(lcmRun($f,['preview','--all-imported','--operation-id='.$operation,'--output='.$preview.'.repeat']));assertSameValue($one,$repeat,'deterministic preview');
    $apply=lcmRun($f,['apply','--preview='.$preview,'--operation-id='.$operation,'--digest='.$one['digest']]);assertSameValue(0,$apply['exit'],'apply exit');$applied=lcmJson($apply);assertSameValue(true,$applied['ok'],'apply success');
    $rows=$f->rows('fm2_control_engineer_assignments');assertSameValue(1,count($rows),'one assignment');assertSameValue([4512,73,'legacy_fmonitor',$operation],[(int)$rows[0]['object_id'],(int)$rows[0]['engineer_user_id'],$rows[0]['assignment_source'],$rows[0]['source_operation_id']],'migration provenance');
    $after=$f->facts();$replay=lcmJson(lcmRun($f,['apply','--preview='.$preview,'--operation-id='.$operation,'--digest='.$one['digest']]));assertSameValue(true,$replay['ok'],'apply replay');assertSameValue($after,$f->facts(),'apply replay no facts');
    $reconcile=lcmJson(lcmRun($f,['reconcile','--operation-id='.$operation]));assertSameValue('applied',$reconcile['rows'][0]['status'],'reconcile terminal');
    assertSameValue($legacyBefore,json_encode($f->db->query("SELECT * FROM {$p}fm_maintable ORDER BY id")->fetch_all(MYSQLI_ASSOC),JSON_THROW_ON_ERROR),'apply legacy read-only');
    $f->db->query("UPDATE {$p}fm_maintable SET responsstroicontrol=74 WHERE id=4512");$beforeDrift=$f->facts();$drift=lcmRun($f,['apply','--preview='.$preview,'--operation-id='.$operation,'--digest='.$one['digest']]);assertSameValue(2,$drift['exit'],'drift rejected');assertSameValue('PREVIEW_STALE',lcmJson($drift)['reason'],'drift reason');assertSameValue($beforeDrift,$f->facts(),'drift no process facts');
    foreach([$f->dmlPassword,'legacy-secret','SQLSTATE','SELECT '] as $secret)assertSameValue(false,str_contains($first['out'].$first['err'].$apply['out'].$apply['err'],$secret),'no disclosure');
    echo "PASS: LEGACY-CONTROL-ENGINEER-MIGRATION-001 preview apply reconcile\n";
}finally{if($f instanceof PreopeningFixture)$f->close();}
