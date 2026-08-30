<?php

declare(strict_types=1);

require_once __DIR__.'/legacy-migration/LegacyChecklistProgressMapping.php';
function mappingEnv(string $name):string{$value=getenv($name);if(!is_string($value)||$value==='')throw new RuntimeException("Missing {$name}");return$value;}
$manifest=json_decode((string)file_get_contents(mappingEnv('FMONITOR_PILOT_ACTIVE_MANIFEST')),true,flags:JSON_THROW_ON_ERROR);
$prefix=(string)($manifest['processPrefix']??'');if(preg_match('/^[A-Za-z0-9_]+$/D',$prefix)!==1)throw new RuntimeException('Invalid pilot prefix');
$db=new mysqli(mappingEnv('FMONITOR_DB_HOST'),mappingEnv('FMONITOR_DB_USER'),mappingEnv('FMONITOR_DB_PASSWORD'),mappingEnv('FMONITOR_DB_NAME'),(int)mappingEnv('FMONITOR_DB_PORT'));$db->set_charset('utf8mb4');
$rows=[];$conflictCounts=[];$eligible=0;
foreach($db->query("SELECT legacy_object_id,content_sha256,payload_json FROM `{$prefix}fm2_history_source_snapshots` ORDER BY legacy_object_id,id DESC")->fetch_all(MYSQLI_ASSOC)as$row){
    $profile=LegacyChecklistProgressMapping::profile(json_decode($row['payload_json'],true,flags:JSON_THROW_ON_ERROR));if($profile['eligibleForCalculation'])$eligible++;
    foreach($profile['conflictCodes']as$code)$conflictCounts[$code]=($conflictCounts[$code]??0)+1;
    $rows[]=['legacyObjectId'=>(int)$row['legacy_object_id'],'snapshotHash'=>(string)$row['content_sha256']]+$profile;
}
ksort($conflictCounts,SORT_STRING);echo json_encode(['ok'=>true,'mappingVersion'=>LegacyChecklistProgressMapping::VERSION,'snapshots'=>count($rows),'eligibleForCalculation'=>$eligible,'conflictCounts'=>$conflictCounts,'objects'=>$rows],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),"\n";
