<?php

declare(strict_types=1);

function wfEnv(string $name): string { $value=getenv($name);if(!is_string($value)||$value==='')throw new RuntimeException("Missing {$name}");return$value; }
function wfUuid(): string { $b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4)); }
function wfTime(mixed $value): ?string { if(!is_string($value)||trim($value)==='')return null;try{return(new DateTimeImmutable($value))->setTimezone(new DateTimeZone('Europe/Moscow'))->format('Y-m-d\TH:i:sP');}catch(Throwable){return null;} }

function wfFetch(string $baseUrl,array $departments): array
{
    $users=[];$start=0;$pages=0;
    do{
        $curl=curl_init($baseUrl.'user.get');
        curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query(['FILTER'=>['UF_DEPARTMENT'=>$departments],'start'=>$start]),CURLOPT_RETURNTRANSFER=>true,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>30]);
        $body=curl_exec($curl);$code=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);
        if(!is_string($body)||$code!==200)throw new RuntimeException('BITRIX_REQUEST_FAILED');
        $json=json_decode($body,true,flags:JSON_THROW_ON_ERROR);if(!is_array($json['result']??null))throw new RuntimeException('BITRIX_RESPONSE_INVALID');
        array_push($users,...$json['result']);$start=isset($json['next'])?(int)$json['next']:-1;$pages++;
    }while($start>=0);
    $rows=[];$seen=[];
    foreach($users as$user){
        $email=trim((string)($user['EMAIL']??''));
        $tab=preg_match('/^tab0*([1-9][0-9]*)@/i',$email,$match)===1?(int)$match[1]:(preg_match('/^[1-9][0-9]*$/D',trim((string)($user['UF_XING']??'')))===1?(int)$user['UF_XING']:0);
        if($tab<1)continue;
        $fio=trim((string)preg_replace('/\s+/u',' ',implode(' ',[$user['LAST_NAME']??'',$user['NAME']??'',$user['SECOND_NAME']??''])));$position=trim((string)($user['WORK_POSITION']??''));$person=filter_var($user['ID']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if($fio===''||$position===''||$person===false||isset($seen[$tab]))throw new RuntimeException('BITRIX_CATALOG_CONFLICT');
        $seen[$tab]=true;$rows[]=['tab'=>$tab,'fio'=>$fio,'position'=>$position,'status'=>(($user['ACTIVE']??null)===true||($user['ACTIVE']??null)==='Y')?'employed':'dismissed','person'=>(int)$person,'modifiedAt'=>wfTime($user['TIMESTAMP_X']??null)];
    }
    if($rows===[])throw new RuntimeException('BITRIX_CATALOG_EMPTY');usort($rows,static fn(array$a,array$b):int=>$a['tab']<=>$b['tab']);return[$rows,$pages];
}

mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$config=json_decode((string)file_get_contents(wfEnv('FMONITOR_BITRIX_CONFIG')),true,flags:JSON_THROW_ON_ERROR);$baseUrl=$config['baseUrl']??null;$departments=$config['departments']??null;
if(!is_string($baseUrl)||!is_array($departments)||$departments===[])throw new RuntimeException('BITRIX_CONFIG_INVALID');
$manifests=glob(getenv('HOME').'/.local/state/fmonitor2/pilot-demo/*/active.json')?:[];if(count($manifests)!==1)throw new RuntimeException('PILOT_MANIFEST_UNAVAILABLE');
$manifest=json_decode((string)file_get_contents($manifests[0]),true,flags:JSON_THROW_ON_ERROR);$prefix=(string)($manifest['processPrefix']??'');if(preg_match('/^[A-Za-z0-9_]+$/D',$prefix)!==1)throw new RuntimeException('PILOT_PREFIX_INVALID');
$db=new mysqli(wfEnv('FMONITOR_DB_HOST'),wfEnv('FMONITOR_DB_USER'),wfEnv('FMONITOR_DB_PASSWORD'),wfEnv('FMONITOR_DB_NAME'),(int)wfEnv('FMONITOR_DB_PORT'));$db->set_charset('utf8mb4');
$run=wfUuid();$now=(new DateTimeImmutable('now',new DateTimeZone('Europe/Moscow')))->format('Y-m-d\TH:i:sP');$observedDate=substr($now,0,10);$started=$db->prepare("INSERT INTO `{$prefix}fm2_workforce_sync_runs`(run_id,status,started_at) VALUES(?,'started',?)");$started->bind_param('ss',$run,$now);$started->execute();
try{[$rows,$pages]=wfFetch($baseUrl,$departments);}catch(Throwable$error){$code=substr(preg_replace('/[^A-Z0-9_]/','_',strtoupper($error->getMessage()))?:'SYNC_FAILED',0,80);$failed=$db->prepare("UPDATE `{$prefix}fm2_workforce_sync_runs` SET status='failed',completed_at=?,failure_code=? WHERE run_id=?");$failed->bind_param('sss',$now,$code,$run);$failed->execute();throw$error;}
$checksum=hash('sha256',json_encode($rows,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));$db->begin_transaction();
try{
    $existing=[];foreach($db->query("SELECT installer_tab_id,fio,position,employment_status,delivery_person_id,reconciliation_state FROM `{$prefix}fm2_workforce_catalog`")->fetch_all(MYSQLI_ASSOC)as$row)$existing[(int)$row['installer_tab_id']]=$row;
    $observation=$db->prepare("INSERT INTO `{$prefix}fm2_workforce_observations`(sync_run_id,delivery_person_id,employee_number,full_name,position,employment_status,employed_from,dismissal_effective_at,authority_system,delivery_system,source_modified_at,reconciliation_state,observed_at,dismissal_time_quality) VALUES(?,?,?,?,?,?,NULL,NULL,'one_c_zup','bitrix24',?,?,?,'observed_only')");
    $upsert=$db->prepare("INSERT INTO `{$prefix}fm2_workforce_catalog`(installer_tab_id,fio,position,employment_status,employed_from,employed_to,workforce_source,workforce_source_updated_at,delivery_system,delivery_person_id,dismissal_effective_at,first_observed_dismissed_at,dismissal_time_quality,reconciliation_state,authority_system,last_successful_sync_run_id,last_successful_sync_at) VALUES(?,?,?,?,?,NULL,'one_c_zup_via_bitrix',?,'bitrix24',?,NULL,?,'observed_only','delivered','one_c_zup',?,?) ON DUPLICATE KEY UPDATE fio=VALUES(fio),position=VALUES(position),employment_status=VALUES(employment_status),employed_from=COALESCE(employed_from,VALUES(employed_from)),workforce_source_updated_at=VALUES(workforce_source_updated_at),delivery_system=VALUES(delivery_system),delivery_person_id=VALUES(delivery_person_id),first_observed_dismissed_at=COALESCE(first_observed_dismissed_at,VALUES(first_observed_dismissed_at)),dismissal_time_quality='observed_only',reconciliation_state='delivered',authority_system='one_c_zup',last_successful_sync_run_id=VALUES(last_successful_sync_run_id),last_successful_sync_at=VALUES(last_successful_sync_at)");
    $changed=0;
    foreach($rows as$row){$previous=$existing[$row['tab']]??null;$material=$previous===null||$previous['fio']!==$row['fio']||$previous['position']!==$row['position']||$previous['employment_status']!==$row['status']||(int)$previous['delivery_person_id']!==$row['person']||$previous['reconciliation_state']!=='delivered';if($material){$reconciliation='delivered';$observation->bind_param('siissssss',$run,$row['person'],$row['tab'],$row['fio'],$row['position'],$row['status'],$row['modifiedAt'],$reconciliation,$now);$observation->execute();$changed++;}$firstDismissed=$row['status']==='dismissed'?$now:null;$upsert->bind_param('isssssisss',$row['tab'],$row['fio'],$row['position'],$row['status'],$observedDate,$now,$row['person'],$firstDismissed,$run,$now);$upsert->execute();unset($existing[$row['tab']]);}
    $missing=0;$markMissing=$db->prepare("UPDATE `{$prefix}fm2_workforce_catalog` SET reconciliation_state='missing_from_delivery',last_successful_sync_run_id=?,last_successful_sync_at=? WHERE installer_tab_id=?");
    foreach($existing as$tab=>$previous){if($previous['reconciliation_state']!=='missing_from_delivery'&&$previous['delivery_person_id']!==null){$person=(int)$previous['delivery_person_id'];$reconciliation='missing_from_delivery';$modified=null;$observation->bind_param('siissssss',$run,$person,$tab,$previous['fio'],$previous['position'],$previous['employment_status'],$modified,$reconciliation,$now);$observation->execute();$changed++;}$markMissing->bind_param('ssi',$run,$now,$tab);$markMissing->execute();$missing++;}
    $completed=$db->prepare("UPDATE `{$prefix}fm2_workforce_sync_runs` SET status='completed',observed_at=?,completed_at=?,page_count=?,delivered_count=?,material_change_count=?,missing_count=?,normalized_checksum=? WHERE run_id=?");$delivered=count($rows);$completed->bind_param('ssiiiiss',$now,$now,$pages,$delivered,$changed,$missing,$checksum,$run);$completed->execute();
    $metadata=$db->prepare("UPDATE `{$prefix}fm2_workforce_sync_metadata` SET last_successful_run_id=?,last_successful_at=? WHERE singleton_id=1");$metadata->bind_param('ss',$run,$now);$metadata->execute();$db->commit();
}catch(Throwable$error){$db->rollback();$code='PERSISTENCE_FAILED';$failed=$db->prepare("UPDATE `{$prefix}fm2_workforce_sync_runs` SET status='failed',completed_at=?,failure_code=? WHERE run_id=?");$failed->bind_param('sss',$now,$code,$run);$failed->execute();throw$error;}
echo json_encode(['ok'=>true,'runId'=>$run,'delivered'=>count($rows),'changed'=>$changed,'missing'=>$missing,'observedAt'=>$now],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),PHP_EOL;
