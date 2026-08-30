<?php
declare(strict_types=1);

final class WorkforceAttributionCoverageProfiler
{
    public const VERSION='workforce-attribution-coverage-v1';

    public static function load(mysqli $db,string $prefix,string $profiledAt):array
    {
        if(preg_match('/^[A-Za-z0-9_]+$/D',$prefix)!==1)throw new InvalidArgumentException('Invalid local table prefix');self::timestamp($profiledAt);
        foreach(['fm2_history_source_snapshots','fm2_workforce_catalog']as$suffix){$table=$db->real_escape_string($prefix.$suffix);if((int)$db->query("SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'")->fetch_assoc()['n']!==1)throw new RuntimeException('Required attribution coverage source is unavailable');}
        $all=$db->query("SELECT id,legacy_object_id,content_sha256,payload_json FROM `{$prefix}fm2_history_source_snapshots` ORDER BY legacy_object_id,id DESC")->fetch_all(MYSQLI_ASSOC);$snapshots=[];$seen=[];foreach($all as$row){$id=(int)$row['legacy_object_id'];if(isset($seen[$id]))continue;$seen[$id]=true;$snapshots[]=$row;}
        $workers=$db->query("SELECT installer_tab_id,fio,employment_status,reconciliation_state,authority_system,workforce_source_updated_at FROM `{$prefix}fm2_workforce_catalog` ORDER BY installer_tab_id")->fetch_all(MYSQLI_ASSOC);
        return self::profile($snapshots,$workers,$profiledAt);
    }

    public static function profile(array $snapshots,array $workers,string $profiledAt):array
    {
        self::timestamp($profiledAt);$catalog=[];$duplicateCatalog=[];
        foreach($workers as$worker){$tab=self::tab($worker['installer_tab_id']??null);if($tab===null)continue;if(isset($catalog[$tab]))$duplicateCatalog[$tab]=true;else$catalog[$tab]=$worker;}
        $counts=['snapshots'=>count($snapshots),'attributionRows'=>0,'uniqueAttributionFacts'=>0,'admissibleWorkforceFacts'=>0,'quarantinedFacts'=>0];$reasons=[];$snapshotDigests=[];
        foreach($snapshots as$snapshot){$json=(string)($snapshot['payload_json']??'');$expected=(string)($snapshot['content_sha256']??'');if(preg_match('/^[a-f0-9]{64}$/D',$expected)!==1||!hash_equals($expected,hash('sha256',$json))){self::reason($reasons,'SNAPSHOT_HASH_MISMATCH');continue;}$payload=json_decode($json,true,flags:JSON_THROW_ON_ERROR);$attrs=is_array($payload['attributions']??null)?$payload['attributions']:[];$counts['attributionRows']+=count($attrs);$facts=[];
            foreach($attrs as$row){$tab=self::tab($row['tab_id']??null);if($tab===null){self::reason($reasons,'MISSING_OR_INVALID_TAB_ID');$counts['quarantinedFacts']++;continue;}$name=self::name($row['fio']??null);$at=self::legacyTime($row['ctime']??null);if($name===null){self::reason($reasons,'MISSING_ATTRIBUTION_NAME');$counts['quarantinedFacts']++;continue;}if($at===null){self::reason($reasons,'INVALID_ATTRIBUTION_TIME');$counts['quarantinedFacts']++;continue;}$key=$tab.'|'.$at;$signature=hash('sha256',$name);if(isset($facts[$key])&&!hash_equals($facts[$key],$signature)){self::reason($reasons,'CONFLICTING_ATTRIBUTION_DUPLICATE');$counts['quarantinedFacts']++;continue;}if(isset($facts[$key]))continue;$facts[$key]=$signature;$counts['uniqueAttributionFacts']++;
                $worker=$catalog[$tab]??null;if($tab==='999999')$code='LEGACY_UNASSIGNED_SENTINEL';elseif(isset($duplicateCatalog[$tab]))$code='AMBIGUOUS_WORKFORCE_TAB';elseif(!is_array($worker))$code='WORKFORCE_NOT_FOUND';elseif(self::name($worker['fio']??null)!==$name)$code='WORKFORCE_NAME_MISMATCH';elseif(($worker['authority_system']??null)!=='one_c_zup')$code='WORKFORCE_AUTHORITY_UNPROVEN';elseif(($worker['reconciliation_state']??null)!=='delivered')$code='WORKFORCE_NOT_RECONCILED';elseif(self::legacyTime($worker['workforce_source_updated_at']??null)===null&&self::rfc3339($worker['workforce_source_updated_at']??null)===null)$code='WORKFORCE_OBSERVED_AT_INVALID';else{$counts['admissibleWorkforceFacts']++;continue;}self::reason($reasons,$code);$counts['quarantinedFacts']++;
            }
            $snapshotDigests[]=$expected;
        }
        sort($snapshotDigests,SORT_STRING);ksort($reasons,SORT_STRING);$basis=['version'=>self::VERSION,'profiledAt'=>$profiledAt,'snapshotDigests'=>$snapshotDigests,'catalogDigest'=>hash('sha256',json_encode(self::canonical($workers),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)),'counts'=>$counts,'quarantineReasons'=>$reasons];
        return ['profileVersion'=>self::VERSION,'mode'=>'read_only_dry_run','profiledAt'=>$profiledAt,'counts'=>$counts,'quarantineReasons'=>$reasons,'coverageBp'=>$counts['uniqueAttributionFacts']===0?0:intdiv($counts['admissibleWorkforceFacts']*10000,$counts['uniqueAttributionFacts']),'sourceDigest'=>hash('sha256',json_encode($basis,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)),'emitsIdentifiers'=>false,'authorizesOperationalUse'=>false];
    }

    private static function tab(mixed$value):?string{$s=trim((string)$value);return preg_match('/^[1-9][0-9]{0,19}$/D',$s)===1?$s:null;}
    private static function name(mixed$value):?string{if(!is_string($value))return null;$s=preg_replace('/\s+/u',' ',trim($value));return is_string($s)&&$s!==''?mb_strtolower($s):null;}
    private static function legacyTime(mixed$value):?string{if(!is_string($value))return null;$d=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$value,new DateTimeZone('UTC'));$e=DateTimeImmutable::getLastErrors();return$d&&($e===false||($e['warning_count']===0&&$e['error_count']===0))&&$d->format('Y-m-d H:i:s')===$value?$value:null;}
    private static function rfc3339(mixed$value):?string{if(!is_string($value))return null;$d=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:sP',$value);return$d&&$d->format('Y-m-d\TH:i:sP')===$value?$value:null;}
    private static function timestamp(string$value):void{if(self::rfc3339($value)===null)throw new InvalidArgumentException('Invalid profiling timestamp');}
    private static function reason(array&$reasons,string$code):void{$reasons[$code]=($reasons[$code]??0)+1;}
    private static function canonical(mixed$value):mixed{if(!is_array($value))return$value;if(!array_is_list($value))ksort($value,SORT_STRING);foreach($value as$key=>$item)$value[$key]=self::canonical($item);return$value;}
}
