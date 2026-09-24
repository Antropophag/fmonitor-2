<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

use yii\db\Connection;

final readonly class MariaDbYiiCompletionRegister
{
    private const PAGE_SIZE = 50;

    public function __construct(private Connection $db, private string $prefix, private string $legacyPrefix)
    {
        foreach ([$prefix, $legacyPrefix] as $value) {
            if (strlen($value) > 28 || preg_match('/^[A-Za-z0-9_]*$/D', $value) !== 1) throw new \InvalidArgumentException('Invalid table prefix.');
        }
    }

    /** @return array{rows:list<array>,filters:array,counters:array<string,int>} */
    public function read(int $actorId,array $filters): array
    {
        if(!$this->authorized($actorId))throw new \DomainException('ACCESS_DENIED');
        $mode=(string)($filters['mode']??'pto_without_declaration');$query=trim((string)($filters['q']??''));$date=(string)($filters['date']??'');
        $from=(string)($filters['from']??'');$to=(string)($filters['to']??'');$sort=(string)($filters['sort']??'');$page=(int)($filters['page']??1);
        if(!in_array($mode,['pto_without_declaration','without_pto','complete','all'],true)||mb_strlen($query)>120||!in_array($date,['','pto','declaration'],true)
            ||!$this->optionalDate($from)||!$this->optionalDate($to)||($from!==''&&$to!==''&&$from>$to)||!in_array($sort,['','pto_asc','pto_desc','object_asc'],true)||$page<1
            ||(($from!==''||$to!=='')&&$date===''))throw new \OutOfRangeException('Invalid completion register filter.');
        $this->assertHistoryIsConsistent();
        [$fromSql,$params]=$this->source($query,$date,$from,$to);$predicates=$this->modePredicates();$counters=[];
        foreach($predicates as$name=>$predicate)$counters[$name]=(int)$this->db->createCommand("SELECT COUNT(*){$fromSql} AND {$predicate}",$params)->queryScalar();
        $total=$counters[$mode];$pages=max(1,(int)ceil($total/self::PAGE_SIZE));if($page>$pages)throw new \OutOfRangeException('Page unavailable.');
        $offset=($page-1)*self::PAGE_SIZE;$order=match($sort!==''?$sort:($mode==='pto_without_declaration'?'pto_asc':'object_asc')){
            'pto_asc'=>'pto_date IS NULL,pto_date,c.legacy_installation_object_id','pto_desc'=>'pto_date IS NULL,pto_date DESC,c.legacy_installation_object_id',default=>'c.legacy_installation_object_id'};
        $columns="c.legacy_installation_object_id object_id,{$this->effective('regnumber','m.regnumber')} registration_number,{$this->effective('address','m.ordadr_address')} address,{$this->effective('entrance','m.entrance')} entrance,".
            'COALESCE(pc.fact_date,p.fact_date) pto_date,COALESCE(dc.fact_date,d.fact_date) declaration_date,COALESCE(dd.details,d.details) declaration_details';
        $rows=$this->db->createCommand("SELECT {$columns}{$fromSql} AND {$predicates[$mode]} ORDER BY {$order} LIMIT ".self::PAGE_SIZE." OFFSET {$offset}",$params)->queryAll();
        $today=new \DateTimeImmutable((string)(getenv('FMONITOR_NOW')?:'now'),new \DateTimeZone('Europe/Moscow'));$mapped=[];
        foreach($rows as$row){$ptoDate=$row['pto_date']===null?null:(string)$row['pto_date'];$mapped[]=[
            'objectId'=>(int)$row['object_id'],'registrationNumber'=>trim((string)$row['registration_number']),'address'=>trim((string)$row['address']),'entrance'=>trim((string)$row['entrance']),
            'ptoDate'=>$ptoDate,'declarationDate'=>$row['declaration_date']===null?null:(string)$row['declaration_date'],'declarationDetails'=>$row['declaration_date']===null?null:(string)$row['declaration_details'],
            'daysSincePto'=>$ptoDate===null?null:(int)(new \DateTimeImmutable($ptoDate,new \DateTimeZone('Europe/Moscow')))->diff($today)->format('%r%a'),'inconsistent'=>$ptoDate===null&&$row['declaration_date']!==null];}
        return['rows'=>$mapped,'counters'=>$counters,'filters'=>['mode'=>$mode,'q'=>$query,'date'=>$date,'from'=>$from,'to'=>$to,'sort'=>$sort,'page'=>$page,'pages'=>$pages,'total'=>$total]];
    }

    private function assertHistoryIsConsistent():void
    {
        $p=$this->prefix;$sql="SELECT COUNT(*) FROM `{$p}fm2_pilot_completion_fact_corrections` x LEFT JOIN `{$p}fm2_pilot_completion_fact_corrections` previous ON previous.id=x.previous_correction_id AND previous.root_fact_id=x.root_fact_id AND previous.version_no=x.previous_version_no WHERE (x.version_no=1 AND (x.previous_correction_id IS NOT NULL OR x.previous_version_no IS NOT NULL)) OR (x.version_no>1 AND (x.previous_correction_id IS NULL OR x.previous_version_no<>x.version_no-1 OR previous.id IS NULL)) OR x.version_no<1 OR EXISTS(SELECT 1 FROM `{$p}fm2_pilot_completion_fact_corrections` gap WHERE gap.root_fact_id=x.root_fact_id AND gap.version_no<x.version_no GROUP BY gap.root_fact_id HAVING COUNT(*)<>x.version_no-1)";
        if((int)$this->db->createCommand($sql)->queryScalar()!==0)throw new \RuntimeException('Malformed completion history.');
    }
    private function authorized(int$actor):bool{$p=$this->prefix;if($actor<1)return false;$sql="SELECT 1 FROM `{$p}fm2_pilot_users`u JOIN `{$p}fm2_pilot_user_roles`ur ON ur.user_id=u.user_id JOIN `{$p}fm2_pilot_roles`r ON r.role_id=ur.role_id JOIN `{$p}fm2_pilot_role_permissions`rp ON rp.role_id=r.role_id WHERE u.user_id=:actor AND u.status=1 AND u.activation_state='active' AND r.status=1 AND BINARY rp.permission='objects.read' LIMIT 1";return(bool)$this->db->createCommand($sql,[':actor'=>$actor])->queryScalar();}

    /** @return array{string,array<string,string>} */
    private function source(string$query,string$date,string$from,string$to):array
    {
        $p=$this->prefix;$l=$this->legacyPrefix;$leaf=static fn(string$root):string=>"LEFT JOIN `{$p}fm2_pilot_completion_fact_corrections` {$root}c ON {$root}c.root_fact_id={$root}.id AND {$root}c.version_no=(SELECT MAX(z.version_no) FROM `{$p}fm2_pilot_completion_fact_corrections` z WHERE z.root_fact_id={$root}.id)";
        $details="LEFT JOIN `{$p}fm2_pilot_completion_fact_corrections` dd ON dd.root_fact_id=d.id AND dd.details IS NOT NULL AND dd.version_no=(SELECT MAX(z.version_no) FROM `{$p}fm2_pilot_completion_fact_corrections` z WHERE z.root_fact_id=d.id AND z.details IS NOT NULL)";
        $fromSql=" FROM `{$p}fm2_installation_cases` c JOIN `{$l}fm_maintable` m ON m.id=c.legacy_installation_object_id LEFT JOIN `{$p}fm2_object_detail_edits` e ON e.object_id=c.legacy_installation_object_id LEFT JOIN `{$p}fm2_pilot_completion_facts` p ON p.installation_case_id=c.id AND p.fact_type='pto_act' ".$leaf('p')." LEFT JOIN `{$p}fm2_pilot_completion_facts` d ON d.installation_case_id=c.id AND d.fact_type='declaration' ".$leaf('d')." {$details} WHERE (c.opened_at IS NOT NULL OR c.actual_start_date IS NOT NULL OR p.id IS NOT NULL OR d.id IS NOT NULL)";$params=[];
        if($query!==''){$fromSql.=' AND ('.$this->effective('address','m.ordadr_address')." LIKE :q ESCAPE '\\\\' OR ".$this->effective('regnumber','m.regnumber')." LIKE :q ESCAPE '\\\\' OR COALESCE(dd.details,d.details,'') LIKE :q ESCAPE '\\\\')";$params[':q']='%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$query).'%';}
        if($date!==''){$expression=$date==='pto'?'COALESCE(pc.fact_date,p.fact_date)':'COALESCE(dc.fact_date,d.fact_date)';if($from!==''){$fromSql.=" AND {$expression}>=:from";$params[':from']=$from;}if($to!==''){$fromSql.=" AND {$expression}<=:to";$params[':to']=$to;}}
        return[$fromSql,$params];
    }

    /** @return array<string,string> */
    private function modePredicates():array{return['pto_without_declaration'=>'p.id IS NOT NULL AND d.id IS NULL','without_pto'=>'p.id IS NULL','complete'=>'p.id IS NOT NULL AND d.id IS NOT NULL','all'=>'1=1'];}
    private function effective(string$field,string$legacy):string{return MariaDbEffectiveObjectDetails::sqlValue($field,$legacy);}
    private function optionalDate(string$value):bool{if($value==='')return true;$parsed=\DateTimeImmutable::createFromFormat('!Y-m-d',$value,new \DateTimeZone('Europe/Moscow'));return$parsed!==false&&$parsed->format('Y-m-d')===$value;}
}
