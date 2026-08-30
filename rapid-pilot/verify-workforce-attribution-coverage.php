<?php
declare(strict_types=1);
require_once __DIR__.'/legacy-migration/WorkforceAttributionCoverageProfiler.php';
function coverageCheck(bool$condition,string$message):void{if(!$condition)throw new RuntimeException($message);}
$payload=['attributions'=>[
 ['tab_id'=>'101','fio'=>'Иванов Иван','ctime'=>'2026-08-01 10:00:00'],
 ['tab_id'=>'102','fio'=>'Сидоров Сергей','ctime'=>'2026-08-01 11:00:00'],
 ['tab_id'=>'103','fio'=>'Другой Человек','ctime'=>'2026-08-01 12:00:00'],
 ['tab_id'=>'','fio'=>'Без номера','ctime'=>'2026-08-01 13:00:00'],
 ['tab_id'=>'104','fio'=>'Петров Петр','ctime'=>'bad'],
 ]];$json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$snapshots=[['content_sha256'=>hash('sha256',$json),'payload_json'=>$json]];
$workers=[
 ['installer_tab_id'=>'101','fio'=>' Иванов   Иван ','authority_system'=>'one_c_zup','reconciliation_state'=>'delivered','workforce_source_updated_at'=>'2026-08-30T12:00:00+03:00'],
 ['installer_tab_id'=>'103','fio'=>'Третий Человек','authority_system'=>'one_c_zup','reconciliation_state'=>'delivered','workforce_source_updated_at'=>'2026-08-30T12:00:00+03:00'],
];
$at='2026-08-30T16:00:00+03:00';$one=WorkforceAttributionCoverageProfiler::profile($snapshots,$workers,$at);$two=WorkforceAttributionCoverageProfiler::profile($snapshots,$workers,$at);coverageCheck($one===$two,'same immutable inputs must produce same profile');coverageCheck($one['counts']===['snapshots'=>1,'attributionRows'=>5,'uniqueAttributionFacts'=>3,'admissibleWorkforceFacts'=>1,'quarantinedFacts'=>4],'aggregate counts');coverageCheck($one['quarantineReasons']===['INVALID_ATTRIBUTION_TIME'=>1,'MISSING_OR_INVALID_TAB_ID'=>1,'WORKFORCE_NAME_MISMATCH'=>1,'WORKFORCE_NOT_FOUND'=>1],'stable redacted reasons');coverageCheck($one['coverageBp']===3333&&!$one['emitsIdentifiers']&&!$one['authorizesOperationalUse']&&strlen($one['sourceDigest'])===64,'coverage and safety claims');
$tampered=$snapshots;$tampered[0]['payload_json'].=' ';$bad=WorkforceAttributionCoverageProfiler::profile($tampered,$workers,$at);coverageCheck($bad['quarantineReasons']===['SNAPSHOT_HASH_MISMATCH'=>1]&&$bad['counts']['uniqueAttributionFacts']===0,'tampered snapshot fails closed');echo "PASS workforce attribution coverage profiler\n";
