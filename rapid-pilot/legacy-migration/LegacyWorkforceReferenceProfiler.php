<?php
declare(strict_types=1);

final class LegacyWorkforceReferenceProfiler
{
    public const VERSION='legacy-workforce-reference-profile-v1';

    public static function profile(array$facts,array$installers,array$users,string$profiledAt,string$hashKey):array
    {
        self::time($profiledAt);if(strlen($hashKey)<32)throw new InvalidArgumentException('Profile hash key is unavailable');
        $byTab=[];$byName=[];foreach($installers as$row){$tab=self::tab($row['tab_id']??$row['tab_id_char']??null);$name=self::name(trim(implode(' ',array_filter([(string)($row['lastname']??''),(string)($row['name']??''),(string)($row['s_name']??'')],static fn($x)=>trim($x)!=='')))?:($row['fio']??null));if($tab===null||$name===null)continue;$byTab[$tab][]=['tab'=>$tab,'name'=>$name];$byName[$name][]=$tab;}
        $userNames=[];foreach($users as$row){$name=self::name($row['name']??null);if($name!==null)$userNames[$name]=($userNames[$name]??0)+1;}
        $counts=['unmatchedObservations'=>count($facts),'uniqueUnmatchedFacts'=>0,'uniqueAuthoritativeMatches'=>0,'proposedLinkIntents'=>0,'unassignedSentinel'=>0,'trulyAbsent'=>0,'formattingDrift'=>0,'nameDrift'=>0,'duplicateOrAmbiguous'=>0,'nonAuthoritativeUserOnly'=>0];$reasons=[];$intents=[];$seen=[];
        foreach($facts as$fact){$raw=trim((string)($fact['tab_id']??''));$tab=self::tab($raw);$name=self::name($fact['fio']??null);$observedAt=trim((string)($fact['ctime']??''));$factKey=hash_hmac('sha256',$raw.'|'.($name??'').'|'.$observedAt,$hashKey);if(isset($seen[$factKey]))continue;$seen[$factKey]=true;$counts['uniqueUnmatchedFacts']++;
            if($tab==='999999'){$counts['unassignedSentinel']++;self::reason($reasons,'LEGACY_UNASSIGNED_SENTINEL');continue;}
            if($tab!==null&&count($byTab[$tab]??[])>1){$counts['duplicateOrAmbiguous']++;self::reason($reasons,'DUPLICATE_AUTHORITATIVE_TAB');continue;}
            if($tab!==null&&count($byTab[$tab]??[])===1){$match=$byTab[$tab][0];$nameSame=$name!==null&&$name===$match['name'];$format=self::digits($raw)!==null&&self::digits($raw)!==$raw;if($format)$counts['formattingDrift']++;if(!$nameSame)$counts['nameDrift']++;$reason=$nameSame?($format?'UNIQUE_KEY_FORMAT_NORMALIZED':'UNIQUE_KEY_AND_NAME_MATCH'):'UNIQUE_KEY_NAME_DRIFT';$confidence=$nameSame?'high':'medium';$intents[]=['intentHash'=>hash_hmac('sha256','link|'.$factKey.'|'.$tab,$hashKey),'targetType'=>'workforce_tab','targetLocatorHash'=>hash_hmac('sha256','workforce_tab:'.$tab,$hashKey),'confidence'=>$confidence,'reasonCodes'=>[$reason]];$counts['uniqueAuthoritativeMatches']++;$counts['proposedLinkIntents']++;self::reason($reasons,$reason);continue;}
            if($name!==null&&count(array_unique($byName[$name]??[]))>1){$counts['duplicateOrAmbiguous']++;self::reason($reasons,'AMBIGUOUS_AUTHORITATIVE_NAME');continue;}
            if($name!==null&&count(array_unique($byName[$name]??[]))===1){$counts['formattingDrift']++;self::reason($reasons,'KEY_DRIFT_NAME_ONLY_MATCH');continue;}
            if($name!==null&&isset($userNames[$name])){$counts['nonAuthoritativeUserOnly']++;self::reason($reasons,'NON_AUTHORITATIVE_USER_ONLY');continue;}
            $counts['trulyAbsent']++;self::reason($reasons,'ABSENT_FROM_LEGACY_REFERENCES');
        }
        usort($intents,static fn($a,$b)=>$a['intentHash']<=>$b['intentHash']);ksort($reasons,SORT_STRING);$basis=['version'=>self::VERSION,'profiledAt'=>$profiledAt,'counts'=>$counts,'reasons'=>$reasons,'intents'=>$intents];return['profileVersion'=>self::VERSION,'mode'=>'read_only_dry_run','profiledAt'=>$profiledAt,'counts'=>$counts,'reasonCounts'=>$reasons,'proposedLinkIntents'=>$intents,'sourceDigest'=>hash('sha256',json_encode($basis,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)),'identifiersExposed'=>false,'autoApply'=>false,'authoritativeSource'=>'legacy_fmonitor.fm_installators'];
    }

    private static function tab(mixed$v):?string{$d=self::digits(trim((string)$v));return$d!==null&&$d!=='0'?$d:null;}
    private static function digits(string$v):?string{if(preg_match('/^[0-9]+$/D',$v)!==1)return null;$d=ltrim($v,'0');return$d===''?'0':$d;}
    private static function name(mixed$v):?string{if(!is_string($v))return null;$n=preg_replace('/\s+/u',' ',mb_strtolower(trim($v)));return is_string($n)&&$n!==''?$n:null;}
    private static function reason(array&$r,string$c):void{$r[$c]=($r[$c]??0)+1;}
    private static function time(string$v):void{$d=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:sP',$v);if(!$d||$d->format('Y-m-d\TH:i:sP')!==$v)throw new InvalidArgumentException('Invalid profiling timestamp');}
}
