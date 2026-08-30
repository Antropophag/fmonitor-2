<?php

declare(strict_types=1);

final class HistoricalPremiumOperandProfiler
{
    public const VERSION='historical-premium-operand-profile-v1';
    public function __construct(private mysqli$db,private int$pageSize=500){if($pageSize<1||$pageSize>5000)throw new InvalidArgumentException('Invalid page size');}

    public function profile():array
    {
        $last=0;$total=0;$candidates=['shaftMaterial'=>0,'planDeadline'=>0,'adjustedDeadline'=>0];$proven=['premiumCents'=>0,'shaftBp'=>0,'deadlineDate'=>0,'reportDate'=>0];$reasons=[];$quarantine=[];$representatives=[];
        do{$s=$this->db->prepare("SELECT id,pitmaterial,plan_finish_date,workdateendadjusted,workdatefinish,ptoactdate,object_status FROM fm_maintable WHERE id>? AND (NULLIF(ptoactdate,'0000-00-00 00:00:00') IS NOT NULL OR object_status=259) ORDER BY id LIMIT ?");$s->bind_param('ii',$last,$this->pageSize);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);
            foreach($rows as$row){$last=(int)$row['id'];$total++;$rowReasons=['FORMULA_CONSTANT_NOT_SOURCE_FACT','CURRENT_MUTABLE_SHAFT_FIELD_WITHOUT_VALIDITY','PLAN_FIELD_WITHOUT_CONTRACT_BASIS','REPORT_DATE_COMMAND_OR_ARTIFACT_ABSENT'];
                if((int)$row['pitmaterial']>0)$candidates['shaftMaterial']++;else$quarantine['SHAFT_MATERIAL_MISSING']=($quarantine['SHAFT_MATERIAL_MISSING']??0)+1;
                if($this->date($row['plan_finish_date']))$candidates['planDeadline']++;else$quarantine['PLAN_DEADLINE_MISSING_OR_INVALID']=($quarantine['PLAN_DEADLINE_MISSING_OR_INVALID']??0)+1;
                if($this->date($row['workdateendadjusted']))$candidates['adjustedDeadline']++;
                foreach($rowReasons as$reason)$reasons[$reason]=($reasons[$reason]??0)+1;
                if(count($representatives)<12)$representatives[]=['objectRef'=>substr(hash('sha256','legacy-object:'.$row['id']),0,16),'candidateFields'=>['pitmaterial'=>(int)$row['pitmaterial']>0,'planFinishDate'=>$this->date($row['plan_finish_date']),'adjustedFinishDate'=>$this->date($row['workdateendadjusted'])],'provenOperands'=>[],'exclusionReasons'=>$rowReasons];
            }
        }while(count($rows)===$this->pageSize);
        ksort($reasons,SORT_STRING);ksort($quarantine,SORT_STRING);
        return['profileVersion'=>self::VERSION,'mode'=>'read_only_dry_run','population'=>'legacy_historical','objects'=>$total,'candidateFieldCounts'=>$candidates,'provenOperandCounts'=>$proven,'exclusionReasonCounts'=>$reasons,'quarantineCounts'=>$quarantine,'redactedRepresentatives'=>$representatives,
            'sourceAssessment'=>['premium'=>'legacy formula constant 780000; not object evidence','shaft'=>'current pitmaterial -> coefficient switch; no validity interval','deadline'=>'current plan/adjusted fields; no contractual basis/version','reportDate'=>'export command/artifact context; absent from object facts']];
    }
    private function date(mixed$value):bool{$value=(string)$value;if(preg_match('/^(\d{4})-(\d{2})-(\d{2})/D',$value,$m)!==1)return false;return checkdate((int)$m[2],(int)$m[3],(int)$m[1])&&(int)$m[1]>1970;}
}
